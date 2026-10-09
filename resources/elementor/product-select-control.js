(function($) {
    var initialized = false;

    // Translated editor strings come from wp_localize_script (fluentCartElementor.i18n).
    // The English argument is the fallback for the window where the global is
    // not yet printed, so the control still renders a usable placeholder.
    var getEditorString = function(key, fallback) {
        var strings = (window.fluentCartElementor || {}).i18n || {};

        return strings[key] || fallback;
    };

    var initFluentProductSelectControl = function() {
        if (initialized) {
            return;
        }

        if (!window.elementor || !elementor.modules || !elementor.modules.controls) {
            return;
        }

        initialized = true;

        // The review widgets hide their Write a Review settings for several products.
        // Elementor's conditions cannot count a list, so this keeps a flag for them.
        // Other widgets have no review_several_products setting and are left alone.
        var syncSeveralProducts = function(settings) {
            if (!settings || settings.get('review_several_products') === undefined) {
                return;
            }

            var value = settings.get('product_id');
            var count = Array.isArray(value) ? value.filter(Boolean).length : (value ? 1 : 0);
            var flag = count > 1 ? 'yes' : '';

            if (settings.get('review_several_products') !== flag) {
                settings.set('review_several_products', flag);
            }
        };

        // On opening, whichever section shows: the picker may not be drawn at all.
        if (elementor.hooks) {
            elementor.hooks.addAction('panel/open_editor/widget', function(panel, model) {
                syncSeveralProducts(model && model.get('settings'));
            });
        }

        var ControlBaseData = elementor.modules.controls.BaseData;

        if (!ControlBaseData) {
            console.error('Fluent Cart: ControlBaseData not found');
            return;
        }

        var FluentProductSelect = ControlBaseData.extend({
            ui: function() {
                return {
                    select: 'select'
                };
            },

            onReady: function() {
                if (typeof fluentCartElementor === 'undefined') {
                    console.error('Fluent Cart: fluentCartElementor is undefined');
                    return;
                }

                var self = this;
                var $select = this.ui.select;

                if (!$select || !$select.length) {
                    $select = this.$el.find('select');
                }

                if (!$select.length) {
                    return;
                }

                // Destroy any Select2 instance Elementor auto-inited on .elementor-select2
                // before we reinitialise with our own AJAX options.
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }

                var isMultiple = this.model.get('multiple') !== false;

                var options = {
                    allowClear: true,
                    multiple: isMultiple,
                    placeholder: this.model.get('placeholder') || getEditorString('searchProducts', 'Search for products...'),
                    dir: (window.elementorCommon && elementorCommon.config && elementorCommon.config.isRTL) ? 'rtl' : 'ltr',
                    ajax: {
                        url: fluentCartElementor.restUrl + 'products',
                        dataType: 'json',
                        delay: 300,
                        headers: {
                            'X-WP-Nonce': fluentCartElementor.nonce
                        },
                        data: function(params) {
                            return {
                                search: params.term,
                                page: params.page || 1,
                                per_page: 10,
                                order_by: 'ID',
                                order_type: 'DESC',
                                active_view: 'publish'
                            };
                        },
                        processResults: function(data, params) {
                            var results = [];
                            var products = data.products && data.products.data ? data.products.data : [];

                            if (Array.isArray(products)) {
                                $.each(products, function(i, product) {
                                    results.push({
                                        id: product.ID,
                                        text: product.post_title,
                                        thumbnail: product.detail && product.detail.featured_media ? product.detail.featured_media.url : null
                                    });
                                });
                            }

                            return {
                                results: results,
                                pagination: {
                                    more: data.products && data.products.next_page_url
                                }
                            };
                        },
                        cache: true
                    },
                    // 0 (not 1) so opening the control immediately shows the
                    // product list (the AJAX runs with an empty search term and
                    // the products endpoint returns the latest published ones),
                    // like the Gutenberg picker — instead of "enter 1 or more
                    // characters". Typing still filters.
                    minimumInputLength: 0,
                    templateResult: function(product) {
                        if (product.loading) {
                            return product.text;
                        }

                        var $container = $(
                            '<div class="fluent-product-select-result">' +
                                '<span class="fluent-product-select-title"></span>' +
                            '</div>'
                        );

                        if (product.thumbnail) {
                            $container.prepend('<img class="fluent-product-select-thumb" src="' + product.thumbnail + '" />');
                        }

                        $container.find('.fluent-product-select-title').text(product.text);

                        return $container;
                    },
                    templateSelection: function(product) {
                        return product.text || product.id;
                    }
                };

                $select.select2(options);

                // And on every pick, while the picker is open.
                var settings = self.container ? self.container.settings : self.elementSettingsModel;

                if (settings && self.model.get('name') === 'product_id') {
                    this.listenTo(settings, 'change:product_id', function() {
                        syncSeveralProducts(settings);
                    });
                    syncSeveralProducts(settings);
                }

                // Fetch initial values if exist
                var initialValue = this.getControlValue();
                if (initialValue && (Array.isArray(initialValue) ? initialValue.length : initialValue)) {
                    var productIds = Array.isArray(initialValue) ? initialValue : [initialValue];

                    $.ajax({
                        url: fluentCartElementor.restUrl + 'products/fetchProductsByIds',
                        dataType: 'json',
                        headers: {
                            'X-WP-Nonce': fluentCartElementor.nonce
                        },
                        data: {
                            productIds: productIds,
                            with: ['detail']
                        }
                    }).then(function(data) {
                        var products = data.products && data.products.data
                            ? data.products.data
                            : (Array.isArray(data.products) ? data.products : []);

                        if (Array.isArray(products)) {
                            $.each(products, function(i, product) {
                                var option = new Option(product.post_title, product.ID, true, true);
                                $select.append(option);
                            });
                            $select.trigger('change.select2');
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

        elementor.addControlView('fluent_product_select', FluentProductSelect);
    };

    // Attempt to init immediately
    initFluentProductSelectControl();

    // Also listen to init just in case
    $(window).on('elementor:init', initFluentProductSelectControl);

})(jQuery);