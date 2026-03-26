<?php
// templates/landing-page.php
if ( ! defined( 'ABSPATH' ) ) exit;
$affiliate = get_query_var( 'affiliate_data' );
if ( ! $affiliate ) wp_die( esc_html__( 'Halaman tidak ditemui.', 'affiliate-mlm-pro' ) );
$user    = get_userdata( $affiliate->user_id );
$stats   = Affiliate_MLM_MLM::get_member_stats( $affiliate->id );
$aff_url = add_query_arg( 'ref', $affiliate->affiliate_slug, home_url() );
$reg_url = add_query_arg( 'ref', $affiliate->affiliate_slug, home_url( '/register' ) );
$wa_num  = preg_replace( '/\D/', '', $affiliate->phone );
$wa_msg  = urlencode( $affiliate->wa_message ?: __( 'Halo, saya ingin tahu lebih lanjut.', 'affiliate-mlm-pro' ) );

get_header();
?>
<div class="amlm-landing-wrap" style="max-width:700px;margin:3rem auto;padding:0 1rem;">
    <div class="amlm-form-card" style="text-align:center;padding:2.5rem;">
        <div class="amlm-avatar" style="width:80px;height:80px;line-height:80px;font-size:28px;margin:0 auto 1rem;"><?php echo esc_html( mb_substr( $user->display_name, 0, 2 ) ); ?></div>
        <h1 style="font-size:1.6rem;margin-bottom:.5rem;"><?php echo esc_html( $user->display_name ); ?></h1>
        <?php if ( $affiliate->description ) : ?>
        <p style="color:#666;margin-bottom:1.5rem;"><?php echo esc_html( $affiliate->description ); ?></p>
        <?php endif; ?>

        <div class="amlm-stat-cards" style="justify-content:center;margin-bottom:2rem;">
            <div class="amlm-card"><span class="amlm-num"><?php echo esc_html( $stats['total_hits'] ); ?></span><span class="amlm-label"><?php esc_html_e( 'Total Hits', 'affiliate-mlm-pro' ); ?></span></div>
            <div class="amlm-card"><span class="amlm-num"><?php echo esc_html( $stats['total_member'] ); ?></span><span class="amlm-label"><?php esc_html_e( 'Total Member', 'affiliate-mlm-pro' ); ?></span></div>
        </div>

        <?php if ( $wa_num ) : ?>
        <a class="amlm-wa-btn" href="https://wa.me/<?php echo esc_attr( $wa_num ); ?>?text=<?php echo $wa_msg; ?>" target="_blank" rel="noopener" style="display:inline-block;margin-bottom:1rem;">
            <?php esc_html_e( 'Hubungi via WhatsApp', 'affiliate-mlm-pro' ); ?>
        </a><br>
        <?php endif; ?>

        <a class="amlm-btn-submit" href="<?php echo esc_url( $reg_url ); ?>" style="display:inline-block;text-decoration:none;padding:.8rem 2.5rem;font-size:1rem;">
            <?php esc_html_e( 'Daftar Sekarang', 'affiliate-mlm-pro' ); ?>
        </a>
    </div>
</div>
<?php get_footer();
