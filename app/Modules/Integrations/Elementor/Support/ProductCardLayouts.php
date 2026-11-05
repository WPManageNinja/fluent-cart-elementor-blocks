<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support;

use Elementor\Controls_Manager;
use FluentCart\App\Modules\Data\ProductDataSetup;
use FluentCart\App\Services\Renderer\ProductCardLayoutPresets;
use FluentCart\App\Services\Renderer\ProductCardRender;
use FluentCart\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core's product card layouts (Compact, Masonry, Floating, Framed). In the widgets "Classic" is
 * each widget's own card, saved as '' (or 'classic'), so core's Classic preset is not offered.
 * Needs a FluentCart core that ships ProductCardLayoutPresets; on an older core the picker stays hidden.
 */
class ProductCardLayouts
{
    /** The widget's own card: '' as saved before, or a saved core 'classic'. */
    const WIDGET_CARD = 'classic';

    public static function available()
    {
        return class_exists(ProductCardLayoutPresets::class)
            && method_exists(ProductCardLayoutPresets::class, 'all')
            && method_exists(ProductCardLayoutPresets::class, 'cardBlock');
    }

    /** @return array<string, string> Classic ('' = the widget's own card) plus core's other layouts, in core's order */
    public static function options()
    {
        $options = ['' => esc_html__('Classic', 'fluent-cart-elementor-blocks')];
        if (!self::available()) {
            return $options;
        }

        foreach (ProductCardLayoutPresets::all() as $layout => $preset) {
            if ($layout !== self::WIDGET_CARD) {
                $options[$layout] = (string) Arr::get($preset, 'label', $layout);
            }
        }

        return $options;
    }

    /**
     * The Card Layout picker, in the widget's open section.
     *
     * @return array Condition for the widget's own card controls: shown only for Classic
     */
    public static function registerControl($widget, $description = '')
    {
        if (!self::available()) {
            return [];
        }

        $widget->add_control(
            'card_layout',
            [
                'label'       => esc_html__('Card Layout', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SELECT,
                'default'     => '',
                'options'     => self::options(),
                'description' => $description ?: esc_html__('Classic is this widget\'s own card, with all of its card settings. A layout builds the whole card, with its own badges, swatches and Quick View.', 'fluent-cart-elementor-blocks'),
            ]
        );

        return ['card_layout' => ''];
    }

    /** Before a loop of layout cards: their styles, and the products their swatches batch over. */
    public static function prepareCards($products)
    {
        ProductCardLayoutPresets::enqueueAssets();
        if (method_exists(ProductDataSetup::class, 'setProductsCache')) {
            ProductDataSetup::setProductsCache($products);
        }
    }

    /** One layout card, through core's card renderer, with the widget's layout card settings. */
    public static function renderCard($product, $layout, $priceFormat, array $options = [])
    {
        (new ProductCardRender($product, [
            'card_layout'         => $layout,
            'layout_price_format' => $priceFormat,
            'layout_options'      => $options,
        ]))->render();
    }

    /** Whether core takes a builder's swatch, badge and rating settings for layout cards. */
    public static function cardOptionsAvailable()
    {
        return self::available() && method_exists(ProductCardLayoutPresets::class, 'withCardOptions');
    }

    /**
     * A layout card's Swatches, Badge and Product Rating switches, each in its own section and on
     * until switched off. Classic keeps its own sections; these show only for a layout.
     */
    public static function registerLayoutCardSections($widget, $withSoldOut = true)
    {
        if (!self::cardOptionsAvailable()) {
            return;
        }
        $condition = ['card_layout!' => ''];
        $switch = function ($name, $label) use ($widget) {
            $widget->add_control($name, [
                'label'        => $label,
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]);
        };
        $sections = [
            'layout_card_swatches_section' => [esc_html__('Swatches', 'fluent-cart-elementor-blocks'), ['layout_show_swatches' => esc_html__('Show Swatches', 'fluent-cart-elementor-blocks')]],
            'layout_card_badge_section'    => [esc_html__('Badge', 'fluent-cart-elementor-blocks'), array_filter([
                'layout_show_sale_badge'     => esc_html__('Show Sale Badge', 'fluent-cart-elementor-blocks'),
                'layout_show_sold_out_badge' => $withSoldOut ? esc_html__('Show Sold Out Badge', 'fluent-cart-elementor-blocks') : '',
            ])],
            'layout_card_rating_section'   => [esc_html__('Product Rating', 'fluent-cart-elementor-blocks'), ['layout_show_rating' => esc_html__('Show Rating', 'fluent-cart-elementor-blocks')]],
        ];
        foreach ($sections as $id => $section) {
            $widget->start_controls_section($id, ['label' => $section[0], 'tab' => Controls_Manager::TAB_CONTENT, 'condition' => $condition]);
            foreach ($section[1] as $name => $label) {
                $switch($name, $label);
            }
            $widget->end_controls_section();
        }
    }

    /** ProductCardRender's layout_options from the widget's layout card switches. */
    public static function cardOptions(array $settings, $withSoldOut = true)
    {
        if (!self::cardOptionsAvailable()) {
            return [];
        }
        $isOn = function ($name) use ($settings) {
            return Arr::get($settings, $name, 'yes') === 'yes';
        };
        $options = [
            'swatches'   => $isOn('layout_show_swatches') ? null : false,
            'sale_badge' => $isOn('layout_show_sale_badge'),
            'rating'     => $isOn('layout_show_rating'),
        ];
        if ($withSoldOut) {
            $options['sold_out_badge'] = $isOn('layout_show_sold_out_badge');
        }

        // Swatches on keeps the layout's own swatch block, so only "off" is passed.
        return array_filter($options, function ($value) {
            return $value !== null;
        });
    }

    /** The same switches as [fluent_cart_related_products]'s layout_hide list. */
    public static function hiddenParts(array $settings, $withSoldOut = true)
    {
        return implode(',', array_keys(array_filter(self::cardOptions($settings, $withSoldOut), function ($on) {
            return $on === false;
        })));
    }

    /**
     * The visible card frame: the article for card elements and Classic, the inner frame for the
     * wrapped layouts, so the Product Card style controls reach both.
     */
    public static function cardFrameSelector($scope = '{{WRAPPER}}')
    {
        return $scope . ' .fct-product-card:not(:has(.fct-card-layout)), '
            . $scope . ' .fct-product-card .fct-card-layout.fct-card-layout';
    }

    /**
     * The card's purchase button, in card elements and in layout cards. The layout branch outranks
     * core's per-layout button colours, (0,6,1) under body:not(.fluent-cart-no-colors).
     */
    public static function buttonSelector($scope = '{{WRAPPER}}', $state = '')
    {
        $selectors = [];
        foreach (['.fct-product-view-button', '.fluent-cart-add-to-cart-button'] as $button) {
            $selectors[] = $scope . ' .fct-product-card ' . $button . $state;
        }
        $selectors[] = self::layoutButtonSelector($scope, $state);

        return implode(', ', $selectors);
    }

    /** Only the layout cards' purchase button, for widgets that build their own button selector list. */
    public static function layoutButtonSelector($scope = '{{WRAPPER}}', $state = '')
    {
        return $scope . ' .fct-product-card .fct-card-layout.fct-card-layout .fct-card-layout-details .fct-card-layout-footer :is(.fct-product-view-button, .fluent-cart-add-to-cart-button)' . $state;
    }

    /** The saved layout when core can render it, else '' for the widget's own card elements. */
    public static function selected(array $settings)
    {
        $layout = sanitize_key((string) Arr::get($settings, 'card_layout', ''));
        if ($layout === '' || $layout === self::WIDGET_CARD || !self::available()) {
            return '';
        }

        return ProductCardLayoutPresets::cardBlock($layout) ? $layout : '';
    }
}
