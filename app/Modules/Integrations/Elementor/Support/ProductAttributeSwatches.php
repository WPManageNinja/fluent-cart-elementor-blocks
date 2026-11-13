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

    /** The most attribute filters core shows on one Products widget, after its limit filter. */
    public static function filterGroupLimit()
    {
        if (method_exists(ShopAttributeFilter::class, 'maxGroups')) {
            return ShopAttributeFilter::maxGroups();
        }

        return defined(ShopAttributeFilter::class . '::MAX_GROUPS') ? (int) ShopAttributeFilter::MAX_GROUPS : 10;
    }

    /**
     * Attribute filter groups, capped at core's limit so the picker and the storefront agree.
     *
     * @param mixed $value Saved select value
     * @return int[]
     */
    public static function filterGroupIds($value)
    {
        return array_slice(self::groupIds($value), 0, self::filterGroupLimit());
    }

    /**
     * The Gutenberg Product Swatches settings, in its panels' order, plus its margin/padding supports.
     *
     * @param \Elementor\Controls_Stack|\Elementor\Repeater $host
     * @param array  $condition Extra condition for every control
     * @param string $selector  Selector of the swatch rows wrapper
     */
    public static function registerSwatchControls($host, array $condition, $selector)
    {
        $advancedOn = array_merge($condition, ['swatch_advanced_enabled' => 'yes']);
        $simpleOn = array_merge($condition, ['swatch_simple_enabled' => 'yes']);

        // A tab per product type, as in the Gutenberg swatches settings. Each tab's switch hides the rest of it.
        $host->start_controls_tabs('swatch_tabs', ['condition' => $condition]);

        $host->start_controls_tab('swatch_tab_attribute', ['label' => esc_html__('Attribute Products', 'fluent-cart-elementor-blocks'), 'condition' => $condition]);

        $host->add_control(
            'swatch_advanced_enabled',
            [
                'label'        => esc_html__('Show Attributes on the Card', 'fluent-cart-elementor-blocks'),
                'description'  => esc_html__('Products with attributes such as Color and Size. One row per attribute.', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => $condition,
            ]
        );

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
                'condition'   => $advancedOn,
            ]
        );

        $host->add_control(
            'swatch_group_card_attribute',
            [
                'label'     => esc_html__('How It Shows on the Card', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => $advancedOn,
            ]
        );

        $host->add_control(
            'swatch_max_visible',
            [
                'label'      => esc_html__('Show Up To', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [''],
                'range'      => ['' => ['min' => 1, 'max' => 12, 'step' => 1]],
                'description' => esc_html__('Extras show as "+2", "+3"…', 'fluent-cart-elementor-blocks'),
                'default'    => ['size' => 3, 'unit' => ''],
                'condition'  => array_merge($advancedOn, ['swatch_max_visible_all' => '']),
            ]
        );

        $host->add_control(
            'swatch_max_visible_all',
            [
                'label'        => esc_html__('Show All', 'fluent-cart-elementor-blocks'),
                'description'  => esc_html__('Every option shows on the card.', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => $advancedOn,
            ]
        );

        $host->add_control(
            'swatch_show_group_name',
            [
                'label'        => esc_html__('Show Label', 'fluent-cart-elementor-blocks'),
                'description'  => esc_html__('e.g. Color above the row.', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => $advancedOn,
            ]
        );

        // One setting for both product types, so it is not hidden with this tab: List products read it too.
        $host->add_control(
            'swatch_show_label',
            [
                'label'        => esc_html__('Show the Selected Choice', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'description'  => esc_html__('Prints the picked value after the row label on both product types, e.g. COLOR · BLACK.', 'fluent-cart-elementor-blocks'),
                'condition'    => $condition,
            ]
        );

        $host->add_control(
            'swatch_selected_position',
            [
                'label'       => esc_html__('Where It Appears', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'name',
                'options'     => self::positionOptions(),
                // Attribute rows are not drawn while this tab's switch is off, so the position does nothing then.
                'condition'   => array_merge($advancedOn, ['swatch_show_label' => 'yes']),
            ]
        );

        $host->end_controls_tab();

        $host->start_controls_tab('swatch_tab_list', ['label' => esc_html__('List Products', 'fluent-cart-elementor-blocks'), 'condition' => $condition]);

        $host->add_control(
            'swatch_simple_enabled',
            [
                'label'        => esc_html__('Show the Option List on the Card', 'fluent-cart-elementor-blocks'),
                'description'  => esc_html__('Products with a plain list of variants, such as pack sizes. One row of their names.', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => $condition,
            ]
        );

        $host->add_control(
            'swatch_group_card_list',
            [
                'label'     => esc_html__('How It Shows on the Card', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => $simpleOn,
            ]
        );

        $host->add_control(
            'swatch_simple_max_visible',
            [
                'label'      => esc_html__('Show Up To', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [''],
                'range'      => ['' => ['min' => 1, 'max' => 12, 'step' => 1]],
                'description' => esc_html__('Extras show as "+2", "+3"…', 'fluent-cart-elementor-blocks'),
                'default'    => ['size' => 4, 'unit' => ''],
                'condition'  => array_merge($simpleOn, ['swatch_simple_max_visible_all' => '']),
            ]
        );

        $host->add_control(
            'swatch_simple_max_visible_all',
            [
                'label'        => esc_html__('Show All', 'fluent-cart-elementor-blocks'),
                'description'  => esc_html__('Every option shows on the card.', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => $simpleOn,
            ]
        );

        $host->add_control(
            'swatch_simple_show_label',
            [
                'label'        => esc_html__('Show Label', 'fluent-cart-elementor-blocks'),
                'description'  => esc_html__('e.g. Option above the row.', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => $simpleOn,
            ]
        );

        $host->add_control(
            'swatch_simple_label',
            [
                'label'       => esc_html__('Label Name', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::TEXT,
                'default'     => '',
                // Quick picks under the field (swatch-label-chips.js), as in the Gutenberg label name.
                'classes'     => 'fct-swatch-label-chips',
                'label_block' => true,
                'ai'          => ['active' => false],
                'placeholder' => esc_html__('Option', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Empty shows "Option".', 'fluent-cart-elementor-blocks'),
                'condition'   => array_merge($simpleOn, ['swatch_simple_show_label' => 'yes']),
            ]
        );

        // Left on Same as Attribute Products, list products follow the attribute tab's switch, so a page saved
        // before this setting looks the same. A select, not a switch, because a switch cannot be unset.
        $host->add_control(
            'swatch_simple_show_selected',
            [
                'label'     => esc_html__('Show the Selected Choice', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => '',
                'options'   => [
                    ''    => esc_html__('Same as Attribute Products', 'fluent-cart-elementor-blocks'),
                    'yes' => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                    'no'  => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                ],
                'condition' => $simpleOn,
            ]
        );

        // List products show the selected choice when their own select says Show, or is left on Same as
        // Attribute Products while that tab shows it. A plain condition cannot say "or", so it becomes terms.
        $positionTerms = [];
        foreach ($simpleOn as $key => $value) {
            $positionTerms[] = ['name' => $key, 'operator' => '==', 'value' => $value];
        }
        $positionTerms[] = ['relation' => 'or', 'terms' => [
            ['name' => 'swatch_simple_show_selected', 'operator' => '==', 'value' => 'yes'],
            ['relation' => 'and', 'terms' => [
                ['name' => 'swatch_simple_show_selected', 'operator' => '==', 'value' => ''],
                ['name' => 'swatch_show_label', 'operator' => '==', 'value' => 'yes'],
            ]],
        ]];

        $host->add_control(
            'swatch_simple_selected_position',
            [
                'label'      => esc_html__('Where It Appears', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SELECT,
                'default'    => 'name',
                'options'     => self::positionOptions(),
                'conditions' => ['relation' => 'and', 'terms' => $positionTerms],
            ]
        );

        $host->end_controls_tab();

        $host->end_controls_tabs();

        // One setting for both product types, so it sits below the tabs, as in Gutenberg.
        $host->add_control(
            'swatch_sold_out_display',
            [
                'label'       => esc_html__('Sold-out Options', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::CHOOSE,
                'toggle'      => false,
                'default'     => 'normal',
                'separator'   => 'before',
                'options'     => self::soldOutOptions(),
                'description' => esc_html__('No Change, Cross Out or Hide, for both attribute and simple variations.', 'fluent-cart-elementor-blocks'),
                'condition'   => $condition,
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

    /** Where the selected choice appears; the layout cards and Classic offer the same two places. */
    public static function positionOptions()
    {
        return [
            'name'  => esc_html__('After the Label', 'fluent-cart-elementor-blocks'),
            'below' => esc_html__('Below the Options', 'fluent-cart-elementor-blocks'),
        ];
    }

    /** The sold-out choices, as a segmented control; the layout cards and Classic offer the same three. */
    public static function soldOutOptions()
    {
        return [
            'normal' => ['title' => esc_html__('No Change', 'fluent-cart-elementor-blocks'), 'icon' => 'eicon-check'],
            'cross'  => ['title' => esc_html__('Cross Out', 'fluent-cart-elementor-blocks'), 'icon' => 'eicon-close'],
            'hide'   => ['title' => esc_html__('Hide', 'fluent-cart-elementor-blocks'), 'icon' => 'eicon-minus-circle'],
        ];
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

        // Assets with the markup, so every widget that draws swatches gets their stylesheet.
        self::enqueueAssets();

        $maxVisible = Arr::get($settings, 'swatch_max_visible', 3);
        if (is_array($maxVisible)) {
            $maxVisible = Arr::get($maxVisible, 'size', 3);
        }

        $class = trim('fct-card-swatch-rows ' . $extraClass);

        $simpleMaxVisible = Arr::get($settings, 'swatch_simple_max_visible', 4);
        if (is_array($simpleMaxVisible)) {
            $simpleMaxVisible = Arr::get($simpleMaxVisible, 'size', 4);
        }

        $soldOut = Arr::get($settings, 'swatch_sold_out_display');

        // Widgets saved before these switches existed have no value; they stay on, as Gutenberg defaults them.
        $swatchSettings = [
            'group_ids'          => self::groupIds(Arr::get($settings, 'swatch_group_ids', [])),
            // Show All is 0 in the swatches block, whatever the number says.
            'max_visible'        => Arr::get($settings, 'swatch_max_visible_all') === 'yes' ? 0 : max(1, min(12, (int) ($maxVisible ?: 3))),
            'show_label'         => Arr::get($settings, 'swatch_show_label') === 'yes',
            'show_group_name'    => Arr::get($settings, 'swatch_show_group_name') === 'yes',
            'advanced_enabled'   => Arr::get($settings, 'swatch_advanced_enabled', 'yes') === 'yes',
            'simple_enabled'     => Arr::get($settings, 'swatch_simple_enabled', 'yes') === 'yes',
            'simple_max_visible' => Arr::get($settings, 'swatch_simple_max_visible_all') === 'yes' ? 0 : max(1, min(12, (int) ($simpleMaxVisible ?: 4))),
            'simple_show_label'  => Arr::get($settings, 'swatch_simple_show_label') === 'yes',
            'simple_label'       => sanitize_text_field((string) Arr::get($settings, 'swatch_simple_label', '')),
            // An unknown value keeps the swatches as before; core ignores it too.
            'sold_out_display'   => in_array($soldOut, ['normal', 'cross', 'hide'], true) ? $soldOut : 'normal',
            'selected_position'        => Arr::get($settings, 'swatch_selected_position') === 'below' ? 'below' : 'name',
            'simple_selected_position' => Arr::get($settings, 'swatch_simple_selected_position') === 'below' ? 'below' : 'name',
        ];
        // Left on Same as Attribute Products, core makes list products follow the attribute tab's switch.
        $ownSelected = Arr::get($settings, 'swatch_simple_show_selected');
        if ($ownSelected === 'yes' || $ownSelected === 'no') {
            $swatchSettings['simple_show_selected'] = $ownSelected === 'yes';
        }

        return ProductCardSwatchesRenderer::render($product, $swatchSettings, 'class="' . esc_attr($class) . '"');
    }
}
