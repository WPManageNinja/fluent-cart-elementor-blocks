/**
 * Sync standalone FluentCart product widgets with the active variation.
 *
 * Listens to core's `fluentCartSingleProductVariationChanged` event and
 * re-applies SKU, Stock, Package Description, variant price, and gallery
 * image updates to widgets dropped as separate Elementor blocks in a Theme
 * Builder Single Product template — i.e. outside core's per-pricing-section
 * scope. Skips elements already managed by core or belonging to other
 * products (related-products lists, product cards).
 */
(function () {
    'use strict';

    var CORE_PRICING_SCOPE = '[data-fluent-cart-product-pricing-section]';
    var CORE_SUMMARY_SCOPE = '.fct-product-summary';
    var RELATED_LIST_SCOPE = '.fct-similar-product-list-container';
    var PRODUCT_CARD_SCOPE = '[data-fct-product-card]';

    function shouldUpdate(el) {
        if (!el) return false;
        if (el.closest(CORE_PRICING_SCOPE)) return false;
        if (el.closest(CORE_SUMMARY_SCOPE)) return false;
        if (el.closest(RELATED_LIST_SCOPE)) return false;
        if (el.closest(PRODUCT_CARD_SCOPE)) return false;
        return true;
    }

    function titleCase(str) {
        return str.replace(/\w\S*/g, function (txt) {
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
    }

    function syncSku(sku) {
        document.querySelectorAll('[data-fluent-cart-product-sku]').forEach(function (el) {
            if (!shouldUpdate(el)) return;
            el.textContent = sku;
            var wrapper = el.closest('.fct-product-sku');
            if (wrapper) wrapper.style.display = sku ? '' : 'none';
        });
    }

    function syncStock(status) {
        var trans = (window.fluentcart_single_product_vars || {}).trans || {};

        document.querySelectorAll('[data-fluent-cart-product-stock]').forEach(function (el) {
            if (!shouldUpdate(el)) return;

            var wrapper = el.closest('.fct-product-stock');

            if (!status) {
                if (wrapper) wrapper.style.display = 'none';
                return;
            }

            var label = titleCase(status.replace(/-/g, ' '));
            el.textContent = trans[label] || label;
            el.className = el.className.replace(/fct_status_badge_[\w-]+/g, '');
            el.classList.add('fct_status_badge_' + status);

            if (wrapper) {
                wrapper.classList.remove('in-stock', 'out-of-stock');
                wrapper.classList.add(status);
                wrapper.style.display = '';
            }
        });
    }

    function syncPackage(packageInfoJson) {
        var trans = (window.fluentcart_single_product_vars || {}).trans || {};

        document.querySelectorAll('[data-fluent-cart-package-description]').forEach(function (wrapper) {
            if (!shouldUpdate(wrapper)) return;
            if (!packageInfoJson) { wrapper.style.display = 'none'; return; }

            try {
                var pkg = JSON.parse(packageInfoJson);
                var table = document.createElement('table');
                table.className = 'fct-package-description__table';
                table.setAttribute('role', 'presentation');
                var tbody = document.createElement('tbody');

                var addRow = function (label, value) {
                    var tr = document.createElement('tr');
                    var th = document.createElement('th');
                    var td = document.createElement('td');
                    th.textContent = label;
                    td.textContent = value;
                    tr.appendChild(th);
                    tr.appendChild(td);
                    tbody.appendChild(tr);
                };

                if (pkg.name) addRow(trans['Package'] || 'Package', pkg.name);
                if (pkg.dimensions) addRow(trans['Dimensions'] || 'Dimensions', pkg.dimensions);
                if (pkg.product_weight) addRow(trans['Weight'] || 'Weight', pkg.product_weight);
                if (pkg.shipping_weight) addRow(trans['Shipping Weight'] || 'Shipping Weight', pkg.shipping_weight);

                if (tbody.childElementCount) {
                    table.appendChild(tbody);
                    wrapper.replaceChildren(table);
                    wrapper.style.display = '';
                } else {
                    wrapper.style.display = 'none';
                }
            } catch (e) {
                wrapper.style.display = 'none';
            }
        });
    }

    function syncPrice(variationId) {
        var idStr = String(variationId);
        document.querySelectorAll('.fluent-cart-product-variation-content[data-variation-id]').forEach(function (el) {
            if (!shouldUpdate(el)) return;
            if (el.dataset.variationId === idStr) {
                el.classList.remove('is-hidden');
            } else {
                el.classList.add('is-hidden');
            }
        });
    }

    window.addEventListener('fluentCartSingleProductVariationChanged', function (e) {
        var variationId = e.detail && e.detail.variationId;
        if (!variationId) return;

        var selector = '[data-fluent-cart-product-variant][data-cart-id="' + CSS.escape(String(variationId)) + '"]';
        var button = document.querySelector(selector);
        if (!button) return;

        syncSku(button.dataset.sku || '');
        syncStock(button.dataset.itemStock);
        syncPackage(button.dataset.packageInfo || '');
        syncPrice(variationId);
    });

    document.addEventListener('click', function (e) {
        var thumb = e.target.closest('[data-fluent-cart-thumb-control-button]');
        if (!thumb) return;
        var url = thumb.dataset.url;
        if (!url) return;

        document.querySelectorAll('[data-fluent-cart-single-product-page-product-thumbnail]').forEach(function (img) {
            if (img.closest(RELATED_LIST_SCOPE) || img.closest(PRODUCT_CARD_SCOPE)) return;
            img.setAttribute('src', url);
        });
    });
})();
