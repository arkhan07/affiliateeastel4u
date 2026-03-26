<?php
/**
 * Plugin Name: Affiliate MLM Pro
 * Plugin URI: https://yoursite.com/affiliate-mlm-pro
 * Description: Sistem Affiliate Multi-Level Marketing lengkap dengan WooCommerce, Elementor, Member Area, Withdrawal, Landing Page, dan REST API.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: affiliate-mlm-pro
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AFFILIATE_MLM_VERSION', '1.0.0' );
define( 'AFFILIATE_MLM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AFFILIATE_MLM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AFFILIATE_MLM_PLUGIN_FILE', __FILE__ );

/**
 * Autoloader
 */
spl_autoload_register( function( $class ) {
    $prefix   = 'Affiliate_MLM_';
    $base_dir = AFFILIATE_MLM_PLUGIN_DIR . 'includes/';
    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
        return;
    }
    $relative = substr( $class, strlen( $prefix ) );
    $file     = $base_dir . 'class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
    if ( file_exists( $file ) ) {
        require $file;
    }
} );

/**
 * Load admin classes manually
 */
function affiliate_mlm_load_admin_classes() {
    $admin_files = [
        'class-affiliate-admin.php',
        'class-affiliate-admin-settings.php',
        'class-affiliate-admin-withdrawals.php',
    ];
    foreach ( $admin_files as $file ) {
        $path = AFFILIATE_MLM_PLUGIN_DIR . 'admin/' . $file;
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}

function affiliate_mlm_load_public_classes() {
    $public_files = [
        'class-affiliate-public.php',
        'class-affiliate-shortcodes.php',
        'class-affiliate-elementor.php',
    ];
    foreach ( $public_files as $file ) {
        $path = AFFILIATE_MLM_PLUGIN_DIR . 'public/' . $file;
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}

/**
 * Main Plugin Class
 */
class Affiliate_MLM_Pro {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_textdomain();
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_textdomain() {
        add_action( 'plugins_loaded', function() {
            load_plugin_textdomain(
                'affiliate-mlm-pro',
                false,
                dirname( plugin_basename( __FILE__ ) ) . '/languages/'
            );
        } );
    }

    private function load_dependencies() {
        require_once AFFILIATE_MLM_PLUGIN_DIR . 'includes/class-core.php';
        require_once AFFILIATE_MLM_PLUGIN_DIR . 'includes/class-tracking.php';
        require_once AFFILIATE_MLM_PLUGIN_DIR . 'includes/class-commission.php';
        require_once AFFILIATE_MLM_PLUGIN_DIR . 'includes/class-mlm.php';
        require_once AFFILIATE_MLM_PLUGIN_DIR . 'includes/class-withdrawal.php';
        require_once AFFILIATE_MLM_PLUGIN_DIR . 'includes/class-woocommerce.php';
        require_once AFFILIATE_MLM_PLUGIN_DIR . 'api/class-affiliate-rest-api.php';

        if ( is_admin() ) {
            affiliate_mlm_load_admin_classes();
        } else {
            affiliate_mlm_load_public_classes();
        }
    }

    private function init_hooks() {
        register_activation_hook( __FILE__, [ 'Affiliate_MLM_Core', 'activate' ] );
        register_deactivation_hook( __FILE__, [ 'Affiliate_MLM_Core', 'deactivate' ] );

        add_action( 'init', [ 'Affiliate_MLM_Tracking', 'init' ] );
        add_action( 'user_register', [ 'Affiliate_MLM_Tracking', 'on_user_register' ] );
        add_action( 'rest_api_init', [ 'Affiliate_MLM_REST_API', 'register_routes' ] );

        if ( class_exists( 'WooCommerce' ) ) {
            add_action( 'woocommerce_checkout_order_processed', [ 'Affiliate_MLM_WooCommerce', 'save_affiliate_to_order' ], 10, 3 );
            add_action( 'woocommerce_order_status_completed', [ 'Affiliate_MLM_WooCommerce', 'process_commission' ] );
            add_action( 'woocommerce_order_status_refunded', [ 'Affiliate_MLM_WooCommerce', 'cancel_commission' ] );
        }

        if ( did_action( 'elementor/loaded' ) || defined( 'ELEMENTOR_VERSION' ) ) {
            add_action( 'elementor/dynamic_tags/register', [ 'Affiliate_MLM_Elementor', 'register_tags' ] );
        }

        if ( ! is_admin() ) {
            add_action( 'init', [ 'Affiliate_MLM_Public', 'init' ] );
            add_action( 'wp_enqueue_scripts', [ 'Affiliate_MLM_Public', 'enqueue_assets' ] );
            $this->register_shortcodes();
        } else {
            add_action( 'admin_menu', [ 'Affiliate_MLM_Admin', 'add_menu' ] );
            add_action( 'admin_enqueue_scripts', [ 'Affiliate_MLM_Admin', 'enqueue_assets' ] );
            add_action( 'wp_ajax_affiliate_mlm_approve_commission', [ 'Affiliate_MLM_Admin', 'ajax_approve_commission' ] );
            add_action( 'wp_ajax_affiliate_mlm_approve_withdrawal', [ 'Affiliate_MLM_Admin_Withdrawals', 'ajax_approve' ] );
            add_action( 'wp_ajax_affiliate_mlm_reject_withdrawal', [ 'Affiliate_MLM_Admin_Withdrawals', 'ajax_reject' ] );
            add_action( 'wp_ajax_affiliate_mlm_export_csv', [ 'Affiliate_MLM_Admin', 'export_csv' ] );
        }

        add_action( 'wp_ajax_affiliate_register', [ 'Affiliate_MLM_Tracking', 'ajax_register' ] );
        add_action( 'wp_ajax_nopriv_affiliate_register', [ 'Affiliate_MLM_Tracking', 'ajax_register' ] );
        add_action( 'wp_ajax_affiliate_withdraw_request', [ 'Affiliate_MLM_Withdrawal', 'ajax_request' ] );
        add_action( 'wp_ajax_affiliate_update_profile', [ 'Affiliate_MLM_Tracking', 'ajax_update_profile' ] );
    }

    private function register_shortcodes() {
        $shortcodes = new Affiliate_MLM_Shortcodes();
        $shortcodes->register();
    }
}

function affiliate_mlm_pro() {
    return Affiliate_MLM_Pro::instance();
}

affiliate_mlm_pro();
