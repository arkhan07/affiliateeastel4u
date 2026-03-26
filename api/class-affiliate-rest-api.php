<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * REST API endpoints for Affiliate MLM Pro.
 */
class Affiliate_MLM_REST_API {

    const NAMESPACE = 'affiliate/v1';

    public static function register_routes() {
        register_rest_route( self::NAMESPACE, '/member-stats', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ __CLASS__, 'get_member_stats' ],
            'permission_callback' => [ __CLASS__, 'is_affiliate' ],
        ] );

        register_rest_route( self::NAMESPACE, '/commissions', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ __CLASS__, 'get_commissions' ],
            'permission_callback' => [ __CLASS__, 'is_affiliate' ],
        ] );

        register_rest_route( self::NAMESPACE, '/leaderboard', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ __CLASS__, 'get_leaderboard' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( self::NAMESPACE, '/downline', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ __CLASS__, 'get_downline' ],
            'permission_callback' => [ __CLASS__, 'is_affiliate' ],
        ] );

        register_rest_route( self::NAMESPACE, '/withdraw/request', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ __CLASS__, 'post_withdraw_request' ],
            'permission_callback' => [ __CLASS__, 'is_affiliate' ],
        ] );
    }

    public static function is_affiliate() {
        return is_user_logged_in();
    }

    public static function get_member_stats( WP_REST_Request $request ) {
        $affiliate = Affiliate_MLM_Core::get_current_affiliate();
        if ( ! $affiliate ) {
            return new WP_Error( 'no_affiliate', __( 'Akaun affiliate tidak dijumpai.', 'affiliate-mlm-pro' ), [ 'status' => 404 ] );
        }

        $stats = Affiliate_MLM_MLM::get_member_stats( $affiliate->id );
        return rest_ensure_response( $stats );
    }

    public static function get_commissions( WP_REST_Request $request ) {
        $affiliate = Affiliate_MLM_Core::get_current_affiliate();
        if ( ! $affiliate ) {
            return new WP_Error( 'no_affiliate', '', [ 'status' => 404 ] );
        }

        $status = sanitize_text_field( $request->get_param( 'status' ) );
        $limit  = absint( $request->get_param( 'limit' ) ?: 20 );
        $offset = absint( $request->get_param( 'offset' ) ?: 0 );

        $commissions = Affiliate_MLM_Commission::get_commissions( $affiliate->id, $status ?: null, $limit, $offset );
        return rest_ensure_response( $commissions );
    }

    public static function get_leaderboard( WP_REST_Request $request ) {
        $limit = absint( $request->get_param( 'limit' ) ?: 10 );
        return rest_ensure_response( Affiliate_MLM_MLM::get_leaderboard( $limit ) );
    }

    public static function get_downline( WP_REST_Request $request ) {
        $affiliate = Affiliate_MLM_Core::get_current_affiliate();
        if ( ! $affiliate ) {
            return new WP_Error( 'no_affiliate', '', [ 'status' => 404 ] );
        }

        $depth = absint( $request->get_param( 'depth' ) ?: 3 );
        $tree  = Affiliate_MLM_MLM::get_downline_tree( $affiliate->id, $depth );
        return rest_ensure_response( $tree );
    }

    public static function post_withdraw_request( WP_REST_Request $request ) {
        $affiliate = Affiliate_MLM_Core::get_current_affiliate();
        if ( ! $affiliate ) {
            return new WP_Error( 'no_affiliate', '', [ 'status' => 404 ] );
        }

        // Simulate POST data for reuse
        $_POST['amount']         = $request->get_param( 'amount' );
        $_POST['method']         = $request->get_param( 'method' );
        $_POST['account_name']   = $request->get_param( 'account_name' );
        $_POST['account_number'] = $request->get_param( 'account_number' );
        $_POST['bank_name']      = $request->get_param( 'bank_name' );
        $_POST['_wpnonce_withdraw'] = wp_create_nonce( 'affiliate_withdraw' );

        ob_start();
        Affiliate_MLM_Withdrawal::ajax_request();
        ob_end_clean();

        return rest_ensure_response( [ 'success' => true ] );
    }
}
