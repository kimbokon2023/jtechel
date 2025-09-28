/**
 * WEBSITE: https://themefisher.com
 * TWITTER: https://twitter.com/themefisher
 * FACEBOOK: https://www.facebook.com/themefisher
 * GITHUB: https://github.com/themefisher/
 */
(function ($) {
    'use strict';

    $(window).scroll(function () {
        if ($('.navigation').length > 0) { // navigation 클래스가 존재하는 경우에만 동작
            if ($('.navigation').offset().top > 100) {
                $('.navigation').addClass('fixed-nav');
            } else {
                $('.navigation').removeClass('fixed-nav');
            }
        }
    });

    $('.portfolio-gallery').each(function () {
        if ($(this).find('.popup-gallery').length > 0) { // popup-gallery 클래스가 존재하는 경우에만 동작
            $(this).find('.popup-gallery').magnificPopup({
                type: 'image',
                gallery: {
                    enabled: true
                }
            });
        }
    });

    if ($('.testimonial-slider').length > 0) { // testimonial-slider 클래스가 존재하는 경우에만 동작
        $('.testimonial-slider').slick({
            slidesToShow: 1,
            infinite: true,
            arrows: false,
            autoplay: true,
            autoplaySpeed: 5000,
            dots: true
        });
    }

    if ($('.portfolio-popup').length > 0) { // portfolio-popup 클래스가 존재하는 경우에만 동작
        $('.portfolio-popup').magnificPopup({
            delegate: 'a',
            type: 'image',
            gallery: {
                enabled: true
            },
            mainClass: 'mfp-with-zoom',
            navigateByImgClick: true,
            arrowMarkup: '<button title="%title%" type="button" class="mfp-arrow mfp-arrow-%dir%"></button>',
            tPrev: 'Previous (Left arrow key)',
            tNext: 'Next (Right arrow key)',
            tCounter: '<span class="mfp-counter">%curr% of %total%</span>',
            zoom: {
                enabled: true,
                duration: 300,
                easing: 'ease-in-out',
                opener: function (openerElement) {
                    return openerElement.is('img') ? openerElement : openerElement.find('img');
                }
            }
        });
    }

})(jQuery);
