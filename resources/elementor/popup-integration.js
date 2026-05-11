/**
 * Bridge between Elementor popup lifecycle and FluentCart's single-product UI.
 *
 * Elementor re-renders popup HTML via innerHTML AFTER firing `elementor/popup/show`.
 * That wipes any JS event listeners bound during the event handler. We defer
 * with requestAnimationFrame so FluentCart binds against the final post-render
 * DOM, then call the public re-init API exposed by SingleProduct.js.
 */
(function () {
    if (!window.jQuery) return;

    window.jQuery(document).on('elementor/popup/show', function (_event, popupId, instance) {
        requestAnimationFrame(function () {
            var popupRoot = (instance && instance.$el && instance.$el[0])
                || document.querySelector('#elementor-popup-modal-' + popupId);
            if (!popupRoot) return;
            if (window.FluentCartSingleProduct && typeof window.FluentCartSingleProduct.reinit === 'function') {
                window.FluentCartSingleProduct.reinit(popupRoot, 'elementor-popup');
            }
        });
    });
})();
