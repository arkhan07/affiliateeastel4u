<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Commission calculation and management.
 */
class Affiliate_MLM_Commission {

    /**
     * Process registration commission for sponsor chain.
     */
    public static function process_registration_commission( $new_affiliate_id, $direct_sponsor_id ) {
        $reg_amount = (float) get_option( 'affiliate_reg_commission', 0 );
        if ( $reg_amount <= 0 ) {
            return;
        }

        $chain    = Affiliate_MLM_MLM::get_upline_chain( $new_affiliate_id );
        $max_lvl  = (int) get_option( 'affiliate_mlm_max_level', 3 );
        $rates    = self::get_level_rates();

        foreach ( $chain as $level => $aff_id ) {
            if ( $level > $max_lvl ) break;
            $rate   = $rates[ $level ] ?? 0;
            $amount = $reg_amount * ( $rate / 100 );
            if ( $amount <= 0 ) continue;
            self::insert_commission( $aff_id, null, $new_affiliate_id, 'registration', $amount, $level );
        }
    }

    /**
     * Process purchase commission for an order.
     */
    public static function process_purchase_commission( $order_id, $affiliate_id, $order_subtotal ) {
        $commission_type = get_option( 'affiliate_commission_type', 'percent' );
        $chain           = Affiliate_MLM_MLM::get_upline_chain( $affiliate_id );
        array_unshift_assoc_level1( $chain, $affiliate_id ); // Level 1 is self (direct affiliate)

        // Rebuild: level 1 = the direct affiliate, rest from upline
        $levels           = [];
        $levels[1]        = $affiliate_id;
        foreach ( $chain as $level => $aff_id ) {
            $levels[ $level + 1 ] = $aff_id;
        }

        $max_lvl = (int) get_option( 'affiliate_mlm_max_level', 3 );
        $rates   = self::get_level_rates();

        foreach ( $levels as $level => $aff_id ) {
            if ( $level > $max_lvl ) break;
            $rate = $rates[ $level ] ?? 0;
            if ( 'percent' === $commission_type ) {
                $amount = $order_subtotal * ( $rate / 100 );
            } else {
                $amount = (float) get_option( 'affiliate_fixed_commission_' . $level, 0 );
            }
            if ( $amount <= 0 ) continue;
            self::insert_commission( $aff_id, $order_id, null, 'purchase', $amount, $level );
            self::send_commission_email( $aff_id, $amount );
        }
    }

    /**
     * Insert a commission record.
     */
    public static function insert_commission( $affiliate_id, $order_id, $referred_user_id, $type, $amount, $level ) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'affiliate_commissions',
            [
                'affiliate_id'     => $affiliate_id,
                'order_id'         => $order_id,
                'referred_user_id' => $referred_user_id,
                'type'             => $type,
                'amount'           => round( $amount, 2 ),
                'level'            => $level,
                'status'           => 'pending',
                'created_at'       => current_time( 'mysql' ),
                'updated_at'       => current_time( 'mysql' ),
            ],
            [ '%d', '%d', '%d', '%s', '%f', '%d', '%s', '%s', '%s' ]
        );
    }

    /**
     * Get commission rates per level from options.
     */
    public static function get_level_rates() {
        return [
            1 => (float) get_option( 'affiliate_level1_rate', 10 ),
            2 => (float) get_option( 'affiliate_level2_rate', 5 ),
            3 => (float) get_option( 'affiliate_level3_rate', 2 ),
        ];
    }

    /**
     * Get commissions for an affiliate.
     */
    public static function get_commissions( $affiliate_id, $status = null, $limit = 20, $offset = 0 ) {
        global $wpdb;
        $where = $wpdb->prepare( 'WHERE affiliate_id = %d', $affiliate_id );
        if ( $status ) {
            $where .= $wpdb->prepare( ' AND status = %s', $status );
        }
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}affiliate_commissions $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset"
        );
    }

    /**
     * Get total commission by status.
     */
    public static function get_total_commission( $affiliate_id, $status = 'approved' ) {
        global $wpdb;
        return (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}affiliate_commissions WHERE affiliate_id = %d AND status = %s",
            $affiliate_id, $status
        ) );
    }

    /**
     * Approve commission (admin).
     */
    public static function approve( $commission_id ) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix . 'affiliate_commissions',
            [ 'status' => 'approved', 'updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $commission_id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );
    }

    /**
     * Reject commission (admin).
     */
    public static function reject( $commission_id, $note = '' ) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix . 'affiliate_commissions',
            [ 'status' => 'rejected', 'note' => sanitize_text_field( $note ), 'updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $commission_id ],
            [ '%s', '%s', '%s' ],
            [ '%d' ]
        );
    }

    /**
     * Cancel commissions for a refunded order.
     */
    public static function cancel_by_order( $order_id ) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'affiliate_commissions',
            [ 'status' => 'rejected', 'note' => 'Order refunded', 'updated_at' => current_time( 'mysql' ) ],
            [ 'order_id' => $order_id ],
            [ '%s', '%s', '%s' ],
            [ '%d' ]
        );
    }

    private static function send_commission_email( $affiliate_id, $amount ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT a.*, u.user_email, u.display_name FROM {$wpdb->prefix}affiliates a
             LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id WHERE a.id = %d LIMIT 1",
            $affiliate_id
        ) );
        if ( ! $row ) return;
        $subject = sprintf( __( 'Komisen Baru - %s', 'affiliate-mlm-pro' ), get_bloginfo( 'name' ) );
        $message = sprintf(
            __( "Salam %s,\n\nAnda telah menerima komisen baharu sebanyak %s.\n\nSalam,\n%s", 'affiliate-mlm-pro' ),
            esc_html( $row->display_name ),
            number_format( $amount, 2 ),
            get_bloginfo( 'name' )
        );
        wp_mail( $row->user_email, $subject, $message );
    }
}

// Helper to fix level numbering
if ( ! function_exists( 'array_unshift_assoc_level1' ) ) {
    function array_unshift_assoc_level1( &$arr, $val ) {
        // no-op helper, logic handled inline
    }
}
