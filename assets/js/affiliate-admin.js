/* Affiliate MLM Pro — Admin JS */
(function($){
    'use strict';
    var nonce = affiliateMLMAdmin.nonce;
    var ajax  = affiliateMLMAdmin.ajaxurl;

    // Approve commission
    $(document).on('click', '.amlm-approve-commission', function(){
        var id  = $(this).data('id');
        var row = $(this).closest('tr');
        if (!confirm('Approve komisen ini?')) return;
        $.post(ajax, { action:'affiliate_mlm_approve_commission', nonce:nonce, id:id }, function(res){
            if (res.success) { row.find('td:nth-last-child(2)').text('approved'); row.find('td:last').html(''); }
        });
    });

    // Reject commission
    $(document).on('click', '.amlm-reject-commission', function(){
        var id  = $(this).data('id');
        var row = $(this).closest('tr');
        var note = prompt('Alasan penolakan (opsional):') || '';
        $.post(ajax, { action:'affiliate_mlm_reject_commission', nonce:nonce, id:id, note:note }, function(res){
            if (res.success) { row.find('td:nth-last-child(2)').text('rejected'); row.find('td:last').html(''); }
        });
    });

    // Approve withdrawal
    $(document).on('click', '.amlm-approve-withdraw', function(){
        var id  = $(this).data('id');
        var row = $(this).closest('tr');
        if (!confirm('Approve pengeluaran ini?')) return;
        $.post(ajax, { action:'affiliate_mlm_approve_withdrawal', nonce:nonce, id:id }, function(res){
            if (res.success) { location.reload(); }
        });
    });

    // Reject withdrawal
    $(document).on('click', '.amlm-reject-withdraw', function(){
        var id   = $(this).data('id');
        var note = prompt('Sebab penolakan:') || '';
        $.post(ajax, { action:'affiliate_mlm_reject_withdrawal', nonce:nonce, id:id, note:note }, function(res){
            if (res.success) { location.reload(); }
        });
    });

    // Mark paid
    $(document).on('click', '.amlm-paid-withdraw', function(){
        var id  = $(this).data('id');
        if (!confirm('Tandakan sebagai PAID?')) return;
        $.post(ajax, { action:'affiliate_mlm_approve_withdrawal', nonce:nonce, id:id, mark_paid:1 }, function(res){
            if (res.success) { location.reload(); }
        });
    });

})(jQuery);
