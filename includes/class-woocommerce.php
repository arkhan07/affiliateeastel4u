<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WooCommerce integration for affiliate tracking and commission.
 */
class Affiliate_MLM_WooCommerce {

    /**
     * Save affiliate ref to order meta during checkout.
     */
    public static function save_affiliate_to_order( $order_id, $posted_data, $order ) {
        $slug = isset( $_COOKIE['affiliate_ref'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['affiliate_ref'] ) ) : '';
        if ( empty( $slug ) ) {
            return;
        }

        $affiliate = Affiliate_MLM_Core::get_affiliate_by_slug( $slug );
        if ( ! $affiliate ) {
            return;
        }

        $order->update_meta_data( '_affiliate_ref', $slug );
        $order->update_meta_data( '_affiliate_id', $affiliate->id );
        $order->save();
    }

    /**
     * Process commission when order status changes to completed.
     */
    public static function process_commission( $order_id ) {
        $order        = wc_get_order( $order_id );
        $affiliate_id = (int) $order->get_meta( '_affiliate_id' );

        if ( ! $affiliate_id ) {
            return;
        }

        // Prevent duplicate commissions
        global $wpdb;
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}affiliate_commissions WHERE order_id = %d",
            $order_id
        ) );
        if ( $exists > 0 ) {
            return;
        }

        $subtotal = (float) $order->get_subtotal();

        // Level 1 is the direct affiliate
        $levels    = [ 1 => $affiliate_id ];
        $upline    = Affiliate_MLM_MLM::get_upline_chain( $affiliate_id );
        foreach ( $upline as $lvl => $aff_id ) {
            $levels[ $lvl + 1 ] = $aff_id;
        }

        $max_lvl         = (int) get_option( 'affiliate_mlm_max_level', 3 );
        $commission_type = get_option( 'affiliate_commission_type', 'percent' );
        $rates           = Affiliate_MLM_Commission::get_level_rates();

        foreach ( $levels as $level => $aff_id ) {
            if ( $level > $max_lvl ) break;

            // Check per-product commission override
            $amount = 0;
            if ( 'percent' === $commission_type ) {
                $rate   = $rates[ $level ] ?? 0;
                $amount = $subtotal * ( $rate / 100 );
            } else {
                $amount = (float) get_option( 'affiliate_fixed_commission_' . $level, 0 );
            }

            if ( $amount <= 0 ) continue;

            Affiliate_MLM_Commission::insert_commission( $aff_id, $order_id, null, 'purchase', $amount, $level );

            // Notify affiliate
            self::send_commission_notification( $aff_id, $amount, $order_id );
        }
    }

    /**
     * Cancel commission when order is refunded.
     */
    public static function cancel_commission( $order_id ) {
        Affiliate_MLM_Commission::cancel_by_order( $order_id );
    }

    /**
     * Get affiliate link for a product page (for display use).
     */
    public static function get_affiliate_product_url( $product_id, $affiliate_slug ) {
        return add_query_arg( 'ref', $affiliate_slug, get_permalink( $product_id ) );
    }

    private static function send_commission_notification( $affiliate_id, $amount, $order_id ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT a.*, u.user_email, u.display_name FROM {$wpdb->prefix}affiliates a
             LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id WHERE a.id = %d LIMIT 1",
            $affiliate_id
        ) );
        if ( ! $row ) return;

        $subject = sprintf( __( 'Komisen Jualan Baru - %s', 'affiliate-mlm-pro' ), get_bloginfo( 'name' ) );
        $message = sprintf(
            __( "Salam %s,\n\nAnda telah mendapat komisen baru sebanyak %s daripada pesanan #%d.\n\nLog masuk ke dashboard anda untuk maklumat lanjut.\n\nSalam,\n%s", 'affiliate-mlm-pro' ),
            esc_html( $row->display_name ),
            number_format( $amount, 2 ),
            $order_id,
            get_bloginfo( 'name' )
        );
        wp_mail( $row->user_email, $subject, $message );
    }
}
