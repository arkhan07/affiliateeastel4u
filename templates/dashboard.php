<?php if ( ! defined( 'ABSPATH' ) ) exit;
$user        = get_userdata( $affiliate->user_id );
$stats       = Affiliate_MLM_MLM::get_member_stats( $affiliate->id );
$balance     = Affiliate_MLM_Core::get_affiliate_balance( $affiliate->id );
$aff_link    = add_query_arg( 'ref', $affiliate->affiliate_slug, home_url() );
$commissions = Affiliate_MLM_Commission::get_commissions( $affiliate->id, null, 10 );
$withdrawals = Affiliate_MLM_Withdrawal::get_withdrawals( $affiliate->id, 5 );
$sponsor     = $stats['sponsor'];
$pyramid     = Affiliate_MLM_MLM::get_downline_tree( $affiliate->id, 3 );
$level_counts= Affiliate_MLM_MLM::count_downline_by_level( $affiliate->id );
$active_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'utama';
$base_url    = get_permalink();
$avatar_initials = mb_strtoupper( mb_substr( $user->display_name, 0, 2 ) );
?>

<div class="amlm-dashboard-v2" id="amlm-dashboard-root">

  <!-- ═══ HEADER BAR ═══ -->
  <div class="amlm-header-bar">
    <div class="amlm-header-left">
      <div class="amlm-header-avatar"><?php echo esc_html($avatar_initials); ?></div>
      <div class="amlm-header-info">
        <div class="amlm-header-name"><?php echo esc_html($user->display_name); ?></div>
        <div class="amlm-header-badge <?php echo esc_attr($affiliate->member_type); ?>"><?php echo strtoupper(esc_html($affiliate->member_type)); ?> MEMBER</div>
      </div>
    </div>
    <div class="amlm-header-right">
      <div class="amlm-header-balance">
        <span class="amlm-header-balance-lbl"><?php esc_html_e('Baki','affiliate-mlm-pro'); ?></span>
        <span class="amlm-header-balance-amt">RM <?php echo esc_html(number_format($balance,2)); ?></span>
      </div>
      <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="amlm-header-logout" title="Logout">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span><?php esc_html_e('Log Keluar','affiliate-mlm-pro'); ?></span>
      </a>
    </div>
  </div>

  <!-- ═══ TOP NAVIGATION ═══ -->
  <nav class="amlm-nav-v2">
    <?php
    $tabs = [
      'utama'   => ['icon'=>'🏠','label'=>'UTAMA'],
      'profile' => ['icon'=>'👤','label'=>'PROFIL'],
      'tajaan'  => ['icon'=>'👥','label'=>'TAJAAN'],
      'pelan'   => ['icon'=>'📊','label'=>'PELAN'],
      'sistem'  => ['icon'=>'🏆','label'=>'SISTEM'],
      'berita'  => ['icon'=>'📢','label'=>'BERITA'],
      'hubungi' => ['icon'=>'💬','label'=>'HUBUNGI'],
    ];
    foreach ($tabs as $key => $tab):
      $is_active = ($active_tab === $key);
    ?>
    <a href="<?php echo esc_url(add_query_arg('tab',$key,$base_url)); ?>"
       class="amlm-nav-v2-item <?php echo $is_active ? 'active' : ''; ?>">
      <span class="amlm-nav-v2-icon"><?php echo $tab['icon']; ?></span>
      <span class="amlm-nav-v2-txt"><?php echo esc_html($tab['label']); ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- ═══ TAB CONTENT WRAPPER ═══ -->
  <div class="amlm-content-v2">

  <?php if ( $active_tab === 'utama' ) : ?>
  <!-- ════════════ TAB: UTAMA ════════════ -->

    <!-- Stat Cards -->
    <div class="amlm-stats-grid">
      <?php
      $stat_items = [
        ['value'=>$stats['total_hits'],   'label'=>'Total Hits',    'icon'=>'👁️', 'color'=>'blue'],
        ['value'=>$stats['total_member'], 'label'=>'Total Member',  'icon'=>'👥', 'color'=>'purple'],
        ['value'=>$stats['vip_member'],   'label'=>'VIP Member',    'icon'=>'⭐', 'color'=>'amber'],
        ['value'=>$stats['free_member'],  'label'=>'Free Member',   'icon'=>'🆓', 'color'=>'green'],
        ['value'=>$stats['active_today'], 'label'=>'Aktif Hari Ini','icon'=>'🔥', 'color'=>'rose'],
      ];
      foreach ($stat_items as $s): ?>
      <div class="amlm-stat-v2 amlm-stat-<?php echo esc_attr($s['color']); ?>">
        <div class="amlm-stat-v2-icon"><?php echo $s['icon']; ?></div>
        <div class="amlm-stat-v2-val"><?php echo esc_html($s['value']); ?></div>
        <div class="amlm-stat-v2-lbl"><?php echo esc_html($s['label']); ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Main Grid: Left + Right -->
    <div class="amlm-utama-grid">

      <!-- LEFT COLUMN -->
      <div class="amlm-utama-left">

        <!-- Affiliate Link Card -->
        <div class="amlm-card-v2 amlm-link-card-v2">
          <div class="amlm-card-v2-header">
            <span class="amlm-card-v2-icon">🔗</span>
            <h3 class="amlm-card-v2-title"><?php esc_html_e('Link Affiliate Anda','affiliate-mlm-pro'); ?></h3>
          </div>
          <div class="amlm-link-copy-v2">
            <input type="text" id="amlm-link-main" value="<?php echo esc_attr($aff_link); ?>" readonly />
            <button class="amlm-btn-copy-v2" data-target="amlm-link-main">
              <span class="copy-icon">📋</span>
              <span class="copy-txt"><?php esc_html_e('Salin','affiliate-mlm-pro'); ?></span>
            </button>
          </div>
          <div class="amlm-qr-section">
            <div class="amlm-qr-box">
              <img src="https://chart.googleapis.com/chart?chs=120x120&cht=qr&chl=<?php echo urlencode($aff_link); ?>" alt="QR Code" />
            </div>
            <div class="amlm-qr-info">
              <code class="amlm-ref-slug">?ref=<?php echo esc_html($affiliate->affiliate_slug); ?></code>
              <p><?php esc_html_e('Kongsi QR Code atau link ini kepada prospek anda untuk mendapatkan komisen.','affiliate-mlm-pro'); ?></p>
              <div class="amlm-share-btns">
                <a href="https://wa.me/?text=<?php echo urlencode($aff_link); ?>" target="_blank" class="amlm-share-wa">
                  <span>💬</span> WhatsApp
                </a>
                <a href="https://t.me/share/url?url=<?php echo urlencode($aff_link); ?>" target="_blank" class="amlm-share-tg">
                  <span>✈️</span> Telegram
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Balance Card -->
        <div class="amlm-card-v2 amlm-balance-v2">
          <div class="amlm-balance-v2-left">
            <div class="amlm-balance-v2-icon">💰</div>
            <div>
              <div class="amlm-balance-v2-lbl"><?php esc_html_e('Baki Komisen Tersedia','affiliate-mlm-pro'); ?></div>
              <div class="amlm-balance-v2-amt">RM <?php echo esc_html(number_format($balance,2)); ?></div>
            </div>
          </div>
          <a href="<?php echo esc_url(add_query_arg('tab','profile',$base_url)); ?>" class="amlm-btn-withdraw-v2">
            💸 <?php esc_html_e('Mohon Pengeluaran','affiliate-mlm-pro'); ?>
          </a>
        </div>

        <!-- Recent Commissions -->
        <div class="amlm-card-v2">
          <div class="amlm-card-v2-header">
            <span class="amlm-card-v2-icon">💎</span>
            <h3 class="amlm-card-v2-title"><?php esc_html_e('Komisen Terbaru','affiliate-mlm-pro'); ?></h3>
          </div>
          <div class="amlm-table-wrap">
            <table class="amlm-table-v2">
              <thead>
                <tr>
                  <th><?php esc_html_e('Jenis','affiliate-mlm-pro'); ?></th>
                  <th>Lvl</th>
                  <th>RM</th>
                  <th><?php esc_html_e('Status','affiliate-mlm-pro'); ?></th>
                </tr>
              </thead>
              <tbody>
              <?php if ($commissions): foreach(array_slice($commissions,0,6) as $c): ?>
              <tr>
                <td><?php echo esc_html(ucfirst($c->type)); ?></td>
                <td><span class="amlm-lvl-badge">L<?php echo esc_html($c->level); ?></span></td>
                <td><strong><?php echo esc_html(number_format($c->amount,2)); ?></strong></td>
                <td><span class="amlm-status-pill <?php echo esc_attr($c->status); ?>"><?php echo esc_html(ucfirst($c->status)); ?></span></td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="4" class="amlm-empty-row">📭 <?php esc_html_e('Tiada rekod komisen.','affiliate-mlm-pro'); ?></td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- /left col -->

      <!-- RIGHT COLUMN -->
      <div class="amlm-utama-right">

        <!-- Sponsor Card -->
        <div class="amlm-card-v2 amlm-sponsor-card-v2">
          <div class="amlm-card-v2-header">
            <span class="amlm-card-v2-icon">🤝</span>
            <h3 class="amlm-card-v2-title"><?php esc_html_e('Bantuan Penaja','affiliate-mlm-pro'); ?></h3>
          </div>
          <div class="amlm-sponsor-avatar-v2"><?php echo esc_html(mb_strtoupper(mb_substr($sponsor['name'],0,2))); ?></div>
          <div class="amlm-sponsor-name-v2"><?php echo esc_html($sponsor['name']); ?></div>
          <ul class="amlm-sponsor-info-v2">
            <li>
              <span class="amlm-sinfo-icon">📞</span>
              <?php $wa_sp=preg_replace('/\D/','',$sponsor['phone']??''); ?>
              <?php if($wa_sp): ?>
              <a href="https://wa.me/<?php echo esc_attr($wa_sp); ?>" target="_blank"><?php echo esc_html($sponsor['phone']); ?></a>
              <?php else: ?><span class="amlm-muted-v2">-</span><?php endif; ?>
            </li>
            <li>
              <span class="amlm-sinfo-icon">✉️</span>
              <a href="mailto:<?php echo esc_attr($sponsor['email']); ?>"><?php echo esc_html($sponsor['email']); ?></a>
            </li>
          </ul>
          <?php if($wa_sp): ?>
          <a href="https://wa.me/<?php echo esc_attr($wa_sp); ?>?text=<?php echo urlencode(__('Salam, saya memerlukan bantuan.','affiliate-mlm-pro')); ?>" target="_blank" class="amlm-wa-btn-v2">
            💬 <?php esc_html_e('WhatsApp Penaja','affiliate-mlm-pro'); ?>
          </a>
          <?php endif; ?>
        </div>

        <!-- Pyramid Summary -->
        <div class="amlm-card-v2 amlm-pyramid-summary-v2">
          <div class="amlm-card-v2-header">
            <span class="amlm-card-v2-icon">🌐</span>
            <h3 class="amlm-card-v2-title"><?php esc_html_e('Rangkaian Saya','affiliate-mlm-pro'); ?></h3>
          </div>
          <?php $max_lvl=(int)get_option('affiliate_mlm_max_level',3); for($lv=1;$lv<=$max_lvl;$lv++): $cnt=$level_counts[$lv]??0; ?>
          <div class="amlm-pyr-level-row">
            <div class="amlm-pyr-level-badge">L<?php echo $lv; ?></div>
            <div class="amlm-pyr-level-bar-wrap">
              <div class="amlm-pyr-level-bar" style="width:<?php echo min(100,($cnt>0?max(10,$cnt*10):0)); ?>%"></div>
            </div>
            <div class="amlm-pyr-level-count"><?php echo esc_html($cnt); ?> <?php esc_html_e('ahli','affiliate-mlm-pro'); ?></div>
          </div>
          <?php endfor; ?>
          <div style="margin-top:12px;text-align:center;">
            <a href="<?php echo esc_url(add_query_arg('tab','tajaan',$base_url)); ?>" class="amlm-link-v2">
              <?php esc_html_e('Lihat Semua Tajaan →','affiliate-mlm-pro'); ?>
            </a>
          </div>
        </div>

      </div><!-- /right col -->
    </div><!-- /utama grid -->

    <!-- Pyramid Tree -->
    <div class="amlm-card-v2 amlm-pyramid-tree-v2">
      <div class="amlm-card-v2-header">
        <span class="amlm-card-v2-icon">🌳</span>
        <h3 class="amlm-card-v2-title">
          <?php esc_html_e('Piramid Affiliate','affiliate-mlm-pro'); ?>
          <span class="amlm-pyramid-level-pills">
            <?php for($lv=1;$lv<=$max_lvl;$lv++): $cnt=$level_counts[$lv]??0; ?>
            <span class="amlm-pyr-pill">L<?php echo $lv; ?>: <b><?php echo esc_html($cnt); ?></b></span>
            <?php endfor; ?>
          </span>
        </h3>
      </div>
      <div class="amlm-pyr-tree-wrap">
        <!-- ME -->
        <div class="amlm-pyr-row-v2">
          <div class="amlm-pyr-node-v2 amlm-pyr-me">
            <div class="amlm-pyr-av-v2 av-me"><?php echo esc_html($avatar_initials); ?></div>
            <div class="amlm-pyr-nm-v2"><?php echo esc_html(mb_substr($user->display_name,0,14)); ?></div>
            <div class="amlm-pyr-role-v2">✦ ANDA</div>
          </div>
        </div>

        <?php if (!empty($pyramid)): ?>
        <div class="amlm-pyr-connector-v2"><div class="amlm-pyr-vline"></div></div>
        <!-- Level 1 -->
        <div class="amlm-pyr-row-v2 pyr-l1-v2">
          <?php foreach(array_slice($pyramid,0,6) as $l1): ?>
          <div class="amlm-pyr-node-v2 amlm-pyr-l1">
            <div class="amlm-pyr-av-v2 av-l1"><?php echo esc_html(mb_strtoupper(mb_substr($l1['display_name'],0,2))); ?></div>
            <div class="amlm-pyr-nm-v2"><?php echo esc_html(mb_substr($l1['display_name'],0,11)); ?></div>
            <span class="amlm-type-badge-v2 type-<?php echo esc_attr($l1['member_type']); ?>"><?php echo esc_html(strtoupper($l1['member_type'])); ?></span>
            <?php if(!empty($l1['children'])): ?>
            <div class="amlm-sub-cnt-v2">+<?php echo count($l1['children']); ?></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php if(count($pyramid)>6): ?><div class="amlm-pyr-more-v2">+<?php echo count($pyramid)-6; ?></div><?php endif; ?>
        </div>

        <?php
        $all_l2=[];
        foreach($pyramid as $l1) if(!empty($l1['children'])) foreach(array_slice($l1['children'],0,2) as $l2) $all_l2[]=$l2;
        if (!empty($all_l2)): ?>
        <!-- Level 2 -->
        <div class="amlm-pyr-row-v2 pyr-l2-v2">
          <?php foreach(array_slice($all_l2,0,8) as $l2): ?>
          <div class="amlm-pyr-node-v2 amlm-pyr-l2">
            <div class="amlm-pyr-av-v2 av-l2"><?php echo esc_html(mb_strtoupper(mb_substr($l2['display_name'],0,2))); ?></div>
            <div class="amlm-pyr-nm-v2"><?php echo esc_html(mb_substr($l2['display_name'],0,10)); ?></div>
          </div>
          <?php endforeach; ?>
          <?php if(count($all_l2)>8): ?><div class="amlm-pyr-more-v2">+<?php echo count($all_l2)-8; ?></div><?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="amlm-pyr-empty-v2">
          <div class="amlm-pyr-empty-icon-v2">🌱</div>
          <p><?php esc_html_e('Belum ada downline. Mulakan kongsi link affiliate anda!','affiliate-mlm-pro'); ?></p>
          <a href="#" onclick="document.getElementById('amlm-link-main').select();navigator.clipboard.writeText(document.getElementById('amlm-link-main').value);return false;" class="amlm-btn-v2-sm">
            📋 <?php esc_html_e('Salin Link Saya','affiliate-mlm-pro'); ?>
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>

  <?php elseif ($active_tab==='profile'): ?>
  <!-- ════════════ TAB: PROFILE ════════════ -->
  <div class="amlm-profile-grid-v2">

    <div class="amlm-profile-left-v2">
      <!-- Profile Card -->
      <div class="amlm-card-v2 amlm-profile-card-v2">
        <div class="amlm-profile-av-v2"><?php echo esc_html($avatar_initials); ?></div>
        <div class="amlm-profile-name-v2"><?php echo esc_html($user->display_name); ?></div>
        <code class="amlm-ref-slug">?ref=<?php echo esc_html($affiliate->affiliate_slug); ?></code>
        <span class="amlm-type-badge-v2 type-<?php echo esc_attr($affiliate->member_type); ?> amlm-profile-type"><?php echo strtoupper(esc_html($affiliate->member_type)); ?></span>
      </div>

      <!-- Info Card -->
      <div class="amlm-card-v2">
        <div class="amlm-card-v2-header">
          <span class="amlm-card-v2-icon">📋</span>
          <h3 class="amlm-card-v2-title"><?php esc_html_e('Maklumat Akaun','affiliate-mlm-pro'); ?></h3>
        </div>
        <?php
        $info_rows=[
          ['icon'=>'✉️','label'=>'Email',    'val'=>$user->user_email],
          ['icon'=>'📱','label'=>'WhatsApp', 'val'=>$affiliate->phone??'-'],
          ['icon'=>'🗺️','label'=>'Negeri',   'val'=>$affiliate->negeri??'-'],
          ['icon'=>'🌍','label'=>'Negara',   'val'=>$affiliate->negara??'-'],
          ['icon'=>'📅','label'=>'Daftar',   'val'=>date_i18n(get_option('date_format'),strtotime($affiliate->joined_at))],
        ];
        foreach($info_rows as $r): ?>
        <div class="amlm-info-row-v2">
          <span class="amlm-info-icon-v2"><?php echo $r['icon']; ?></span>
          <span class="amlm-info-lbl-v2"><?php echo esc_html(__($r['label'],'affiliate-mlm-pro')); ?></span>
          <span class="amlm-info-val-v2"><?php echo esc_html($r['val']); ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="amlm-profile-right-v2">
      <?php include AFFILIATE_MLM_PLUGIN_DIR.'templates/withdrawal-form.php'; ?>
    </div>
  </div>

  <?php elseif ($active_tab==='tajaan'): ?>
  <!-- ════════════ TAB: TAJAAN ════════════ -->
  <div class="amlm-card-v2">
    <div class="amlm-card-v2-header">
      <span class="amlm-card-v2-icon">👥</span>
      <h3 class="amlm-card-v2-title"><?php esc_html_e('Senarai Tajaan (Downline)','affiliate-mlm-pro'); ?></h3>
    </div>
    <?php include AFFILIATE_MLM_PLUGIN_DIR.'templates/ref-table.php'; ?>
  </div>

  <?php elseif ($active_tab==='pelan'): ?>
  <!-- ════════════ TAB: PELAN ════════════ -->
  <div class="amlm-card-v2-header" style="margin-bottom:1.5rem;">
    <span class="amlm-card-v2-icon">📊</span>
    <h3 class="amlm-card-v2-title"><?php esc_html_e('Pelan Komisen','affiliate-mlm-pro'); ?></h3>
  </div>
  <div class="amlm-plan-grid-v2">
    <?php
    $rates  = Affiliate_MLM_Commission::get_level_rates();
    $max    = (int)get_option('affiliate_mlm_max_level',3);
    $plan_icons  = [1=>'🥇',2=>'🥈',3=>'🥉'];
    $plan_labels = [1=>__('Referral Terus','affiliate-mlm-pro'),2=>__('Generasi Kedua','affiliate-mlm-pro'),3=>__('Generasi Ketiga','affiliate-mlm-pro')];
    for ($lv=1;$lv<=$max;$lv++): ?>
    <div class="amlm-plan-card-v2">
      <div class="amlm-plan-icon-v2"><?php echo $plan_icons[$lv]??'⭐'; ?></div>
      <div class="amlm-plan-lv-v2">Level <?php echo $lv; ?></div>
      <div class="amlm-plan-rate-v2"><?php echo esc_html($rates[$lv]??0); ?>%</div>
      <div class="amlm-plan-lbl-v2"><?php echo esc_html($plan_labels[$lv]??''); ?></div>
      <div class="amlm-plan-cnt-v2">
        <span class="amlm-plan-cnt-num"><?php echo esc_html($level_counts[$lv]??0); ?></span>
        <span><?php esc_html_e('ahli','affiliate-mlm-pro'); ?></span>
      </div>
    </div>
    <?php endfor; ?>
  </div>
  <!-- Commission Info -->
  <div class="amlm-card-v2" style="margin-top:1.5rem;">
    <div class="amlm-card-v2-header">
      <span class="amlm-card-v2-icon">ℹ️</span>
      <h3 class="amlm-card-v2-title"><?php esc_html_e('Maklumat Komisen','affiliate-mlm-pro'); ?></h3>
    </div>
    <div class="amlm-info-box-v2">
      <p>✅ <?php esc_html_e('Komisen dikira berdasarkan peratusan daripada nilai pembelian.','affiliate-mlm-pro'); ?></p>
      <p>✅ <?php esc_html_e('Komisen Level 1 (Referral Terus): Dapatkan komisen terus dari setiap ahli yang anda undang.','affiliate-mlm-pro'); ?></p>
      <p>✅ <?php esc_html_e('Komisen Multi-Level: Dapatkan komisen dari downline Level 2 dan Level 3.','affiliate-mlm-pro'); ?></p>
      <p>✅ <?php esc_html_e('Komisen akan dikreditkan setelah pesanan disahkan oleh admin.','affiliate-mlm-pro'); ?></p>
    </div>
  </div>

  <?php elseif ($active_tab==='sistem'): ?>
  <!-- ════════════ TAB: SISTEM (Leaderboard) ════════════ -->
  <div class="amlm-card-v2">
    <div class="amlm-card-v2-header">
      <span class="amlm-card-v2-icon">🏆</span>
      <h3 class="amlm-card-v2-title"><?php esc_html_e('Leaderboard Top Affiliate','affiliate-mlm-pro'); ?></h3>
    </div>
    <?php $rows = Affiliate_MLM_MLM::get_leaderboard(20); include AFFILIATE_MLM_PLUGIN_DIR.'templates/leaderboard.php'; ?>
  </div>

  <?php elseif ($active_tab==='berita'): ?>
  <!-- ════════════ TAB: BERITA ════════════ -->
  <div class="amlm-card-v2-header" style="margin-bottom:1.5rem;">
    <span class="amlm-card-v2-icon">📢</span>
    <h3 class="amlm-card-v2-title"><?php esc_html_e('Berita & Pengumuman','affiliate-mlm-pro'); ?></h3>
  </div>
  <div class="amlm-berita-grid-v2">
    <?php $posts=get_posts(['numberposts'=>10,'post_status'=>'publish']); ?>
    <?php if ($posts): foreach($posts as $post): ?>
    <div class="amlm-berita-card-v2">
      <div class="amlm-berita-date-v2">📅 <?php echo esc_html(get_the_date('d M Y',$post)); ?></div>
      <div class="amlm-berita-title-v2"><?php echo esc_html($post->post_title); ?></div>
      <a href="<?php echo esc_url(get_permalink($post)); ?>" class="amlm-berita-link-v2">
        <?php esc_html_e('Baca Selanjutnya →','affiliate-mlm-pro'); ?>
      </a>
    </div>
    <?php endforeach; else: ?>
    <div class="amlm-empty-state-v2">
      <div>📭</div>
      <p><?php esc_html_e('Tiada berita pada masa ini.','affiliate-mlm-pro'); ?></p>
    </div>
    <?php endif; ?>
  </div>

  <?php elseif ($active_tab==='hubungi'): ?>
  <!-- ════════════ TAB: HUBUNGI ════════════ -->
  <div class="amlm-hubungi-v2">
    <div class="amlm-card-v2 amlm-sponsor-card-v2">
      <div class="amlm-card-v2-header">
        <span class="amlm-card-v2-icon">🤝</span>
        <h3 class="amlm-card-v2-title"><?php esc_html_e('Hubungi Penaja Anda','affiliate-mlm-pro'); ?></h3>
      </div>
      <div class="amlm-sponsor-avatar-v2"><?php echo esc_html(mb_strtoupper(mb_substr($sponsor['name'],0,2))); ?></div>
      <div class="amlm-sponsor-name-v2"><?php echo esc_html($sponsor['name']); ?></div>
      <ul class="amlm-sponsor-info-v2">
        <li>
          <span class="amlm-sinfo-icon">📞</span>
          <?php $wa3=preg_replace('/\D/','',$sponsor['phone']??''); ?>
          <?php if($wa3): ?><a href="https://wa.me/<?php echo esc_attr($wa3); ?>" target="_blank"><?php echo esc_html($sponsor['phone']); ?></a>
          <?php else: ?><span class="amlm-muted-v2">-</span><?php endif; ?>
        </li>
        <li>
          <span class="amlm-sinfo-icon">✉️</span>
          <a href="mailto:<?php echo esc_attr($sponsor['email']); ?>"><?php echo esc_html($sponsor['email']); ?></a>
        </li>
      </ul>
      <?php if($wa3): ?>
      <a href="https://wa.me/<?php echo esc_attr($wa3); ?>?text=<?php echo urlencode(__('Salam, saya memerlukan bantuan.','affiliate-mlm-pro')); ?>" target="_blank" class="amlm-wa-btn-v2">
        💬 <?php esc_html_e('WhatsApp Sekarang','affiliate-mlm-pro'); ?>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <?php endif; ?>

  </div><!-- /content-v2 -->
</div><!-- /dashboard-v2 -->
