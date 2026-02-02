(function($) {
    'use strict';

    var initCarouselWidget = function($scope) {
        var $carousel = $scope.find('.swiper.fct-product-carousel[data-fluent-cart-product-carousel]');

        if (!$carousel.length) {
            return;
        }

        // Check if already initialized
        if ($carousel.data('swiperInitialized') === 'yes') {
            return;
        }

        // Use the global FluentCartProductCarousel if available
        if (window.FluentCartProductCarousel && typeof window.FluentCartProductCarousel.initCarousels === 'function') {
            window.FluentCartProductCarousel.initCarousels($scope[0]);
            return;
        }

        // Fallback: Initialize Swiper directly if the global isn't available yet
        if (typeof Swiper === 'undefined') {
            return;
        }

        $carousel.each(function() {
            var carousel = this;

            if (carousel.dataset.swiperInitialized === 'yes') {
                return;
            }
            carousel.dataset.swiperInitialized = 'yes';

            var settings = {};
            try {
                settings = JSON.parse(carousel.getAttribute('data-carousel-settings') || '{}');
            } catch (e) {
                console.warn('FluentCart: Invalid carousel settings', e);
            }

            var slidesToShow = Math.max(1, Number(settings.slidesToShow || 3));
            var spaceBetween = Number(settings.spaceBetween || 16);

            var nextEl = carousel.querySelector('.swiper-button-next');
            var prevEl = carousel.querySelector('.swiper-button-prev');
            var paginationEl = carousel.querySelector('.swiper-pagination');

            var paginationTypeMap = {
                dots: 'bullets',
                fraction: 'fraction',
                progress: 'progressbar'
            };

            var swiperConfig = {
                slidesPerView: slidesToShow,
                spaceBetween: spaceBetween,
                loop: settings.infinite === 'yes',
                grabCursor: true,
                watchOverflow: true,
                observer: true,
                observeParents: true,
                rtl: document.documentElement.dir === 'rtl',

                autoplay: settings.autoplay === 'yes' ? {
                    delay: Number(settings.autoplayDelay || 3000),
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                } : false,

                navigation: (settings.arrows === 'yes' && nextEl && prevEl) ? {
                    nextEl: nextEl,
                    prevEl: prevEl
                } : false,

                pagination: (settings.dots === 'yes' && paginationEl) ? {
                    el: paginationEl,
                    clickable: true,
                    type: paginationTypeMap[settings.paginationType] || 'bullets'
                } : false,

                breakpoints: {
                    0: { slidesPerView: 1 },
                    640: { slidesPerView: Math.min(2, slidesToShow) },
                    1024: { slidesPerView: Math.min(3, slidesToShow) },
                    1280: { slidesPerView: slidesToShow }
                }
            };

            new Swiper(carousel, swiperConfig);
        });
    };

    // Hook into Elementor frontend init
    $(window).on('elementor/frontend/init', function() {
        if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
            elementorFrontend.hooks.addAction(
                'frontend/element_ready/fluent_cart_product_carousel.default',
                initCarouselWidget
            );
        }
    });

})(jQuery);