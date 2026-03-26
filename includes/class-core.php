<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Core class - handles activation, deactivation, and database setup.
 */
class Affiliate_MLM_Core {

    /**
     * Plugin activation hook.
     */
    public static function activate() {
        self::create_tables();
        self::create_affiliate_role();
        self::set_default_options();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook.
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Create custom database tables.
     */
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$wpdb->prefix}affiliates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            sponsor_id bigint(20) DEFAULT NULL,
            affiliate_slug varchar(100) NOT NULL,
            phone varchar(30) DEFAULT NULL,
            description text DEFAULT NULL,
            negeri varchar(100) DEFAULT NULL,
            negara varchar(100) DEFAULT NULL,
            wa_message text DEFAULT NULL,
            member_type enum('free','vip') DEFAULT 'free',
            status enum('active','inactive','suspended') DEFAULT 'active',
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY affiliate_slug (affiliate_slug),
            UNIQUE KEY user_id (user_id),
            KEY sponsor_id (sponsor_id)
        ) $charset;";
        dbDelta( $sql );

        $sql = "CREATE TABLE {$wpdb->prefix}affiliate_referrals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            referred_user_id bigint(20) DEFAULT NULL,
            session_id varchar(100) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            type enum('hit','registration') DEFAULT 'hit',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY referred_user_id (referred_user_id)
        ) $charset;";
        dbDelta( $sql );

        $sql = "CREATE TABLE {$wpdb->prefix}affiliate_commissions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            order_id bigint(20) DEFAULT NULL,
            referred_user_id bigint(20) DEFAULT NULL,
            type enum('registration','purchase') DEFAULT 'purchase',
            amount decimal(15,2) NOT NULL DEFAULT 0,
            level tinyint(1) DEFAULT 1,
            status enum('pending','approved','rejected') DEFAULT 'pending',
            note text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset;";
        dbDelta( $sql );

        $sql = "CREATE TABLE {$wpdb->prefix}affiliate_withdrawals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            amount decimal(15,2) NOT NULL,
            method enum('bank','ewallet') DEFAULT 'bank',
            account_name varchar(150) DEFAULT NULL,
            account_number varchar(100) DEFAULT NULL,
            bank_name varchar(100) DEFAULT NULL,
            status enum('pending','approved','paid','rejected') DEFAULT 'pending',
            note text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY status (status)
        ) $charset;";
        dbDelta( $sql );

        $sql = "CREATE TABLE {$wpdb->prefix}affiliate_levels (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) NOT NULL,
            parent_id bigint(20) DEFAULT NULL,
            level tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY parent_id (parent_id)
        ) $charset;";
        dbDelta( $sql );
    }

    /**
     * Create affiliate custom role.
     */
    public static function create_affiliate_role() {
        add_role(
            'affiliate',
            __( 'Affiliate', 'affiliate-mlm-pro' ),
            [
                'read'         => true,
                'upload_files' => false,
            ]
        );

        add_role(
            'affiliate_vip',
            __( 'Affiliate VIP', 'affiliate-mlm-pro' ),
            [
                'read'         => true,
                'upload_files' => false,
            ]
        );
    }

    /**
     * Set default plugin options.
     */
    public static function set_default_options() {
        $defaults = [
            'affiliate_cookie_days'       => 30,
            'affiliate_mlm_max_level'     => 3,
            'affiliate_level1_rate'       => 10,
            'affiliate_level2_rate'       => 5,
            'affiliate_level3_rate'       => 2,
            'affiliate_reg_commission'    => 0,
            'affiliate_min_withdraw'      => 50000,
            'affiliate_commission_type'   => 'percent',
            'affiliate_dashboard_page'    => 0,
            'affiliate_register_page'     => 0,
        ];
        foreach ( $defaults as $key => $value ) {
            if ( get_option( $key ) === false ) {
                update_option( $key, $value );
            }
        }
    }

    /**
     * Get affiliate record by user_id.
     */
    public static function get_affiliate_by_user( $user_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}affiliates WHERE user_id = %d LIMIT 1",
            $user_id
        ) );
    }

    /**
     * Get affiliate record by slug.
     */
    public static function get_affiliate_by_slug( $slug ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}affiliates WHERE affiliate_slug = %s LIMIT 1",
            $slug
        ) );
    }

    /**
     * Get current logged-in user's affiliate record.
     */
    public static function get_current_affiliate() {
        if ( ! is_user_logged_in() ) {
            return null;
        }
        return self::get_affiliate_by_user( get_current_user_id() );
    }

    /**
     * Get approved balance for an affiliate.
     */
    public static function get_affiliate_balance( $affiliate_id ) {
        global $wpdb;
        $earned = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}affiliate_commissions
             WHERE affiliate_id = %d AND status = 'approved'",
            $affiliate_id
        ) );
        $withdrawn = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}affiliate_withdrawals
             WHERE affiliate_id = %d AND status IN ('approved','paid')",
            $affiliate_id
        ) );
        return max( 0, $earned - $withdrawn );
    }

    /**
     * Get list of negeri/provinsi options.
     */
    public static function get_negeri_options() {
        return [
            'Malaysia' => [
                'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang',
                'Perak', 'Perlis', 'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor',
                'Terengganu', 'W.P. Kuala Lumpur', 'W.P. Labuan', 'W.P. Putrajaya',
            ],
            'Indonesia' => [
                'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Kepulauan Riau',
                'Jambi', 'Bengkulu', 'Sumatera Selatan', 'Kepulauan Bangka Belitung',
                'Lampung', 'DKI Jakarta', 'Banten', 'Jawa Barat', 'Jawa Tengah',
                'D.I. Yogyakarta', 'Jawa Timur', 'Bali', 'Nusa Tenggara Barat',
                'Nusa Tenggara Timur', 'Kalimantan Barat', 'Kalimantan Tengah',
                'Kalimantan Selatan', 'Kalimantan Timur', 'Kalimantan Utara',
                'Sulawesi Utara', 'Gorontalo', 'Sulawesi Tengah', 'Sulawesi Barat',
                'Sulawesi Selatan', 'Sulawesi Tenggara', 'Maluku', 'Maluku Utara',
                'Papua Barat', 'Papua',
            ],
        ];
    }

    /**
     * Get country options.
     */
    public static function get_country_options() {
        return [
            'Malaysia', 'Indonesia', 'Singapura', 'Brunei Darussalam',
            'Thailand', 'Filipina', 'Vietnam', 'Myanmar', 'Kemboja',
            'Laos', 'Australia', 'United Kingdom', 'United States',
            'Lain-lain',
        ];
    }
}
