/* Affiliate MLM Pro — Public JS v2.0 */
(function($){
  'use strict';

  // ─── COPY TO CLIPBOARD (old selector: amlm-btn-copy) ─────────
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

  // ─── COPY V2 (new selector: amlm-btn-copy-v2) ────────────────
  $(document).on('click', '.amlm-btn-copy-v2', function(){
    var btn    = $(this);
    var target = btn.data('target');
    var value  = btn.data('value');

    if (!value && target) {
      var el = document.getElementById(target);
      value = el ? el.value : '';
    }
    if (!value) return;

    var copyIcon = btn.find('.copy-icon');
    var copyTxt  = btn.find('.copy-txt');

    function onCopied() {
      copyIcon.text('✅');
      copyTxt.text('Disalin!');
      btn.addClass('copied');
      setTimeout(function(){
        copyIcon.text('📋');
        copyTxt.text('Salin');
        btn.removeClass('copied');
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

  // ─── INLINE COPY (ref-table inline buttons) ───────────────────
  $(document).on('click', '.amlm-copy-btn[data-value]', function(){
    var btn   = $(this);
    var value = btn.data('value');
    if (!value) return;
    var orig  = btn.text();
    if (navigator.clipboard) {
      navigator.clipboard.writeText(value).then(function(){
        btn.text('✓ Disalin!').css('background','rgba(16,185,129,0.2)').css('color','#34d399');
        setTimeout(function(){ btn.text(orig).css('background','').css('color',''); }, 2000);
      });
    } else {
      fallbackCopy(value, function(){
        btn.text('✓').css('color','#34d399');
        setTimeout(function(){ btn.text(orig).css('color',''); }, 2000);
      });
    }
  });

  // ─── FALLBACK COPY ────────────────────────────────────────────
  function fallbackCopy(text, cb) {
    var $t = $('<input type="text">').val(text).css({position:'fixed',top:0,opacity:0});
    $('body').append($t);
    $t[0].select();
    try { document.execCommand('copy'); if(cb) cb(); } catch(e){}
    $t.remove();
  }

  // ─── ANIMATE STATS ON SCROLL ──────────────────────────────────
  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if (entry.isIntersecting) {
          var el = entry.target;
          el.style.animationPlayState = 'running';
          observer.unobserve(el);
        }
      });
    }, { threshold:0.1 });

    document.querySelectorAll('.amlm-stat-v2').forEach(function(el, i){
      el.style.animationDelay = (i * 0.08) + 's';
      observer.observe(el);
    });
  }

  // ─── AUTO TAB SCROLL ACTIVE NAV INTO VIEW ────────────────────
  var activeNav = document.querySelector('.amlm-nav-v2-item.active');
  if (activeNav) {
    setTimeout(function(){ activeNav.scrollIntoView({inline:'center',behavior:'smooth'}); }, 100);
  }

})(jQuery);
