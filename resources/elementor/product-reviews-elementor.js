/**
 * Review widgets in the Elementor canvas.
 *
 * Core binds its review containers once, on DOM ready. That is enough for a
 * published page, where the markup is there before the script runs, but not
 * for the editor: changing a setting replaces the widget's HTML over AJAX,
 * long after DOM ready, and the replacement arrives with nothing listening.
 * A list switched to slider view then sits as a plain stack, and a column
 * count changed after that does nothing at all.
 *
 * So every review widget re-binds when Elementor says it has drawn one. Core
 * skips containers it has already bound, so this costs nothing on a page that
 * is only loading.
 */
(function ($) {
    'use strict';

    var WIDGETS = [
        'fluentcart_product_reviews',
        'fluentcart_product_review_list',
        'fluentcart_product_review_summary',
        'fluentcart_product_review_form',
        'fluentcart_write_a_review_button',
        'fluentcart_product_rating'
    ];

    function bindReviews($scope) {
        if (typeof window.fluentCartInitReviews !== 'function') {
            return;
        }

        window.fluentCartInitReviews(($scope && $scope[0]) || document);
    }

    $(window).on('elementor/frontend/init', function () {
        if (typeof elementorFrontend === 'undefined' || !elementorFrontend.hooks) {
            return;
        }

        WIDGETS.forEach(function (name) {
            elementorFrontend.hooks.addAction(
                'frontend/element_ready/' + name + '.default',
                bindReviews
            );
        });
    });
})(jQuery);
