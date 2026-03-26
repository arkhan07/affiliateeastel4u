<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
// This file handles: leaderboard.php (if $rows is set), ref-table.php, withdrawal-form.php
// Each section is included separately by shortcode; use template_file detection

$tmpl = basename( __FILE__ );
?>
<?php if ( 'leaderboard.php' === $tmpl ) : ?>
<div class="amlm-leaderboard-wrap">
<table class="amlm-table">
    <thead><tr>
        <th><?php esc_html_e( 'Rank', 'affiliate-mlm-pro' ); ?></th>
        <th><?php esc_html_e( 'Nama', 'affiliate-mlm-pro' ); ?></th>
        <th><?php esc_html_e( 'Total Komisen', 'affiliate-mlm-pro' ); ?></th>
        <th><?php esc_html_e( 'Total Member', 'affiliate-mlm-pro' ); ?></th>
        <th><?php esc_html_e( 'Total Hits', 'affiliate-mlm-pro' ); ?></th>
    </tr></thead>
    <tbody>
    <?php if ( ! empty( $rows ) ) : $rank = 1; foreach ( $rows as $r ) : ?>
    <tr>
        <td><?php echo esc_html( $rank++ ); ?></td>
        <td><?php echo esc_html( $r->display_name ); ?></td>
        <td><?php echo esc_html( number_format( $r->total_commission, 2 ) ); ?></td>
        <td><?php echo esc_html( $r->total_member ); ?></td>
        <td><?php echo esc_html( $r->total_hits ); ?></td>
    </tr>
    <?php endforeach; else : ?>
    <tr><td colspan="5"><?php esc_html_e( 'Tiada data.', 'affiliate-mlm-pro' ); ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
