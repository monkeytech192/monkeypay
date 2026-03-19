/**
 * API Docs JavaScript
 */

(function($) {
    'use strict';

    // Copy to clipboard
    $('.mkp-copy-btn').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var textToCopy = btn.data('copy');
        
        var tempTextarea = $('<textarea>');
        $('body').append(tempTextarea);
        tempTextarea.val(textToCopy).select();
        
        try {
            document.execCommand('copy');
            var originalHtml = btn.html();
            btn.html('<span class="dashicons dashicons-yes"></span>');
            setTimeout(function() {
                btn.html(originalHtml);
            }, 2000);
        } catch (err) {
            console.error('Lỗi khi copy', err);
        }
        
        tempTextarea.remove();
    });

    // Smooth scroll and active state updates
    var isScrolling = false;

    $('.mkp-docs-nav a').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        var $target = $(target);

        if ($target.length) {
            isScrolling = true;
            $('.mkp-docs-nav a').removeClass('active');
            $(this).addClass('active');

            $('html, body').animate({
                scrollTop: $target.offset().top - 60
            }, 300, function() {
                isScrolling = false;
            });
        }
    });

    // Scroll spy
    $(window).on('scroll', function() {
        if (isScrolling) return;

        var scrollPos = $(document).scrollTop();
        var firstActive = null;

        $('.mkp-doc-section').each(function() {
            var top = $(this).offset().top - 100;
            var bottom = top + $(this).outerHeight();

            if (scrollPos >= top && scrollPos <= bottom) {
                var id = $(this).attr('id');
                $('.mkp-docs-nav a').removeClass('active');
                firstActive = $('.mkp-docs-nav a[href="#' + id + '"]');
                firstActive.addClass('active');
            }
        });

        // Highlight first item if scrolled to top
        if (scrollPos < 100) {
            $('.mkp-docs-nav a').removeClass('active');
            $('.mkp-docs-nav a:first').addClass('active');
        }
    });

})(jQuery);
