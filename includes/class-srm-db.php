<?php
/**
 * Database & Storage Handler — Safe Redirect Masker
 *
 * Distinct and isolated from AdsExplorer plugin (uses srm_ prefix).
 *
 * @package SafeRedirectMasker
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class SRM_DB {

    // ── Table helpers ─────────────────────────────────────────────────────────

    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'srm_links';
    }

    public static function get_clicks_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'srm_click_log';
    }

    // ── Schema installation & upgrade ─────────────────────────────────────────

    public static function maybe_upgrade( $force = false ) {
        global $wpdb;

        $links_table = self::get_table_name();
        $log_table   = self::get_clicks_table_name();

        // Check if tables actually exist in MySQL
        $links_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $links_table ) );
        $log_exist   = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $log_table ) );

        $current_version = get_option( 'srm_db_version' );

        if ( ! $force && SRM_VERSION === $current_version && ! empty( $links_exist ) && ! empty( $log_exist ) ) {
            return;
        }

        self::install_tables();
    }

    public static function ensure_tables_exist() {
        global $wpdb;
        $links_table = self::get_table_name();
        $links_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $links_table ) );
        if ( empty( $links_exist ) ) {
            self::install_tables();
        }
    }

    public static function install_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $links   = self::get_table_name();
        $log     = self::get_clicks_table_name();

        // Links table — use portable DEFAULT '0000-00-00 00:00:00' for broad MySQL/MariaDB compatibility
        $sql_links = "CREATE TABLE {$links} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(191) NOT NULL,
            real_url text NOT NULL,
            article_title text DEFAULT NULL,
            article_content longtext DEFAULT NULL,
            article_image text DEFAULT NULL,
            timer int(11) NOT NULL DEFAULT 5,
            status tinyint(1) NOT NULL DEFAULT 1,
            clicks bigint(20) unsigned NOT NULL DEFAULT 0,
            bot_hits bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            last_clicked datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) {$charset};";

        // Click-log table for anti-bomb / analytics
        $sql_log = "CREATE TABLE {$log} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            link_id bigint(20) unsigned NOT NULL,
            ip_hash varchar(64) NOT NULL,
            is_bot tinyint(1) NOT NULL DEFAULT 0,
            clicked_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY link_id (link_id),
            KEY ip_hash_time (ip_hash, clicked_at)
        ) {$charset};";

        dbDelta( $sql_links );
        dbDelta( $sql_log );

        // Direct fallback verification if dbDelta was bypassed or failed
        $check_links = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $links ) );
        if ( empty( $check_links ) ) {
            $wpdb->query( "CREATE TABLE IF NOT EXISTS {$links} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                slug varchar(191) NOT NULL,
                real_url text NOT NULL,
                article_title text DEFAULT NULL,
                article_content longtext DEFAULT NULL,
                article_image text DEFAULT NULL,
                timer int(11) NOT NULL DEFAULT 5,
                status tinyint(1) NOT NULL DEFAULT 1,
                clicks bigint(20) unsigned NOT NULL DEFAULT 0,
                bot_hits bigint(20) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                last_clicked datetime DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY slug (slug)
            ) {$charset};" );
        }

        $check_log = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $log ) );
        if ( empty( $check_log ) ) {
            $wpdb->query( "CREATE TABLE IF NOT EXISTS {$log} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                link_id bigint(20) unsigned NOT NULL,
                ip_hash varchar(64) NOT NULL,
                is_bot tinyint(1) NOT NULL DEFAULT 0,
                clicked_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY (id),
                KEY link_id (link_id),
                KEY ip_hash_time (ip_hash, clicked_at)
            ) {$charset};" );
        }

        update_option( 'srm_db_version', SRM_VERSION );

        // Seed default settings
        if ( false === get_option( 'srm_settings' ) ) {
            update_option( 'srm_settings', self::default_settings() );
        }
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    public static function default_settings() {
        return array(
            'url_prefix'         => 'go',       // masked URL prefix (e.g. /go/slug)
            'timer'              => 5,          // countdown seconds for real users
            'anti_bomb_limit'    => 5,          // max human clicks per window
            'anti_bomb_window'   => 60,         // rate-limit window in seconds
            'bomb_lockout'       => 300,        // lockout duration after limit hit (seconds)
            'ads_head'           => '',         // custom HTML injected into <head> of safelink page
            'ads_foot'           => '',         // custom HTML injected into </body> of safelink page
        );
    }

    public static function get_settings() {
        $settings = get_option( 'srm_settings', array() );
        return wp_parse_args( $settings, self::default_settings() );
    }

    public static function save_settings( $data ) {
        $current = self::get_settings();
        $updated = array(
            'url_prefix'         => ! empty( $data['url_prefix'] ) ? sanitize_key( $data['url_prefix'] ) : $current['url_prefix'],
            'timer'              => isset( $data['timer'] )            ? max( 1, intval( $data['timer'] ) )             : $current['timer'],
            'anti_bomb_limit'    => isset( $data['anti_bomb_limit'] )  ? max( 1, intval( $data['anti_bomb_limit'] ) )   : $current['anti_bomb_limit'],
            'anti_bomb_window'   => isset( $data['anti_bomb_window'] ) ? max( 10, intval( $data['anti_bomb_window'] ) ) : $current['anti_bomb_window'],
            'bomb_lockout'       => isset( $data['bomb_lockout'] )     ? max( 30, intval( $data['bomb_lockout'] ) )     : $current['bomb_lockout'],
            'ads_head'           => isset( $data['ads_head'] )         ? $data['ads_head']                              : $current['ads_head'],
            'ads_foot'           => isset( $data['ads_foot'] )         ? $data['ads_foot']                              : $current['ads_foot'],
        );
        update_option( 'srm_settings', $updated );
        return $updated;
    }

    // ── Link CRUD ─────────────────────────────────────────────────────────────

    public static function sanitize_slug( $raw ) {
        $raw = trim( (string) $raw );
        if ( preg_match( '#^https?://#i', $raw ) ) {
            $path = parse_url( $raw, PHP_URL_PATH );
            if ( ! empty( $path ) ) { $raw = $path; }
        }
        $slug = strtolower( trim( $raw, " /\t\n\r\0\x0B" ) );
        $slug = preg_replace( '~[^a-z0-9\-_/]~', '-', $slug );
        $slug = preg_replace( '~-+~', '-', $slug );
        $slug = preg_replace( '~/{2,}~', '/', $slug );
        return trim( $slug, ' /-' );
    }

    public static function slug_exists( $slug, $exclude_id = 0 ) {
        global $wpdb;
        self::ensure_tables_exist();
        $tbl = self::get_table_name();
        $s   = self::sanitize_slug( $slug );
        if ( $exclude_id > 0 ) {
            return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl} WHERE LOWER(slug)=%s AND id!=%d", $s, $exclude_id ) ) > 0;
        }
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl} WHERE LOWER(slug)=%s", $s ) ) > 0;
    }

    public static function insert_link( $data ) {
        global $wpdb;
        self::ensure_tables_exist();
        $tbl  = self::get_table_name();

        $slug = self::sanitize_slug( $data['slug'] ?? '' );
        if ( empty( $slug ) ) {
            $slug = 'link-' . substr( md5( uniqid( '', true ) ), 0, 6 );
        }

        $real_url = trim( $data['real_url'] ?? '' );
        if ( ! empty( $real_url ) && ! preg_match( '#^https?://#i', $real_url ) ) {
            $real_url = 'https://' . $real_url;
        }

        $inserted = $wpdb->insert( $tbl, array(
            'slug'            => $slug,
            'real_url'        => esc_url_raw( $real_url ),
            'article_title'   => sanitize_text_field( $data['article_title'] ?? '' ),
            'article_content' => wp_kses_post( $data['article_content'] ?? '' ),
            'article_image'   => esc_url_raw( trim( $data['article_image'] ?? '' ) ),
            'timer'           => max( 1, intval( $data['timer'] ?? 5 ) ),
            'status'          => 1,
            'clicks'          => 0,
            'bot_hits'        => 0,
            'created_at'      => current_time( 'mysql' ),
        ), array( '%s','%s','%s','%s','%s','%d','%d','%d','%d','%s' ) );

        if ( false === $inserted ) {
            // Self-heal: retry after force-checking table
            self::install_tables();
            $inserted = $wpdb->insert( $tbl, array(
                'slug'            => $slug,
                'real_url'        => esc_url_raw( $real_url ),
                'article_title'   => sanitize_text_field( $data['article_title'] ?? '' ),
                'article_content' => wp_kses_post( $data['article_content'] ?? '' ),
                'article_image'   => esc_url_raw( trim( $data['article_image'] ?? '' ) ),
                'timer'           => max( 1, intval( $data['timer'] ?? 5 ) ),
                'status'          => 1,
                'clicks'          => 0,
                'bot_hits'        => 0,
                'created_at'      => current_time( 'mysql' ),
            ), array( '%s','%s','%s','%s','%s','%d','%d','%d','%d','%s' ) );
        }

        return $inserted ? $wpdb->insert_id : false;
    }

    public static function update_link( $id, $data ) {
        global $wpdb;
        self::ensure_tables_exist();
        $tbl      = self::get_table_name();
        $existing = self::get_link_by_id( $id );
        if ( ! $existing ) { return false; }

        $slug = isset( $data['slug'] ) ? self::sanitize_slug( $data['slug'] ) : $existing['slug'];

        $real_url = isset( $data['real_url'] ) ? trim( $data['real_url'] ) : $existing['real_url'];
        if ( ! empty( $real_url ) && ! preg_match( '#^https?://#i', $real_url ) ) {
            $real_url = 'https://' . $real_url;
        }

        $updated = $wpdb->update( $tbl, array(
            'slug'            => $slug,
            'real_url'        => esc_url_raw( $real_url ),
            'article_title'   => sanitize_text_field( $data['article_title']   ?? $existing['article_title']   ),
            'article_content' => wp_kses_post( $data['article_content'] ?? $existing['article_content'] ),
            'article_image'   => esc_url_raw( trim( $data['article_image'] ?? $existing['article_image'] ) ),
            'timer'           => max( 1, intval( $data['timer'] ?? $existing['timer'] ) ),
        ), array( 'id' => $id ), array( '%s','%s','%s','%s','%s','%d' ), array( '%d' ) );

        return false !== $updated;
    }

    public static function delete_link( $id ) {
        global $wpdb;
        self::ensure_tables_exist();
        $deleted = $wpdb->delete( self::get_table_name(), array( 'id' => $id ), array( '%d' ) );
        // also prune click log
        $wpdb->delete( self::get_clicks_table_name(), array( 'link_id' => $id ), array( '%d' ) );
        return false !== $deleted && $deleted > 0;
    }

    public static function get_link_by_id( $id ) {
        global $wpdb;
        self::ensure_tables_exist();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::get_table_name() . " WHERE id=%d", $id ), ARRAY_A );
    }

    public static function get_link_by_slug( $slug ) {
        global $wpdb;
        self::ensure_tables_exist();
        $s = self::sanitize_slug( $slug );
        if ( empty( $s ) ) { return null; }
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::get_table_name() . " WHERE LOWER(slug)=%s LIMIT 1", $s ), ARRAY_A );
    }

    public static function get_links( $search = '', $page = 1, $per_page = 20 ) {
        global $wpdb;
        self::ensure_tables_exist();
        $tbl    = self::get_table_name();
        $offset = ( max( 1, (int) $page ) - 1 ) * (int) $per_page;

        if ( ! empty( $search ) ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $sql  = $wpdb->prepare(
                "SELECT * FROM {$tbl} WHERE (slug LIKE %s OR real_url LIKE %s OR article_title LIKE %s) ORDER BY id DESC LIMIT %d OFFSET %d",
                $like, $like, $like, $per_page, $offset
            );
        } else {
            $sql = $wpdb->prepare( "SELECT * FROM {$tbl} ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset );
        }
        $results = $wpdb->get_results( $sql, ARRAY_A );
        return is_array( $results ) ? $results : array();
    }

    public static function count_links( $search = '' ) {
        global $wpdb;
        self::ensure_tables_exist();
        $tbl = self::get_table_name();
        if ( ! empty( $search ) ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            return (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$tbl} WHERE (slug LIKE %s OR real_url LIKE %s OR article_title LIKE %s)",
                $like, $like, $like
            ) );
        }
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl}" );
    }

    // ── Click recording ───────────────────────────────────────────────────────

    public static function record_click( $link_id, $ip_hash, $is_bot = false ) {
        global $wpdb;
        self::ensure_tables_exist();
        $tbl = self::get_table_name();
        $log = self::get_clicks_table_name();

        if ( $is_bot ) {
            $wpdb->query( $wpdb->prepare( "UPDATE {$tbl} SET bot_hits=bot_hits+1 WHERE id=%d", $link_id ) );
        } else {
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$tbl} SET clicks=clicks+1, last_clicked=%s WHERE id=%d",
                current_time( 'mysql' ), $link_id
            ) );
        }

        $wpdb->insert( $log, array(
            'link_id'    => $link_id,
            'ip_hash'    => $ip_hash,
            'is_bot'     => $is_bot ? 1 : 0,
            'clicked_at' => current_time( 'mysql' ),
        ), array( '%d', '%s', '%d', '%s' ) );
    }

    // ── Dashboard stats ───────────────────────────────────────────────────────

    public static function get_stats() {
        global $wpdb;
        self::ensure_tables_exist();
        $tbl = self::get_table_name();
        return array(
            'total_links'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl}" ),
            'total_clicks' => (int) $wpdb->get_var( "SELECT COALESCE(SUM(clicks), 0) FROM {$tbl}" ),
            'total_bots'   => (int) $wpdb->get_var( "SELECT COALESCE(SUM(bot_hits), 0) FROM {$tbl}" ),
            'top_link'     => $wpdb->get_row( "SELECT slug, clicks FROM {$tbl} ORDER BY clicks DESC LIMIT 1", ARRAY_A ),
        );
    }
}
