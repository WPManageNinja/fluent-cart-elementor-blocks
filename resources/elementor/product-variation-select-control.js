(function($) {
    var initialized = false;

    // Translated editor strings come from wp_localize_script (fluentCartElementor.i18n).
    // The English argument is the fallback for the window where the global is
    // not yet printed, so the control still renders a usable placeholder.
    var getEditorString = function(key, fallback) {
        var strings = (window.fluentCartElementor || {}).i18n || {};

        return strings[key] || fallback;
    };

    var initFluentCartControl = function() {
        if (initialized) {
            return;
        }

        // Ensure Elementor modules are available
        if (!window.elementor || !elementor.modules || !elementor.modules.controls) {
            return;
        }

        initialized = true;

        // Use BaseData as a safer base class, manually handling the UI
        var ControlBaseData = elementor.modules.controls.BaseData;

        if (!ControlBaseData) {
            console.error('Fluent Cart: ControlBaseData not found');
            return;
        }

        var FluentProductVariationSelect = ControlBaseData.extend({
            // Explicitly define UI map
            ui: function() {
                return {
                    select: 'select'
                };
            },

            onReady: function() {
                // Check if required global object exists
                if (typeof fluentCartElementor === 'undefined') {
                    console.error('Fluent Cart: fluentCartElementor is undefined');
                    return;
                }

                var self = this;
                var $select = this.ui.select;

                // Fallback: if this.ui.select is empty, try finding it manually
                if (!$select || !$select.length) {
                    $select = this.$el.find('select');
                }

                if (!$select.length) {
                    return;
                }

                var options = {
                    allowClear: true,
                    placeholder: this.model.get('placeholder') || getEditorString('searchVariation', 'Search for a variation...'),
                    dir: (window.elementorCommon && elementorCommon.config && elementorCommon.config.isRTL) ? 'rtl' : 'ltr',
                    ajax: {
                        url: fluentCartElementor.restUrl + 'products/search-product-variant-options',
                        dataType: 'json',
                        delay: 250,
                        headers: {
                            'X-WP-Nonce': fluentCartElementor.nonce
                        },
                        data: function (params) {
                            var queryParams = self.model.get('query_params') || {};
                            var data = { search: params.term, page: params.page || 1 };
                            return Object.assign({}, data, queryParams);
                        },
                        processResults: function (data) {
                            var results = [];
                            // Handle potential API response variations
                            var productGroups = data.products || data || [];

                            if (!Array.isArray(productGroups)) {
                                productGroups = [];
                            }

                            $.each(productGroups, function(i, group) {
                                var newGroup = {
                                    text: group.label || getEditorString('unknownProduct', 'Unknown Product'),
                                    children: []
                                };

                                if (group.children && Array.isArray(group.children)) {
                                    $.each(group.children, function(j, child) {
                                        newGroup.children.push({
                                            id: child.value,
                                            text: child.label
                                        });
                                    });
                                }

                                if (newGroup.children.length) {
                                    results.push(newGroup);
                                }
                            });

                            return {
                                results: results
                            };
                        },
                        cache: true
                    },
                    // 0 (not 1) so opening the control immediately shows the
                    // variant list (searchProductVariantOptions returns the first
                    // 20 when the search term is empty), like the Gutenberg picker
                    // — instead of "enter 1 or more characters". Typing still filters.
                    minimumInputLength: 0
                };

                // Initialize Select2
                $select.select2(options);

                // Fetch initial value if exists
                var initialValue = this.getControlValue();
                if (initialValue) {
                    $.ajax({
                        url: fluentCartElementor.restUrl + 'products/search-product-variant-options',
                        dataType: 'json',
                        headers: {
                            'X-WP-Nonce': fluentCartElementor.nonce
                        },
                        data: {
                            include_ids: [initialValue]
                        }
                    }).then(function (data) {
                        var selectedOption = null;
                        var productGroups = data.products || data || [];

                        if (!Array.isArray(productGroups)) {
                            productGroups = [];
                        }

                        $.each(productGroups, function(i, group) {
                            if (group.children && Array.isArray(group.children)) {
                                $.each(group.children, function(j, child) {
                                    if (child.value == initialValue) {
                                        selectedOption = child;
                                        return false; // break inner
                                    }
                                });
                            }
                            if (selectedOption) return false; // break outer
                        });

                        if (selectedOption) {
                            var option = new Option(selectedOption.label, selectedOption.value, true, true);
                            $select.append(option).trigger('change');
                        }
                    });
                }
            },

            onBeforeDestroy: function() {
                var $select = this.ui.select;
                if (!$select || !$select.length) {
                    $select = this.$el.find('select');
                }
                if ($select.length && $select.data('select2')) {
                    $select.select2('destroy');
                }
            }
        });

        elementor.addControlView('fluent_product_variation_select', FluentProductVariationSelect);
    };

    // Attempt to init immediately
    initFluentCartControl();

    // Also listen to init just in case
    $(window).on('elementor:init', initFluentCartControl);

})(jQuery);
