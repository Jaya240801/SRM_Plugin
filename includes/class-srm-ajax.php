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
        );
        foreach ( $actions as $action ) {
            add_action( 'wp_ajax_' . $action, array( __CLASS__, $action ) );
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function verify() {
        check_ajax_referer( 'srm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    // ── Endpoints ─────────────────────────────────────────────────────────────

    public static function srm_get_links() {
        self::verify();
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        $page     = isset( $_POST['page']   ) ? max( 1, intval( $_POST['page'] ) )      : 1;
        $per_page = 20;

        $links = SRM_DB::get_links( $search, $page, $per_page );
        $total = SRM_DB::count_links( $search );

        $settings = SRM_DB::get_settings();
        $prefix   = $settings['url_prefix'];

        foreach ( $links as &$link ) {
            $link['masked_url'] = home_url( '/' . $prefix . '/' . $link['slug'] );
        }
        unset( $link );

        wp_send_json_success( array(
            'links'      => $links,
            'total'      => $total,
            'total_pages' => ceil( $total / $per_page ),
            'page'        => $page,
        ) );
    }

    public static function srm_get_link() {
        self::verify();
        $id   = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $link = SRM_DB::get_link_by_id( $id );
        if ( ! $link ) {
            wp_send_json_error( array( 'message' => 'Link not found' ), 404 );
        }
        $settings         = SRM_DB::get_settings();
        $link['masked_url'] = home_url( '/' . $settings['url_prefix'] . '/' . $link['slug'] );
        wp_send_json_success( array( 'link' => $link ) );
    }

    public static function srm_save_link() {
        self::verify();

        $id   = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $data = array(
            'slug'            => isset( $_POST['slug']            ) ? sanitize_text_field( $_POST['slug']            ) : '',
            'real_url'        => isset( $_POST['real_url']        ) ? esc_url_raw( trim( $_POST['real_url'] ) )        : '',
            'article_title'   => isset( $_POST['article_title']   ) ? sanitize_text_field( $_POST['article_title']   ) : '',
            'article_content' => isset( $_POST['article_content'] ) ? wp_kses_post( $_POST['article_content'] )        : '',
            'article_image'   => isset( $_POST['article_image']   ) ? esc_url_raw( trim( $_POST['article_image'] ) )  : '',
            'timer'           => isset( $_POST['timer']           ) ? max( 1, intval( $_POST['timer'] ) )              : 5,
        );

        if ( empty( $data['real_url'] ) ) {
            wp_send_json_error( array( 'message' => 'Real URL is required.' ) );
        }

        // Auto-generate slug from real_url if not provided
        if ( empty( $data['slug'] ) ) {
            $data['slug'] = SRM_DB::sanitize_slug( basename( rtrim( parse_url( $data['real_url'], PHP_URL_PATH ), '/' ) ) );
            if ( empty( $data['slug'] ) ) {
                $data['slug'] = 'link-' . time();
            }
        }

        $data['slug'] = SRM_DB::sanitize_slug( $data['slug'] );

        if ( $id > 0 ) {
            // Update
            if ( SRM_DB::slug_exists( $data['slug'], $id ) ) {
                wp_send_json_error( array( 'message' => 'Slug already in use by another link.' ) );
            }
            $ok = SRM_DB::update_link( $id, $data );
            if ( ! $ok ) {
                wp_send_json_error( array( 'message' => 'Failed to update link.' ) );
            }
            $link = SRM_DB::get_link_by_id( $id );
        } else {
            // Insert
            if ( SRM_DB::slug_exists( $data['slug'] ) ) {
                $data['slug'] .= '-' . substr( md5( uniqid() ), 0, 6 );
            }
            $new_id = SRM_DB::insert_link( $data );
            if ( ! $new_id ) {
                wp_send_json_error( array( 'message' => 'Failed to create link.' ) );
            }
            $link = SRM_DB::get_link_by_id( $new_id );
        }

        $settings            = SRM_DB::get_settings();
        $link['masked_url']  = home_url( '/' . $settings['url_prefix'] . '/' . $link['slug'] );

        wp_send_json_success( array( 'link' => $link, 'message' => $id > 0 ? 'Link updated.' : 'Link created.' ) );
    }

    public static function srm_delete_link() {
        self::verify();
        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( $id <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid ID.' ) );
        }
        $ok = SRM_DB::delete_link( $id );
        if ( ! $ok ) {
            wp_send_json_error( array( 'message' => 'Failed to delete.' ) );
        }
        wp_send_json_success( array( 'message' => 'Link deleted.' ) );
    }

    public static function srm_save_settings() {
        self::verify();
        $data = array(
            'url_prefix'         => isset( $_POST['url_prefix']         ) ? sanitize_key( $_POST['url_prefix'] )                          : 'go',
            'timer'              => isset( $_POST['timer']              ) ? max( 1, intval( $_POST['timer'] ) )                            : 5,
            'anti_bomb_limit'    => isset( $_POST['anti_bomb_limit']    ) ? max( 1, intval( $_POST['anti_bomb_limit'] ) )                   : 5,
            'anti_bomb_window'   => isset( $_POST['anti_bomb_window']   ) ? max( 10, intval( $_POST['anti_bomb_window'] ) )                 : 60,
            'bomb_lockout'       => isset( $_POST['bomb_lockout']       ) ? max( 30, intval( $_POST['bomb_lockout'] ) )                     : 300,
            'ads_head'           => isset( $_POST['ads_head']           ) ? wp_unslash( $_POST['ads_head'] )                               : '',
            'ads_foot'           => isset( $_POST['ads_foot']           ) ? wp_unslash( $_POST['ads_foot'] )                               : '',
        );
        $updated = SRM_DB::save_settings( $data );
        wp_send_json_success( array( 'settings' => $updated, 'message' => 'Settings saved.' ) );
    }

    public static function srm_get_stats() {
        self::verify();
        wp_send_json_success( SRM_DB::get_stats() );
    }
}
