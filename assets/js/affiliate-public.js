/* Affiliate MLM Pro — Public JS v1.1 */
(function($){
  'use strict';

  // ─── COPY TO CLIPBOARD ───────────────────────────────
  $(document).on('click', '.amlm-btn-copy', function(){
    var btn    = $(this);
    var target = btn.data('target');
    var value  = btn.data('value');

    if (!value && target) {
      var el = document.getElementById(target);
      value = el ? el.value : '';
    }
    if (!value) return;

    var origHTML = btn.html();

    function onCopied() {
      btn.html('✓ Disalin!').addClass('copied');
      setTimeout(function(){
        btn.html(origHTML).removeClass('copied');
      }, 2000);
    }

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(value).then(onCopied).catch(function(){
        fallbackCopy(value, onCopied);
      });
    } else {
      fallbackCopy(value, onCopied);
    }
  });

  function fallbackCopy(text, cb) {
    var $t = $('<input type="text">').val(text).css({position:'fixed',top:0,opacity:0});
    $('body').append($t);
    $t[0].select();
    try { document.execCommand('copy'); cb(); } catch(e){}
    $t.remove();
  }

  // ─── INLINE COPY (ref-table inline buttons) ───────────
  $(document).on('click', '.amlm-copy-btn[data-value]', function(){
    var btn   = $(this);
    var value = btn.data('value');
    if (!value) return;
    var orig  = btn.text();
    if (navigator.clipboard) {
      navigator.clipboard.writeText(value).then(function(){
        btn.text('✓').css('color','#22c55e');
        setTimeout(function(){ btn.text(orig).css('color',''); }, 2000);
      });
    }
  });

})(jQuery);
