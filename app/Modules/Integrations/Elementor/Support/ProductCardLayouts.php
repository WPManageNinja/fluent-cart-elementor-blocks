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
 * Core's product card layouts (Classic, Compact, Floating, Framed). Needs a FluentCart core
 * that ships ProductCardLayoutPresets; on an older core the picker stays hidden.
 */
class ProductCardLayouts
{
    public static function available()
    {
        return class_exists(ProductCardLayoutPresets::class)
            && method_exists(ProductCardLayoutPresets::class, 'all')
            && method_exists(ProductCardLayoutPresets::class, 'cardBlock');
    }

    /**
     * @return array<string, string> '' (the widget's own card elements) plus core's layouts, in core's order
     */
    public static function options()
    {
        $options = ['' => esc_html__('Custom (Card Elements)', 'fluent-cart-elementor-blocks')];
        if (!self::available()) {
            return $options;
        }

        foreach (ProductCardLayoutPresets::all() as $layout => $preset) {
            $options[$layout] = (string) Arr::get($preset, 'label', $layout);
        }

        return $options;
    }

    /**
     * The Card Layout picker, in the widget's open section.
     *
     * @return array Condition for the card element controls: shown only for Custom
     */
    public static function registerControl($widget)
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
                'description' => esc_html__('A FluentCart layout builds the whole card, with its own badges, swatches and Quick View. Custom uses the card elements below.', 'fluent-cart-elementor-blocks'),
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

    /** One layout card, through core's card renderer. */
    public static function renderCard($product, $layout, $priceFormat)
    {
        (new ProductCardRender($product, [
            'card_layout'         => $layout,
            'layout_price_format' => $priceFormat,
        ]))->render();
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
        $buttons = ['.fct-product-view-button', '.fluent-cart-add-to-cart-button'];
        $selectors = [];
        foreach ($buttons as $button) {
            $selectors[] = $scope . ' .fct-product-card ' . $button . $state;
        }
        $selectors[] = $scope . ' .fct-product-card .fct-card-layout.fct-card-layout .fct-card-layout-details .fct-card-layout-footer :is(' . implode(', ', $buttons) . ')' . $state;

        return implode(', ', $selectors);
    }

    /** The saved layout when core can render it, else '' for the widget's own card elements. */
    public static function selected(array $settings)
    {
        $layout = sanitize_key((string) Arr::get($settings, 'card_layout', ''));
        if ($layout === '' || !self::available()) {
            return '';
        }

        return ProductCardLayoutPresets::cardBlock($layout) ? $layout : '';
    }
}
