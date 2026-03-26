<?php if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;
$aff_id  = $affiliate->id;
$members = $wpdb->get_results( $wpdb->prepare(
    "SELECT a.*, u.display_name, u.user_email,
            COUNT(DISTINCT d.id) AS total_referral,
            COALESCE(SUM(CASE WHEN c.status='approved' THEN c.amount ELSE 0 END),0) AS total_commission
     FROM {$wpdb->prefix}affiliates a
     LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
     LEFT JOIN {$wpdb->prefix}affiliates d ON d.sponsor_id = a.id
     LEFT JOIN {$wpdb->prefix}affiliate_commissions c ON c.affiliate_id = a.id
     WHERE a.sponsor_id = %d
     GROUP BY a.id
     ORDER BY a.joined_at DESC",
    $aff_id
) );
?>
<div class="amlm-ref-table-wrap">
    <table id="amlm-ref-datatable" class="amlm-table display" style="width:100%">
        <thead><tr>
            <th><?php esc_html_e( 'Nama', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Username', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Link Affiliate', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Total Referral', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Komisen (RM)', 'affiliate-mlm-pro' ); ?></th>
            <th><?php esc_html_e( 'Status', 'affiliate-mlm-pro' ); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ( $members as $m ) :
            $link = add_query_arg( 'ref', $m->affiliate_slug, home_url() );
        ?>
        <tr>
            <td><?php echo esc_html( $m->display_name ); ?></td>
            <td><?php echo esc_html( $m->affiliate_slug ); ?></td>
            <td>
                <input type="text" value="<?php echo esc_attr( $link ); ?>" readonly style="width:180px;font-size:12px;" />
                <button class="amlm-copy-btn" data-value="<?php echo esc_attr( $link ); ?>"><?php esc_html_e( 'Salin', 'affiliate-mlm-pro' ); ?></button>
            </td>
            <td><?php echo esc_html( $m->total_referral ); ?></td>
            <td><?php echo esc_html( number_format( $m->total_commission, 2 ) ); ?></td>
            <td><span class="amlm-status-<?php echo esc_attr( $m->status ); ?>"><?php echo esc_html( ucfirst( $m->status ) ); ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
jQuery(document).ready(function($){
    if ( $.fn.DataTable ) {
        $('#amlm-ref-datatable').DataTable({
            language: {
                search: '<?php esc_html_e( 'Cari:', 'affiliate-mlm-pro' ); ?>',
                lengthMenu: '<?php esc_html_e( 'Papar _MENU_ rekod', 'affiliate-mlm-pro' ); ?>',
                info: '<?php esc_html_e( 'Menunjukkan _START_ hingga _END_ daripada _TOTAL_ rekod', 'affiliate-mlm-pro' ); ?>',
                paginate: { previous: '<?php esc_html_e( 'Sebelum', 'affiliate-mlm-pro' ); ?>', next: '<?php esc_html_e( 'Seterusnya', 'affiliate-mlm-pro' ); ?>' },
                zeroRecords: '<?php esc_html_e( 'Tiada rekod ditemui.', 'affiliate-mlm-pro' ); ?>',
            },
            responsive: true,
            order: [[3, 'desc']],
        });
    }
    // copy buttons inside table
    $(document).on('click', '.amlm-copy-btn[data-value]', function(){
        var val = $(this).data('value');
        navigator.clipboard.writeText(val).then(function(){
            // brief feedback
        });
    });
});
</script>
