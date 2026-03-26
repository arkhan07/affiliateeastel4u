<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Affiliate_MLM_Elementor {

    public static function register_tags( $dynamic_tags ) {
        $tags = [
            'Affiliate_Tag_Name',
            'Affiliate_Tag_Phone',
            'Affiliate_Tag_Total_Member',
            'Affiliate_Tag_Total_Hits',
            'Affiliate_Tag_Commission',
            'Affiliate_Tag_Link',
            'Affiliate_Tag_Vip_Member',
            'Affiliate_Tag_Free_Member',
            'Affiliate_Tag_Active_Today',
        ];
        foreach ( $tags as $tag ) {
            if ( class_exists( $tag ) ) {
                $dynamic_tags->register( new $tag() );
            }
        }
    }
}

if ( defined( 'ELEMENTOR_VERSION' ) ) {

    abstract class Affiliate_Base_Tag extends \Elementor\Core\DynamicTags\Tag {
        public function get_categories() {
            return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
        }
        public function get_group() { return 'affiliate-mlm'; }
    }

    class Affiliate_Tag_Name extends Affiliate_Base_Tag {
        public function get_name() { return 'affiliate-name'; }
        public function get_title() { return __( 'Affiliate Name', 'affiliate-mlm-pro' ); }
        public function render() {
            $a = Affiliate_MLM_Core::get_current_affiliate();
            $u = $a ? get_userdata( $a->user_id ) : null;
            echo $u ? esc_html( $u->display_name ) : '';
        }
    }

    class Affiliate_Tag_Phone extends Affiliate_Base_Tag {
        public function get_name() { return 'affiliate-phone'; }
        public function get_title() { return __( 'Affiliate Phone', 'affiliate-mlm-pro' ); }
        public function render() {
            $a = Affiliate_MLM_Core::get_current_affiliate();
            echo $a ? esc_html( $a->phone ) : '';
        }
    }

    class Affiliate_Tag_Total_Member extends Affiliate_Base_Tag {
        public function get_name() { return 'affiliate-total-member'; }
        public function get_title() { return __( 'Total Member', 'affiliate-mlm-pro' ); }
        public function render() {
            $a = Affiliate_MLM_Core::get_current_affiliate();
            echo $a ? esc_html( Affiliate_MLM_MLM::get_total_members( $a->id ) ) : '0';
        }
    }

    class Affiliate_Tag_Total_Hits extends Affiliate_Base_Tag {
        public function get_name() { return 'affiliate-total-hits'; }
        public function get_title() { return __( 'Total Hits', 'affiliate-mlm-pro' ); }
        public function render() {
            $a = Affiliate_MLM_Core::get_current_affiliate();
            echo $a ? esc_html( Affiliate_MLM_MLM::get_total_hits( $a->id ) ) : '0';
        }
    }

    class Affiliate_Tag_Commission extends Affiliate_Base_Tag {
        public function get_name() { return 'affiliate-commission'; }
        public function get_title() { return __( 'Total Commission', 'affiliate-mlm-pro' ); }
        public function render() {
            $a = Affiliate_MLM_Core::get_current_affiliate();
            echo $a ? esc_html( number_format( Affiliate_MLM_Commission::get_total_commission( $a->id ), 2 ) ) : '0.00';
        }
    }

    class Affiliate_Tag_Link extends Affiliate_Base_Tag {
        public function get_name() { return 'affiliate-link'; }
        public function get_title() { return __( 'Affiliate Link', 'affiliate-mlm-pro' ); }
        public function render() {
            $a = Affiliate_MLM_Core::get_current_affiliate();
            echo $a ? esc_url( add_query_arg( 'ref', $a->affiliate_slug, home_url() ) ) : '';
        }
    }

    class Affiliate_Tag_Vip_Member extends Affiliate_Base_Tag {
        public function get_name() { return 'affiliate-vip-member'; }
        public function get_title() { return __( 'VIP Member', 'affiliate-mlm-pro' ); }
        public function render() {
            $a = Affiliate_MLM_Core::get_current_affiliate();
            echo $a ? esc_html( Affiliate_MLM_MLM::get_vip_members( $a->id ) ) : '0';
        }
    }

    class Affiliate_Tag_Free_Member extends Affiliate_Base_Tag {
        public function get_name() { return 'affiliate-free-member'; }
        public function get_title() { return __( 'Free Member', 'affiliate-mlm-pro' ); }
        public function render() {
            $a = Affiliate_MLM_Core::get_current_affiliate();
            echo $a ? esc_html( Affiliate_MLM_MLM::get_free_members( $a->id ) ) : '0';
        }
    }

    class Affiliate_Tag_Active_Today extends Affiliate_Base_Tag {
        public function get_name() { return 'affiliate-active-today'; }
        public function get_title() { return __( 'Active Today', 'affiliate-mlm-pro' ); }
        public function render() {
            $a = Affiliate_MLM_Core::get_current_affiliate();
            echo $a ? esc_html( Affiliate_MLM_MLM::get_active_today( $a->id ) ) : '0';
        }
    }
}
