<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Affiliate_MLM_Shortcodes {

    public function register() {
        $codes = [
            'affiliate_register'       => 'render_register',
            'affiliate_dashboard'      => 'render_dashboard',
            'affiliate_total_hits'     => 'render_total_hits',
            'affiliate_total_member'   => 'render_total_member',
            'affiliate_vip_member'     => 'render_vip_member',
            'affiliate_free_member'    => 'render_free_member',
            'affiliate_active_today'   => 'render_active_today',
            'affiliate_leaderboard'    => 'render_leaderboard',
            'affiliate_link'           => 'render_link',
            'affiliate_qr_code'        => 'render_qr_code',
            'affiliate_ref_table'      => 'render_ref_table',
            'affiliate_withdraw_form'  => 'render_withdraw_form',
            'affiliate_bantuan_penaja' => 'render_bantuan_penaja',
        ];
        foreach ( $codes as $tag => $method ) {
            add_shortcode( $tag, [ $this, $method ] );
        }
    }

    public function render_register( $atts ) {
        if ( is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Anda sudah log masuk.', 'affiliate-mlm-pro' ) . ' <a href="' . esc_url( get_permalink( get_option('affiliate_dashboard_page') ?: '' ) ?: home_url('/dashboard/') ) . '">' . esc_html__( 'Dashboard', 'affiliate-mlm-pro' ) . '</a></p>';
        }
        ob_start();
        include AFFILIATE_MLM_PLUGIN_DIR . 'templates/register-form.php';
        return ob_get_clean();
    }

    public function render_dashboard( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Sila log masuk untuk melihat dashboard.', 'affiliate-mlm-pro' ) . ' <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log Masuk', 'affiliate-mlm-pro' ) . '</a></p>';
        }
        $affiliate = Affiliate_MLM_Core::get_current_affiliate();
        if ( ! $affiliate ) {
            return '<p>' . esc_html__( 'Akaun affiliate tidak dijumpai.', 'affiliate-mlm-pro' ) . '</p>';
        }
        ob_start();
        include AFFILIATE_MLM_PLUGIN_DIR . 'templates/dashboard.php';
        return ob_get_clean();
    }

    public function render_total_hits( $atts ) {
        $a = Affiliate_MLM_Core::get_current_affiliate();
        return $a ? esc_html( Affiliate_MLM_MLM::get_total_hits( $a->id ) ) : '0';
    }

    public function render_total_member( $atts ) {
        $a = Affiliate_MLM_Core::get_current_affiliate();
        return $a ? esc_html( Affiliate_MLM_MLM::get_total_members( $a->id ) ) : '0';
    }

    public function render_vip_member( $atts ) {
        $a = Affiliate_MLM_Core::get_current_affiliate();
        return $a ? esc_html( Affiliate_MLM_MLM::get_vip_members( $a->id ) ) : '0';
    }

    public function render_free_member( $atts ) {
        $a = Affiliate_MLM_Core::get_current_affiliate();
        return $a ? esc_html( Affiliate_MLM_MLM::get_free_members( $a->id ) ) : '0';
    }

    public function render_active_today( $atts ) {
        $a = Affiliate_MLM_Core::get_current_affiliate();
        return $a ? esc_html( Affiliate_MLM_MLM::get_active_today( $a->id ) ) : '0';
    }

    public function render_leaderboard( $atts ) {
        $atts  = shortcode_atts( [ 'limit' => 10 ], $atts );
        $rows  = Affiliate_MLM_MLM::get_leaderboard( absint( $atts['limit'] ) );
        ob_start();
        include AFFILIATE_MLM_PLUGIN_DIR . 'templates/leaderboard.php';
        return ob_get_clean();
    }

    public function render_link( $atts ) {
        $a = Affiliate_MLM_Core::get_current_affiliate();
        if ( ! $a ) return '';
        return esc_url( add_query_arg( 'ref', $a->affiliate_slug, home_url() ) );
    }

    public function render_qr_code( $atts ) {
        $a = Affiliate_MLM_Core::get_current_affiliate();
        if ( ! $a ) return '';
        $link = esc_url( add_query_arg( 'ref', $a->affiliate_slug, home_url() ) );
        return '<img src="https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode( $link ) . '" alt="' . esc_attr__( 'QR Code Affiliate', 'affiliate-mlm-pro' ) . '" style="max-width:200px;" />';
    }

    public function render_ref_table( $atts ) {
        $affiliate = Affiliate_MLM_Core::get_current_affiliate();
        if ( ! $affiliate ) return '';
        ob_start();
        include AFFILIATE_MLM_PLUGIN_DIR . 'templates/ref-table.php';
        return ob_get_clean();
    }

    public function render_withdraw_form( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Sila log masuk.', 'affiliate-mlm-pro' ) . '</p>';
        }
        $affiliate = Affiliate_MLM_Core::get_current_affiliate();
        if ( ! $affiliate ) return '';
        ob_start();
        include AFFILIATE_MLM_PLUGIN_DIR . 'templates/withdrawal-form.php';
        return ob_get_clean();
    }

    /**
     * [affiliate_bantuan_penaja] — Display sponsor/penaja contact info box.
     *
     * Usage:
     *   [affiliate_bantuan_penaja]            — shows sponsor of logged-in user
     *   [affiliate_bantuan_penaja ref="slug"] — shows sponsor based on ref slug
     *
     * If no affiliate / no ref: defaults to admin.
     */
    public function render_bantuan_penaja( $atts ) {
        $atts = shortcode_atts( [ 'ref' => '' ], $atts );

        $sponsor = null;

        // 1. If logged in, use their actual sponsor
        if ( is_user_logged_in() ) {
            $affiliate = Affiliate_MLM_Core::get_current_affiliate();
            if ( $affiliate ) {
                $sponsor = Affiliate_MLM_MLM::get_sponsor_info( $affiliate->id );
            }
        }

        // 2. If ref attribute provided, resolve from that affiliate
        if ( ! $sponsor && ! empty( $atts['ref'] ) ) {
            $ref_aff = Affiliate_MLM_Core::get_affiliate_by_slug( sanitize_text_field( $atts['ref'] ) );
            if ( $ref_aff ) {
                $ref_user = get_userdata( $ref_aff->user_id );
                $sponsor  = [
                    'name'  => $ref_user ? $ref_user->display_name : '',
                    'phone' => $ref_aff->phone ?? '',
                    'email' => $ref_user ? $ref_user->user_email : '',
                ];
            }
        }

        // 3. Try cookie/URL ref
        if ( ! $sponsor ) {
            $ref_slug = '';
            if ( ! empty( $_COOKIE['affiliate_ref'] ) ) {
                $ref_slug = sanitize_text_field( wp_unslash( $_COOKIE['affiliate_ref'] ) );
            } elseif ( ! empty( $_GET['ref'] ) ) {
                $ref_slug = sanitize_text_field( wp_unslash( $_GET['ref'] ) );
            }
            if ( $ref_slug ) {
                $ref_aff = Affiliate_MLM_Core::get_affiliate_by_slug( $ref_slug );
                if ( $ref_aff ) {
                    $ref_user = get_userdata( $ref_aff->user_id );
                    $sponsor  = [
                        'name'  => $ref_user ? $ref_user->display_name : '',
                        'phone' => $ref_aff->phone ?? '',
                        'email' => $ref_user ? $ref_user->user_email : '',
                    ];
                }
            }
        }

        // 4. Default: admin
        if ( ! $sponsor ) {
            $admins   = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
            $adm      = ! empty( $admins ) ? $admins[0] : get_userdata(1);
            $adm_aff  = $adm ? Affiliate_MLM_Core::get_affiliate_by_user( $adm->ID ) : null;
            $sponsor  = [
                'name'  => $adm ? $adm->display_name : 'admin',
                'phone' => $adm_aff ? ( $adm_aff->phone ?? '' ) : '',
                'email' => $adm ? $adm->user_email : '',
            ];
        }

        $wa_num = preg_replace( '/\D/', '', $sponsor['phone'] ?? '' );

        ob_start(); ?>
<div class="amlm-penaja-card amlm-penaja-shortcode">
  <div class="amlm-penaja-title"><?php esc_html_e('Bantuan Penaja','affiliate-mlm-pro'); ?></div>
  <div class="amlm-penaja-divider"></div>
  <ul class="amlm-penaja-list">
    <li>
      <span class="amlm-pi amlm-pi-user">&#128100;</span>
      <span class="amlm-pval"><?php echo esc_html($sponsor['name']); ?></span>
    </li>
    <li>
      <span class="amlm-pi amlm-pi-phone">&#128222;</span>
      <?php if ($wa_num): ?>
      <a class="amlm-pval amlm-plink" href="https://wa.me/<?php echo esc_attr($wa_num); ?>" target="_blank" rel="noopener"><?php echo esc_html($sponsor['phone']); ?></a>
      <?php else: ?><span class="amlm-pval">-</span><?php endif; ?>
    </li>
    <li>
      <span class="amlm-pi amlm-pi-mail">&#9993;</span>
      <?php if ($sponsor['email']): ?>
      <a class="amlm-pval amlm-plink" href="mailto:<?php echo esc_attr($sponsor['email']); ?>"><?php echo esc_html($sponsor['email']); ?></a>
      <?php else: ?><span class="amlm-pval">-</span><?php endif; ?>
    </li>
  </ul>
  <?php if ($wa_num): ?>
  <a class="amlm-wa-btn" href="https://wa.me/<?php echo esc_attr($wa_num); ?>?text=<?php echo urlencode(__('Salam, saya ingin tahu lebih lanjut.','affiliate-mlm-pro')); ?>" target="_blank" rel="noopener">
    <?php esc_html_e('Hubungi via WhatsApp','affiliate-mlm-pro'); ?>
  </a>
  <?php endif; ?>
</div>
        <?php
        return ob_get_clean();
    }
}
