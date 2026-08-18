<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use FluentCart\Api\Taxonomy;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers\ElementorShopAppHandler;
use FluentCartElementorBlocks\App\Services\Badges\BadgeRenderer;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\Framework\Support\Str;
use FluentCart\Framework\Support\Arr;

class ShopAppWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_shop_app';
    }

    public function get_title()
    {
        return esc_html__('Products', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-products fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['products', 'shop', 'store', 'commerce', 'fluent', 'grid', 'list'];
    }

    public function get_style_depends()
    {
        // Register+enqueue FluentCart product archive styles so they're
        // available in the Elementor editor preview iframe.
        AssetLoader::loadProductArchiveAssets();

        $app = \FluentCart\App\App::getInstance();
        $slug = $app->config->get('app.slug');

        return [
            'fluentcart-product-card-page-css',
            'fluentcart-single-product-css',
            'fluentcart-similar-product-css',
            'fluentcart-add-to-cart-btn-css',
            'fluentcart-direct-checkout-btn-css',
            $slug . '-fluentcart-product-page-css',
            $slug . '-fluentcart-product-filter-slider-css',
        ];
    }

    protected function register_controls()
    {
        $this->registerContentControls();
        $this->registerShopLayoutControls();
        $this->registerCardLayoutControls();
        $this->registerFilterControls();
        $this->registerDefaultFilterControls();
        // Sold Out panel is gated on the shop's allow_out_of_stock filter — an
        // out-of-stock card only appears in the grid when that is on.
        BadgeControls::registerBadgeContentControls($this, true, ['default_filter_allow_out_of_stock' => 'yes']);
        $this->registerStyleControls();
        BadgeControls::registerBadgeStyleControls($this, true, ['default_filter_allow_out_of_stock' => 'yes']);
    }

    private function registerContentControls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('General Settings', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'per_page',
            [
                'label'   => esc_html__('Products Per Page', 'fluent-cart'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 10,
                'min'     => 1,
                'max'     => 100,
                'step'    => 1,
            ]
        );

        $this->add_control(
            'view_mode',
            [
                'label'   => esc_html__('View Mode', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => esc_html__('Grid', 'fluent-cart'),
                    'list' => esc_html__('List', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'product_box_grid_size',
            [
                'label'   => esc_html__('Grid Columns', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => '4',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
            ]
        );

        $this->add_control(
            'paginator',
            [
                'label'   => esc_html__('Pagination Type', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'scroll',
                'options' => [
                    'scroll'  => esc_html__('Infinite Scroll', 'fluent-cart'),
                    'numbers' => esc_html__('Page Numbers', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'price_format',
            [
                'label'   => esc_html__('Price Format', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'starts_from',
                'options' => [
                    'starts_from' => esc_html__('Starts From', 'fluent-cart'),
                    'range'       => esc_html__('Range', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'use_default_style',
            [
                'label'        => esc_html__('Use Default Style', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    private function registerShopLayoutControls()
    {
        $this->start_controls_section(
            'shop_layout_section',
            [
                'label' => esc_html__('Shop Layout', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'element_type',
            [
                'label'   => esc_html__('Section', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'view_switcher',
                'options' => [
                    'view_switcher' => esc_html__('View Switcher', 'fluent-cart'),
                    'sort_by'       => esc_html__('Sort By', 'fluent-cart'),
                    'filter'        => esc_html__('Filter', 'fluent-cart'),
                    'product_grid'  => esc_html__('Product Grid', 'fluent-cart'),
                    'paginator'     => esc_html__('Paginator', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'shop_layout',
            [
                'label'       => esc_html__('Layout Sections', 'fluent-cart'),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [
                    ['element_type' => 'view_switcher'],
                    ['element_type' => 'sort_by'],
                    ['element_type' => 'filter'],
                    ['element_type' => 'product_grid'],
                    ['element_type' => 'paginator'],
                ],
                'title_field' => '{{{ {"view_switcher":"View Switcher","sort_by":"Sort By","filter":"Filter","product_grid":"Product Grid","paginator":"Paginator"}[element_type] || element_type }}}',
            ]
        );

        $this->end_controls_section();
    }

    private function registerCardLayoutControls()
    {
        $this->start_controls_section(
            'card_layout_section',
            [
                'label' => esc_html__('Product Card Layout', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'element_type',
            [
                'label'   => esc_html__('Element', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'image',
                'options' => [
                    'image'   => esc_html__('Image', 'fluent-cart'),
                    'title'   => esc_html__('Title', 'fluent-cart'),
                    'excerpt' => esc_html__('Excerpt', 'fluent-cart'),
                    'price'   => esc_html__('Price', 'fluent-cart'),
                    'button'  => esc_html__('Button', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'card_elements',
            [
                'label'       => esc_html__('Card Elements', 'fluent-cart'),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [
                    ['element_type' => 'image'],
                    ['element_type' => 'title'],
                    ['element_type' => 'price'],
                    ['element_type' => 'button'],
                ],
                'title_field' => '{{{ element_type.charAt(0).toUpperCase() + element_type.slice(1) }}}',
            ]
        );

        $this->end_controls_section();
    }

    private function registerFilterControls()
    {
        $this->start_controls_section(
            'filter_section',
            [
                'label' => esc_html__('Filter Settings', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'enable_filter',
            [
                'label'        => esc_html__('Enable Filter', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'live_filter',
            [
                'label'        => esc_html__('Live Filter', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => esc_html__('Apply filters instantly without a submit button.', 'fluent-cart'),
                'condition'    => [
                    'enable_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'enable_wildcard_filter',
            [
                'label'        => esc_html__('Wildcard Filter', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'enable_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'enable_wildcard_for_post_content',
            [
                'label'        => esc_html__('Search in Post Content', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'enable_filter'          => 'yes',
                    'enable_wildcard_filter' => 'yes',
                ],
            ]
        );

        // Per-taxonomy toggle + display name controls
        $taxonomies = Taxonomy::getTaxonomies();
        foreach ($taxonomies as $taxonomy) {
            $label = esc_html(Str::headline($taxonomy));
            $key = sanitize_key(str_replace('-', '_', $taxonomy));

            $this->add_control(
                'enable_taxonomy_' . $key,
                [
                    'label'        => $label,
                    'type'         => Controls_Manager::SWITCHER,
                    'label_on'     => esc_html__('Yes', 'fluent-cart'),
                    'label_off'    => esc_html__('No', 'fluent-cart'),
                    'return_value' => 'yes',
                    'default'      => '',
                    'separator'    => 'before',
                    'condition'    => [
                        'enable_filter' => 'yes',
                    ],
                ]
            );

            $this->add_control(
                'taxonomy_label_' . $key,
                [
                    'label'     => esc_html__('Display Name', 'fluent-cart'),
                    'type'      => Controls_Manager::TEXT,
                    'default'   => $label,
                    'condition' => [
                        'enable_filter'           => 'yes',
                        'enable_taxonomy_' . $key => 'yes',
                    ],
                ]
            );
        }

        // Price Range toggle + display name
        $this->add_control(
            'enable_price_range_filter',
            [
                'label'        => esc_html__('Price Range', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
                'separator'    => 'before',
                'condition'    => [
                    'enable_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'price_range_label',
            [
                'label'     => esc_html__('Display Name', 'fluent-cart'),
                'type'      => Controls_Manager::TEXT,
                'default'   => esc_html__('Price', 'fluent-cart'),
                'condition' => [
                    'enable_filter'             => 'yes',
                    'enable_price_range_filter' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerDefaultFilterControls()
    {
        $this->start_controls_section(
            'default_filter_section',
            [
                'label' => esc_html__('Default Filter', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'default_filter_enabled',
            [
                'label'        => esc_html__('Enable Default Filter', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => esc_html__('Presets applied to the product query by default, independent of the visitor-facing filter above.', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'default_filter_allow_out_of_stock',
            [
                'label'        => esc_html__('Allow Out Of Stock', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'default_filter_enabled' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'default_filter_wildcard',
            [
                'label'     => esc_html__('Search', 'fluent-cart'),
                'type'      => Controls_Manager::TEXT,
                'default'   => '',
                'condition' => [
                    'default_filter_enabled' => 'yes',
                ],
            ]
        );

        $taxonomies = Taxonomy::getTaxonomies();
        foreach ($taxonomies as $taxonomy) {
            $label = esc_html(Str::headline($taxonomy));
            $key = sanitize_key(str_replace('-', '_', $taxonomy));

            $options = [];
            $flattenTerms = function ($terms) use (&$flattenTerms, &$options) {
                foreach ($terms as $term) {
                    $options[$term['value']] = $term['label'];
                    if (!empty($term['children'])) {
                        $flattenTerms($term['children']);
                    }
                }
            };
            $flattenTerms(Taxonomy::getFormattedTerms($taxonomy));

            $this->add_control(
                'default_filter_taxonomy_' . $key,
                [
                    'label'     => $label,
                    'type'      => Controls_Manager::SELECT2,
                    'multiple'  => true,
                    'options'   => $options,
                    'default'   => [],
                    'condition' => [
                        'default_filter_enabled' => 'yes',
                    ],
                ]
            );
        }

        $this->end_controls_section();
    }

    private function registerStyleControls()
    {
        $this->start_controls_section(
            'card_style_section',
            [
                'label' => esc_html__('Product Card', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        ProductCardWidget::registerCardStyleControls($this, '{{WRAPPER}} .fct-product-card');
        $this->end_controls_section();

        $this->registerGridStyleControls();

        $this->start_controls_section(
            'image_style_section',
            [
                'label' => esc_html__('Product Image', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        ProductCardWidget::registerCardImageStyleControls($this, '{{WRAPPER}} .fct-product-card-image');
        $this->end_controls_section();

        $this->start_controls_section(
            'title_style_section',
            [
                'label' => esc_html__('Product Title', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        ProductCardWidget::registerCardTitleStyleControls($this, '{{WRAPPER}} .fct-product-card-title');
        $this->end_controls_section();

        $this->start_controls_section(
            'excerpt_style_section',
            [
                'label' => esc_html__('Product Excerpt', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        ProductCardWidget::registerCardExcerptStyleControls($this, '{{WRAPPER}} .fct-product-card-excerpt');
        $this->end_controls_section();

        $this->start_controls_section(
            'price_style_section',
            [
                'label' => esc_html__('Product Price', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        ProductCardWidget::registerCardPriceStyleControls($this, '{{WRAPPER}} .fct-product-card-prices');
        $this->end_controls_section();

        $btnSelector = '{{WRAPPER}} .fct-product-card .fct-product-view-button, {{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button';
        $btnHoverSelector = '{{WRAPPER}} .fct-product-card .fct-product-view-button:hover, {{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button:hover';

        $this->start_controls_section(
            'button_style_section',
            [
                'label' => esc_html__('Product Button', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        ProductCardWidget::registerCardButtonStyleControls($this, $btnSelector, $btnHoverSelector);
        $this->end_controls_section();

        $this->registerFilterStyleControls();
        $this->registerPaginationStyleControls();
    }

    private function registerGridStyleControls()
    {
        $this->start_controls_section(
            'grid_style_section',
            [
                'label' => esc_html__('Grid Layout', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'grid_column_gap',
            [
                'label'      => esc_html__('Column Gap', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 80],
                    'em' => ['min' => 0, 'max' => 5],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .fct-products-container' => 'column-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'grid_row_gap',
            [
                'label'      => esc_html__('Row Gap', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 80],
                    'em' => ['min' => 0, 'max' => 5],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .fct-products-container' => 'row-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerFilterStyleControls()
    {
        $this->start_controls_section(
            'filter_style_section',
            [
                'label' => esc_html__('Filter', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'filter_heading_typography',
                'label'    => esc_html__('Heading Typography', 'fluent-cart'),
                'selector' => '{{WRAPPER}} .fct-shop-filter-form .item-heading',
            ]
        );

        $this->add_control(
            'filter_heading_color',
            [
                'label'     => esc_html__('Heading Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-shop-filter-form .item-heading' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_checkbox_color',
            [
                'label'     => esc_html__('Checkbox Label Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-shop-checkbox' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_search_bg',
            [
                'label'     => esc_html__('Search Background', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-shop-product-search .fct-shop-input' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_search_border_color',
            [
                'label'     => esc_html__('Search Border Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-shop-product-search .fct-shop-input' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_apply_btn_heading',
            [
                'label'     => esc_html__('Apply Button', 'fluent-cart'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'filter_apply_btn_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-shop-apply-filter-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_apply_btn_bg',
            [
                'label'     => esc_html__('Background', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-shop-apply-filter-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerPaginationStyleControls()
    {
        $this->start_controls_section(
            'pagination_style_section',
            [
                'label' => esc_html__('Pagination', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'pagination_typography',
                'selector' => '{{WRAPPER}} .fct-shop-paginator',
            ]
        );

        $this->add_control(
            'pagination_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-shop-paginator'                          => 'color: {{VALUE}};',
                    '{{WRAPPER}} .fct-shop-paginator-pager button'             => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'pagination_active_color',
            [
                'label'     => esc_html__('Active Page Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-shop-paginator-pager .active button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'pagination_active_bg',
            [
                'label'     => esc_html__('Active Page Background', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-shop-paginator-pager .active button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'pagination_spacing',
            [
                'label'      => esc_html__('Top Spacing', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .fct-shop-paginator' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $isEditor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        AssetLoader::loadProductArchiveAssets();

        $enableFilter = ($settings['enable_filter'] ?? '') === 'yes';

        // Build custom_filters for ShopAppRenderer when filtering is enabled
        // The renderer uses custom_filters (not the shortcode 'filters' attribute) to
        // determine isFilterEnabled and which taxonomy filters to display.
        $customFilters = [];
        $filters = [];
        $liveFilter = ($settings['live_filter'] ?? '') === 'yes';
        $allTaxonomies = Taxonomy::getTaxonomies();

        if ($enableFilter) {
            $enablePriceRange = ($settings['enable_price_range_filter'] ?? '') === 'yes';

            // Build taxonomy list from individual per-taxonomy toggles
            $enabledTaxonomies = [];
            foreach ($allTaxonomies as $taxonomy) {
                $key = sanitize_key(str_replace('-', '_', $taxonomy));
                if (($settings['enable_taxonomy_' . $key] ?? '') === 'yes') {
                    $enabledTaxonomies[] = $taxonomy;
                }
            }

            // custom_filters drives the ShopAppRenderer filter UI
            $customFilters = [
                'enabled'     => true,
                'live_filter' => $liveFilter,
                'taxonomies'  => $enabledTaxonomies,
                'price_range' => $enablePriceRange,
            ];

            // filters drives the ShopAppHandler query-level filter config
            foreach ($enabledTaxonomies as $taxonomy) {
                $key = sanitize_key(str_replace('-', '_', $taxonomy));
                $label = sanitize_text_field($settings['taxonomy_label_' . $key] ?? Str::headline($taxonomy));

                $filters[$taxonomy] = [
                    'enabled'     => true,
                    'filter_type' => 'options',
                    'is_meta'     => true,
                    'label'       => $label,
                    'multiple'    => false,
                ];
            }

            if ($enablePriceRange) {
                $priceLabel = sanitize_text_field($settings['price_range_label'] ?? __('Price', 'fluent-cart'));
                $filters['price_range'] = [
                    'enabled'     => true,
                    'filter_type' => 'range',
                    'is_meta'     => false,
                    'label'       => $priceLabel,
                ];
            }
        }

        // Build default_filters (server-baked query presets, independent of the visitor filter UI)
        $defaultFilterEnabled = ($settings['default_filter_enabled'] ?? '') === 'yes';
        $defaultFilters = ['enabled' => $defaultFilterEnabled];

        if ($defaultFilterEnabled) {
            $defaultFilters['allow_out_of_stock'] = ($settings['default_filter_allow_out_of_stock'] ?? '') === 'yes';
            $defaultFilters['wildcard'] = sanitize_text_field($settings['default_filter_wildcard'] ?? '');

            foreach ($allTaxonomies as $taxonomy) {
                $key = sanitize_key(str_replace('-', '_', $taxonomy));
                $selectedTerms = $settings['default_filter_taxonomy_' . $key] ?? [];
                if (!empty($selectedTerms)) {
                    $defaultFilters[$taxonomy] = array_map('strval', (array) $selectedTerms);
                }
            }
        }

        // Build shortcode attributes from widget settings
        $shortcodeAtts = [
            'per_page'                         => $settings['per_page'] ?? 10,
            'view_mode'                        => $settings['view_mode'] ?? 'grid',
            'paginator'                        => $settings['paginator'] ?? 'scroll',
            'price_format'                     => $settings['price_format'] ?? 'starts_from',
            'product_box_grid_size'            => $settings['product_box_grid_size'] ?? 4,
            'product_grid_size'                => $settings['product_box_grid_size'] ?? 4,
            'use_default_style'                => ($settings['use_default_style'] ?? '') === 'yes' ? 1 : 0,
            'enable_filter'                    => $enableFilter ? 1 : 0,
            'live_filter'                      => $liveFilter ? 1 : 0,
            'enable_wildcard_filter'           => ($settings['enable_wildcard_filter'] ?? '') === 'yes' ? 1 : 0,
            'enable_wildcard_for_post_content' => ($settings['enable_wildcard_for_post_content'] ?? '') === 'yes' ? 1 : 0,
            'filters'                          => $filters,
            'custom_filters'                   => $customFilters,
            'default_filters'                  => $defaultFilters,
        ];

        // Extract card layout elements from the repeater
        $cardElements = $settings['card_elements'] ?? [
            ['element_type' => 'image'],
            ['element_type' => 'title'],
            ['element_type' => 'price'],
            ['element_type' => 'button'],
        ];

        // Extract shop layout sections from the repeater
        $shopLayout = $settings['shop_layout'] ?? [
            ['element_type' => 'view_switcher'],
            ['element_type' => 'sort_by'],
            ['element_type' => 'filter'],
            ['element_type' => 'product_grid'],
            ['element_type' => 'paginator'],
        ];

        // Sale + Sold Out badge settings that drive the overlay closures. Kept
        // in the cache key so toggling a badge (or changing its text/style/
        // position) invalidates the cached HTML — otherwise the transient below
        // would serve pre-badge markup.
        // Sold Out only makes sense when out-of-stock products actually appear in
        // the grid. FluentCart filters them out unless allow_out_of_stock is on
        // (ProductQuery: "when false, show only in-stock products"), so gate the
        // badge on the same flag — otherwise it advertises a state no visible
        // card can ever be in.
        $allowOutOfStock = Arr::get($settings, 'default_filter_allow_out_of_stock', '') === 'yes';

        $badgeSettings = [
            'show_sale_badge'         => Arr::get($settings, 'show_sale_badge', ''),
            'sale_badge_style'        => Arr::get($settings, 'sale_badge_style', ''),
            'sale_badge_position'     => Arr::get($settings, 'sale_badge_position', ''),
            'sale_badge_text'         => Arr::get($settings, 'sale_badge_text', ''),
            'show_percentage'         => Arr::get($settings, 'show_percentage', ''),
            'sale_percentage_text'    => Arr::get($settings, 'sale_percentage_text', ''),
            'sale_price_source'       => Arr::get($settings, 'sale_price_source', ''),
            'show_sold_out_badge'     => $allowOutOfStock ? Arr::get($settings, 'show_sold_out_badge', '') : '',
            'sold_out_badge_style'    => Arr::get($settings, 'sold_out_badge_style', ''),
            'sold_out_badge_position' => Arr::get($settings, 'sold_out_badge_position', ''),
            'sold_out_badge_text'     => Arr::get($settings, 'sold_out_badge_text', ''),
        ];

        // Build a transient cache key based on the relevant settings. The
        // archive term must be part of the key: on product taxonomy archives
        // the handler scopes the query to the current term (applyArchiveScope),
        // so the same widget renders different products per category/brand —
        // without the term, one archive's HTML would be served on all of them.
        $fluentCartTaxonomies = get_object_taxonomies('fluent-products');
        $archiveTermId = $fluentCartTaxonomies && is_tax($fluentCartTaxonomies) ? get_queried_object_id() : 0;
        $cacheKey = 'fce_shop_app_' . md5(wp_json_encode($shortcodeAtts) . wp_json_encode($cardElements) . wp_json_encode($shopLayout) . wp_json_encode($badgeSettings) . '|term:' . $archiveTermId);

        if (!$isEditor) {
            $cached = get_transient($cacheKey);

            if ($cached) {
                echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                return;
            }
        }

        // Sale + Sold Out badge overlays via core's before/after_image_block
        // hooks (fire inside each card's renderProductImage). The closures
        // buffer each card image and re-wrap it so the badge overlays the
        // IMAGE, not the whole card. Scoped: removed right after the render.
        // Page 2+ (AJAX) re-renders through the addon's own pagination provider
        // (ElementorIntegration::preloadProductCollectionsAjax), which re-applies
        // these same closures from the badge settings the handler caches
        // alongside the card layout — keeping the configured card elements.
        $badgeHooks = BadgeRenderer::cardBadgeClosures($badgeSettings, true);
        if ($badgeHooks) {
            add_action('fluent_cart/product/group/before_image_block', $badgeHooks['before'], 10, 1);
            add_action('fluent_cart/product/group/after_image_block', $badgeHooks['after'], 10, 1);
        }

        $handler = new ElementorShopAppHandler();
        $handler->setCardElements($cardElements);
        $handler->setShopLayout($shopLayout);
        $handler->setBadgeSettings($badgeSettings);
        $output  = $handler->handelShortcodeCall($shortcodeAtts);

        BadgeRenderer::removeCardBadgeHooks($badgeHooks);

        $html = '<div class="fluent-cart-elementor-shop-app">' . $output . '</div>';

        if (!$isEditor) {
            set_transient($cacheKey, $html, 4 * HOUR_IN_SECONDS);
        }

        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
