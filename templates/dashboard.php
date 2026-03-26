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
?>
<div class="amlm-wrap">

  <!-- ═══ TOP NAV BAR (style Image 1) ═══ -->
  <nav class="amlm-topnav">
    <a href="<?php echo esc_url(add_query_arg('tab','utama',$base_url)); ?>" class="amlm-nav-item <?php echo $active_tab==='utama'?'active':''; ?>">
      <span class="amlm-nav-icon">◉</span><?php esc_html_e('UTAMA','affiliate-mlm-pro'); ?>
    </a>
    <a href="<?php echo esc_url(add_query_arg('tab','profile',$base_url)); ?>" class="amlm-nav-item <?php echo $active_tab==='profile'?'active':''; ?>">
      <span class="amlm-nav-icon">▤</span><?php esc_html_e('PROFILE','affiliate-mlm-pro'); ?>
    </a>
    <a href="<?php echo esc_url(add_query_arg('tab','tajaan',$base_url)); ?>" class="amlm-nav-item <?php echo $active_tab==='tajaan'?'active':''; ?>">
      <span class="amlm-nav-icon">⚭</span><?php esc_html_e('TAJAAN','affiliate-mlm-pro'); ?>
    </a>
    <a href="<?php echo esc_url(add_query_arg('tab','pelan',$base_url)); ?>" class="amlm-nav-item <?php echo $active_tab==='pelan'?'active':''; ?>">
      <span class="amlm-nav-icon">▽</span><?php esc_html_e('PELAN','affiliate-mlm-pro'); ?>
    </a>
    <a href="<?php echo esc_url(add_query_arg('tab','sistem',$base_url)); ?>" class="amlm-nav-item <?php echo $active_tab==='sistem'?'active':''; ?>">
      <span class="amlm-nav-icon">↻</span><?php esc_html_e('SISTEM','affiliate-mlm-pro'); ?>
    </a>
    <a href="<?php echo esc_url(add_query_arg('tab','berita',$base_url)); ?>" class="amlm-nav-item <?php echo $active_tab==='berita'?'active':''; ?>">
      <span class="amlm-nav-icon">🔔</span><?php esc_html_e('BERITA','affiliate-mlm-pro'); ?>
    </a>
    <a href="<?php echo esc_url(add_query_arg('tab','hubungi',$base_url)); ?>" class="amlm-nav-item <?php echo $active_tab==='hubungi'?'active':''; ?>">
      <span class="amlm-nav-icon">⊡</span><?php esc_html_e('HUBUNGI','affiliate-mlm-pro'); ?>
    </a>
    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="amlm-nav-item amlm-nav-logout">
      <span class="amlm-nav-icon">↩</span><?php esc_html_e('LOGOUT','affiliate-mlm-pro'); ?>
    </a>
  </nav>

  <!-- ═══ TAB: UTAMA ═══ -->
  <?php if ( $active_tab === 'utama' ) : ?>
  <div class="amlm-tab-content">
    <div class="amlm-dashboard-grid">

      <!-- LEFT COLUMN -->
      <div class="amlm-left-col">
        <div class="amlm-section-title"><?php esc_html_e('Statistik Saya','affiliate-mlm-pro'); ?></div>
        <div class="amlm-stat-boxes">
          <div class="amlm-stat-box s-hits">
            <div class="amlm-stat-val"><?php echo esc_html($stats['total_hits']); ?></div>
            <div class="amlm-stat-lbl"><?php esc_html_e('Total Hits','affiliate-mlm-pro'); ?></div>
          </div>
          <div class="amlm-stat-box s-member">
            <div class="amlm-stat-val"><?php echo esc_html($stats['total_member']); ?></div>
            <div class="amlm-stat-lbl"><?php esc_html_e('Total Member','affiliate-mlm-pro'); ?></div>
          </div>
          <div class="amlm-stat-box s-vip">
            <div class="amlm-stat-val"><?php echo esc_html($stats['vip_member']); ?></div>
            <div class="amlm-stat-lbl"><?php esc_html_e('VIP Member','affiliate-mlm-pro'); ?></div>
          </div>
          <div class="amlm-stat-box s-free">
            <div class="amlm-stat-val"><?php echo esc_html($stats['free_member']); ?></div>
            <div class="amlm-stat-lbl"><?php esc_html_e('Free Member','affiliate-mlm-pro'); ?></div>
          </div>
          <div class="amlm-stat-box s-active">
            <div class="amlm-stat-val"><?php echo esc_html($stats['active_today']); ?></div>
            <div class="amlm-stat-lbl"><?php esc_html_e('Active Today','affiliate-mlm-pro'); ?></div>
          </div>
        </div>

        <!-- Copy Link Card -->
        <div class="amlm-link-card">
          <div class="amlm-link-card-title"><?php esc_html_e('Link Affiliate Anda','affiliate-mlm-pro'); ?></div>
          <div class="amlm-link-copy-row">
            <input type="text" id="amlm-link-main" value="<?php echo esc_attr($aff_link); ?>" readonly />
            <button class="amlm-btn-copy" data-target="amlm-link-main">
              ⎘ <span class="copy-txt"><?php esc_html_e('Salin','affiliate-mlm-pro'); ?></span>
            </button>
          </div>
          <div class="amlm-qr-row">
            <img src="https://chart.googleapis.com/chart?chs=100x100&cht=qr&chl=<?php echo urlencode($aff_link); ?>" alt="QR Code" />
            <div>
              <code class="amlm-ref-code">?ref=<?php echo esc_html($affiliate->affiliate_slug); ?></code>
              <p><?php esc_html_e('Kongsi kepada prospek anda.','affiliate-mlm-pro'); ?></p>
            </div>
          </div>
        </div>

        <!-- Balance -->
        <div class="amlm-balance-card">
          <div>
            <div class="amlm-bal-lbl"><?php esc_html_e('Baki Tersedia','affiliate-mlm-pro'); ?></div>
            <div class="amlm-bal-amt">RM <?php echo esc_html(number_format($balance,2)); ?></div>
          </div>
          <a href="<?php echo esc_url(add_query_arg('tab','profile',$base_url)); ?>" class="amlm-btn-wd">
            <?php esc_html_e('Mohon Pengeluaran','affiliate-mlm-pro'); ?>
          </a>
        </div>
      </div>

      <!-- RIGHT COLUMN: Penaja Card (Image 2 style) -->
      <div class="amlm-right-col">
        <div class="amlm-penaja-card">
          <div class="amlm-penaja-title"><?php esc_html_e('Bantuan Penaja','affiliate-mlm-pro'); ?></div>
          <div class="amlm-penaja-divider"></div>
          <ul class="amlm-penaja-list">
            <li>
              <span class="amlm-pi amlm-pi-user">&#128100;</span>
              <span class="amlm-pval"><?php echo esc_html($sponsor['name']); ?></span>
            </li>
            <li>
              <span class="amlm-pi amlm-pi-phone">&#128222;</span>
              <?php $wa_sp = preg_replace('/\D/','',$sponsor['phone']??''); ?>
              <?php if ($wa_sp): ?>
              <a class="amlm-pval amlm-plink" href="https://wa.me/<?php echo esc_attr($wa_sp); ?>" target="_blank" rel="noopener"><?php echo esc_html($sponsor['phone']); ?></a>
              <?php else: ?><span class="amlm-pval">-</span><?php endif; ?>
            </li>
            <li>
              <span class="amlm-pi amlm-pi-mail">&#9993;</span>
              <a class="amlm-pval amlm-plink" href="mailto:<?php echo esc_attr($sponsor['email']); ?>"><?php echo esc_html($sponsor['email']); ?></a>
            </li>
          </ul>
          <?php if ($wa_sp): ?>
          <a class="amlm-wa-btn" href="https://wa.me/<?php echo esc_attr($wa_sp); ?>?text=<?php echo urlencode(__('Salam, saya memerlukan bantuan.','affiliate-mlm-pro')); ?>" target="_blank" rel="noopener">
            <?php esc_html_e('Hubungi via WhatsApp','affiliate-mlm-pro'); ?>
          </a>
          <?php endif; ?>
        </div>

        <!-- Mini Commission Table -->
        <div class="amlm-mini-card">
          <div class="amlm-mini-title"><?php esc_html_e('Komisen Terbaru','affiliate-mlm-pro'); ?></div>
          <table class="amlm-mini-table">
            <thead><tr>
              <th><?php esc_html_e('Jenis','affiliate-mlm-pro'); ?></th>
              <th>Lvl</th>
              <th>RM</th>
              <th><?php esc_html_e('Status','affiliate-mlm-pro'); ?></th>
            </tr></thead>
            <tbody>
            <?php if ($commissions): foreach(array_slice($commissions,0,5) as $c): ?>
            <tr>
              <td><?php echo esc_html(ucfirst($c->type)); ?></td>
              <td><span class="amlm-lbadge"><?php echo esc_html($c->level); ?></span></td>
              <td><?php echo esc_html(number_format($c->amount,2)); ?></td>
              <td><span class="amlm-status-<?php echo esc_attr($c->status); ?>"><?php echo esc_html(ucfirst($c->status)); ?></span></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="4" style="text-align:center;padding:1rem;color:#999"><?php esc_html_e('Tiada rekod.','affiliate-mlm-pro'); ?></td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══ PYRAMID SECTION ═══ -->
    <div class="amlm-pyramid-section">
      <div class="amlm-section-title">
        <?php esc_html_e('Piramid Affiliate','affiliate-mlm-pro'); ?>
        <span class="amlm-pyramid-counts">
          <?php $max_lvl=(int)get_option('affiliate_mlm_max_level',3); for($lv=1;$lv<=$max_lvl;$lv++): $cnt=$level_counts[$lv]??0; ?>
          <span class="amlm-lvl-pill">L<?php echo $lv; ?>: <b><?php echo esc_html($cnt); ?></b></span>
          <?php endfor; ?>
        </span>
      </div>

      <div class="amlm-pyramid-wrap">
        <!-- ME -->
        <div class="amlm-pyr-row pyr-me">
          <div class="amlm-pyr-node node-me">
            <div class="amlm-pyr-av av-me"><?php echo esc_html(mb_substr($user->display_name,0,2)); ?></div>
            <div class="amlm-pyr-nm"><?php echo esc_html(mb_substr($user->display_name,0,14)); ?></div>
            <div class="amlm-pyr-role"><?php esc_html_e('ANDA','affiliate-mlm-pro'); ?></div>
          </div>
        </div>

        <?php if (!empty($pyramid)): ?>
        <div class="amlm-pyr-connector">
          <?php foreach(array_slice($pyramid,0,min(count($pyramid),6)) as $i=>$l1): ?><div class="amlm-pyr-line"></div><?php endforeach; ?>
        </div>
        <!-- LEVEL 1 -->
        <div class="amlm-pyr-row pyr-l1">
          <?php foreach(array_slice($pyramid,0,6) as $l1): ?>
          <div class="amlm-pyr-node node-l1">
            <div class="amlm-pyr-av av-l1"><?php echo esc_html(mb_substr($l1['display_name'],0,2)); ?></div>
            <div class="amlm-pyr-nm"><?php echo esc_html(mb_substr($l1['display_name'],0,11)); ?></div>
            <span class="amlm-type-badge type-<?php echo esc_attr($l1['member_type']); ?>"><?php echo esc_html(strtoupper($l1['member_type'])); ?></span>
            <?php if (!empty($l1['children'])): ?>
            <div class="amlm-sub-cnt"><?php echo count($l1['children']); ?> ↓</div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php if (count($pyramid)>6): ?>
          <div class="amlm-pyr-more">+<?php echo count($pyramid)-6; ?></div>
          <?php endif; ?>
        </div>

        <?php
        $all_l2=[];
        foreach($pyramid as $l1) if(!empty($l1['children'])) foreach(array_slice($l1['children'],0,2) as $l2) $all_l2[]=$l2;
        if (!empty($all_l2)): ?>
        <!-- LEVEL 2 -->
        <div class="amlm-pyr-row pyr-l2">
          <?php foreach(array_slice($all_l2,0,8) as $l2): ?>
          <div class="amlm-pyr-node node-l2">
            <div class="amlm-pyr-av av-l2"><?php echo esc_html(mb_substr($l2['display_name'],0,2)); ?></div>
            <div class="amlm-pyr-nm"><?php echo esc_html(mb_substr($l2['display_name'],0,10)); ?></div>
          </div>
          <?php endforeach; ?>
          <?php if (count($all_l2)>8): ?>
          <div class="amlm-pyr-more">+<?php echo count($all_l2)-8; ?></div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="amlm-pyr-empty">
          <div class="amlm-pyr-empty-icon">△</div>
          <p><?php esc_html_e('Belum ada downline. Kongsi link affiliate anda!','affiliate-mlm-pro'); ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- tab utama -->

  <!-- TAB: PROFILE -->
  <?php elseif ($active_tab==='profile'): ?>
  <div class="amlm-tab-content">
    <div class="amlm-profile-grid">
      <div>
        <div class="amlm-section-title"><?php esc_html_e('Profil Saya','affiliate-mlm-pro'); ?></div>
        <div class="amlm-profile-card">
          <div class="amlm-profile-av"><?php echo esc_html(mb_substr($user->display_name,0,2)); ?></div>
          <div class="amlm-profile-name"><?php echo esc_html($user->display_name); ?></div>
          <code class="amlm-ref-code">?ref=<?php echo esc_html($affiliate->affiliate_slug); ?></code>
          <span class="amlm-badge-type <?php echo esc_attr($affiliate->member_type); ?>"><?php echo strtoupper(esc_html($affiliate->member_type)); ?></span>
        </div>
        <div class="amlm-info-card">
          <?php $info_rows=[['Email',$user->user_email],['WhatsApp',$affiliate->phone??'-'],['Negeri',$affiliate->negeri??'-'],['Negara',$affiliate->negara??'-'],['Daftar',date_i18n(get_option('date_format'),strtotime($affiliate->joined_at))]]; ?>
          <?php foreach($info_rows as [$lbl,$val]): ?>
          <div class="amlm-info-row">
            <span><?php echo esc_html(__($lbl,'affiliate-mlm-pro')); ?></span>
            <strong><?php echo esc_html($val); ?></strong>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div><?php include AFFILIATE_MLM_PLUGIN_DIR.'templates/withdrawal-form.php'; ?></div>
    </div>
  </div>

  <!-- TAB: TAJAAN -->
  <?php elseif ($active_tab==='tajaan'): ?>
  <div class="amlm-tab-content">
    <div class="amlm-section-title"><?php esc_html_e('Senarai Tajaan (Downline)','affiliate-mlm-pro'); ?></div>
    <?php include AFFILIATE_MLM_PLUGIN_DIR.'templates/ref-table.php'; ?>
  </div>

  <!-- TAB: PELAN -->
  <?php elseif ($active_tab==='pelan'): ?>
  <div class="amlm-tab-content">
    <div class="amlm-section-title"><?php esc_html_e('Pelan Komisen','affiliate-mlm-pro'); ?></div>
    <div class="amlm-plan-cards">
      <?php
      $rates  = Affiliate_MLM_Commission::get_level_rates();
      $max    = (int)get_option('affiliate_mlm_max_level',3);
      $labels = [1=>__('Referral Terus','affiliate-mlm-pro'),2=>__('Generasi Kedua','affiliate-mlm-pro'),3=>__('Generasi Ketiga','affiliate-mlm-pro')];
      for ($lv=1;$lv<=$max;$lv++): ?>
      <div class="amlm-plan-card">
        <div class="amlm-plan-lv">Level <?php echo $lv; ?></div>
        <div class="amlm-plan-rate"><?php echo esc_html($rates[$lv]??0); ?>%</div>
        <div class="amlm-plan-lbl"><?php echo esc_html($labels[$lv]??''); ?></div>
        <div class="amlm-plan-cnt"><?php echo esc_html($level_counts[$lv]??0); ?> <?php esc_html_e('ahli','affiliate-mlm-pro'); ?></div>
      </div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- TAB: SISTEM (Leaderboard) -->
  <?php elseif ($active_tab==='sistem'): ?>
  <div class="amlm-tab-content">
    <div class="amlm-section-title"><?php esc_html_e('Leaderboard','affiliate-mlm-pro'); ?></div>
    <?php $rows = Affiliate_MLM_MLM::get_leaderboard(20); include AFFILIATE_MLM_PLUGIN_DIR.'templates/leaderboard.php'; ?>
  </div>

  <!-- TAB: BERITA -->
  <?php elseif ($active_tab==='berita'): ?>
  <div class="amlm-tab-content">
    <div class="amlm-section-title"><?php esc_html_e('Berita & Pengumuman','affiliate-mlm-pro'); ?></div>
    <div class="amlm-berita-list">
      <?php $posts=get_posts(['numberposts'=>10,'post_status'=>'publish']); ?>
      <?php if ($posts): foreach($posts as $post): ?>
      <div class="amlm-berita-item">
        <div class="amlm-berita-meta"><?php echo esc_html(get_the_date('d M Y',$post)); ?></div>
        <div class="amlm-berita-ttl"><?php echo esc_html($post->post_title); ?></div>
        <a href="<?php echo esc_url(get_permalink($post)); ?>" class="amlm-berita-lnk"><?php esc_html_e('Baca →','affiliate-mlm-pro'); ?></a>
      </div>
      <?php endforeach; else: ?>
      <p style="color:#aaa"><?php esc_html_e('Tiada berita.','affiliate-mlm-pro'); ?></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- TAB: HUBUNGI -->
  <?php elseif ($active_tab==='hubungi'): ?>
  <div class="amlm-tab-content">
    <div class="amlm-section-title"><?php esc_html_e('Hubungi Penaja','affiliate-mlm-pro'); ?></div>
    <div style="max-width:420px;">
      <div class="amlm-penaja-card">
        <div class="amlm-penaja-title"><?php esc_html_e('Bantuan Penaja','affiliate-mlm-pro'); ?></div>
        <div class="amlm-penaja-divider"></div>
        <ul class="amlm-penaja-list">
          <li><span class="amlm-pi amlm-pi-user">&#128100;</span><span class="amlm-pval"><?php echo esc_html($sponsor['name']); ?></span></li>
          <li>
            <span class="amlm-pi amlm-pi-phone">&#128222;</span>
            <?php $wa3=preg_replace('/\D/','',$sponsor['phone']??''); ?>
            <?php if($wa3): ?><a class="amlm-pval amlm-plink" href="https://wa.me/<?php echo esc_attr($wa3); ?>" target="_blank"><?php echo esc_html($sponsor['phone']); ?></a>
            <?php else: ?><span class="amlm-pval">-</span><?php endif; ?>
          </li>
          <li><span class="amlm-pi amlm-pi-mail">&#9993;</span><a class="amlm-pval amlm-plink" href="mailto:<?php echo esc_attr($sponsor['email']); ?>"><?php echo esc_html($sponsor['email']); ?></a></li>
        </ul>
        <?php if($wa3): ?>
        <a class="amlm-wa-btn" href="https://wa.me/<?php echo esc_attr($wa3); ?>?text=<?php echo urlencode(__('Salam, saya memerlukan bantuan.','affiliate-mlm-pro')); ?>" target="_blank" rel="noopener">
          <?php esc_html_e('WhatsApp Sekarang','affiliate-mlm-pro'); ?>
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div><!-- .amlm-wrap -->
