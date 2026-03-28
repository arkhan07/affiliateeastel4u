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
            $dashboard_url = get_option('affiliate_dashboard_page')
                ? get_permalink( get_option('affiliate_dashboard_page') )
                : home_url('/affiliate-dashboard/');
            return '<div class="amlm-already-logged-in">
                <p>✅ ' . esc_html__( 'Anda sudah log masuk.', 'affiliate-mlm-pro' ) . '
                <a href="' . esc_url( $dashboard_url ) . '">' . esc_html__( 'Pergi ke Dashboard →', 'affiliate-mlm-pro' ) . '</a></p>
            </div>';
        }
        ob_start();
        include AFFILIATE_MLM_PLUGIN_DIR . 'templates/register-form.php';
        return ob_get_clean();
    }

    public function render_dashboard( $atts ) {
        if ( ! is_user_logged_in() ) {
            // Redirect to login
            $login_url = wp_login_url( get_permalink() );
            ob_start();
            ?>
            <div class="amlm-access-denied">
                <div class="amlm-access-icon">🔒</div>
                <h2><?php esc_html_e( 'Akses Dikehendaki Log Masuk', 'affiliate-mlm-pro' ); ?></h2>
                <p><?php esc_html_e( 'Sila log masuk untuk melihat dashboard affiliate anda.', 'affiliate-mlm-pro' ); ?></p>
                <a href="<?php echo esc_url( $login_url ); ?>" class="amlm-btn-login">
                    <?php esc_html_e( '🔑 Log Masuk Sekarang', 'affiliate-mlm-pro' ); ?>
                </a>
            </div>
            <script>
            // Auto redirect after 1.5 seconds
            setTimeout(function(){
                window.location.href = '<?php echo esc_js( $login_url ); ?>';
            }, 2000);
            </script>
            <?php
            return ob_get_clean();
        }

        $affiliate = Affiliate_MLM_Core::get_current_affiliate();

        // ─── Fallback: Jika record tidak wujud, cuba create ────────────
        if ( ! $affiliate ) {
            $user_id = get_current_user_id();
            $user    = get_userdata( $user_id );
            if ( $user ) {
                global $wpdb;
                $affiliate_slug = sanitize_user( $user->user_login, true );
                $existing = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}affiliates WHERE affiliate_slug = %s",
                    $affiliate_slug
                ) );
                if ( $existing ) {
                    $affiliate_slug = $affiliate_slug . '_' . $user_id;
                }
                $wpdb->insert(
                    $wpdb->prefix . 'affiliates',
                    [
                        'user_id'        => $user_id,
                        'sponsor_id'     => null,
                        'affiliate_slug' => $affiliate_slug,
                        'status'         => 'active',
                        'joined_at'      => current_time( 'mysql' ),
                    ],
                    [ '%d', '%d', '%s', '%s', '%s' ]
                );
                // Assign role
                $user_obj = new WP_User( $user_id );
                if ( ! in_array( 'affiliate', (array) $user_obj->roles ) ) {
                    $user_obj->add_role( 'affiliate' );
                }
                // Re-fetch
                $affiliate = Affiliate_MLM_Core::get_current_affiliate();
            }
        }

        if ( ! $affiliate ) {
            return '<div class="amlm-access-denied">
                <div class="amlm-access-icon">⚠️</div>
                <h2>' . esc_html__( 'Akaun Affiliate Tidak Dijumpai', 'affiliate-mlm-pro' ) . '</h2>
                <p>' . esc_html__( 'Sila hubungi admin untuk bantuan.', 'affiliate-mlm-pro' ) . '</p>
            </div>';
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

    public function render_bantuan_penaja( $atts ) {
        $atts = shortcode_atts( [ 'ref' => '' ], $atts );

        $sponsor = null;

        if ( is_user_logged_in() ) {
            $affiliate = Affiliate_MLM_Core::get_current_affiliate();
            if ( $affiliate ) {
                $sponsor = Affiliate_MLM_MLM::get_sponsor_info( $affiliate->id );
            }
        }

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
