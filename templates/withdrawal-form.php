<?php if ( ! defined( 'ABSPATH' ) ) exit;
$balance     = Affiliate_MLM_Core::get_affiliate_balance( $affiliate->id );
$min_wd      = (float) get_option( 'affiliate_min_withdraw', 50000 );
$withdrawals = Affiliate_MLM_Withdrawal::get_withdrawals( $affiliate->id, 10 );
?>
<div class="amlm-form-card">
    <h3 class="amlm-panel-title"><?php esc_html_e( 'Permohonan Pengeluaran', 'affiliate-mlm-pro' ); ?></h3>

    <div class="amlm-balance-info">
        <span><?php esc_html_e( 'Baki Tersedia:', 'affiliate-mlm-pro' ); ?></span>
        <strong id="amlm-current-balance"><?php echo esc_html( number_format( $balance, 2 ) ); ?></strong>
    </div>
    <p style="font-size:13px;color:#888;margin-bottom:1rem;">
        <?php printf( esc_html__( 'Minimum pengeluaran: %s', 'affiliate-mlm-pro' ), '<strong>' . number_format( $min_wd, 2 ) . '</strong>' ); ?>
    </p>

    <div id="amlm-withdraw-message" class="amlm-message" style="display:none;"></div>

    <div class="amlm-form-fields">
        <div class="amlm-field">
            <label><?php esc_html_e( 'Jumlah (RM)', 'affiliate-mlm-pro' ); ?> *</label>
            <input type="number" id="amlm_wd_amount" step="0.01" min="<?php echo esc_attr( $min_wd ); ?>" max="<?php echo esc_attr( $balance ); ?>" placeholder="0.00" />
        </div>
        <div class="amlm-field">
            <label><?php esc_html_e( 'Kaedah Pengeluaran', 'affiliate-mlm-pro' ); ?> *</label>
            <select id="amlm_wd_method">
                <option value="bank"><?php esc_html_e( 'Bank Transfer', 'affiliate-mlm-pro' ); ?></option>
                <option value="ewallet"><?php esc_html_e( 'E-Wallet', 'affiliate-mlm-pro' ); ?></option>
            </select>
        </div>
        <div class="amlm-field">
            <label><?php esc_html_e( 'Nama Pemilik Akaun', 'affiliate-mlm-pro' ); ?> *</label>
            <input type="text" id="amlm_wd_account_name" placeholder="<?php esc_attr_e( 'Nama seperti di buku bank', 'affiliate-mlm-pro' ); ?>" />
        </div>
        <div class="amlm-field">
            <label><?php esc_html_e( 'Nombor Akaun', 'affiliate-mlm-pro' ); ?> *</label>
            <input type="text" id="amlm_wd_account_number" placeholder="<?php esc_attr_e( 'Contoh: 1234567890', 'affiliate-mlm-pro' ); ?>" />
        </div>
        <div class="amlm-field">
            <label><?php esc_html_e( 'Nama Bank / E-Wallet', 'affiliate-mlm-pro' ); ?></label>
            <input type="text" id="amlm_wd_bank_name" placeholder="<?php esc_attr_e( 'Contoh: Maybank, Touch n Go', 'affiliate-mlm-pro' ); ?>" />
        </div>

        <?php wp_nonce_field( 'affiliate_withdraw', '_wpnonce_withdraw' ); ?>

        <button type="button" id="amlm-withdraw-btn" class="amlm-btn-submit">
            <?php esc_html_e( 'Hantar Permohonan', 'affiliate-mlm-pro' ); ?>
        </button>
    </div>

    <!-- History table -->
    <h4 style="margin-top:2rem;"><?php esc_html_e( 'Sejarah Pengeluaran', 'affiliate-mlm-pro' ); ?></h4>
    <table class="amlm-table">
        <thead><tr>
            <th><?php esc_html_e( 'Tarikh', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Jumlah', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Kaedah', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Bank', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Status', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Catatan', 'affiliate-mlm-pro' ); ?></th>
        </tr></thead>
        <tbody>
        <?php if ( $withdrawals ) : foreach ( $withdrawals as $w ) : ?>
        <tr>
            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $w->created_at ) ) ); ?></td>
            <td><?php echo esc_html( number_format( $w->amount, 2 ) ); ?></td>
            <td><?php echo esc_html( strtoupper( $w->method ) ); ?></td>
            <td><?php echo esc_html( $w->bank_name ); ?></td>
            <td><span class="amlm-status-<?php echo esc_attr( $w->status ); ?>"><?php echo esc_html( ucfirst( $w->status ) ); ?></span></td>
            <td><?php echo esc_html( $w->note ); ?></td>
        </tr>
        <?php endforeach; else : ?>
        <tr><td colspan="6"><?php esc_html_e( 'Tiada rekod.', 'affiliate-mlm-pro' ); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById('amlm-withdraw-btn').addEventListener('click', function(){
    var btn  = this;
    btn.disabled = true;
    btn.textContent = '<?php esc_html_e( 'Memproses...', 'affiliate-mlm-pro' ); ?>';

    var data = new FormData();
    data.append('action', 'affiliate_withdraw_request');
    data.append('amount', document.getElementById('amlm_wd_amount').value);
    data.append('method', document.getElementById('amlm_wd_method').value);
    data.append('account_name', document.getElementById('amlm_wd_account_name').value);
    data.append('account_number', document.getElementById('amlm_wd_account_number').value);
    data.append('bank_name', document.getElementById('amlm_wd_bank_name').value);
    data.append('_wpnonce_withdraw', document.querySelector('[name="_wpnonce_withdraw"]').value);

    fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', { method:'POST', body:data })
    .then(function(r){ return r.json(); })
    .then(function(res){
        var msg = document.getElementById('amlm-withdraw-message');
        msg.style.display = 'block';
        if (res.success) {
            msg.className = 'amlm-message amlm-success';
            msg.textContent = res.data.message;
            setTimeout(function(){ window.location.reload(); }, 2000);
        } else {
            msg.className = 'amlm-message amlm-error';
            msg.textContent = res.data && res.data.message ? res.data.message : '<?php esc_html_e( 'Ralat berlaku.', 'affiliate-mlm-pro' ); ?>';
            btn.disabled = false;
            btn.textContent = '<?php esc_html_e( 'Hantar Permohonan', 'affiliate-mlm-pro' ); ?>';
        }
    });
});
</script>
