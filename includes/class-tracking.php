<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles affiliate link tracking, cookie management, and user registration.
 */
class Affiliate_MLM_Tracking {

    /**
     * Initialize tracking hooks.
     */
    public static function init() {
        self::handle_ref_param();
        self::register_landing_page_rewrite();
        add_filter( 'query_vars', [ __CLASS__, 'add_query_vars' ] );
        add_action( 'template_redirect', [ __CLASS__, 'handle_landing_page' ] );
    }

    /**
     * Read ?ref= parameter and set cookie + record hit.
     */
    public static function handle_ref_param() {
        if ( ! isset( $_GET['ref'] ) ) {
            return;
        }
        $ref = sanitize_text_field( wp_unslash( $_GET['ref'] ) );
        if ( empty( $ref ) ) {
            return;
        }

        $affiliate = null;
        if ( is_numeric( $ref ) ) {
            global $wpdb;
            $affiliate = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}affiliates WHERE user_id = %d AND status = 'active' LIMIT 1",
                (int) $ref
            ) );
        } else {
            $affiliate = Affiliate_MLM_Core::get_affiliate_by_slug( $ref );
        }

        if ( ! $affiliate || $affiliate->status !== 'active' ) {
            return;
        }

        $cookie_days = (int) get_option( 'affiliate_cookie_days', 30 );
        $cookie_name = 'affiliate_ref';

        // First-click attribution: don't overwrite existing valid cookie
        if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
            setcookie( $cookie_name, $affiliate->affiliate_slug, time() + ( $cookie_days * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
            $_COOKIE[ $cookie_name ] = $affiliate->affiliate_slug;
        }

        // Record hit
        self::record_referral( $affiliate->id, 'hit' );
    }

    /**
     * Insert a referral record.
     */
    public static function record_referral( $affiliate_id, $type = 'hit', $referred_user_id = null ) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'affiliate_referrals',
            [
                'affiliate_id'      => $affiliate_id,
                'referred_user_id'  => $referred_user_id,
                'session_id'        => session_id() ?: substr( md5( uniqid() ), 0, 32 ),
                'ip_address'        => self::get_ip(),
                'user_agent'        => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
                'type'              => $type,
                'created_at'        => current_time( 'mysql' ),
            ],
            [ '%d', '%d', '%s', '%s', '%s', '%s', '%s' ]
        );
    }

    /**
     * Hook: after user registers, link to affiliate sponsor.
     */
    public static function on_user_register( $user_id ) {
        $slug = isset( $_COOKIE['affiliate_ref'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['affiliate_ref'] ) ) : '';
        if ( empty( $slug ) ) {
            // Also check POST (for AJAX form)
            $slug = isset( $_POST['affiliate_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['affiliate_ref'] ) ) : '';
        }

        $sponsor    = ! empty( $slug ) ? Affiliate_MLM_Core::get_affiliate_by_slug( $slug ) : null;
        $sponsor_id = $sponsor ? $sponsor->id : null;

        // Get user info
        $user = get_userdata( $user_id );
        if ( ! $user ) return;

        $affiliate_slug = sanitize_user( $user->user_login, true );
        // Ensure unique slug
        global $wpdb;
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}affiliates WHERE affiliate_slug = %s",
            $affiliate_slug
        ) );
        if ( $existing ) {
            $affiliate_slug = $affiliate_slug . '_' . $user_id;
        }

        // Insert affiliate record
        $wpdb->insert(
            $wpdb->prefix . 'affiliates',
            [
                'user_id'        => $user_id,
                'sponsor_id'     => $sponsor_id,
                'affiliate_slug' => $affiliate_slug,
                'status'         => 'active',
                'joined_at'      => current_time( 'mysql' ),
            ],
            [ '%d', '%d', '%s', '%s', '%s' ]
        );

        $affiliate_id = $wpdb->insert_id;

        // Record registration referral
        if ( $sponsor_id ) {
            self::record_referral( $sponsor->id, 'registration', $user_id );
            // Registration commission
            Affiliate_MLM_Commission::process_registration_commission( $affiliate_id, $sponsor_id );
        }

        // Assign affiliate role
        $user_obj = new WP_User( $user_id );
        $user_obj->set_role( 'affiliate' );

        // Clear cookie
        setcookie( 'affiliate_ref', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
    }

    /**
     * AJAX: handle registration form submission.
     */
    public static function ajax_register() {
        // Rate limiting
        $ip      = self::get_ip();
        $rl_key  = 'affiliate_reg_attempt_' . md5( $ip );
        $attempts = (int) get_transient( $rl_key );
        if ( $attempts >= 5 ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Terlalu banyak percubaan. Cuba lagi dalam 15 minit.', 'affiliate-mlm-pro' ) ] );
        }

        // Nonce
        if ( ! check_ajax_referer( 'affiliate_register', '_wpnonce_affiliate_reg', false ) ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Permintaan tidak sah.', 'affiliate-mlm-pro' ) ] );
        }

        // Honeypot
        if ( ! empty( $_POST['website_url'] ) ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Ralat pengesahan.', 'affiliate-mlm-pro' ) ] );
        }

        // Captcha
        $cap_key    = 'aff_cap_' . md5( $ip . date( 'YmdH' ) );
        $cap_answer = (int) get_transient( $cap_key );
        $user_cap   = (int) sanitize_text_field( wp_unslash( $_POST['captcha_answer'] ?? '' ) );
        if ( $cap_answer !== $user_cap ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Jawapan captcha salah.', 'affiliate-mlm-pro' ) ] );
        }

        set_transient( $rl_key, $attempts + 1, 15 * MINUTE_IN_SECONDS );

        // Collect & sanitize
        $username  = sanitize_user( wp_unslash( $_POST['username'] ?? '' ), true );
        $password  = wp_unslash( $_POST['password'] ?? '' );
        $confirm   = wp_unslash( $_POST['confirm_password'] ?? '' );
        $nama      = sanitize_text_field( wp_unslash( $_POST['nama_penuh'] ?? '' ) );
        $email     = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $phone     = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        $negeri    = sanitize_text_field( wp_unslash( $_POST['negeri'] ?? '' ) );
        $negara    = sanitize_text_field( wp_unslash( $_POST['negara'] ?? '' ) );
        $desc      = sanitize_textarea_field( wp_unslash( $_POST['deskripsi'] ?? '' ) );
        $aff_ref   = sanitize_text_field( wp_unslash( $_POST['affiliate_ref'] ?? '' ) );

        // Validation
        $errors = [];
        if ( strlen( $username ) < 3 || strlen( $username ) > 30 ) {
            $errors['username'] = __( 'Username mesti antara 3-30 aksara.', 'affiliate-mlm-pro' );
        }
        if ( username_exists( $username ) ) {
            $errors['username'] = __( 'Username sudah digunakan.', 'affiliate-mlm-pro' );
        }
        if ( strlen( $password ) < 8 ) {
            $errors['password'] = __( 'Kata laluan mesti sekurang-kurangnya 8 aksara.', 'affiliate-mlm-pro' );
        }
        if ( $password !== $confirm ) {
            $errors['confirm_password'] = __( 'Kata laluan tidak sepadan.', 'affiliate-mlm-pro' );
        }
        if ( ! is_email( $email ) ) {
            $errors['email'] = __( 'Format email tidak sah.', 'affiliate-mlm-pro' );
        }
        if ( email_exists( $email ) ) {
            $errors['email'] = __( 'Email sudah didaftarkan.', 'affiliate-mlm-pro' );
        }
        if ( empty( $nama ) ) {
            $errors['nama_penuh'] = __( 'Nama penuh diperlukan.', 'affiliate-mlm-pro' );
        }
        if ( ! preg_match( '/^[0-9+\-\s]{8,20}$/', $phone ) ) {
            $errors['phone'] = __( 'Format nombor telefon tidak sah.', 'affiliate-mlm-pro' );
        }

        if ( ! empty( $errors ) ) {
            wp_send_json_error( [ 'errors' => $errors ] );
        }

        // Create user — this fires on_user_register hook automatically
        $user_id = wp_create_user( $username, $password, $email );
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
        }

        wp_update_user( [
            'ID'           => $user_id,
            'display_name' => $nama,
            'first_name'   => $nama,
        ] );

        // Update phone, negeri, negara, desc in affiliate table (after on_user_register fires)
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'affiliates',
            [
                'phone'       => $phone,
                'negeri'      => $negeri,
                'negara'      => $negara,
                'description' => $desc,
                'wa_message'  => sprintf( __( 'Halo, saya ingin tahu lebih lanjut.', 'affiliate-mlm-pro' ) ),
            ],
            [ 'user_id' => $user_id ],
            [ '%s', '%s', '%s', '%s', '%s' ],
            [ '%d' ]
        );

        // ─── Auto-login user selepas register ─────────────────────────
        // This is critical so that get_current_affiliate() works immediately on redirect
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );

        delete_transient( $cap_key );

        // Send welcome email with HTML template
        self::send_welcome_email( $user_id, $phone, $negeri, $negara );

        // Notify sponsor
        $sponsor_slug = ! empty( $aff_ref ) ? $aff_ref : '';
        if ( empty( $sponsor_slug ) && ! empty( $_COOKIE['affiliate_ref'] ) ) {
            $sponsor_slug = sanitize_text_field( wp_unslash( $_COOKIE['affiliate_ref'] ) );
        }
        if ( $sponsor_slug ) {
            $sponsor = Affiliate_MLM_Core::get_affiliate_by_slug( $sponsor_slug );
            if ( $sponsor ) {
                self::send_sponsor_notification( $sponsor->user_id, $nama );
            }
        }

        // Notify admin
        self::send_admin_notification( $nama, $email, $phone, $negeri, $negara );

        $redirect = get_option( 'affiliate_dashboard_page' )
            ? get_permalink( get_option( 'affiliate_dashboard_page' ) )
            : home_url( '/affiliate-dashboard/' );

        wp_send_json_success( [
            'message'  => esc_html__( 'Pendaftaran berjaya! Mengalihkan ke dashboard...', 'affiliate-mlm-pro' ),
            'redirect' => $redirect,
        ] );
    }

    /**
     * AJAX: update affiliate profile.
     */
    public static function ajax_update_profile() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Anda perlu log masuk.', 'affiliate-mlm-pro' ) ] );
        }
        check_ajax_referer( 'affiliate_update_profile', '_wpnonce_profile' );

        $user_id   = get_current_user_id();
        $affiliate = Affiliate_MLM_Core::get_affiliate_by_user( $user_id );
        if ( ! $affiliate ) {
            wp_send_json_error( [ 'message' => esc_html__( 'Akaun affiliate tidak dijumpai.', 'affiliate-mlm-pro' ) ] );
        }

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'affiliates',
            [
                'phone'       => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
                'negeri'      => sanitize_text_field( wp_unslash( $_POST['negeri'] ?? '' ) ),
                'negara'      => sanitize_text_field( wp_unslash( $_POST['negara'] ?? '' ) ),
                'description' => sanitize_textarea_field( wp_unslash( $_POST['deskripsi'] ?? '' ) ),
                'wa_message'  => sanitize_textarea_field( wp_unslash( $_POST['wa_message'] ?? '' ) ),
            ],
            [ 'id' => $affiliate->id ],
            [ '%s', '%s', '%s', '%s', '%s' ],
            [ '%d' ]
        );

        $nama = sanitize_text_field( wp_unslash( $_POST['nama_penuh'] ?? '' ) );
        if ( $nama ) {
            wp_update_user( [ 'ID' => $user_id, 'display_name' => $nama, 'first_name' => $nama ] );
        }

        wp_send_json_success( [ 'message' => esc_html__( 'Profil dikemaskini.', 'affiliate-mlm-pro' ) ] );
    }

    /**
     * Register rewrite rule for /affiliate/username landing page.
     */
    public static function register_landing_page_rewrite() {
        add_rewrite_rule( '^affiliate/([^/]+)/?$', 'index.php?affiliate_slug=$matches[1]', 'top' );
    }

    public static function add_query_vars( $vars ) {
        $vars[] = 'affiliate_slug';
        return $vars;
    }

    /**
     * Handle landing page template for /affiliate/username.
     */
    public static function handle_landing_page() {
        $slug = get_query_var( 'affiliate_slug' );
        if ( ! $slug ) return;

        $affiliate = Affiliate_MLM_Core::get_affiliate_by_slug( sanitize_text_field( $slug ) );
        if ( ! $affiliate ) {
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            return;
        }

        $template = AFFILIATE_MLM_PLUGIN_DIR . 'templates/landing-page.php';
        if ( file_exists( $template ) ) {
            set_query_var( 'affiliate_data', $affiliate );
            load_template( $template );
            exit;
        }
    }

    /**
     * Get client IP address.
     */
    private static function get_ip() {
        $keys = [ 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ];
        foreach ( $keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) );
                return trim( $ip[0] );
            }
        }
        return '0.0.0.0';
    }

    /**
     * Send HTML welcome email to new affiliate.
     */
    private static function send_welcome_email( $user_id, $phone = '', $negeri = '', $negara = '' ) {
        $user        = get_userdata( $user_id );
        $site_name   = get_bloginfo( 'name' );
        $site_url    = home_url();
        $dashboard   = get_option( 'affiliate_dashboard_page' )
                        ? get_permalink( get_option( 'affiliate_dashboard_page' ) )
                        : home_url( '/affiliate-dashboard/' );

        // Get affiliate slug
        $affiliate   = Affiliate_MLM_Core::get_affiliate_by_user( $user_id );
        $aff_link    = $affiliate ? add_query_arg( 'ref', $affiliate->affiliate_slug, home_url() ) : '';
        $aff_slug    = $affiliate ? $affiliate->affiliate_slug : '';

        $subject = sprintf( __( '🎉 Selamat Datang ke %s – Akaun Affiliate Anda Berjaya Didaftarkan!', 'affiliate-mlm-pro' ), $site_name );

        $message = self::get_welcome_email_html( $user->display_name, $site_name, $site_url, $dashboard, $aff_link, $aff_slug );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>',
        ];

        wp_mail( $user->user_email, $subject, $message, $headers );
    }

    /**
     * Send HTML notification email to sponsor.
     */
    private static function send_sponsor_notification( $sponsor_user_id, $new_member_name ) {
        $sponsor   = get_userdata( $sponsor_user_id );
        if ( ! $sponsor ) return;

        $site_name = get_bloginfo( 'name' );
        $dashboard = get_option( 'affiliate_dashboard_page' )
                        ? get_permalink( get_option( 'affiliate_dashboard_page' ) )
                        : home_url( '/affiliate-dashboard/' );

        $subject = sprintf( __( '🎊 Ahli Baru Bergabung Melalui Link Anda – %s', 'affiliate-mlm-pro' ), $site_name );

        $message = self::get_sponsor_notification_html( $sponsor->display_name, $new_member_name, $site_name, $dashboard );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>',
        ];

        wp_mail( $sponsor->user_email, $subject, $message, $headers );
    }

    /**
     * Send admin notification on new registration.
     */
    private static function send_admin_notification( $nama, $email, $phone, $negeri, $negara ) {
        $site_name  = get_bloginfo( 'name' );
        $admin_email = get_option( 'admin_email' );
        $admin_url  = admin_url( 'admin.php?page=affiliate-mlm-members' );

        $subject = sprintf( __( '🆕 Ahli Affiliate Baru: %s', 'affiliate-mlm-pro' ), $nama );

        $message = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:20px;">
<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);">
  <div style="background:linear-gradient(135deg,#1e2328,#2c3138);padding:30px;text-align:center;">
    <h2 style="color:#e8a020;margin:0;font-size:22px;">🆕 Ahli Affiliate Baru</h2>
    <p style="color:#9aa0a9;margin:5px 0 0;font-size:14px;">' . esc_html( $site_name ) . '</p>
  </div>
  <div style="padding:30px;">
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="padding:10px;border-bottom:1px solid #eee;color:#666;width:120px;font-weight:bold;">Nama</td><td style="padding:10px;border-bottom:1px solid #eee;">' . esc_html( $nama ) . '</td></tr>
      <tr><td style="padding:10px;border-bottom:1px solid #eee;color:#666;font-weight:bold;">Email</td><td style="padding:10px;border-bottom:1px solid #eee;">' . esc_html( $email ) . '</td></tr>
      <tr><td style="padding:10px;border-bottom:1px solid #eee;color:#666;font-weight:bold;">Phone</td><td style="padding:10px;border-bottom:1px solid #eee;">' . esc_html( $phone ) . '</td></tr>
      <tr><td style="padding:10px;border-bottom:1px solid #eee;color:#666;font-weight:bold;">Negeri</td><td style="padding:10px;border-bottom:1px solid #eee;">' . esc_html( $negeri ) . '</td></tr>
      <tr><td style="padding:10px;color:#666;font-weight:bold;">Negara</td><td style="padding:10px;">' . esc_html( $negara ) . '</td></tr>
    </table>
    <div style="text-align:center;margin-top:25px;">
      <a href="' . esc_url( $admin_url ) . '" style="display:inline-block;padding:12px 30px;background:#e8a020;color:#1e2328;border-radius:6px;text-decoration:none;font-weight:bold;">Lihat di Admin Panel</a>
    </div>
  </div>
</div></body></html>';

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . $admin_email . '>',
        ];

        wp_mail( $admin_email, $subject, $message, $headers );
    }

    /**
     * Build welcome email HTML.
     */
    private static function get_welcome_email_html( $name, $site_name, $site_url, $dashboard_url, $aff_link, $aff_slug ) {
        return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="font-family:Arial,sans-serif;background:#f0f4f8;margin:0;padding:20px;">
<div style="max-width:600px;margin:0 auto;">

  <!-- Header -->
  <div style="background:linear-gradient(135deg,#7c3aed,#4f46e5);border-radius:16px 16px 0 0;padding:40px 30px;text-align:center;">
    <div style="font-size:48px;margin-bottom:10px;">🎉</div>
    <h1 style="color:#fff;margin:0;font-size:26px;font-weight:700;">Selamat Datang!</h1>
    <p style="color:rgba(255,255,255,0.85);margin:8px 0 0;font-size:15px;">Akaun Affiliate Anda Berjaya Didaftarkan</p>
  </div>

  <!-- Body -->
  <div style="background:#fff;padding:35px 30px;">
    <p style="font-size:16px;color:#333;margin-top:0;">Salam <strong style="color:#7c3aed;">' . esc_html( $name ) . '</strong>,</p>
    <p style="font-size:15px;color:#555;line-height:1.7;">Terima kasih kerana mendaftar sebagai Ahli Affiliate <strong>' . esc_html( $site_name ) . '</strong>. Akaun anda telah berjaya diwujudkan dan anda sudah boleh mula berkongsi link affiliate anda!</p>

    <!-- Stats Box -->
    <div style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);border:1px solid #ddd6fe;border-radius:12px;padding:20px;margin:25px 0;">
      <p style="margin:0 0 5px;font-size:13px;color:#7c3aed;font-weight:700;text-transform:uppercase;letter-spacing:1px;">📎 Link Affiliate Anda</p>
      <p style="margin:0;font-size:14px;color:#4c1d95;word-break:break-all;font-family:monospace;background:#fff;padding:10px;border-radius:8px;border:1px dashed #c4b5fd;">' . esc_html( $aff_link ) . '</p>
    </div>

    <!-- Steps -->
    <h3 style="color:#333;font-size:16px;margin-bottom:15px;">🚀 Langkah Seterusnya:</h3>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <div style="display:flex;align-items:flex-start;gap:12px;padding:12px;background:#fafafa;border-radius:8px;border-left:3px solid #7c3aed;">
        <span style="font-size:20px;">1️⃣</span>
        <div>
          <strong style="color:#333;font-size:14px;">Log masuk ke Dashboard</strong><br>
          <span style="color:#666;font-size:13px;">Lihat statistik, komisen, dan downline anda</span>
        </div>
      </div>
      <div style="display:flex;align-items:flex-start;gap:12px;padding:12px;background:#fafafa;border-radius:8px;border-left:3px solid #10b981;">
        <span style="font-size:20px;">2️⃣</span>
        <div>
          <strong style="color:#333;font-size:14px;">Kongsikan Link Affiliate Anda</strong><br>
          <span style="color:#666;font-size:13px;">Kongsi kepada rakan, keluarga, dan media sosial</span>
        </div>
      </div>
      <div style="display:flex;align-items:flex-start;gap:12px;padding:12px;background:#fafafa;border-radius:8px;border-left:3px solid #f59e0b;">
        <span style="font-size:20px;">3️⃣</span>
        <div>
          <strong style="color:#333;font-size:14px;">Dapatkan Komisen</strong><br>
          <span style="color:#666;font-size:13px;">Setiap rujukan yang berjaya akan memberikan komisen kepada anda</span>
        </div>
      </div>
    </div>

    <!-- CTA Button -->
    <div style="text-align:center;margin-top:30px;">
      <a href="' . esc_url( $dashboard_url ) . '" style="display:inline-block;padding:14px 40px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;border-radius:50px;text-decoration:none;font-weight:700;font-size:16px;box-shadow:0 4px 15px rgba(124,58,237,0.4);">
        🚀 Pergi ke Dashboard Saya
      </a>
    </div>
  </div>

  <!-- Footer -->
  <div style="background:#1e2328;border-radius:0 0 16px 16px;padding:20px 30px;text-align:center;">
    <p style="color:#9aa0a9;margin:0;font-size:13px;">© ' . date('Y') . ' ' . esc_html( $site_name ) . ' | <a href="' . esc_url( $site_url ) . '" style="color:#7c3aed;text-decoration:none;">Lawati Laman Web</a></p>
  </div>
</div>
</body>
</html>';
    }

    /**
     * Build sponsor notification email HTML.
     */
    private static function get_sponsor_notification_html( $sponsor_name, $new_member_name, $site_name, $dashboard_url ) {
        return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f0f4f8;margin:0;padding:20px;">
<div style="max-width:560px;margin:0 auto;">

  <!-- Header -->
  <div style="background:linear-gradient(135deg,#059669,#10b981);border-radius:16px 16px 0 0;padding:35px 30px;text-align:center;">
    <div style="font-size:44px;margin-bottom:8px;">🎊</div>
    <h1 style="color:#fff;margin:0;font-size:24px;font-weight:700;">Ahli Baru Bergabung!</h1>
  </div>

  <!-- Body -->
  <div style="background:#fff;padding:30px;">
    <p style="font-size:16px;color:#333;margin-top:0;">Salam <strong style="color:#059669;">' . esc_html( $sponsor_name ) . '</strong>,</p>
    <p style="font-size:15px;color:#555;line-height:1.7;">Tahniah! Seorang ahli baru telah mendaftar melalui link affiliate anda:</p>

    <div style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1px solid #a7f3d0;border-radius:12px;padding:20px;text-align:center;margin:20px 0;">
      <p style="margin:0;font-size:14px;color:#065f46;font-weight:600;text-transform:uppercase;letter-spacing:1px;">Ahli Baru</p>
      <p style="margin:8px 0 0;font-size:22px;font-weight:700;color:#047857;">' . esc_html( $new_member_name ) . '</p>
    </div>

    <p style="font-size:14px;color:#666;line-height:1.7;">Komisen anda akan dikreditkan setelah transaksi mereka disahkan oleh admin. Log masuk ke dashboard untuk memantau perkembangan anda.</p>

    <div style="text-align:center;margin-top:25px;">
      <a href="' . esc_url( $dashboard_url ) . '" style="display:inline-block;padding:13px 35px;background:linear-gradient(135deg,#059669,#10b981);color:#fff;border-radius:50px;text-decoration:none;font-weight:700;font-size:15px;">
        📊 Lihat Dashboard Saya
      </a>
    </div>
  </div>

  <!-- Footer -->
  <div style="background:#1e2328;border-radius:0 0 16px 16px;padding:15px 30px;text-align:center;">
    <p style="color:#9aa0a9;margin:0;font-size:12px;">© ' . date('Y') . ' ' . esc_html( $site_name ) . '</p>
  </div>
</div>
</body>
</html>';
    }
}
