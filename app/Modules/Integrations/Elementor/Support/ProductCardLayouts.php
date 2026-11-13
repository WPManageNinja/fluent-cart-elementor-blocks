<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
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

    /** The layout whose cards are already staggered. */
    const MASONRY_LAYOUT = 'masonry';

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

    /**
     * The Masonry switch, for the widgets that draw their cards in a grid (as Gutenberg shows it only in grid
     * loops). It staggers a layout's cards; the Masonry layout is already staggered and Classic has no such look.
     */
    public static function registerMasonryControl($widget)
    {
        if (!self::available()) {
            return;
        }

        $widget->add_control(
            'layout_masonry',
            [
                'label'        => esc_html__('Masonry', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => esc_html__('Staggers the cards, each image keeping its own height.', 'fluent-cart-elementor-blocks'),
                'condition'    => ['card_layout' => array_values(array_diff(array_keys(self::options()), ['', self::MASONRY_LAYOUT]))],
            ]
        );
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
     * Whether core gives Classic and every layout the same card settings (Card Elements, badge,
     * swatch, rating and Quick View); the newest of those core methods stands for all of them.
     */
    public static function cardSettingsAvailable()
    {
        return self::cardOptionsAvailable()
            && method_exists(ProductCardLayoutPresets::class, 'classicCardParts')
            && method_exists(ProductCardRender::class, 'renderCardPart');
    }

    /** @return array<string, string> Card part => label, for Card Elements lists. */
    public static function partLabels()
    {
        return [
            'image'      => esc_html__('Image', 'fluent-cart-elementor-blocks'),
            'quick_view' => esc_html__('Quick View', 'fluent-cart-elementor-blocks'),
            'category'   => esc_html__('Category', 'fluent-cart-elementor-blocks'),
            'rating'     => esc_html__('Rating', 'fluent-cart-elementor-blocks'),
            'title'      => esc_html__('Title', 'fluent-cart-elementor-blocks'),
            'excerpt'    => esc_html__('Excerpt', 'fluent-cart-elementor-blocks'),
            'swatches'   => esc_html__('Swatches', 'fluent-cart-elementor-blocks'),
            'price'      => esc_html__('Price', 'fluent-cart-elementor-blocks'),
            'button'     => esc_html__('Button', 'fluent-cart-elementor-blocks'),
        ];
    }

    /** A repeater row title that names the element as its option label does. */
    public static function elementTitleField()
    {
        return '{{{ (' . wp_json_encode(self::partLabels()) . ')[element_type] || element_type }}}';
    }

    /** Rating, Swatches and Quick View options for a Classic Card Elements list, with this core. */
    public static function classicElementOptions()
    {
        if (!self::cardSettingsAvailable()) {
            return [];
        }

        return array_intersect_key(self::partLabels(), array_flip(['rating', 'swatches', 'quick_view']));
    }

    /**
     * A Classic Card Elements default: core's Classic layout for a new widget, or $old on a core
     * without the parts. Elementor saves the list when a widget is added, so one saved before keeps its own.
     */
    public static function classicElementsDefault(array $old)
    {
        if (!self::cardSettingsAvailable()) {
            return $old;
        }
        $parts = array_values(array_diff(ProductCardLayoutPresets::classicCardParts(), ['excerpt']));
        if (!ProductAttributeSwatches::swatchesAvailable()) {
            $parts = array_values(array_diff($parts, ['swatches']));
        }

        return array_map(function ($part) {
            return ['element_type' => $part];
        }, $parts);
    }

    /**
     * The Classic Card Elements to draw. Elementor fills an unsaved repeater from its default, so a
     * widget saved before the list had Rating, Swatches and Quick View keeps $old.
     */
    public static function classicElements($widget, array $settings, array $old)
    {
        $raw = method_exists($widget, 'get_data') ? (array) $widget->get_data('settings') : [];
        if (!array_key_exists('card_elements', $raw)) {
            return $old;
        }

        return (array) Arr::get($settings, 'card_elements', $old);
    }

    /**
     * Classic's rows with the settings each part needs to draw itself, so they travel with the rows
     * (the Products widget's AJAX pages redraw from them). Rating and Quick View carry Classic's
     * settings; a Swatches row saved with its own swatch settings keeps them, any other takes the
     * widget's Classic Swatches settings.
     */
    public static function withClassicPartSettings($widget, array $rows, array $settings)
    {
        if (!self::cardSettingsAvailable()) {
            return $rows;
        }
        $raw = method_exists($widget, 'get_data') ? array_values((array) Arr::get((array) $widget->get_data('settings'), 'card_elements', [])) : [];
        $saved = [];
        foreach ($raw as $position => $row) {
            if (is_array($row)) {
                $saved[!empty($row['_id']) ? $row['_id'] : '#' . $position] = $row;
            }
        }
        $swatchSettings = array_filter($settings, function ($key) {
            return strpos((string) $key, 'swatch_') === 0;
        }, ARRAY_FILTER_USE_KEY);

        $position = 0;
        foreach ($rows as $index => $row) {
            $type = is_array($row) ? Arr::get($row, 'element_type', '') : '';
            // The saved row, by its id, else by its place in the list.
            $savedKey = is_array($row) && !empty($row['_id']) ? $row['_id'] : '#' . $position;
            $position++;
            if ($type === 'rating' || $type === 'quick_view') {
                $rows[$index]['_part_settings'] = self::classicPartSettings($settings, $type);
            } elseif ($type === 'swatches') {
                $own = array_filter((array) Arr::get($saved, $savedKey, []), function ($key) {
                    return strpos((string) $key, 'swatch_') === 0;
                }, ARRAY_FILTER_USE_KEY);
                $rows[$index] = $own ? array_merge($row, $own) : array_merge($row, $swatchSettings);
            }
        }

        return $rows;
    }

    /** A Rating or Quick View row of Classic's card, through core's block with the row's settings. */
    public static function renderClassicRow($cardRender, array $row)
    {
        if (!self::cardSettingsAvailable() || !is_array(Arr::get($row, '_part_settings'))) {
            return '';
        }

        return $cardRender->renderCardPart((string) Arr::get($row, 'element_type', ''), $row['_part_settings']);
    }

    /** A Card Elements list for each layout, in the Card Layout section, holding its own parts. */
    public static function registerLayoutElementsControls($widget)
    {
        if (!self::cardSettingsAvailable()) {
            return;
        }
        foreach (array_keys(ProductCardLayoutPresets::all()) as $layout) {
            $parts = ProductCardLayoutPresets::cardParts($layout);
            if (!$parts) {
                continue;
            }
            $repeater = new Repeater();
            $repeater->add_control('element_type', [
                'label'   => esc_html__('Element', 'fluent-cart-elementor-blocks'),
                'type'    => Controls_Manager::SELECT,
                'default' => $parts[0],
                'options' => array_intersect_key(array_replace(array_flip($parts), self::partLabels()), array_flip($parts)),
            ]);
            $widget->add_control('layout_elements_' . $layout, [
                'label'       => esc_html__('Card Elements', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => array_map(function ($part) {
                    return ['element_type' => $part];
                }, $parts),
                'title_field' => self::elementTitleField(),
                'description' => esc_html__('Each element moves within its own area of the card. Empty shows them all.', 'fluent-cart-elementor-blocks'),
                'condition'   => ['card_layout' => $layout],
            ]);
        }
    }

    /**
     * Swatches, Product Rating and Quick View sections in the Content tab, with each layout's own
     * settings and, for the parts in $classicParts, Classic's. Classic's swatches are the add-on's
     * own swatch settings ($classicSwatchSelector), since its card draws them with the add-on renderer.
     */
    public static function registerPartSections($widget, array $classicParts = [], $classicSwatchSelector = '')
    {
        if (!self::cardSettingsAvailable()) {
            return;
        }
        $titles = [
            'swatches'   => esc_html__('Swatches', 'fluent-cart-elementor-blocks'),
            'rating'     => esc_html__('Product Rating', 'fluent-cart-elementor-blocks'),
            'quick_view' => esc_html__('Quick View', 'fluent-cart-elementor-blocks'),
        ];
        foreach ($titles as $part => $title) {
            $layouts = self::layoutsWith($part);
            $withClassic = in_array($part, $classicParts, true) && ($part !== 'swatches' || ProductAttributeSwatches::swatchesAvailable());
            if (!$layouts && !$withClassic) {
                continue;
            }
            $widget->start_controls_section('layout_' . $part . '_section', [
                'label'     => $title,
                'tab'       => Controls_Manager::TAB_CONTENT,
                'condition' => ['card_layout' => array_merge($withClassic ? [''] : [], $layouts)],
            ]);
            if ($withClassic && $part === 'swatches') {
                ProductAttributeSwatches::registerSwatchControls($widget, ['card_layout' => ''], $classicSwatchSelector);
            } elseif ($withClassic) {
                self::registerPartControls($widget, $part, self::WIDGET_CARD, ['card_layout' => '']);
            }
            foreach ($layouts as $layout) {
                self::registerPartControls($widget, $part, $layout, ['card_layout' => $layout]);
            }
            $widget->end_controls_section();
        }
    }

    /** Style sections for Swatches, Product Rating and Quick View; the same for Classic and every layout. */
    public static function registerPartStyleSections($widget)
    {
        if (!self::cardSettingsAvailable()) {
            return;
        }
        $sections = [
            'layout_swatches_style_section'   => [esc_html__('Swatches', 'fluent-cart-elementor-blocks'), [
                ['swatches_typography', 'typography', esc_html__('Typography', 'fluent-cart-elementor-blocks'), '{{WRAPPER}} .fct-card-swatch'],
                ['swatches_label_typography', 'typography', esc_html__('Label Typography', 'fluent-cart-elementor-blocks'), '{{WRAPPER}} .fct-card-swatch-heading'],
                ['swatches_border', 'border', '', '{{WRAPPER}} .fct-card-swatch'],
            ]],
            'layout_rating_style_section'     => [esc_html__('Product Rating', 'fluent-cart-elementor-blocks'), [
                ['rating_star_color', 'color', esc_html__('Star Color', 'fluent-cart-elementor-blocks'), '{{WRAPPER}} .fct-product-card-rating .fct-star-filled, {{WRAPPER}} .fct-product-card-rating .fct-star-half'],
                ['rating_empty_star_color', 'color', esc_html__('Empty Star Color', 'fluent-cart-elementor-blocks'), '{{WRAPPER}} .fct-product-card-rating .fct-star:not(.fct-star-filled):not(.fct-star-half)'],
                ['rating_count_typography', 'typography', esc_html__('Count Typography', 'fluent-cart-elementor-blocks'), '{{WRAPPER}} .fct-product-card-review-count'],
            ]],
            'layout_quick_view_style_section' => [esc_html__('Quick View', 'fluent-cart-elementor-blocks'), [
                ['quick_view_typography', 'typography', esc_html__('Typography', 'fluent-cart-elementor-blocks'), '{{WRAPPER}} .fct-card-quick-view-button'],
                ['quick_view_text_color', 'color', esc_html__('Text Color', 'fluent-cart-elementor-blocks'), '{{WRAPPER}} .fct-card-quick-view-button'],
                ['quick_view_background', 'background-color', esc_html__('Background Color', 'fluent-cart-elementor-blocks'), '{{WRAPPER}} .fct-card-quick-view-button'],
                ['quick_view_border', 'border', '', '{{WRAPPER}} .fct-card-quick-view-button'],
            ]],
        ];
        foreach ($sections as $id => $section) {
            $widget->start_controls_section($id, ['label' => $section[0], 'tab' => Controls_Manager::TAB_STYLE]);
            foreach ($section[1] as $control) {
                list($name, $type, $label, $selector) = $control;
                if ($type === 'typography') {
                    $widget->add_group_control(Group_Control_Typography::get_type(), ['name' => $name, 'label' => $label, 'selector' => $selector]);
                } elseif ($type === 'border') {
                    $widget->add_group_control(Group_Control_Border::get_type(), ['name' => $name, 'selector' => $selector]);
                } else {
                    $widget->add_control($name, [
                        'label'     => $label,
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [$selector => ($type === 'color' ? 'color' : 'background-color') . ': {{VALUE}};'],
                    ]);
                }
            }
            $widget->end_controls_section();
        }
    }

    /**
     * ProductCardRender's layout_options from the selected layout's own settings: its Card
     * Elements, its badge copies ("layout_sale_badge_text"), and its Swatches, Rating and Quick View.
     */
    public static function cardOptions(array $settings, $withSoldOut = true, $inGrid = false)
    {
        $layout = self::selected($settings);
        if (!$layout || !self::cardOptionsAvailable()) {
            return [];
        }
        $options = [];
        // Only the widgets with a Masonry switch pass $inGrid; a value left in a Carousel or Product Card is ignored.
        if ($inGrid && $layout !== self::MASONRY_LAYOUT && Arr::get($settings, 'layout_masonry') === 'yes') {
            $options['masonry'] = true;
        }
        $rows = Arr::get($settings, 'layout_elements_' . $layout);
        if (is_array($rows) && $rows) {
            $options['elements'] = array_values(array_filter(array_map(function ($row) {
                return is_array($row) ? (string) Arr::get($row, 'element_type', '') : '';
            }, $rows)));
        }
        foreach (self::BADGE_SETTINGS as $part => $map) {
            if ($part === 'sold_out_badge' && !$withSoldOut) {
                continue;
            }
            $toggle = 'layout_' . key($map);
            if (Arr::get($settings, $toggle, 'yes') !== 'yes') {
                $options[$part] = false;
                continue;
            }
            $badge = [];
            foreach (array_slice($map, 1, null, true) as $key => $attribute) {
                if (array_key_exists('layout_' . $key, $settings)) {
                    $badge[$attribute] = $attribute === 'show_percentage' ? $settings['layout_' . $key] === 'yes' : $settings['layout_' . $key];
                }
            }
            if ($badge) {
                $options[$part] = $badge;
            }
        }
        foreach (array_keys(self::partSettings()) as $part) {
            $values = self::partValues($settings, $part, $layout);
            if ($values) {
                $options[$part] = $values;
            }
        }

        return $options;
    }

    /** Classic's own Product Rating or Quick View settings, for ProductCardRender::renderCardPart(). */
    public static function classicPartSettings(array $settings, $part)
    {
        return self::partValues($settings, $part, self::WIDGET_CARD);
    }

    /** The Classic badge settings => the badge block's; the first is the Show switch. */
    const BADGE_SETTINGS = [
        'sale_badge'     => [
            'show_sale_badge'      => null,
            'sale_badge_text'      => 'badge_text',
            'show_percentage'      => 'show_percentage',
            'sale_percentage_text' => 'percentage_text',
            'sale_price_source'    => 'price_source',
            'sale_badge_style'     => 'badge_style',
            'sale_badge_position'  => 'badge_position',
        ],
        'sold_out_badge' => [
            'show_sold_out_badge'     => null,
            'sold_out_badge_text'     => 'badge_text',
            'sold_out_badge_style'    => 'badge_style',
            'sold_out_badge_position' => 'badge_position',
        ],
    ];

    /** The layouts whose card has this part. */
    private static function layoutsWith($part)
    {
        return array_values(array_filter(array_keys(ProductCardLayoutPresets::all()), function ($layout) use ($part) {
            return in_array($part, ProductCardLayoutPresets::cardParts($layout), true);
        }));
    }

    private static function partKey($part, $name, $layout)
    {
        return 'layout_' . $part . '_' . $name . '_' . $layout;
    }

    /** One layout's (or Classic's) controls for a part, pre-filled with its own values. */
    private static function registerPartControls($widget, $part, $layout, array $condition)
    {
        $values = ProductCardLayoutPresets::partAttributes($layout, $part);
        $tabs = self::partTabs($part);
        $openTab = null;
        $lastGroup = null;
        $tabsOpen = false;
        foreach (self::partSettings()[$part] as $name => $entry) {
            // An entry naming a tab this part does not have is drawn outside the tabs.
            $tab = isset($entry['tab'], $tabs[$entry['tab']]) ? $entry['tab'] : null;
            if ($tab !== $openTab) {
                if ($openTab !== null) {
                    $widget->end_controls_tab();
                }
                if ($tab !== null) {
                    if (!$tabsOpen) {
                        $widget->start_controls_tabs(self::partKey($part, 'tabs', $layout), ['condition' => $condition]);
                        $tabsOpen = true;
                    }
                    $widget->start_controls_tab(self::partKey($part, 'tab_' . $tab, $layout), ['label' => $tabs[$tab]['title'], 'condition' => $condition]);
                } elseif ($tabsOpen) {
                    $widget->end_controls_tabs();
                    $tabsOpen = false;
                }
                $openTab = $tab;
                $lastGroup = null;
            }
            if ($tab !== null && ($entry['group'] ?? null) === 'card' && $lastGroup !== 'card') {
                $widget->add_control(self::partKey($part, 'group_card_' . $tab, $layout), [
                    'label'     => esc_html__('How It Shows on the Card', 'fluent-cart-elementor-blocks'),
                    'type'      => Controls_Manager::HEADING,
                    'separator' => 'before',
                    'condition' => $condition + [self::partKey($part, $tabs[$tab]['switch'], $layout) => 'yes'],
                ]);
            }
            $lastGroup = $entry['group'] ?? null;
            $control = $entry['control'] + ['condition' => $condition];
            // Filled here, so reading saved settings on a render never queries the attribute groups.
            if ($name === 'group_ids') {
                $control['options'] = ProductAttributeSwatches::groupOptions();
            }
            if (isset($entry['attribute'])) {
                $value = $values[$entry['attribute']] ?? $entry['default'];
                if ($control['type'] === Controls_Manager::SWITCHER) {
                    $value = $value ? 'yes' : '';
                    $control += ['label_on' => esc_html__('Show', 'fluent-cart-elementor-blocks'), 'label_off' => esc_html__('Hide', 'fluent-cart-elementor-blocks'), 'return_value' => 'yes'];
                }
                $control['default'] = $value;
            }
            if (!empty($entry['onImage'])) {
                $control['condition'][self::partKey($part, 'position', $layout)] = 'image';
            }
            // The number is hidden while its All switch is on.
            if (!empty($entry['allSwitch'])) {
                $control['condition'][self::partKey($part, $entry['allSwitch'], $layout)] = '';
            }
            if ($tab !== null && $name !== $tabs[$tab]['switch']) {
                $control['condition'][self::partKey($part, $tabs[$tab]['switch'], $layout)] = 'yes';
            }
            if (!empty($entry['shownWith'])) {
                $control['condition'][self::partKey($part, $entry['shownWith'], $layout)] = 'yes';
            }
            if (!empty($entry['shownWhenSelected'])) {
                // List Products show the selected choice when their own select says Show, or is left on Same as
                // Attribute Products while that tab shows it. A plain condition cannot say "or", so it becomes terms.
                $all = [];
                foreach ($control['condition'] as $key => $value) {
                    $all[] = ['name' => $key, 'operator' => '==', 'value' => $value];
                }
                $own = self::partKey($part, 'simple_show_selected', $layout);
                $all[] = ['relation' => 'or', 'terms' => [
                    ['name' => $own, 'operator' => '==', 'value' => 'yes'],
                    ['relation' => 'and', 'terms' => [
                        ['name' => $own, 'operator' => '==', 'value' => ''],
                        ['name' => self::partKey($part, 'show_label', $layout), 'operator' => '==', 'value' => 'yes'],
                    ]],
                ]];
                $control['conditions'] = ['relation' => 'and', 'terms' => $all];
                unset($control['condition']);
            }
            $widget->add_control(self::partKey($part, $name, $layout), $control);
        }
        if ($openTab !== null) {
            $widget->end_controls_tab();
        }
        if ($tabsOpen) {
            $widget->end_controls_tabs();
        }
    }

    /** A part's tabs, each with its title and the switch that hides the rest of it; only Swatches has them. */
    private static function partTabs($part)
    {
        if ($part !== 'swatches') {
            return [];
        }

        return [
            'attribute' => ['title' => esc_html__('Attribute Products', 'fluent-cart-elementor-blocks'), 'switch' => 'advanced_enabled'],
            'list'      => ['title' => esc_html__('List Products', 'fluent-cart-elementor-blocks'), 'switch' => 'simple_enabled'],
        ];
    }

    /** A part's saved settings for one layout (or Classic), as the block's own settings. */
    private static function partValues(array $settings, $part, $layout)
    {
        $values = [];
        foreach (self::partSettings()[$part] ?? [] as $name => $entry) {
            if (!isset($entry['attribute'])) {
                continue;
            }
            $key = self::partKey($part, $name, $layout);
            $isCount = !empty($entry['allSwitch']);
            // All is 0 in the swatches block, whatever number is saved beside it.
            if ($isCount && Arr::get($settings, self::partKey($part, $entry['allSwitch'], $layout)) === 'yes') {
                $values[$entry['attribute']] = 0;
                continue;
            }
            if (!array_key_exists($key, $settings)) {
                continue;
            }
            if ($isCount) {
                // A cleared number keeps the layout's own count; a typed 0 is one option, since only the switch means All.
                if ($settings[$key] !== '' && $settings[$key] !== null) {
                    $values[$entry['attribute']] = max(1, min(12, (int) $settings[$key]));
                }
                continue;
            }
            if (!empty($entry['unsetFollows'])) {
                // Left unset, core follows the attribute tab's switch.
                if ($settings[$key] === 'yes' || $settings[$key] === 'no') {
                    $values[$entry['attribute']] = $settings[$key] === 'yes';
                }
                continue;
            }
            $values[$entry['attribute']] = $entry['control']['type'] === Controls_Manager::SWITCHER ? $settings[$key] === 'yes' : $settings[$key];
        }

        return $values;
    }

    /**
     * Each part's settings: name => the block's attribute, the block's default, the control. Optional flags:
     * 'tab' and 'group' place it in a tab and a heading group; 'allSwitch' pairs a number with its All switch;
     * 'shownWith' shows it only while another setting is on; 'shownWhenSelected' follows the selected choice's
     * effective state; 'unsetFollows' is a Show/Hide/Same select that is only passed on when set.
     */
    private static function partSettings()
    {
        return [
            'swatches'   => [
                'advanced_enabled'   => ['tab' => 'attribute', 'attribute' => 'advanced_enabled', 'default' => true, 'control' => ['label' => esc_html__('Show Attributes on the Card', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SWITCHER, 'description' => esc_html__('Products with attributes such as Color and Size.', 'fluent-cart-elementor-blocks')]],
                'group_ids'          => ['tab' => 'attribute', 'attribute' => 'group_ids', 'default' => [], 'control' => ['label' => esc_html__('Attributes', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SELECT2, 'multiple' => true, 'label_block' => true, 'description' => esc_html__('Empty shows every attribute; rows follow the order you pick.', 'fluent-cart-elementor-blocks')]],
                'max_visible'        => ['tab' => 'attribute', 'group' => 'card', 'attribute' => 'max_visible', 'default' => 3, 'allSwitch' => 'max_visible_all', 'control' => ['label' => esc_html__('Show Up To', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 12, 'step' => 1, 'description' => esc_html__('Extras show as "+2", "+3"…', 'fluent-cart-elementor-blocks')]],
                'max_visible_all'    => ['tab' => 'attribute', 'group' => 'card', 'control' => ['label' => esc_html__('Show All', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'label_on' => esc_html__('Yes', 'fluent-cart-elementor-blocks'), 'label_off' => esc_html__('No', 'fluent-cart-elementor-blocks'), 'description' => esc_html__('Every option shows on the card.', 'fluent-cart-elementor-blocks')]],
                'show_group_name'    => ['tab' => 'attribute', 'group' => 'card', 'attribute' => 'show_group_name', 'default' => false, 'control' => ['label' => esc_html__('Show Label', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SWITCHER, 'description' => esc_html__('e.g. Color above the row.', 'fluent-cart-elementor-blocks')]],
                'show_label'         => ['tab' => 'attribute', 'group' => 'card', 'attribute' => 'show_label', 'default' => false, 'control' => ['label' => esc_html__('Show the Selected Choice', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SWITCHER, 'description' => esc_html__('e.g. Color · Navy.', 'fluent-cart-elementor-blocks')]],
                'selected_position'  => ['tab' => 'attribute', 'group' => 'card', 'shownWith' => 'show_label', 'attribute' => 'selected_position', 'default' => 'name', 'control' => ['label' => esc_html__('Where It Appears', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SELECT, 'options' => ['name' => esc_html__('After the Label', 'fluent-cart-elementor-blocks'), 'below' => esc_html__('Below the Options', 'fluent-cart-elementor-blocks')]]],
                'simple_enabled'     => ['tab' => 'list', 'attribute' => 'simple_enabled', 'default' => true, 'control' => ['label' => esc_html__('Show the Option List on the Card', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SWITCHER, 'description' => esc_html__('Products with a plain list of variants, such as pack sizes.', 'fluent-cart-elementor-blocks')]],
                'simple_max_visible' => ['tab' => 'list', 'group' => 'card', 'attribute' => 'simple_max_visible', 'default' => 4, 'allSwitch' => 'simple_max_visible_all', 'control' => ['label' => esc_html__('Show Up To', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::NUMBER, 'min' => 1, 'max' => 12, 'step' => 1, 'description' => esc_html__('Extras show as "+2", "+3"…', 'fluent-cart-elementor-blocks')]],
                'simple_max_visible_all' => ['tab' => 'list', 'group' => 'card', 'control' => ['label' => esc_html__('Show All', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'label_on' => esc_html__('Yes', 'fluent-cart-elementor-blocks'), 'label_off' => esc_html__('No', 'fluent-cart-elementor-blocks'), 'description' => esc_html__('Every option shows on the card.', 'fluent-cart-elementor-blocks')]],
                'simple_show_label'  => ['tab' => 'list', 'group' => 'card', 'attribute' => 'simple_show_label', 'default' => false, 'control' => ['label' => esc_html__('Show Label', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SWITCHER, 'description' => esc_html__('e.g. Option above the row.', 'fluent-cart-elementor-blocks')]],
                'simple_label'       => ['tab' => 'list', 'group' => 'card', 'shownWith' => 'simple_show_label', 'attribute' => 'simple_label', 'default' => '', 'control' => ['label' => esc_html__('Label Name', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::TEXT, 'classes' => 'fct-swatch-label-chips', 'label_block' => true, 'ai' => ['active' => false], 'description' => esc_html__('Empty shows "Option".', 'fluent-cart-elementor-blocks'), 'placeholder' => esc_html__('Option', 'fluent-cart-elementor-blocks')]],
                // Unset follows the attribute tab's switch, as a Gutenberg block saved before the split does; so a select, not a switch.
                'simple_show_selected' => ['tab' => 'list', 'group' => 'card', 'unsetFollows' => true, 'attribute' => 'simple_show_selected', 'default' => '', 'control' => ['label' => esc_html__('Show the Selected Choice', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SELECT, 'options' => ['' => esc_html__('Same as Attribute Products', 'fluent-cart-elementor-blocks'), 'yes' => esc_html__('Show', 'fluent-cart-elementor-blocks'), 'no' => esc_html__('Hide', 'fluent-cart-elementor-blocks')]]],
                'simple_selected_position' => ['tab' => 'list', 'group' => 'card', 'shownWhenSelected' => true, 'attribute' => 'simple_selected_position', 'default' => 'name', 'control' => ['label' => esc_html__('Where It Appears', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SELECT, 'options' => ['name' => esc_html__('After the Label', 'fluent-cart-elementor-blocks'), 'below' => esc_html__('Below the Options', 'fluent-cart-elementor-blocks')]]],
                'sold_out_display'   => ['attribute' => 'sold_out_display', 'default' => 'normal', 'control' => ['label' => esc_html__('Sold-out Options', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::CHOOSE, 'toggle' => false, 'separator' => 'before', 'options' => ['normal' => ['title' => esc_html__('No Change', 'fluent-cart-elementor-blocks'), 'icon' => 'eicon-check'], 'cross' => ['title' => esc_html__('Cross Out', 'fluent-cart-elementor-blocks'), 'icon' => 'eicon-close'], 'hide' => ['title' => esc_html__('Hide', 'fluent-cart-elementor-blocks'), 'icon' => 'eicon-minus-circle']], 'description' => esc_html__('No Change, Cross Out or Hide, for both attribute and simple variations.', 'fluent-cart-elementor-blocks')]],
            ],
            'rating'     => [
                'min_review_count'   => ['attribute' => 'minReviewCount', 'default' => 0, 'control' => ['label' => esc_html__('Minimum Reviews', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::NUMBER, 'min' => 0, 'description' => esc_html__('0 shows the stars on every product, even one with no reviews.', 'fluent-cart-elementor-blocks')]],
                'min_average_rating' => ['attribute' => 'minAverageRating', 'default' => 0, 'control' => ['label' => esc_html__('Minimum Average Rating', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::NUMBER, 'min' => 0, 'max' => 5, 'step' => 0.5]],
            ],
            'quick_view' => [
                'position' => ['attribute' => 'position', 'default' => 'button', 'control' => ['label' => esc_html__('Position', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SELECT, 'options' => ['image' => esc_html__('On the Image', 'fluent-cart-elementor-blocks'), 'button' => esc_html__('Beside the Button', 'fluent-cart-elementor-blocks')], 'description' => esc_html__('Beside the button it is always shown, as an icon.', 'fluent-cart-elementor-blocks')]],
                'image_position' => ['attribute' => 'image_position', 'default' => 'bottom-center', 'onImage' => true, 'control' => ['label' => esc_html__('Image Position', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SELECT, 'options' => ['top-left' => esc_html__('Top Left', 'fluent-cart-elementor-blocks'), 'top-center' => esc_html__('Top Middle', 'fluent-cart-elementor-blocks'), 'top-right' => esc_html__('Top Right', 'fluent-cart-elementor-blocks'), 'bottom-left' => esc_html__('Bottom Left', 'fluent-cart-elementor-blocks'), 'bottom-center' => esc_html__('Bottom Middle', 'fluent-cart-elementor-blocks'), 'bottom-right' => esc_html__('Bottom Right', 'fluent-cart-elementor-blocks')]]],
                'show_on'  => ['attribute' => 'show_on', 'default' => 'always', 'onImage' => true, 'control' => ['label' => esc_html__('Show', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SELECT, 'options' => ['always' => esc_html__('Always', 'fluent-cart-elementor-blocks'), 'hover' => esc_html__('On Hover', 'fluent-cart-elementor-blocks')]]],
                'display'  => ['attribute' => 'display', 'default' => 'icon', 'onImage' => true, 'control' => ['label' => esc_html__('Display', 'fluent-cart-elementor-blocks'), 'type' => Controls_Manager::SELECT, 'options' => ['icon' => esc_html__('Icon', 'fluent-cart-elementor-blocks'), 'icon_text' => esc_html__('Icon and Text', 'fluent-cart-elementor-blocks')]]],
            ],
        ];
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
