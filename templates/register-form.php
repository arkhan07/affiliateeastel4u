<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
// Generate captcha
$ip      = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1' );
$cap_a   = rand(1,9);
$cap_b   = rand(1,9);
$cap_key = 'aff_cap_' . md5($ip . date('YmdH'));
set_transient($cap_key, $cap_a + $cap_b, HOUR_IN_SECONDS);

// ─── Resolve sponsor ─────────────────────────────────────────────────
$ref_slug     = '';
$sponsor_name = '';
$sponsor_phone= '';
$sponsor_email= '';
$sponsor_wa   = '';

if ( ! empty($_COOKIE['affiliate_ref']) ) {
    $ref_slug = sanitize_text_field(wp_unslash($_COOKIE['affiliate_ref']));
}
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
        $ref_slug = '';
    }
}

// Default to admin
if ( empty($sponsor_name) ) {
    $admin_users = get_users(['role'=>'administrator','number'=>1]);
    $admin_user  = !empty($admin_users) ? $admin_users[0] : get_userdata(1);
    if ($admin_user) {
        $admin_aff     = Affiliate_MLM_Core::get_affiliate_by_user($admin_user->ID);
        $ref_slug      = $admin_aff ? $admin_aff->affiliate_slug : '';
        $sponsor_name  = $admin_user->display_name;
        $sponsor_email = $admin_user->user_email;
        $sponsor_phone = $admin_aff ? ($admin_aff->phone ?? '') : '';
        $sponsor_wa    = preg_replace('/\D/', '', $sponsor_phone);
    }
}

$negeri_opts  = Affiliate_MLM_Core::get_negeri_options();
$country_opts = Affiliate_MLM_Core::get_country_options();
?>

<div class="amlm-reg-v2" id="amlm-reg-root">

  <!-- Hero Banner -->
  <div class="amlm-reg-hero">
    <div class="amlm-reg-hero-content">
      <div class="amlm-reg-hero-badge">🚀 Program Affiliate</div>
      <h1 class="amlm-reg-hero-title">Daftar &amp; Mula Jana Pendapatan</h1>
      <p class="amlm-reg-hero-subtitle">Kongsi. Undang. Dapatkan Komisen. Mudah &amp; Percuma!</p>
      <div class="amlm-reg-hero-stats">
        <div class="amlm-reg-hero-stat"><span class="amlm-reg-hero-stat-val">3</span><span class="amlm-reg-hero-stat-lbl">Level Komisen</span></div>
        <div class="amlm-reg-hero-divider"></div>
        <div class="amlm-reg-hero-stat"><span class="amlm-reg-hero-stat-val">∞</span><span class="amlm-reg-hero-stat-lbl">Potensi Pendapatan</span></div>
        <div class="amlm-reg-hero-divider"></div>
        <div class="amlm-reg-hero-stat"><span class="amlm-reg-hero-stat-val">FREE</span><span class="amlm-reg-hero-stat-lbl">Pendaftaran</span></div>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="amlm-reg-main">

    <!-- Form Section -->
    <div class="amlm-reg-form-section">
      <div class="amlm-card-v2 amlm-reg-form-card">

        <div class="amlm-reg-form-header">
          <h2>📝 <?php esc_html_e('Borang Pendaftaran','affiliate-mlm-pro'); ?></h2>
          <p><?php esc_html_e('Sila lengkapkan maklumat berikut untuk mendaftar','affiliate-mlm-pro'); ?></p>
        </div>

        <div id="amlm-reg-msg" class="amlm-msg-v2" style="display:none;"></div>

        <div class="amlm-reg-fields">

          <!-- Row: username + password -->
          <div class="amlm-field-row-v2">
            <div class="amlm-field-v2">
              <label class="amlm-label-v2">👤 <?php esc_html_e('Username','affiliate-mlm-pro'); ?> *</label>
              <input type="text" name="username" id="f_username"
                placeholder="<?php esc_attr_e('Contoh: ahmad123','affiliate-mlm-pro'); ?>" required autocomplete="username" />
              <span class="amlm-ferr-v2" id="err_username"></span>
            </div>
            <div class="amlm-field-v2">
              <label class="amlm-label-v2">🔒 <?php esc_html_e('Nama Penuh','affiliate-mlm-pro'); ?> *</label>
              <input type="text" name="nama_penuh" id="f_nama"
                placeholder="<?php esc_attr_e('Nama penuh anda','affiliate-mlm-pro'); ?>" required />
              <span class="amlm-ferr-v2" id="err_nama_penuh"></span>
            </div>
          </div>

          <!-- Row: password + confirm -->
          <div class="amlm-field-row-v2">
            <div class="amlm-field-v2">
              <label class="amlm-label-v2">🔑 <?php esc_html_e('Kata Laluan','affiliate-mlm-pro'); ?> *</label>
              <div class="amlm-password-wrap">
                <input type="password" name="password" id="f_password"
                  placeholder="<?php esc_attr_e('Min. 8 aksara','affiliate-mlm-pro'); ?>" required autocomplete="new-password" />
                <button type="button" class="amlm-toggle-pw" data-target="f_password">👁️</button>
              </div>
              <span class="amlm-ferr-v2" id="err_password"></span>
            </div>
            <div class="amlm-field-v2">
              <label class="amlm-label-v2">🔑 <?php esc_html_e('Sahkan Kata Laluan','affiliate-mlm-pro'); ?> *</label>
              <div class="amlm-password-wrap">
                <input type="password" name="confirm_password" id="f_confirm"
                  placeholder="<?php esc_attr_e('Ulang kata laluan','affiliate-mlm-pro'); ?>" required autocomplete="new-password" />
                <button type="button" class="amlm-toggle-pw" data-target="f_confirm">👁️</button>
              </div>
              <span class="amlm-ferr-v2" id="err_confirm_password"></span>
            </div>
          </div>

          <!-- Row: email + phone -->
          <div class="amlm-field-row-v2">
            <div class="amlm-field-v2">
              <label class="amlm-label-v2">✉️ <?php esc_html_e('Email','affiliate-mlm-pro'); ?> *</label>
              <input type="email" name="email" id="f_email"
                placeholder="<?php esc_attr_e('email@contoh.com','affiliate-mlm-pro'); ?>" required autocomplete="email" />
              <span class="amlm-ferr-v2" id="err_email"></span>
            </div>
            <div class="amlm-field-v2">
              <label class="amlm-label-v2">📱 <?php esc_html_e('Nombor WhatsApp','affiliate-mlm-pro'); ?> *</label>
              <input type="tel" name="phone" id="f_phone"
                placeholder="<?php esc_attr_e('Contoh: +60123456789','affiliate-mlm-pro'); ?>" required />
              <span class="amlm-ferr-v2" id="err_phone"></span>
            </div>
          </div>

          <!-- Row: negara + negeri -->
          <div class="amlm-field-row-v2">
            <div class="amlm-field-v2">
              <label class="amlm-label-v2">🌍 <?php esc_html_e('Negara','affiliate-mlm-pro'); ?> *</label>
              <select name="negara" id="f_negara" required>
                <option value=""><?php esc_html_e('-- Pilih Negara --','affiliate-mlm-pro'); ?></option>
                <?php foreach($country_opts as $c): ?>
                <option value="<?php echo esc_attr($c); ?>"><?php echo esc_html($c); ?></option>
                <?php endforeach; ?>
              </select>
              <span class="amlm-ferr-v2" id="err_negara"></span>
            </div>
            <div class="amlm-field-v2">
              <label class="amlm-label-v2">🗺️ <?php esc_html_e('Negeri / Wilayah','affiliate-mlm-pro'); ?> *</label>
              <select name="negeri" id="f_negeri" required>
                <option value=""><?php esc_html_e('-- Pilih Negeri --','affiliate-mlm-pro'); ?></option>
                <?php foreach($negeri_opts as $grp => $list): ?>
                <optgroup label="<?php echo esc_attr($grp); ?>">
                  <?php foreach($list as $n): ?>
                  <option value="<?php echo esc_attr($n); ?>"><?php echo esc_html($n); ?></option>
                  <?php endforeach; ?>
                </optgroup>
                <?php endforeach; ?>
              </select>
              <span class="amlm-ferr-v2" id="err_negeri"></span>
            </div>
          </div>

          <!-- Deskripsi -->
          <div class="amlm-field-v2">
            <label class="amlm-label-v2">📝 <?php esc_html_e('Deskripsi / Bio','affiliate-mlm-pro'); ?> <span class="amlm-optional">(opsional)</span></label>
            <textarea name="deskripsi" id="f_desc" rows="2"
              placeholder="<?php esc_attr_e('Ceritakan sedikit tentang diri anda...','affiliate-mlm-pro'); ?>" maxlength="300"></textarea>
          </div>

          <!-- Captcha -->
          <div class="amlm-captcha-v2">
            <div class="amlm-captcha-label-v2">🧮 <?php esc_html_e('Soalan Keselamatan','affiliate-mlm-pro'); ?></div>
            <div class="amlm-captcha-row-v2">
              <div class="amlm-captcha-badge-v2"><?php echo esc_html("$cap_a + $cap_b ="); ?></div>
              <input type="number" name="captcha_answer" id="f_cap"
                placeholder="<?php esc_attr_e('Jawapan','affiliate-mlm-pro'); ?>" required />
            </div>
          </div>

          <!-- Honeypot -->
          <input type="text" name="website_url" style="display:none;position:absolute;left:-9999px;" tabindex="-1" autocomplete="off" />

          <input type="hidden" name="affiliate_ref" id="f_ref" value="<?php echo esc_attr($ref_slug); ?>" />
          <input type="hidden" name="action" value="affiliate_register" />
          <?php wp_nonce_field('affiliate_register','_wpnonce_affiliate_reg'); ?>

          <!-- Sponsor Info Row -->
          <div class="amlm-reg-sponsor-row">
            <span class="amlm-reg-sponsor-lbl">🤝 <?php esc_html_e('Penaja Anda:','affiliate-mlm-pro'); ?></span>
            <span class="amlm-reg-sponsor-name"><?php echo esc_html($sponsor_name ?: 'Admin'); ?></span>
          </div>

          <!-- Submit -->
          <button type="button" id="amlm-reg-btn" class="amlm-btn-submit-v2">
            <span class="amlm-btn-txt">🚀 <?php esc_html_e('DAFTAR SEKARANG','affiliate-mlm-pro'); ?></span>
            <span class="amlm-btn-loading" style="display:none;">⏳ <?php esc_html_e('Memproses...','affiliate-mlm-pro'); ?></span>
          </button>

          <p class="amlm-reg-terms">
            <?php esc_html_e('Dengan mendaftar, anda bersetuju dengan syarat-syarat platform ini.','affiliate-mlm-pro'); ?>
          </p>

        </div><!-- /fields -->
      </div><!-- /form card -->
    </div><!-- /form section -->

    <!-- Sidebar -->
    <div class="amlm-reg-sidebar">

      <!-- Sponsor Card -->
      <div class="amlm-card-v2 amlm-sponsor-card-v2" id="amlm-reg-penaja-box">
        <div class="amlm-card-v2-header">
          <span class="amlm-card-v2-icon">🤝</span>
          <h3 class="amlm-card-v2-title"><?php esc_html_e('Penaja Anda','affiliate-mlm-pro'); ?></h3>
        </div>
        <div class="amlm-sponsor-avatar-v2" id="sp-avatar"><?php echo mb_strtoupper(mb_substr($sponsor_name?:'A',0,2)); ?></div>
        <div class="amlm-sponsor-name-v2" id="sp-name"><?php echo esc_html($sponsor_name ?: 'Admin'); ?></div>
        <ul class="amlm-sponsor-info-v2">
          <li>
            <span class="amlm-sinfo-icon">📞</span>
            <span id="sp-phone">
              <?php if ($sponsor_wa): ?>
              <a href="https://wa.me/<?php echo esc_attr($sponsor_wa); ?>" target="_blank" id="sp-wa-link"><?php echo esc_html($sponsor_phone); ?></a>
              <?php else: ?><span id="sp-wa-link">-</span><?php endif; ?>
            </span>
          </li>
          <li>
            <span class="amlm-sinfo-icon">✉️</span>
            <a href="mailto:<?php echo esc_attr($sponsor_email); ?>" id="sp-email"><?php echo esc_html($sponsor_email ?: '-'); ?></a>
          </li>
        </ul>
        <?php if ($sponsor_wa): ?>
        <a class="amlm-wa-btn-v2" href="https://wa.me/<?php echo esc_attr($sponsor_wa); ?>?text=<?php echo urlencode(__('Salam, saya ingin tahu lebih lanjut tentang program affiliate.','affiliate-mlm-pro')); ?>" target="_blank" id="sp-wa-btn">
          💬 <?php esc_html_e('Hubungi via WhatsApp','affiliate-mlm-pro'); ?>
        </a>
        <?php endif; ?>
      </div>

      <!-- Benefits Card -->
      <div class="amlm-card-v2 amlm-benefits-card">
        <div class="amlm-card-v2-header">
          <span class="amlm-card-v2-icon">🎁</span>
          <h3 class="amlm-card-v2-title"><?php esc_html_e('Kenapa Sertai Kami?','affiliate-mlm-pro'); ?></h3>
        </div>
        <ul class="amlm-benefits-list">
          <li>✅ <?php esc_html_e('100% Percuma untuk mendaftar','affiliate-mlm-pro'); ?></li>
          <li>✅ <?php esc_html_e('Komisen pelbagai level (MLM)','affiliate-mlm-pro'); ?></li>
          <li>✅ <?php esc_html_e('Dashboard real-time canggih','affiliate-mlm-pro'); ?></li>
          <li>✅ <?php esc_html_e('Pengeluaran mudah via bank/e-wallet','affiliate-mlm-pro'); ?></li>
          <li>✅ <?php esc_html_e('Sokongan penaja 24/7','affiliate-mlm-pro'); ?></li>
          <li>✅ <?php esc_html_e('QR Code &amp; link affiliate unik','affiliate-mlm-pro'); ?></li>
        </ul>
      </div>

    </div><!-- /sidebar -->

  </div><!-- /main -->
</div><!-- /reg-v2 -->

<script>
(function(){
  // Password toggle
  document.querySelectorAll('.amlm-toggle-pw').forEach(function(btn){
    btn.addEventListener('click', function(){
      var input = document.getElementById(this.dataset.target);
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
      this.textContent = input.type === 'password' ? '👁️' : '🙈';
    });
  });

  // Submit handler
  var btn     = document.getElementById('amlm-reg-btn');
  var btnTxt  = btn.querySelector('.amlm-btn-txt');
  var btnLoad = btn.querySelector('.amlm-btn-loading');
  if (!btn) return;

  btn.addEventListener('click', function(){
    btn.disabled = true;
    btnTxt.style.display  = 'none';
    btnLoad.style.display = 'inline';

    var data = new FormData();
    var fields = ['username','password','confirm_password','nama_penuh','email','phone','negara','negeri','deskripsi','captcha_answer','affiliate_ref','action','_wpnonce_affiliate_reg','website_url'];
    fields.forEach(function(f){
      var el = document.querySelector('[name="'+f+'"]');
      if (el) data.append(f, el.value);
    });

    document.querySelectorAll('.amlm-ferr-v2').forEach(function(e){ e.textContent=''; e.style.display='none'; });
    var msgEl = document.getElementById('amlm-reg-msg');

    fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {method:'POST',body:data})
    .then(function(r){ return r.json(); })
    .then(function(res){
      msgEl.style.display = 'block';
      if (res.success) {
        msgEl.className = 'amlm-msg-v2 amlm-msg-success';
        msgEl.innerHTML = '🎉 ' + res.data.message;
        setTimeout(function(){ window.location.href = res.data.redirect; }, 1800);
      } else {
        msgEl.className = 'amlm-msg-v2 amlm-msg-error';
        if (res.data && res.data.errors) {
          Object.keys(res.data.errors).forEach(function(k){
            var el = document.getElementById('err_'+k);
            if (el){ el.textContent = res.data.errors[k]; el.style.display='block'; }
          });
          msgEl.innerHTML = '⚠️ <?php esc_html_e('Sila semak semula maklumat.','affiliate-mlm-pro'); ?>';
        } else {
          msgEl.innerHTML = '❌ ' + ((res.data && res.data.message) ? res.data.message : '<?php esc_html_e('Ralat berlaku.','affiliate-mlm-pro'); ?>');
        }
        btn.disabled = false;
        btnTxt.style.display  = 'inline';
        btnLoad.style.display = 'none';
      }
    })
    .catch(function(){
      msgEl.style.display = 'block';
      msgEl.className = 'amlm-msg-v2 amlm-msg-error';
      msgEl.innerHTML = '❌ <?php esc_html_e('Ralat rangkaian. Cuba lagi.','affiliate-mlm-pro'); ?>';
      btn.disabled = false;
      btnTxt.style.display  = 'inline';
      btnLoad.style.display = 'none';
    });
  });
})();
</script>
