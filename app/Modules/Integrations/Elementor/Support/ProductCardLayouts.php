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
     * @param string $customLabel Name of the widget's own card ('' option)
     * @return array<string, string> '' (the widget's own card) plus core's layouts, in core's order
     */
    public static function options($customLabel = '')
    {
        $options = ['' => $customLabel ?: esc_html__('Custom (Card Elements)', 'fluent-cart-elementor-blocks')];
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
    public static function registerControl($widget, $description = '', $customLabel = '')
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
                'options'     => self::options($customLabel),
                'description' => $description ?: esc_html__('A FluentCart layout builds the whole card, with its own badges, swatches and Quick View. Custom uses the card elements below.', 'fluent-cart-elementor-blocks'),
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

    /** Core's script that staggers a grid of the widget's own cards ships with the Masonry layout. */
    public static function masonryGridAvailable()
    {
        return self::available() && array_key_exists('masonry', ProductCardLayoutPresets::all());
    }

    /** A Masonry Grid switch for the widget's own cards; a card layout brings its own grid. */
    public static function registerMasonryGridControl($widget, array $condition)
    {
        if (!self::masonryGridAvailable()) {
            return;
        }

        $widget->add_control(
            'masonry_grid',
            [
                'label'        => esc_html__('Masonry Grid', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'default'      => '',
                'description'  => esc_html__('Staggers the cards, each at its own height, like the Masonry card layout.', 'fluent-cart-elementor-blocks'),
                'condition'    => $condition,
            ]
        );
    }

    /**
     * The wrapper attribute that makes core stagger the grid of the widget's own cards, with the script and
     * stylesheet that do it. '' when the switch is off or a card layout draws the cards.
     */
    public static function masonryGridAttribute(array $settings)
    {
        if (Arr::get($settings, 'masonry_grid') !== 'yes' || self::selected($settings) || !self::masonryGridAvailable()) {
            return '';
        }
        ProductCardLayoutPresets::enqueueAssets();

        return ' data-fct-masonry';
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
