<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Withdrawal request management.
 */
class Affiliate_MLM_Withdrawal {

    /**
     * AJAX: submit withdrawal request.
     */
    public static function ajax_request() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Anda perlu log masuk.', 'affiliate-mlm-pro' ) ] );
        }

        check_ajax_referer( 'affiliate_withdraw', '_wpnonce_withdraw' );

        $affiliate = Affiliate_MLM_Core::get_current_affiliate();
        if ( ! $affiliate ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Akaun affiliate tidak dijumpai.', 'affiliate-mlm-pro' ) ] );
        }

        $amount         = floatval( wp_unslash( $_POST['amount'] ?? 0 ) );
        $method         = sanitize_text_field( wp_unslash( $_POST['method'] ?? '' ) );
        $account_name   = sanitize_text_field( wp_unslash( $_POST['account_name'] ?? '' ) );
        $account_number = sanitize_text_field( wp_unslash( $_POST['account_number'] ?? '' ) );
        $bank_name      = sanitize_text_field( wp_unslash( $_POST['bank_name'] ?? '' ) );

        $min_withdraw = (float) get_option( 'affiliate_min_withdraw', 50000 );
        $balance      = Affiliate_MLM_Core::get_affiliate_balance( $affiliate->id );

        if ( $amount < $min_withdraw ) {
            wp_send_json_error( [
                'message' => sprintf(
                    esc_html__( 'Jumlah minimum pengeluaran ialah %s.', 'affiliate-mlm-pro' ),
                    number_format( $min_withdraw, 2 )
                ),
            ] );
        }

        if ( $amount > $balance ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Baki tidak mencukupi.', 'affiliate-mlm-pro' ) ] );
        }

        if ( ! in_array( $method, [ 'bank', 'ewallet' ], true ) ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Kaedah pengeluaran tidak sah.', 'affiliate-mlm-pro' ) ] );
        }

        if ( empty( $account_name ) || empty( $account_number ) ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Maklumat akaun diperlukan.', 'affiliate-mlm-pro' ) ] );
        }

        // Check for pending withdrawal
        global $wpdb;
        $pending = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}affiliate_withdrawals WHERE affiliate_id = %d AND status = 'pending'",
            $affiliate->id
        ) );

        if ( $pending > 0 ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Anda mempunyai permintaan pengeluaran yang belum diproses.', 'affiliate-mlm-pro' ) ] );
        }

        $wpdb->insert(
            $wpdb->prefix . 'affiliate_withdrawals',
            [
                'affiliate_id'   => $affiliate->id,
                'amount'         => round( $amount, 2 ),
                'method'         => $method,
                'account_name'   => $account_name,
                'account_number' => $account_number,
                'bank_name'      => $bank_name,
                'status'         => 'pending',
                'created_at'     => current_time( 'mysql' ),
                'updated_at'     => current_time( 'mysql' ),
            ],
            [ '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        wp_send_json_success( [ 'message' => esc_html__( 'Permintaan pengeluaran berjaya dihantar.', 'affiliate-mlm-pro' ) ] );
    }

    /**
     * Get withdrawals for an affiliate.
     */
    public static function get_withdrawals( $affiliate_id, $limit = 20, $offset = 0 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}affiliate_withdrawals WHERE affiliate_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $affiliate_id, $limit, $offset
        ) );
    }

    /**
     * Approve withdrawal (admin).
     */
    public static function approve( $withdrawal_id, $note = '' ) {
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'affiliate_withdrawals',
            [ 'status' => 'approved', 'note' => sanitize_text_field( $note ), 'updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $withdrawal_id ],
            [ '%s', '%s', '%s' ],
            [ '%d' ]
        );
        if ( $result ) {
            self::send_status_email( $withdrawal_id, 'approved' );
        }
        return $result;
    }

    /**
     * Mark withdrawal as paid (admin).
     */
    public static function mark_paid( $withdrawal_id, $note = '' ) {
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'affiliate_withdrawals',
            [ 'status' => 'paid', 'note' => sanitize_text_field( $note ), 'updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $withdrawal_id ],
            [ '%s', '%s', '%s' ],
            [ '%d' ]
        );
        return $result;
    }

    /**
     * Reject withdrawal (admin).
     */
    public static function reject( $withdrawal_id, $note = '' ) {
        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'affiliate_withdrawals',
            [ 'status' => 'rejected', 'note' => sanitize_text_field( $note ), 'updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $withdrawal_id ],
            [ '%s', '%s', '%s' ],
            [ '%d' ]
        );
        if ( $result ) {
            self::send_status_email( $withdrawal_id, 'rejected', $note );
        }
        return $result;
    }

    private static function send_status_email( $withdrawal_id, $status, $note = '' ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT w.*, a.user_id, u.user_email, u.display_name
             FROM {$wpdb->prefix}affiliate_withdrawals w
             LEFT JOIN {$wpdb->prefix}affiliates a ON a.id = w.affiliate_id
             LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
             WHERE w.id = %d LIMIT 1",
            $withdrawal_id
        ) );
        if ( ! $row ) return;

        $label   = 'approved' === $status
            ? __( 'Diluluskan', 'affiliate-mlm-pro' )
            : __( 'Ditolak', 'affiliate-mlm-pro' );
        $subject = sprintf( __( 'Permintaan Pengeluaran %s - %s', 'affiliate-mlm-pro' ), $label, get_bloginfo( 'name' ) );
        $message = sprintf(
            __( "Salam %s,\n\nPermintaan pengeluaran anda sebanyak %s telah %s.\n\n%s\n\nSalam,\n%s", 'affiliate-mlm-pro' ),
            esc_html( $row->display_name ),
            number_format( $row->amount, 2 ),
            esc_html( $label ),
            $note ? __( 'Catatan: ', 'affiliate-mlm-pro' ) . esc_html( $note ) : '',
            get_bloginfo( 'name' )
        );
        wp_mail( $row->user_email, $subject, $message );
    }
}
