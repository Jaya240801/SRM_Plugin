<?php
/**
 * Front-end Request Router — Safe Redirect Masker
 *
 * Flow:
 *   1. A request arrives at /{prefix}/{slug}
 *   2. If bot → 302 to real_url (the original article)
 *   3. If human → anti-bomb check; if clear → render safelink page with countdown
 *   4. After countdown the user is redirected to real_url via JS
 *
 * @package SafeRedirectMasker
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class SRM_Router {

    public static function init() {
        add_action( 'template_redirect', array( __CLASS__, 'handle_request' ), 1 );
    }

    public static function handle_request() {
        if ( is_admin() ) { return; }

        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
        if ( empty( $request_uri ) ) { return; }

        $path = trim( (string) parse_url( $request_uri, PHP_URL_PATH ), '/' );

        // Strip WP subfolder prefix if present
        $home_path = trim( (string) parse_url( home_url(), PHP_URL_PATH ), '/' );
        if ( ! empty( $home_path ) && 0 === strpos( $path, $home_path ) ) {
            $path = trim( substr( $path, strlen( $home_path ) ), '/' );
        }

        if ( empty( $path ) ) { return; }

        $settings = SRM_DB::get_settings();
        $prefix   = sanitize_key( $settings['url_prefix'] );   // e.g. "go"

        // Match /{prefix}/{slug}
        if ( ! empty( $prefix ) ) {
            $pattern = '~^' . preg_quote( $prefix, '~' ) . '/(.+)$~i';
            if ( preg_match( $pattern, $path, $m ) ) {
                $slug = SRM_DB::sanitize_slug( $m[1] );
                self::dispatch( $slug );
                return;
            }
        } else {
            // No prefix — match any slug (flat)
            $slug = SRM_DB::sanitize_slug( $path );
            self::dispatch( $slug );
        }
    }

    /**
     * Look up the link and decide: bot redirect vs. human safelink page.
     */
    private static function dispatch( $slug ) {
        $link = SRM_DB::get_link_by_slug( $slug );
        if ( ! $link ) { return; }   // Not our link → let WP handle it (404)

        if ( empty( $link['real_url'] ) ) {
            wp_redirect( home_url() ); exit;
        }

        $ip      = SRM_Bot_Detector::get_ip();
        $ip_hash = SRM_Bot_Detector::get_ip_hash();
        $is_bot  = SRM_Bot_Detector::is_bot();

        // ── BOT PATH ─────────────────────────────────────────────────────────
        if ( $is_bot ) {
            SRM_DB::record_click( $link['id'], $ip_hash, true );
            wp_redirect( esc_url_raw( $link['real_url'] ), 301 );
            exit;
        }

        // ── HUMAN PATH ───────────────────────────────────────────────────────

        // Anti-bomb check
        if ( ! SRM_Anti_Bomb::is_allowed( $link['id'], $ip ) ) {
            SRM_Anti_Bomb::render_blocked_page();
            // render_blocked_page() calls exit()
        }

        // Record legitimate human click
        SRM_DB::record_click( $link['id'], $ip_hash, false );

        // Render the safelink/cloaking page
        require_once SRM_PATH . 'includes/class-srm-renderer.php';
        SRM_Renderer::render( $link );
        exit;
    }
}
