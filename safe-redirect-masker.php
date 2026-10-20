<?php
/**
 * Plugin Name: Safe Redirect Masker
 * Plugin URI:  https://github.com/M-Sulton/SafeRedirect-WP-Plugin
 * Description: Smart URL masking with bot/crawler detection. Real users see a cloaking safelink page with countdown; crawlers get redirected straight to the real article. Includes anti-bomb click (IP rate-limiting) to block click fraud.
 * Version:     1.0.0
 * Author:      Muhammad Sulton
 * Author URI:  https://github.com/M-Sulton
 * Text Domain: safe-redirect-masker
 * Domain Path: /languages
 * License:     GPL v2 or later
 * Requires at least: 5.6
 * Requires PHP: 7.4
 *
 * @package SafeRedirectMasker
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SRM_VERSION',  '1.0.0' );
define( 'SRM_PATH',     plugin_dir_path( __FILE__ ) );
define( 'SRM_URL',      plugin_dir_url( __FILE__ ) );
define( 'SRM_BASENAME', plugin_basename( __FILE__ ) );

require_once SRM_PATH . 'includes/class-srm-db.php';
require_once SRM_PATH . 'includes/class-srm-bot-detector.php';
require_once SRM_PATH . 'includes/class-srm-anti-bomb.php';
require_once SRM_PATH . 'includes/class-srm-router.php';
require_once SRM_PATH . 'includes/class-srm-renderer.php';
require_once SRM_PATH . 'includes/class-srm-ajax.php';

final class SafeRedirectMasker {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook( __FILE__,   array( $this, 'activate'   ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        SRM_DB::maybe_upgrade();
        SRM_Router::init();
        SRM_AJAX::init();

        if ( is_admin() ) {
            add_action( 'admin_menu',            array( $this, 'register_admin_menu'  ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
            add_filter( 'plugin_action_links_' . SRM_BASENAME, array( $this, 'add_action_links' ) );
        }
    }

    public function activate() {
        SRM_DB::install_tables();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function register_admin_menu() {
        $svg = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a5b4fc" stroke-width="2.2">'
            . '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'
            . '</svg>'
        );

        add_menu_page(
            __( 'Safe Redirect Masker', 'safe-redirect-masker' ),
            __( 'Safe Redirect', 'safe-redirect-masker' ),
            'manage_options',
            'safe-redirect-masker',
            array( $this, 'render_admin_page' ),
            $svg,
            27
        );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_safe-redirect-masker' !== $hook && ( ! isset( $_GET['page'] ) || 'safe-redirect-masker' !== $_GET['page'] ) ) {
            return;
        }

        $css_ver = file_exists( SRM_PATH . 'admin/css/admin-style.css' ) ? filemtime( SRM_PATH . 'admin/css/admin-style.css' ) : SRM_VERSION;
        $js_ver  = file_exists( SRM_PATH . 'admin/js/admin-script.js' )  ? filemtime( SRM_PATH . 'admin/js/admin-script.js' )  : SRM_VERSION;

        wp_enqueue_style(
            'srm-google-fonts',
            'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap',
            array(),
            null
        );

        wp_enqueue_style(
            'srm-admin-style',
            SRM_URL . 'admin/css/admin-style.css',
            array(),
            $css_ver
        );

        wp_enqueue_script(
            'srm-admin-script',
            SRM_URL . 'admin/js/admin-script.js',
            array(),
            $js_ver,
            true
        );

        $settings = SRM_DB::get_settings();
        wp_localize_script( 'srm-admin-script', 'srm_data', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'srm_admin_nonce' ),
            'base_url' => home_url( '/' ),
            'site_url' => home_url( '/' ),
            'prefix'   => $settings['url_prefix'],
            'version'  => SRM_VERSION,
        ) );
    }

    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied.', 'safe-redirect-masker' ) );
        }
        require_once SRM_PATH . 'admin/views/dashboard.php';
    }

    public function add_action_links( $links ) {
        array_unshift( $links,
            '<a href="' . esc_url( admin_url( 'admin.php?page=safe-redirect-masker' ) ) . '">'
            . __( 'Dashboard', 'safe-redirect-masker' )
            . '</a>'
        );
        return $links;
    }
}

SafeRedirectMasker::get_instance();
