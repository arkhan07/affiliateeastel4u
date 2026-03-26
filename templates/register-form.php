<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
// Generate captcha
$ip      = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1' );
$cap_a   = rand(1,9);
$cap_b   = rand(1,9);
$cap_key = 'aff_cap_' . md5($ip . date('YmdH'));
set_transient($cap_key, $cap_a + $cap_b, HOUR_IN_SECONDS);

// ─── Resolve sponsor: cookie → ?ref param → admin default ─────────
$ref_slug     = '';
$sponsor_name = '';
$sponsor_phone= '';
$sponsor_email= '';
$sponsor_wa   = '';

// Priority 1: cookie
if ( ! empty($_COOKIE['affiliate_ref']) ) {
    $ref_slug = sanitize_text_field(wp_unslash($_COOKIE['affiliate_ref']));
}
// Priority 2: ?ref= URL parameter
if ( empty($ref_slug) && ! empty($_GET['ref']) ) {
    $ref_slug = sanitize_text_field(wp_unslash($_GET['ref']));
}

if ( ! empty($ref_slug) ) {
    $sponsor_aff = Affiliate_MLM_Core::get_affiliate_by_slug($ref_slug);
    if ($sponsor_aff && $sponsor_aff->status === 'active') {
        $sponsor_user  = get_userdata($sponsor_aff->user_id);
        $sponsor_name  = $sponsor_user ? $sponsor_user->display_name : '';
        $sponsor_phone = $sponsor_aff->phone ?? '';
        $sponsor_email = $sponsor_user ? $sponsor_user->user_email : '';
        $sponsor_wa    = preg_replace('/\D/', '', $sponsor_phone);
    } else {
        $ref_slug = ''; // invalid, fall to admin default
    }
}

// Default: admin (user ID 1 or first admin)
if ( empty($sponsor_name) ) {
    $admin_users = get_users(['role'=>'administrator','number'=>1]);
    $admin_user  = !empty($admin_users) ? $admin_users[0] : get_userdata(1);
    if ($admin_user) {
        $admin_aff     = Affiliate_MLM_Core::get_affiliate_by_user($admin_user->ID);
        $ref_slug      = $admin_aff ? $admin_aff->affiliate_slug : '';
        $sponsor_name  = $admin_user->display_name;
        $sponsor_email = $admin_user->user_email;
        // Try get phone from affiliate record
        $sponsor_phone = $admin_aff ? ($admin_aff->phone ?? '') : '';
        $sponsor_wa    = preg_replace('/\D/', '', $sponsor_phone);
    }
}

$negeri_opts  = Affiliate_MLM_Core::get_negeri_options();
$country_opts = Affiliate_MLM_Core::get_country_options();
?>
<div class="amlm-register-wrap">
  <div class="amlm-register-grid">

    <!-- LEFT: Form -->
    <div class="amlm-form-card">
      <h2 class="amlm-form-title"><?php esc_html_e('Pendaftaran','affiliate-mlm-pro'); ?></h2>
      <div class="amlm-title-bar"></div>

      <div id="amlm-reg-msg" class="amlm-message" style="display:none;"></div>

      <div class="amlm-form-layout">
        <div class="amlm-form-label-col">
          <?php esc_html_e('Sila lengkapkan borang berikut :','affiliate-mlm-pro'); ?>
        </div>
        <div class="amlm-form-fields">

          <div class="amlm-field">
            <input type="text" name="username" id="f_username"
              placeholder="<?php esc_attr_e('Username*','affiliate-mlm-pro'); ?>" required autocomplete="username" />
            <span class="amlm-ferr" id="err_username"></span>
          </div>

          <div class="amlm-field">
            <input type="password" name="password" id="f_password"
              placeholder="<?php esc_attr_e('Password Login*','affiliate-mlm-pro'); ?>" required autocomplete="new-password" />
            <span class="amlm-ferr" id="err_password"></span>
          </div>

          <div class="amlm-field">
            <input type="password" name="confirm_password" id="f_confirm"
              placeholder="<?php esc_attr_e('Konfirmasi Password*','affiliate-mlm-pro'); ?>" required autocomplete="new-password" />
            <span class="amlm-ferr" id="err_confirm_password"></span>
          </div>

          <div class="amlm-field">
            <input type="text" name="nama_penuh" id="f_nama"
              placeholder="<?php esc_attr_e('Nama Penuh*','affiliate-mlm-pro'); ?>" required />
            <span class="amlm-ferr" id="err_nama_penuh"></span>
          </div>

          <div class="amlm-field">
            <input type="email" name="email" id="f_email"
              placeholder="<?php esc_attr_e('Email*','affiliate-mlm-pro'); ?>" required autocomplete="email" />
            <span class="amlm-ferr" id="err_email"></span>
          </div>

          <div class="amlm-field">
            <input type="tel" name="phone" id="f_phone"
              placeholder="<?php esc_attr_e('Nombor Telefon / WhatsApp*','affiliate-mlm-pro'); ?>" required />
            <span class="amlm-ferr" id="err_phone"></span>
          </div>

          <div class="amlm-field">
            <select name="negara" id="f_negara" required>
              <option value=""><?php esc_html_e('Pilih Negara*','affiliate-mlm-pro'); ?></option>
              <?php foreach($country_opts as $c): ?>
              <option value="<?php echo esc_attr($c); ?>"><?php echo esc_html($c); ?></option>
              <?php endforeach; ?>
            </select>
            <span class="amlm-ferr" id="err_negara"></span>
          </div>

          <div class="amlm-field">
            <select name="negeri" id="f_negeri" required>
              <option value=""><?php esc_html_e('Pilih Negeri / Provinsi*','affiliate-mlm-pro'); ?></option>
              <?php foreach($negeri_opts as $grp => $list): ?>
              <optgroup label="<?php echo esc_attr($grp); ?>">
                <?php foreach($list as $n): ?>
                <option value="<?php echo esc_attr($n); ?>"><?php echo esc_html($n); ?></option>
                <?php endforeach; ?>
              </optgroup>
              <?php endforeach; ?>
            </select>
            <span class="amlm-ferr" id="err_negeri"></span>
          </div>

          <div class="amlm-field">
            <textarea name="deskripsi" id="f_desc" rows="2"
              placeholder="<?php esc_attr_e('Deskripsi / Bio (opsional)','affiliate-mlm-pro'); ?>" maxlength="300"></textarea>
          </div>

          <!-- Captcha -->
          <div class="amlm-field amlm-captcha-row">
            <div class="amlm-captcha-badge"><?php echo esc_html("$cap_a + $cap_b ="); ?></div>
            <input type="number" name="captcha_answer" id="f_cap"
              placeholder="<?php esc_attr_e('Jawab soalan di sini.','affiliate-mlm-pro'); ?>" required />
          </div>

          <!-- Honeypot -->
          <input type="text" name="website_url" style="display:none;position:absolute;left:-9999px;" tabindex="-1" autocomplete="off" />

          <input type="hidden" name="affiliate_ref" id="f_ref" value="<?php echo esc_attr($ref_slug); ?>" />
          <input type="hidden" name="action" value="affiliate_register" />
          <?php wp_nonce_field('affiliate_register','_wpnonce_affiliate_reg'); ?>

          <div class="amlm-submit-row">
            <button type="button" id="amlm-reg-btn" class="amlm-btn-submit">
              <?php esc_html_e('HANTAR','affiliate-mlm-pro'); ?>
            </button>
            <span class="amlm-penaja-inline">
              <?php esc_html_e('Penaja','affiliate-mlm-pro'); ?> :
              <strong id="amlm-sponsor-display"><?php echo esc_html($sponsor_name ?: 'admin'); ?></strong>
            </span>
          </div>

        </div><!-- .amlm-form-fields -->
      </div><!-- .amlm-form-layout -->
    </div><!-- .amlm-form-card -->

    <!-- RIGHT: Bantuan Penaja (image 2 style) -->
    <div class="amlm-penaja-card amlm-reg-penaja" id="amlm-reg-penaja-box">
      <div class="amlm-penaja-title"><?php esc_html_e('Bantuan Penaja','affiliate-mlm-pro'); ?></div>
      <div class="amlm-penaja-divider"></div>
      <ul class="amlm-penaja-list">
        <li>
          <span class="amlm-pi amlm-pi-user">&#128100;</span>
          <span class="amlm-pval" id="sp-name"><?php echo esc_html($sponsor_name ?: 'admin'); ?></span>
        </li>
        <li>
          <span class="amlm-pi amlm-pi-phone">&#128222;</span>
          <span class="amlm-pval" id="sp-phone">
            <?php if ($sponsor_wa): ?>
            <a class="amlm-plink" href="https://wa.me/<?php echo esc_attr($sponsor_wa); ?>" target="_blank" rel="noopener" id="sp-wa-link"><?php echo esc_html($sponsor_phone); ?></a>
            <?php else: ?><span id="sp-wa-link">-</span><?php endif; ?>
          </span>
        </li>
        <li>
          <span class="amlm-pi amlm-pi-mail">&#9993;</span>
          <a class="amlm-pval amlm-plink" href="mailto:<?php echo esc_attr($sponsor_email); ?>" id="sp-email"><?php echo esc_html($sponsor_email ?: '-'); ?></a>
        </li>
      </ul>
      <?php if ($sponsor_wa): ?>
      <a class="amlm-wa-btn" href="https://wa.me/<?php echo esc_attr($sponsor_wa); ?>?text=<?php echo urlencode(__('Salam, saya ingin tahu lebih lanjut.','affiliate-mlm-pro')); ?>" target="_blank" rel="noopener" id="sp-wa-btn">
        <?php esc_html_e('Hubungi via WhatsApp','affiliate-mlm-pro'); ?>
      </a>
      <?php endif; ?>
    </div>

  </div><!-- .amlm-register-grid -->
</div>

<script>
(function(){
  // Submit handler
  var btn = document.getElementById('amlm-reg-btn');
  if (!btn) return;
  btn.addEventListener('click', function(){
    btn.disabled = true;
    btn.textContent = '<?php esc_html_e('Memproses...','affiliate-mlm-pro'); ?>';
    var data = new FormData();
    var fields = ['username','password','confirm_password','nama_penuh','email','phone','negara','negeri','deskripsi','captcha_answer','affiliate_ref','action','_wpnonce_affiliate_reg','website_url'];
    fields.forEach(function(f){
      var el = document.querySelector('[name="'+f+'"]');
      if (el) data.append(f, el.value);
    });
    document.querySelectorAll('.amlm-ferr').forEach(function(e){e.textContent='';});
    var msgEl = document.getElementById('amlm-reg-msg');
    fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {method:'POST',body:data})
    .then(function(r){return r.json();})
    .then(function(res){
      msgEl.style.display = 'block';
      if (res.success) {
        msgEl.className = 'amlm-message amlm-success';
        msgEl.textContent = res.data.message;
        setTimeout(function(){ window.location.href = res.data.redirect; }, 1500);
      } else {
        msgEl.className = 'amlm-message amlm-error';
        if (res.data && res.data.errors) {
          Object.keys(res.data.errors).forEach(function(k){
            var el = document.getElementById('err_'+k);
            if (el) el.textContent = res.data.errors[k];
          });
          msgEl.textContent = '<?php esc_html_e('Sila semak semula maklumat.','affiliate-mlm-pro'); ?>';
        } else {
          msgEl.textContent = (res.data && res.data.message) ? res.data.message : '<?php esc_html_e('Ralat berlaku.','affiliate-mlm-pro'); ?>';
        }
        btn.disabled = false;
        btn.textContent = '<?php esc_html_e('HANTAR','affiliate-mlm-pro'); ?>';
      }
    })
    .catch(function(){
      btn.disabled=false;
      btn.textContent='<?php esc_html_e('HANTAR','affiliate-mlm-pro'); ?>';
    });
  });
})();
</script>
