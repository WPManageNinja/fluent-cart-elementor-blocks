(function($) {
    var initialized = false;

    var initFluentProductSelectControl = function() {
        if (initialized) {
            return;
        }

        if (!window.elementor || !elementor.modules || !elementor.modules.controls) {
            return;
        }

        initialized = true;

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

                var isMultiple = this.model.get('multiple') !== false;

                var options = {
                    allowClear: true,
                    multiple: isMultiple,
                    placeholder: this.model.get('placeholder') || 'Search for products...',
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
                    minimumInputLength: 1,
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
                            $select.trigger('change');
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