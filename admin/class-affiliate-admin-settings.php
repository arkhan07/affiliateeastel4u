<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Affiliate_MLM_Admin_Settings {

    public static function page() {
        if ( isset( $_POST['affiliate_mlm_save_settings'] ) ) {
            check_admin_referer( 'affiliate_mlm_settings' );
            $options = [
                'affiliate_cookie_days', 'affiliate_mlm_max_level',
                'affiliate_level1_rate', 'affiliate_level2_rate', 'affiliate_level3_rate',
                'affiliate_reg_commission', 'affiliate_min_withdraw', 'affiliate_commission_type',
            ];
            foreach ( $options as $opt ) {
                if ( isset( $_POST[ $opt ] ) ) {
                    update_option( $opt, sanitize_text_field( wp_unslash( $_POST[ $opt ] ) ) );
                }
            }
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Tetapan disimpan.', 'affiliate-mlm-pro' ) . '</p></div>';
        }
        ?>
        <div class="wrap affiliate-mlm-admin">
            <h1><?php esc_html_e( 'Settings', 'affiliate-mlm-pro' ); ?></h1>
            <form method="post">
                <?php wp_nonce_field( 'affiliate_mlm_settings' ); ?>
                <table class="form-table">
                    <tr><th><?php esc_html_e( 'Cookie Duration (days)', 'affiliate-mlm-pro' ); ?></th>
                        <td><input type="number" name="affiliate_cookie_days" value="<?php echo esc_attr( get_option( 'affiliate_cookie_days', 30 ) ); ?>" min="1" /></td></tr>
                    <tr><th><?php esc_html_e( 'Max MLM Level (1-3)', 'affiliate-mlm-pro' ); ?></th>
                        <td><select name="affiliate_mlm_max_level">
                            <?php foreach ( [1,2,3] as $l ) : ?>
                            <option value="<?php echo $l; ?>" <?php selected( get_option( 'affiliate_mlm_max_level', 3 ), $l ); ?>><?php echo $l; ?></option>
                            <?php endforeach; ?>
                        </select></td></tr>
                    <tr><th><?php esc_html_e( 'Level 1 Commission Rate (%)', 'affiliate-mlm-pro' ); ?></th>
                        <td><input type="number" step="0.01" name="affiliate_level1_rate" value="<?php echo esc_attr( get_option( 'affiliate_level1_rate', 10 ) ); ?>" /></td></tr>
                    <tr><th><?php esc_html_e( 'Level 2 Commission Rate (%)', 'affiliate-mlm-pro' ); ?></th>
                        <td><input type="number" step="0.01" name="affiliate_level2_rate" value="<?php echo esc_attr( get_option( 'affiliate_level2_rate', 5 ) ); ?>" /></td></tr>
                    <tr><th><?php esc_html_e( 'Level 3 Commission Rate (%)', 'affiliate-mlm-pro' ); ?></th>
                        <td><input type="number" step="0.01" name="affiliate_level3_rate" value="<?php echo esc_attr( get_option( 'affiliate_level3_rate', 2 ) ); ?>" /></td></tr>
                    <tr><th><?php esc_html_e( 'Registration Commission (fixed amount)', 'affiliate-mlm-pro' ); ?></th>
                        <td><input type="number" step="0.01" name="affiliate_reg_commission" value="<?php echo esc_attr( get_option( 'affiliate_reg_commission', 0 ) ); ?>" /></td></tr>
                    <tr><th><?php esc_html_e( 'Minimum Withdrawal', 'affiliate-mlm-pro' ); ?></th>
                        <td><input type="number" step="0.01" name="affiliate_min_withdraw" value="<?php echo esc_attr( get_option( 'affiliate_min_withdraw', 50000 ) ); ?>" /></td></tr>
                    <tr><th><?php esc_html_e( 'Commission Type', 'affiliate-mlm-pro' ); ?></th>
                        <td><select name="affiliate_commission_type">
                            <option value="percent" <?php selected( get_option( 'affiliate_commission_type' ), 'percent' ); ?>><?php esc_html_e( 'Percentage (%)', 'affiliate-mlm-pro' ); ?></option>
                            <option value="fixed" <?php selected( get_option( 'affiliate_commission_type' ), 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount', 'affiliate-mlm-pro' ); ?></option>
                        </select></td></tr>
                </table>
                <p><input type="submit" name="affiliate_mlm_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Simpan Tetapan', 'affiliate-mlm-pro' ); ?>" /></p>
            </form>
        </div>
        <?php
    }
}
