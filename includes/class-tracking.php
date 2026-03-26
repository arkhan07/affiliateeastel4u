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

        // Welcome email
        self::send_welcome_email( $user_id );

        // Notify sponsor
        if ( $sponsor_id ) {
            self::send_sponsor_notification( $sponsor->user_id, $user->display_name );
        }
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

        // Create user
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

        delete_transient( $cap_key );

        $redirect = get_option( 'affiliate_dashboard_page' )
            ? get_permalink( get_option( 'affiliate_dashboard_page' ) )
            : home_url( '/affiliate-dashboard/' );

        wp_send_json_success( [
            'message'  => esc_html__( 'Pendaftaran berjaya! Mengalihkan...', 'affiliate-mlm-pro' ),
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

    private static function send_welcome_email( $user_id ) {
        $user    = get_userdata( $user_id );
        $subject = sprintf( __( 'Selamat Datang ke %s - Akaun Affiliate Anda', 'affiliate-mlm-pro' ), get_bloginfo( 'name' ) );
        $message = sprintf(
            __( "Salam %s,\n\nTerima kasih kerana mendaftar sebagai affiliate kami.\n\nLog masuk ke dashboard anda untuk mendapatkan link affiliate anda.\n\nSalam,\n%s", 'affiliate-mlm-pro' ),
            esc_html( $user->display_name ),
            get_bloginfo( 'name' )
        );
        wp_mail( $user->user_email, $subject, $message );
    }

    private static function send_sponsor_notification( $sponsor_user_id, $new_member_name ) {
        $sponsor = get_userdata( $sponsor_user_id );
        if ( ! $sponsor ) return;
        $subject = sprintf( __( 'Member Baru Bergabung - %s', 'affiliate-mlm-pro' ), get_bloginfo( 'name' ) );
        $message = sprintf(
            __( "Salam %s,\n\nAhli baru telah mendaftar melalui link affiliate anda: %s\n\nSalam,\n%s", 'affiliate-mlm-pro' ),
            esc_html( $sponsor->display_name ),
            esc_html( $new_member_name ),
            get_bloginfo( 'name' )
        );
        wp_mail( $sponsor->user_email, $subject, $message );
    }
}
