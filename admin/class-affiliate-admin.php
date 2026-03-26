<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Affiliate_MLM_Admin {

    public static function add_menu() {
        add_menu_page(
            __( 'Affiliate MLM Pro', 'affiliate-mlm-pro' ),
            __( 'Affiliate MLM', 'affiliate-mlm-pro' ),
            'manage_options',
            'affiliate-mlm-pro',
            [ __CLASS__, 'page_dashboard' ],
            'dashicons-networking',
            30
        );
        add_submenu_page( 'affiliate-mlm-pro', __( 'Dashboard', 'affiliate-mlm-pro' ), __( 'Dashboard', 'affiliate-mlm-pro' ), 'manage_options', 'affiliate-mlm-pro', [ __CLASS__, 'page_dashboard' ] );
        add_submenu_page( 'affiliate-mlm-pro', __( 'Members', 'affiliate-mlm-pro' ), __( 'Members', 'affiliate-mlm-pro' ), 'manage_options', 'affiliate-mlm-members', [ __CLASS__, 'page_members' ] );
        add_submenu_page( 'affiliate-mlm-pro', __( 'Commissions', 'affiliate-mlm-pro' ), __( 'Commissions', 'affiliate-mlm-pro' ), 'manage_options', 'affiliate-mlm-commissions', [ __CLASS__, 'page_commissions' ] );
        add_submenu_page( 'affiliate-mlm-pro', __( 'Withdrawals', 'affiliate-mlm-pro' ), __( 'Withdrawals', 'affiliate-mlm-pro' ), 'manage_options', 'affiliate-mlm-withdrawals', [ 'Affiliate_MLM_Admin_Withdrawals', 'page' ] );
        add_submenu_page( 'affiliate-mlm-pro', __( 'Settings', 'affiliate-mlm-pro' ), __( 'Settings', 'affiliate-mlm-pro' ), 'manage_options', 'affiliate-mlm-settings', [ 'Affiliate_MLM_Admin_Settings', 'page' ] );
    }

    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'affiliate-mlm' ) === false ) return;
        wp_enqueue_style( 'affiliate-mlm-admin', AFFILIATE_MLM_PLUGIN_URL . 'assets/css/affiliate-admin.css', [], AFFILIATE_MLM_VERSION );
        wp_enqueue_script( 'affiliate-mlm-admin', AFFILIATE_MLM_PLUGIN_URL . 'assets/js/affiliate-admin.js', [ 'jquery' ], AFFILIATE_MLM_VERSION, true );
        wp_localize_script( 'affiliate-mlm-admin', 'affiliateMLMAdmin', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'affiliate_mlm_admin' ),
        ] );
    }

    public static function page_dashboard() {
        global $wpdb;
        $total_affiliates   = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}affiliates" );
        $total_commissions  = $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}affiliate_commissions WHERE status='approved'" );
        $pending_withdrawals = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}affiliate_withdrawals WHERE status='pending'" );
        $pending_commissions = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}affiliate_commissions WHERE status='pending'" );
        ?>
        <div class="wrap affiliate-mlm-admin">
            <h1><?php esc_html_e( 'Affiliate MLM Pro — Dashboard', 'affiliate-mlm-pro' ); ?></h1>
            <div class="amlm-stat-cards">
                <div class="amlm-card"><span class="amlm-num"><?php echo esc_html( $total_affiliates ); ?></span><span class="amlm-label"><?php esc_html_e( 'Total Affiliates', 'affiliate-mlm-pro' ); ?></span></div>
                <div class="amlm-card"><span class="amlm-num"><?php echo esc_html( number_format( $total_commissions, 2 ) ); ?></span><span class="amlm-label"><?php esc_html_e( 'Total Commissions (Approved)', 'affiliate-mlm-pro' ); ?></span></div>
                <div class="amlm-card"><span class="amlm-num"><?php echo esc_html( $pending_commissions ); ?></span><span class="amlm-label"><?php esc_html_e( 'Pending Commissions', 'affiliate-mlm-pro' ); ?></span></div>
                <div class="amlm-card"><span class="amlm-num"><?php echo esc_html( $pending_withdrawals ); ?></span><span class="amlm-label"><?php esc_html_e( 'Pending Withdrawals', 'affiliate-mlm-pro' ); ?></span></div>
            </div>
        </div>
        <?php
    }

    public static function page_members() {
        global $wpdb;
        $members = $wpdb->get_results(
            "SELECT a.*, u.display_name, u.user_email FROM {$wpdb->prefix}affiliates a
             LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id ORDER BY a.joined_at DESC"
        );
        ?>
        <div class="wrap affiliate-mlm-admin">
            <h1><?php esc_html_e( 'Affiliate Members', 'affiliate-mlm-pro' ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=affiliate_mlm_export_csv&type=members&_wpnonce=' . wp_create_nonce( 'affiliate_mlm_admin' ) ) ); ?>" class="button"><?php esc_html_e( 'Export CSV', 'affiliate-mlm-pro' ); ?></a>
            <table class="wp-list-table widefat fixed striped" style="margin-top:1em;">
                <thead><tr>
                    <th><?php esc_html_e( 'Nama', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Username (Slug)', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Email', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Telefon', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Negara', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Jenis', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Daftar', 'affiliate-mlm-pro' ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $members as $m ) : ?>
                <tr>
                    <td><?php echo esc_html( $m->display_name ); ?></td>
                    <td><?php echo esc_html( $m->affiliate_slug ); ?></td>
                    <td><?php echo esc_html( $m->user_email ); ?></td>
                    <td><?php echo esc_html( $m->phone ); ?></td>
                    <td><?php echo esc_html( $m->negara ); ?></td>
                    <td><?php echo esc_html( strtoupper( $m->member_type ) ); ?></td>
                    <td><?php echo esc_html( ucfirst( $m->status ) ); ?></td>
                    <td><?php echo esc_html( $m->joined_at ); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function page_commissions() {
        global $wpdb;
        $commissions = $wpdb->get_results(
            "SELECT c.*, a.affiliate_slug, u.display_name FROM {$wpdb->prefix}affiliate_commissions c
             LEFT JOIN {$wpdb->prefix}affiliates a ON a.id = c.affiliate_id
             LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
             ORDER BY c.created_at DESC LIMIT 100"
        );
        ?>
        <div class="wrap affiliate-mlm-admin">
            <h1><?php esc_html_e( 'Commissions', 'affiliate-mlm-pro' ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=affiliate_mlm_export_csv&type=commissions&_wpnonce=' . wp_create_nonce( 'affiliate_mlm_admin' ) ) ); ?>" class="button"><?php esc_html_e( 'Export CSV', 'affiliate-mlm-pro' ); ?></a>
            <table class="wp-list-table widefat fixed striped" style="margin-top:1em;">
                <thead><tr>
                    <th>ID</th><th><?php esc_html_e( 'Affiliate', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Jenis', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Jumlah', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Level', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'affiliate-mlm-pro' ); ?></th>
                    <th><?php esc_html_e( 'Tindakan', 'affiliate-mlm-pro' ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $commissions as $c ) : ?>
                <tr>
                    <td><?php echo esc_html( $c->id ); ?></td>
                    <td><?php echo esc_html( $c->display_name . ' (' . $c->affiliate_slug . ')' ); ?></td>
                    <td><?php echo esc_html( $c->type ); ?></td>
                    <td><?php echo esc_html( number_format( $c->amount, 2 ) ); ?></td>
                    <td><?php echo esc_html( $c->level ); ?></td>
                    <td><?php echo esc_html( $c->status ); ?></td>
                    <td>
                        <?php if ( 'pending' === $c->status ) : ?>
                        <button class="button button-small amlm-approve-commission" data-id="<?php echo esc_attr( $c->id ); ?>"><?php esc_html_e( 'Approve', 'affiliate-mlm-pro' ); ?></button>
                        <button class="button button-small amlm-reject-commission" data-id="<?php echo esc_attr( $c->id ); ?>"><?php esc_html_e( 'Reject', 'affiliate-mlm-pro' ); ?></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function ajax_approve_commission() {
        check_ajax_referer( 'affiliate_mlm_admin', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die();
        $id = absint( $_POST['id'] ?? 0 );
        Affiliate_MLM_Commission::approve( $id );
        wp_send_json_success();
    }

    public static function export_csv() {
        check_admin_referer( 'affiliate_mlm_admin' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die();
        global $wpdb;
        $type = sanitize_text_field( $_GET['type'] ?? 'members' );
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename="affiliate-' . $type . '-' . date( 'Y-m-d' ) . '.csv"' );
        $output = fopen( 'php://output', 'w' );
        if ( 'members' === $type ) {
            fputcsv( $output, [ 'ID', 'Nama', 'Slug', 'Email', 'Telefon', 'Negara', 'Jenis', 'Status', 'Tarikh Daftar' ] );
            $rows = $wpdb->get_results( "SELECT a.*, u.display_name, u.user_email FROM {$wpdb->prefix}affiliates a LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id", ARRAY_A );
            foreach ( $rows as $r ) {
                fputcsv( $output, [ $r['id'], $r['display_name'], $r['affiliate_slug'], $r['user_email'], $r['phone'], $r['negara'], $r['member_type'], $r['status'], $r['joined_at'] ] );
            }
        } elseif ( 'commissions' === $type ) {
            fputcsv( $output, [ 'ID', 'Affiliate', 'Jenis', 'Jumlah', 'Level', 'Status', 'Tarikh' ] );
            $rows = $wpdb->get_results( "SELECT c.*, u.display_name FROM {$wpdb->prefix}affiliate_commissions c LEFT JOIN {$wpdb->prefix}affiliates a ON a.id = c.affiliate_id LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id", ARRAY_A );
            foreach ( $rows as $r ) {
                fputcsv( $output, [ $r['id'], $r['display_name'], $r['type'], $r['amount'], $r['level'], $r['status'], $r['created_at'] ] );
            }
        }
        fclose( $output );
        exit;
    }
}
