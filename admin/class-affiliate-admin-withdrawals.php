<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Affiliate_MLM_Admin_Withdrawals {

    public static function page() {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT w.*, a.affiliate_slug, u.display_name, u.user_email
             FROM {$wpdb->prefix}affiliate_withdrawals w
             LEFT JOIN {$wpdb->prefix}affiliates a ON a.id = w.affiliate_id
             LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
             ORDER BY w.created_at DESC LIMIT 100"
        );
        ?>
        <div class="wrap affiliate-mlm-admin">
            <h1><?php esc_html_e( 'Withdrawal Requests', 'affiliate-mlm-pro' ); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr>
                    <th>ID</th>
                    <th><?php esc_html_e( 'Affiliate', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Jumlah', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Kaedah', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Nama Akaun', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'No Akaun', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Bank', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Tarikh', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Tindakan', 'affiliate-mlm-pro' ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $rows as $r ) : ?>
                <tr>
                    <td><?php echo esc_html( $r->id ); ?></td>
                    <td><?php echo esc_html( $r->display_name . ' (' . $r->affiliate_slug . ')' ); ?></td>
                    <td><?php echo esc_html( number_format( $r->amount, 2 ) ); ?></td>
                    <td><?php echo esc_html( strtoupper( $r->method ) ); ?></td>
                    <td><?php echo esc_html( $r->account_name ); ?></td>
                    <td><?php echo esc_html( $r->account_number ); ?></td>
                    <td><?php echo esc_html( $r->bank_name ); ?></td>
                    <td><span class="amlm-status-<?php echo esc_attr( $r->status ); ?>"><?php echo esc_html( ucfirst( $r->status ) ); ?></span></td>
                    <td><?php echo esc_html( $r->created_at ); ?></td>
                    <td>
                        <?php if ( 'pending' === $r->status ) : ?>
                        <button class="button button-small amlm-approve-withdraw" data-id="<?php echo esc_attr( $r->id ); ?>"><?php esc_html_e( 'Approve', 'affiliate-mlm-pro' ); ?></button>
                        <button class="button button-small amlm-reject-withdraw" data-id="<?php echo esc_attr( $r->id ); ?>"><?php esc_html_e( 'Reject', 'affiliate-mlm-pro' ); ?></button>
                        <?php elseif ( 'approved' === $r->status ) : ?>
                        <button class="button button-small amlm-paid-withdraw" data-id="<?php echo esc_attr( $r->id ); ?>"><?php esc_html_e( 'Mark Paid', 'affiliate-mlm-pro' ); ?></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function ajax_approve() {
        check_ajax_referer( 'affiliate_mlm_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die();
        $id   = absint( $_POST['id'] ?? 0 );
        $note = sanitize_text_field( $_POST['note'] ?? '' );
        Affiliate_MLM_Withdrawal::approve( $id, $note );
        wp_send_json_success();
    }

    public static function ajax_reject() {
        check_ajax_referer( 'affiliate_mlm_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die();
        $id   = absint( $_POST['id'] ?? 0 );
        $note = sanitize_text_field( $_POST['note'] ?? '' );
        Affiliate_MLM_Withdrawal::reject( $id, $note );
        wp_send_json_success();
    }
}
