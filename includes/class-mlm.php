<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MLM multi-level affiliate logic.
 */
class Affiliate_MLM_MLM {

    /**
     * Get upline chain for an affiliate (returns array of affiliate IDs by level).
     * Level 1 = direct sponsor, Level 2 = sponsor's sponsor, etc.
     */
    public static function get_upline_chain( $affiliate_id, $max_level = null ) {
        if ( null === $max_level ) {
            $max_level = (int) get_option( 'affiliate_mlm_max_level', 3 );
        }

        $chain   = [];
        $current = $affiliate_id;

        for ( $i = 1; $i <= $max_level; $i++ ) {
            global $wpdb;
            $sponsor_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT sponsor_id FROM {$wpdb->prefix}affiliates WHERE id = %d LIMIT 1",
                $current
            ) );

            if ( ! $sponsor_id ) {
                break;
            }

            $chain[ $i ] = (int) $sponsor_id;
            $current     = (int) $sponsor_id;
        }

        return $chain;
    }

    /**
     * Get downline tree for an affiliate (nested array).
     */
    public static function get_downline_tree( $affiliate_id, $depth = 3, $current_depth = 1 ) {
        if ( $current_depth > $depth ) {
            return [];
        }

        global $wpdb;
        $children = $wpdb->get_results( $wpdb->prepare(
            "SELECT a.*, u.display_name, u.user_email
             FROM {$wpdb->prefix}affiliates a
             LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
             WHERE a.sponsor_id = %d AND a.status = 'active'",
            $affiliate_id
        ) );

        $tree = [];
        foreach ( $children as $child ) {
            $node           = (array) $child;
            $node['level']    = $current_depth;
            $node['children'] = self::get_downline_tree( $child->id, $depth, $current_depth + 1 );
            $tree[]           = $node;
        }

        return $tree;
    }

    /**
     * Count downline members per level.
     */
    public static function count_downline_by_level( $affiliate_id ) {
        $max_level = (int) get_option( 'affiliate_mlm_max_level', 3 );
        $counts    = [];

        for ( $level = 1; $level <= $max_level; $level++ ) {
            $counts[ $level ] = self::count_level( $affiliate_id, $level );
        }

        return $counts;
    }

    /**
     * Recursively count members at a specific level down.
     */
    private static function count_level( $affiliate_id, $target_level, $current_level = 1 ) {
        global $wpdb;
        $children = $wpdb->get_col( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}affiliates WHERE sponsor_id = %d AND status = 'active'",
            $affiliate_id
        ) );

        if ( empty( $children ) ) {
            return 0;
        }

        if ( $current_level === $target_level ) {
            return count( $children );
        }

        $total = 0;
        foreach ( $children as $child_id ) {
            $total += self::count_level( (int) $child_id, $target_level, $current_level + 1 );
        }

        return $total;
    }

    /**
     * Get total downline count (all levels).
     */
    public static function count_total_downline( $affiliate_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}affiliates WHERE sponsor_id = %d",
            $affiliate_id
        ) );
    }

    /**
     * Get total hits for affiliate.
     */
    public static function get_total_hits( $affiliate_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}affiliate_referrals WHERE affiliate_id = %d AND type = 'hit'",
            $affiliate_id
        ) );
    }

    /**
     * Get total registered members (all downline).
     */
    public static function get_total_members( $affiliate_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}affiliates WHERE sponsor_id = %d",
            $affiliate_id
        ) );
    }

    /**
     * Get VIP member count in downline.
     */
    public static function get_vip_members( $affiliate_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}affiliates WHERE sponsor_id = %d AND member_type = 'vip'",
            $affiliate_id
        ) );
    }

    /**
     * Get free member count in downline.
     */
    public static function get_free_members( $affiliate_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}affiliates WHERE sponsor_id = %d AND member_type = 'free'",
            $affiliate_id
        ) );
    }

    /**
     * Get count of downline members active today (logged in today).
     */
    public static function get_active_today( $affiliate_id ) {
        global $wpdb;
        $today_start = current_time( 'Y-m-d' ) . ' 00:00:00';
        $today_end   = current_time( 'Y-m-d' ) . ' 23:59:59';

        $child_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}affiliates WHERE sponsor_id = %d",
            $affiliate_id
        ) );

        if ( empty( $child_ids ) ) {
            return 0;
        }

        $placeholders = implode( ',', array_fill( 0, count( $child_ids ), '%d' ) );
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}usermeta
             WHERE meta_key = 'session_tokens'
             AND user_id IN ($placeholders)",
            $child_ids
        ) );

        return (int) $count;
    }

    /**
     * Get sponsor (upline) info for an affiliate.
     * Falls back to admin (first administrator user) if no sponsor.
     */
    public static function get_sponsor_info( $affiliate_id ) {
        global $wpdb;
        $affiliate = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}affiliates WHERE id = %d LIMIT 1",
            $affiliate_id
        ) );

        if ( $affiliate && $affiliate->sponsor_id ) {
            $sponsor = $wpdb->get_row( $wpdb->prepare(
                "SELECT a.*, u.display_name, u.user_email FROM {$wpdb->prefix}affiliates a
                 LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
                 WHERE a.id = %d LIMIT 1",
                $affiliate->sponsor_id
            ) );
            if ( $sponsor ) {
                return [
                    'name'  => $sponsor->display_name ?? '',
                    'phone' => $sponsor->phone ?? '',
                    'email' => $sponsor->user_email ?? '',
                ];
            }
        }

        // Default: first administrator
        $admins = get_users( [ 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC' ] );
        if ( ! empty( $admins ) ) {
            $admin     = $admins[0];
            $admin_aff = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}affiliates WHERE user_id = %d LIMIT 1",
                $admin->ID
            ) );
            return [
                'name'  => $admin->display_name,
                'phone' => $admin_aff ? ( $admin_aff->phone ?? '' ) : '',
                'email' => $admin->user_email,
            ];
        }

        return [ 'name' => 'admin', 'phone' => '', 'email' => get_bloginfo( 'admin_email' ) ];
    }

    /**
     * Get leaderboard data.
     */
    public static function get_leaderboard( $limit = 10 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT a.id, a.affiliate_slug, u.display_name,
                    COALESCE(SUM(CASE WHEN c.status='approved' THEN c.amount ELSE 0 END), 0) AS total_commission,
                    COUNT(DISTINCT d.id) AS total_member,
                    COUNT(DISTINCT r.id) AS total_hits
             FROM {$wpdb->prefix}affiliates a
             LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
             LEFT JOIN {$wpdb->prefix}affiliate_commissions c ON c.affiliate_id = a.id
             LEFT JOIN {$wpdb->prefix}affiliates d ON d.sponsor_id = a.id
             LEFT JOIN {$wpdb->prefix}affiliate_referrals r ON r.affiliate_id = a.id AND r.type = 'hit'
             WHERE a.status = 'active'
             GROUP BY a.id
             ORDER BY total_commission DESC
             LIMIT %d",
            $limit
        ) );
    }

    /**
     * Get all stats for an affiliate.
     */
    public static function get_member_stats( $affiliate_id ) {
        return [
            'total_hits'    => self::get_total_hits( $affiliate_id ),
            'total_member'  => self::get_total_members( $affiliate_id ),
            'vip_member'    => self::get_vip_members( $affiliate_id ),
            'free_member'   => self::get_free_members( $affiliate_id ),
            'active_today'  => self::get_active_today( $affiliate_id ),
            'sponsor'       => self::get_sponsor_info( $affiliate_id ),
        ];
    }
}
