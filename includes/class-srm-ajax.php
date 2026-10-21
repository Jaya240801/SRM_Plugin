<?php
/**
 * AJAX Handler — Safe Redirect Masker
 *
 * All endpoints are admin-only and protected by a nonce.
 *
 * @package SafeRedirectMasker
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class SRM_AJAX {

    public static function init() {
        $actions = array(
            'srm_get_links',
            'srm_save_link',
            'srm_delete_link',
            'srm_get_link',
            'srm_save_settings',
            'srm_get_stats',
            'srm_test_link',
        );
        foreach ( $actions as $action ) {
            add_action( 'wp_ajax_' . $action, array( __CLASS__, $action ) );
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function verify() {
        if ( ob_get_length() ) {
            @ob_clean();
        }
        if ( ! check_ajax_referer( 'srm_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Security token expired. Please refresh the page and try again.' ), 403 );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized access.' ), 403 );
        }
    }

    // ── Endpoints ─────────────────────────────────────────────────────────────

    public static function srm_get_links() {
        self::verify();
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $page     = isset( $_POST['page']   ) ? max( 1, intval( $_POST['page'] ) )                    : 1;
        $per_page = 20;

        $links = SRM_DB::get_links( $search, $page, $per_page );
        $total = SRM_DB::count_links( $search );

        $settings = SRM_DB::get_settings();
        $prefix   = $settings['url_prefix'];

        foreach ( $links as &$link ) {
            $link['masked_url'] = home_url( '/' . ( $prefix ? $prefix . '/' : '' ) . $link['slug'] );
        }
        unset( $link );

        wp_send_json_success( array(
            'links'       => $links,
            'total'       => $total,
            'total_pages' => max( 1, ceil( $total / $per_page ) ),
            'page'        => $page,
        ) );
    }

    public static function srm_get_link() {
        self::verify();
        $id   = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $link = SRM_DB::get_link_by_id( $id );
        if ( ! $link ) {
            wp_send_json_error( array( 'message' => 'Link not found.' ), 404 );
        }
        $settings           = SRM_DB::get_settings();
        $prefix             = $settings['url_prefix'];
        $link['masked_url'] = home_url( '/' . ( $prefix ? $prefix . '/' : '' ) . $link['slug'] );
        wp_send_json_success( array( 'link' => $link ) );
    }

    public static function srm_save_link() {
        self::verify();

        $id       = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $raw_url  = isset( $_POST['real_url'] ) ? trim( wp_unslash( $_POST['real_url'] ) ) : '';

        // Auto-prepend https:// if user omitted protocol
        if ( ! empty( $raw_url ) && ! preg_match( '#^https?://#i', $raw_url ) ) {
            $raw_url = 'https://' . $raw_url;
        }

        $real_url = esc_url_raw( $raw_url );

        if ( empty( $real_url ) || false === filter_var( $real_url, FILTER_VALIDATE_URL ) ) {
            wp_send_json_error( array( 'message' => 'Please provide a valid Real URL (e.g. https://yourblog.com/my-article).' ) );
        }

        $raw_slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

        // Auto-generate slug from real_url if empty
        if ( empty( $raw_slug ) ) {
            $parsed_path = parse_url( $real_url, PHP_URL_PATH );
            $clean_path  = is_string( $parsed_path ) ? trim( $parsed_path, '/' ) : '';
            if ( ! empty( $clean_path ) ) {
                $segments = explode( '/', $clean_path );
                $raw_slug = end( $segments );
            }
            if ( empty( $raw_slug ) ) {
                $host = parse_url( $real_url, PHP_URL_HOST );
                if ( ! empty( $host ) ) {
                    $clean_host = preg_replace( '/^www\./i', '', $host );
                    $raw_slug   = str_replace( '.', '-', $clean_host ) . '-' . substr( md5( uniqid( '', true ) ), 0, 4 );
                }
            }
            if ( empty( $raw_slug ) ) {
                $raw_slug = 'link-' . substr( md5( uniqid( '', true ) ), 0, 6 );
            }
        }

        $slug = SRM_DB::sanitize_slug( $raw_slug );
        if ( empty( $slug ) ) {
            $slug = 'link-' . substr( md5( uniqid( '', true ) ), 0, 6 );
        }

        $data = array(
            'slug'            => $slug,
            'real_url'        => $real_url,
            'article_title'   => isset( $_POST['article_title'] )   ? sanitize_text_field( wp_unslash( $_POST['article_title'] ) )   : '',
            'article_content' => isset( $_POST['article_content'] ) ? wp_kses_post( wp_unslash( $_POST['article_content'] ) )        : '',
            'article_image'   => isset( $_POST['article_image'] )   ? esc_url_raw( trim( wp_unslash( $_POST['article_image'] ) ) )  : '',
            'timer'           => isset( $_POST['timer'] )           ? max( 1, intval( $_POST['timer'] ) )                            : 5,
        );

        global $wpdb;

        if ( $id > 0 ) {
            // Update existing
            if ( SRM_DB::slug_exists( $data['slug'], $id ) ) {
                wp_send_json_error( array( 'message' => 'The slug "' . esc_html( $data['slug'] ) . '" is already in use by another link.' ) );
            }
            $ok = SRM_DB::update_link( $id, $data );
            if ( false === $ok ) {
                $error_msg = ! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Database update failed.';
                wp_send_json_error( array( 'message' => 'Failed to update link: ' . $error_msg ) );
            }
            $link = SRM_DB::get_link_by_id( $id );
        } else {
            // Insert new
            if ( SRM_DB::slug_exists( $data['slug'] ) ) {
                $data['slug'] .= '-' . substr( md5( uniqid( '', true ) ), 0, 4 );
            }
            $new_id = SRM_DB::insert_link( $data );
            if ( ! $new_id ) {
                $error_msg = ! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Database insert failed.';
                wp_send_json_error( array( 'message' => 'Failed to create link: ' . $error_msg ) );
            }
            $link = SRM_DB::get_link_by_id( $new_id );
        }

        $settings           = SRM_DB::get_settings();
        $prefix             = $settings['url_prefix'];
        $link['masked_url'] = home_url( '/' . ( $prefix ? $prefix . '/' : '' ) . $link['slug'] );

        wp_send_json_success( array(
            'link'    => $link,
            'message' => $id > 0 ? 'Link updated successfully.' : 'New masked link created successfully!',
        ) );
    }

    public static function srm_delete_link() {
        self::verify();
        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( $id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid link ID.' ) );
        }
        $ok = SRM_DB::delete_link( $id );
        if ( ! $ok ) {
            global $wpdb;
            $err = ! empty( $wpdb->last_error ) ? $wpdb->last_error : 'Database error.';
            wp_send_json_error( array( 'message' => 'Failed to delete link: ' . $err ) );
        }
        wp_send_json_success( array( 'message' => 'Link deleted successfully.' ) );
    }

    public static function srm_save_settings() {
        self::verify();
        $data = array();

        if ( isset( $_POST['url_prefix'] ) ) {
            $raw_prefix = sanitize_key( wp_unslash( $_POST['url_prefix'] ) );
            $data['url_prefix'] = ! empty( $raw_prefix ) ? $raw_prefix : 'go';
        }
        if ( isset( $_POST['timer'] ) ) {
            $data['timer'] = max( 1, intval( $_POST['timer'] ) );
        }
        if ( isset( $_POST['anti_bomb_limit'] ) ) {
            $data['anti_bomb_limit'] = max( 1, intval( $_POST['anti_bomb_limit'] ) );
        }
        if ( isset( $_POST['anti_bomb_window'] ) ) {
            $data['anti_bomb_window'] = max( 10, intval( $_POST['anti_bomb_window'] ) );
        }
        if ( isset( $_POST['bomb_lockout'] ) ) {
            $data['bomb_lockout'] = max( 30, intval( $_POST['bomb_lockout'] ) );
        }
        if ( isset( $_POST['ad_slot_1'] ) ) {
            $data['ad_slot_1'] = wp_unslash( $_POST['ad_slot_1'] );
        }
        if ( isset( $_POST['ad_slot_2'] ) ) {
            $data['ad_slot_2'] = wp_unslash( $_POST['ad_slot_2'] );
        }
        if ( isset( $_POST['ad_slot_3'] ) ) {
            $data['ad_slot_3'] = wp_unslash( $_POST['ad_slot_3'] );
        }
        if ( isset( $_POST['ad_modal'] ) ) {
            $data['ad_modal'] = wp_unslash( $_POST['ad_modal'] );
        }
        if ( isset( $_POST['ads_head'] ) ) {
            $data['ads_head'] = wp_unslash( $_POST['ads_head'] );
        }
        if ( isset( $_POST['ads_foot'] ) ) {
            $data['ads_foot'] = wp_unslash( $_POST['ads_foot'] );
        }

        $updated = SRM_DB::save_settings( $data );
        wp_send_json_success( array(
            'settings' => $updated,
            'message'  => 'Settings saved successfully!',
        ) );
    }

    public static function srm_get_stats() {
        self::verify();
        wp_send_json_success( SRM_DB::get_stats() );
    }

    public static function srm_test_link() {
        self::verify();
        $slug      = isset( $_POST['slug'] ) ? SRM_DB::sanitize_slug( wp_unslash( $_POST['slug'] ) ) : '';
        $sim_ua    = isset( $_POST['user_agent'] ) ? trim( wp_unslash( $_POST['user_agent'] ) ) : 'chrome';

        $link = SRM_DB::get_link_by_slug( $slug );
        if ( ! $link ) {
            wp_send_json_error( array( 'message' => 'Link with slug "' . esc_html( $slug ) . '" not found.' ) );
        }

        // Test UA string
        $ua_string = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        if ( 'googlebot' === $sim_ua ) {
            $ua_string = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';
        } elseif ( 'bingbot' === $sim_ua ) {
            $ua_string = 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)';
        } elseif ( 'facebook' === $sim_ua ) {
            $ua_string = 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)';
        } elseif ( 'curl' === $sim_ua ) {
            $ua_string = 'curl/7.88.1';
        }

        $ua_lower = strtolower( $ua_string );
        $is_bot   = false;
        $matched_rule = 'None (Real User)';

        foreach ( SRM_Bot_Detector::BOT_UA_PATTERNS as $pattern ) {
            if ( false !== strpos( $ua_lower, $pattern ) ) {
                $is_bot = true;
                $matched_rule = 'Matched crawler pattern: "' . $pattern . '"';
                break;
            }
        }

        $settings = SRM_DB::get_settings();
        $prefix   = $settings['url_prefix'];

        wp_send_json_success( array(
            'slug'          => $link['slug'],
            'masked_url'    => home_url( '/' . ( $prefix ? $prefix . '/' : '' ) . $link['slug'] ),
            'real_url'      => $link['real_url'],
            'simulated_ua'  => $ua_string,
            'is_bot'        => $is_bot,
            'matched_rule'  => $matched_rule,
            'http_action'   => $is_bot ? '301 Moved Permanently' : '302 Found (Render Cloaking Page)',
            'destination'   => $is_bot ? $link['real_url'] : 'Safelink Page with ' . $link['timer'] . 's countdown timer',
            'anti_bomb'     => $is_bot ? 'Bypassed (Bot crawler)' : 'Protected (Rate-limit: ' . $settings['anti_bomb_limit'] . ' clicks / ' . $settings['anti_bomb_window'] . 's)',
        ) );
    }
}
