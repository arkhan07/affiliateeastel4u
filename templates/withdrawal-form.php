<?php if ( ! defined( 'ABSPATH' ) ) exit;
$balance     = Affiliate_MLM_Core::get_affiliate_balance( $affiliate->id );
$min_wd      = (float) get_option( 'affiliate_min_withdraw', 50000 );
$withdrawals = Affiliate_MLM_Withdrawal::get_withdrawals( $affiliate->id, 10 );
?>

<div class="amlm-withdrawal-v2">

  <!-- Withdrawal Request Form -->
  <div class="amlm-card-v2 amlm-wd-form-card">
    <div class="amlm-card-v2-header">
      <span class="amlm-card-v2-icon">💸</span>
      <h3 class="amlm-card-v2-title"><?php esc_html_e( 'Permohonan Pengeluaran', 'affiliate-mlm-pro' ); ?></h3>
    </div>

    <!-- Balance Display -->
    <div class="amlm-wd-balance-display">
      <div class="amlm-wd-balance-inner">
        <span class="amlm-wd-balance-lbl">💰 <?php esc_html_e( 'Baki Tersedia', 'affiliate-mlm-pro' ); ?></span>
        <span class="amlm-wd-balance-amt" id="amlm-current-balance">RM <?php echo esc_html( number_format( $balance, 2 ) ); ?></span>
      </div>
      <div class="amlm-wd-min-note">
        <?php printf( esc_html__( 'Minimum pengeluaran: RM %s', 'affiliate-mlm-pro' ), '<strong>' . number_format( $min_wd, 2 ) . '</strong>' ); ?>
      </div>
    </div>

    <div id="amlm-withdraw-message" class="amlm-msg-v2" style="display:none;"></div>

    <div class="amlm-wd-fields">

      <div class="amlm-field-row-v2">
        <div class="amlm-field-v2">
          <label class="amlm-label-v2">💵 <?php esc_html_e( 'Jumlah (RM)', 'affiliate-mlm-pro' ); ?> *</label>
          <input type="number" id="amlm_wd_amount" step="0.01"
            min="<?php echo esc_attr( $min_wd ); ?>"
            max="<?php echo esc_attr( $balance ); ?>"
            placeholder="0.00" />
        </div>
        <div class="amlm-field-v2">
          <label class="amlm-label-v2">🏦 <?php esc_html_e( 'Kaedah Pengeluaran', 'affiliate-mlm-pro' ); ?> *</label>
          <select id="amlm_wd_method">
            <option value="bank">🏦 <?php esc_html_e( 'Bank Transfer', 'affiliate-mlm-pro' ); ?></option>
            <option value="ewallet">📱 <?php esc_html_e( 'E-Wallet', 'affiliate-mlm-pro' ); ?></option>
          </select>
        </div>
      </div>

      <div class="amlm-field-row-v2">
        <div class="amlm-field-v2">
          <label class="amlm-label-v2">👤 <?php esc_html_e( 'Nama Pemilik Akaun', 'affiliate-mlm-pro' ); ?> *</label>
          <input type="text" id="amlm_wd_account_name"
            placeholder="<?php esc_attr_e( 'Nama seperti di buku bank', 'affiliate-mlm-pro' ); ?>" />
        </div>
        <div class="amlm-field-v2">
          <label class="amlm-label-v2">🔢 <?php esc_html_e( 'Nombor Akaun', 'affiliate-mlm-pro' ); ?> *</label>
          <input type="text" id="amlm_wd_account_number"
            placeholder="<?php esc_attr_e( 'Contoh: 1234567890', 'affiliate-mlm-pro' ); ?>" />
        </div>
      </div>

      <div class="amlm-field-v2">
        <label class="amlm-label-v2">🏛️ <?php esc_html_e( 'Nama Bank / E-Wallet', 'affiliate-mlm-pro' ); ?></label>
        <input type="text" id="amlm_wd_bank_name"
          placeholder="<?php esc_attr_e( 'Contoh: Maybank, Touch n Go, GrabPay', 'affiliate-mlm-pro' ); ?>" />
      </div>

      <?php wp_nonce_field( 'affiliate_withdraw', '_wpnonce_withdraw' ); ?>

      <button type="button" id="amlm-withdraw-btn" class="amlm-btn-submit-v2"
        <?php echo ($balance < $min_wd) ? 'disabled title="' . esc_attr__('Baki tidak mencukupi','affiliate-mlm-pro') . '"' : ''; ?>>
        <span class="amlm-btn-txt">💸 <?php esc_html_e( 'Hantar Permohonan', 'affiliate-mlm-pro' ); ?></span>
        <span class="amlm-btn-loading" style="display:none;">⏳ <?php esc_html_e( 'Memproses...', 'affiliate-mlm-pro' ); ?></span>
      </button>

      <?php if ($balance < $min_wd): ?>
      <div class="amlm-wd-insufficient">
        ⚠️ <?php printf( esc_html__( 'Baki anda (RM %s) belum mencapai minimum pengeluaran (RM %s).', 'affiliate-mlm-pro' ),
          number_format($balance,2), number_format($min_wd,2) ); ?>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- Withdrawal History -->
  <div class="amlm-card-v2">
    <div class="amlm-card-v2-header">
      <span class="amlm-card-v2-icon">📜</span>
      <h3 class="amlm-card-v2-title"><?php esc_html_e( 'Sejarah Pengeluaran', 'affiliate-mlm-pro' ); ?></h3>
    </div>
    <div class="amlm-table-wrap">
      <table class="amlm-table-v2">
        <thead>
          <tr>
            <th><?php esc_html_e( 'Tarikh', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Jumlah (RM)', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Kaedah', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Bank', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Status', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Catatan', 'affiliate-mlm-pro' ); ?></th>
          </tr>
        </thead>
        <tbody>
        <?php if ( $withdrawals ) : foreach ( $withdrawals as $w ) : ?>
        <tr>
          <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $w->created_at ) ) ); ?></td>
          <td><strong>RM <?php echo esc_html( number_format( $w->amount, 2 ) ); ?></strong></td>
          <td><?php echo esc_html( strtoupper( $w->method ) ); ?></td>
          <td><?php echo esc_html( $w->bank_name ); ?></td>
          <td><span class="amlm-status-pill <?php echo esc_attr( $w->status ); ?>"><?php echo esc_html( ucfirst( $w->status ) ); ?></span></td>
          <td><?php echo esc_html( $w->note ?: '-' ); ?></td>
        </tr>
        <?php endforeach; else : ?>
        <tr><td colspan="6" class="amlm-empty-row">📭 <?php esc_html_e( 'Tiada rekod pengeluaran.', 'affiliate-mlm-pro' ); ?></td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /withdrawal-v2 -->

<script>
(function(){
  var btn     = document.getElementById('amlm-withdraw-btn');
  if (!btn) return;
  var btnTxt  = btn.querySelector('.amlm-btn-txt');
  var btnLoad = btn.querySelector('.amlm-btn-loading');

  btn.addEventListener('click', function(){
    btn.disabled = true;
    btnTxt.style.display  = 'none';
    btnLoad.style.display = 'inline';

    var data = new FormData();
    data.append('action',         'affiliate_withdraw_request');
    data.append('amount',         document.getElementById('amlm_wd_amount').value);
    data.append('method',         document.getElementById('amlm_wd_method').value);
    data.append('account_name',   document.getElementById('amlm_wd_account_name').value);
    data.append('account_number', document.getElementById('amlm_wd_account_number').value);
    data.append('bank_name',      document.getElementById('amlm_wd_bank_name').value);
    data.append('_wpnonce_withdraw', document.querySelector('[name="_wpnonce_withdraw"]').value);

    fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', { method:'POST', body:data })
    .then(function(r){ return r.json(); })
    .then(function(res){
      var msg = document.getElementById('amlm-withdraw-message');
      msg.style.display = 'block';
      if (res.success) {
        msg.className = 'amlm-msg-v2 amlm-msg-success';
        msg.innerHTML = '✅ ' + res.data.message;
        setTimeout(function(){ window.location.reload(); }, 2000);
      } else {
        msg.className = 'amlm-msg-v2 amlm-msg-error';
        msg.innerHTML = '❌ ' + (res.data && res.data.message ? res.data.message : '<?php esc_html_e( 'Ralat berlaku.', 'affiliate-mlm-pro' ); ?>');
        btn.disabled = false;
        btnTxt.style.display  = 'inline';
        btnLoad.style.display = 'none';
      }
    })
    .catch(function(){
      var msg = document.getElementById('amlm-withdraw-message');
      msg.style.display = 'block';
      msg.className = 'amlm-msg-v2 amlm-msg-error';
      msg.innerHTML = '❌ <?php esc_html_e( 'Ralat rangkaian. Cuba lagi.', 'affiliate-mlm-pro' ); ?>';
      btn.disabled = false;
      btnTxt.style.display  = 'inline';
      btnLoad.style.display = 'none';
    });
  });
})();
</script>
