<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support;

use Elementor\Controls_Manager;
use FluentCart\App\Services\Filter\ShopAttributeFilter;
use FluentCart\App\Services\Renderer\ProductCardSwatchesRenderer;
use FluentCart\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Attribute filters and card swatches. Both need a FluentCart core that ships
 * ShopAttributeFilter and ProductCardSwatchesRenderer; on an older core the controls stay hidden.
 */
class ProductAttributeSwatches
{
    private static $groupOptions = null;

    public static function filtersAvailable()
    {
        return class_exists(ShopAttributeFilter::class)
            && method_exists(ShopAttributeFilter::class, 'groupOptions')
            && method_exists(ShopAttributeFilter::class, 'filterConfig');
    }

    public static function swatchesAvailable()
    {
        return self::filtersAvailable()
            && class_exists(ProductCardSwatchesRenderer::class)
            && method_exists(ProductCardSwatchesRenderer::class, 'render');
    }

    /**
     * @return array<int, string> group id => title, library order
     */
    public static function groupOptions()
    {
        if (self::$groupOptions !== null) {
            return self::$groupOptions;
        }

        self::$groupOptions = [];
        if (!self::filtersAvailable()) {
            return self::$groupOptions;
        }

        foreach (ShopAttributeFilter::groupOptions() as $group) {
            self::$groupOptions[(int) $group['id']] = $group['title'];
        }

        return self::$groupOptions;
    }

    /**
     * The card element option, empty when core cannot render swatches.
     */
    public static function cardElementOption()
    {
        return self::swatchesAvailable()
            ? ['swatches' => esc_html__('Swatches', 'fluent-cart-elementor-blocks')]
            : [];
    }

    /**
     * @param mixed $value Saved select value
     * @return int[]
     */
    public static function groupIds($value)
    {
        $ids = array_map('absint', array_slice((array) $value, 0, 20));

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * The four Gutenberg swatch settings plus its align/margin/padding supports.
     *
     * @param \Elementor\Controls_Stack|\Elementor\Repeater $host
     * @param array  $condition Extra condition for every control
     * @param string $selector  Selector of the swatch rows wrapper
     */
    public static function registerSwatchControls($host, array $condition, $selector)
    {
        $host->add_control(
            'swatch_group_ids',
            [
                'label'       => esc_html__('Attributes', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SELECT2,
                'multiple'    => true,
                'label_block' => true,
                'options'     => self::groupOptions(),
                'default'     => [],
                'select2options' => ['placeholder' => esc_html__('All attributes', 'fluent-cart-elementor-blocks')],
                'description' => esc_html__('Empty shows every attribute.', 'fluent-cart-elementor-blocks'),
                'condition'   => $condition,
            ]
        );

        $host->add_control(
            'swatch_max_visible',
            [
                'label'      => esc_html__('Max Swatches', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [''],
                'range'      => ['' => ['min' => 1, 'max' => 6, 'step' => 1]],
                'default'    => ['size' => 3, 'unit' => ''],
                'condition'  => $condition,
            ]
        );

        $host->add_control(
            'swatch_show_group_name',
            [
                'label'        => esc_html__('Show Attribute Label', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => $condition,
            ]
        );

        $host->add_control(
            'swatch_show_label',
            [
                'label'        => esc_html__('Show Selected Name', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => $condition,
            ]
        );

        $host->add_responsive_control(
            'swatch_margin',
            [
                'label'      => esc_html__('Margin', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem', '%'],
                'selectors'  => [
                    $selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition'  => $condition,
            ]
        );

        $host->add_responsive_control(
            'swatch_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', 'rem'],
                'selectors'  => [
                    $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition'  => $condition,
            ]
        );
    }

    public static function enqueueAssets()
    {
        if (self::swatchesAvailable()) {
            ProductCardSwatchesRenderer::enqueueAssets();
        }
    }

    /**
     * @param \FluentCart\App\Models\Product $product
     * @param array  $settings   Saved swatch_* settings
     * @param string $extraClass Extra wrapper class, e.g. the repeater item class
     * @return string
     */
    public static function render($product, array $settings, $extraClass = '')
    {
        if (!$product || !self::swatchesAvailable()) {
            return '';
        }

        $maxVisible = Arr::get($settings, 'swatch_max_visible', 3);
        if (is_array($maxVisible)) {
            $maxVisible = Arr::get($maxVisible, 'size', 3);
        }

        $class = trim('fct-card-swatch-rows ' . $extraClass);

        return ProductCardSwatchesRenderer::render($product, [
            'group_ids'       => self::groupIds(Arr::get($settings, 'swatch_group_ids', [])),
            'max_visible'     => max(1, min(6, (int) ($maxVisible ?: 3))),
            'show_label'      => Arr::get($settings, 'swatch_show_label') === 'yes',
            'show_group_name' => Arr::get($settings, 'swatch_show_group_name') === 'yes',
        ], 'class="' . esc_attr($class) . '"');
    }
}
