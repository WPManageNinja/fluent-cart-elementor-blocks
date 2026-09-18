/**
 * Sync standalone FluentCart product widgets with the active variation.
 *
 * Listens to core's `fluentCartSingleProductVariationChanged` event and
 * re-applies SKU, Stock, Package Description, variant price, Add to Cart /
 * Buy Now button state, and gallery image updates to widgets dropped as
 * separate Elementor blocks in a Theme Builder Single Product template —
 * i.e. outside core's per-pricing-section scope. Skips elements already
 * managed by core or belonging to other products (related-products lists,
 * product cards).
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

    function isSafeClassName(value) {
        return typeof value === 'string' && /^[a-zA-Z0-9_-]+$/.test(value);
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
        var isValid = isSafeClassName(status);

        document.querySelectorAll('[data-fluent-cart-product-stock]').forEach(function (el) {
            if (!shouldUpdate(el)) return;

            var wrapper = el.closest('.fct-product-stock');

            if (!isValid) {
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

    function syncAddToCart(variationId, status, paymentType, variantName) {
        var vars = window.fluentcart_single_product_vars || {};
        var outOfStock = (vars.out_of_stock_status || 'out-of-stock').toString();
        var isOutOfStock = status === outOfStock;
        var isSubscription = paymentType === 'subscription';

        document.querySelectorAll('[data-fluent-cart-add-to-cart-button]').forEach(function (btn) {
            if (!shouldUpdate(btn)) return;

            btn.setAttribute('data-cart-id', variationId);
            var textEl = btn.querySelector('.text');

            if (isOutOfStock) {
                btn.setAttribute('disabled', 'disabled');
                btn.classList.add('out-of-stock');
                btn.classList.remove('is-hidden');
                if (textEl && vars.out_of_stock_button_text) {
                    textEl.textContent = vars.out_of_stock_button_text;
                }
            } else {
                btn.removeAttribute('disabled');
                btn.classList.remove('out-of-stock');
                if (textEl && vars.cart_button_text) {
                    textEl.textContent = vars.cart_button_text;
                }
                if (isSubscription) {
                    btn.classList.add('is-hidden');
                } else {
                    btn.classList.remove('is-hidden');
                }
            }

            if (variantName) {
                var baseText = textEl ? textEl.textContent.trim() : btn.textContent.trim();
                btn.setAttribute('aria-label', baseText + ' - ' + variantName);
            }
        });
    }

    function syncBuyNow(variationId, status, variantName) {
        var vars = window.fluentcart_single_product_vars || {};
        var outOfStock = (vars.out_of_stock_status || 'out-of-stock').toString();
        var isOutOfStock = status === outOfStock;

        document.querySelectorAll('[data-fluent-cart-direct-checkout-button]').forEach(function (btn) {
            if (!shouldUpdate(btn)) return;

            if (isOutOfStock) {
                btn.removeAttribute('href');
                btn.classList.add('is-hidden');
            } else {
                var quantity = btn.dataset.quantity || '1';
                var baseUrl = btn.getAttribute('data-url') || '';
                btn.setAttribute('href', baseUrl + variationId + '&quantity=' + quantity);
                btn.setAttribute('data-cart-id', variationId);
                btn.classList.remove('is-hidden');
            }

            if (variantName) {
                btn.setAttribute('aria-label', btn.textContent.trim() + ' - ' + variantName);
            }
        });
    }

    window.addEventListener('fluentCartSingleProductVariationChanged', function (e) {
        var variationId = e.detail && e.detail.variationId;
        if (!variationId) return;

        // Always sync variation-content visibility — this is the only operation
        // that needs just the variationId. Advanced variation products never render
        // [data-fluent-cart-product-variant] buttons, so syncPrice must run before
        // the button lookup to avoid skipping it entirely.
        syncPrice(variationId);

        var selector = '[data-fluent-cart-product-variant][data-cart-id="' + CSS.escape(String(variationId)) + '"]';
        var button = document.querySelector(selector);
        if (!button) return;

        var status = button.dataset.itemStock;
        var paymentType = button.dataset.paymentType;
        var variantName = button.getAttribute('aria-label') || '';

        syncSku(button.dataset.sku || '');
        syncStock(status);
        syncPackage(button.dataset.packageInfo || '');
        syncAddToCart(variationId, status, paymentType, variantName);
        syncBuyNow(variationId, status, variantName);
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
