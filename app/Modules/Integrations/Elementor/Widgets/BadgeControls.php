<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shared Sale / Sold Out badge controls for the image-bearing widgets
 * (Product Card, Product Carousel, Products, Related Products). One place so
 * the control names/defaults stay identical across widgets — the render side
 * (BadgeRenderer::cardBadgeClosures) reads these exact setting keys.
 *
 * Matrix: Sale badge is available everywhere (default OFF); Sold Out is
 * Products/Carousel-only (a card catalog exposes stock, single-product-derived
 * widgets do not) — callers pass $includeSoldOut accordingly.
 *
 * All badge controls default OFF, so an existing widget instance is unchanged
 * until the user opts in.
 */
class BadgeControls
{
    const STYLE_OPTIONS = ['badge', 'ribbon', 'tag'];
    const POSITION_OPTIONS = ['top-left', 'top-right', 'bottom-left', 'bottom-right'];

    /**
     * Content-tab badge controls, laid out to MATCH the Gutenberg Sale Badge
     * inspector (BlockEditor/SaleBadge/Components/InspectorSettings.jsx): a
     * "Badge" panel followed by a "Position & Style" panel, with the
     * same labels, help text, option labels/values and order. Elementor adds an
     * explicit enable switcher first (Gutenberg enables the badge by inserting
     * the block; a widget toggle has no equivalent). The live "Product is/ is
     * not on sale" status pill is intentionally skipped — an Elementor panel
     * cannot render per-product live status.
     *
     * Sold Out (Products only) lives in the SAME "Badge" panel, appended after
     * the Sale controls behind a separator — the Sold Out toggle plus text +
     * position/style (no percentage or price source; that block has neither).
     * $soldOutCondition gates the whole group (ShopApp passes allow_out_of_stock
     * so it only shows when out-of-stock cards can appear).
     *
     * @param \Elementor\Widget_Base $widget
     * @param bool $includeSoldOut
     * @param array $soldOutCondition Extra display condition for the whole Sold
     *        Out panel (e.g. ShopApp passes allow_out_of_stock=yes so the badge
     *        only surfaces when out-of-stock cards can actually appear).
     * @return void
     */
    public static function registerBadgeContentControls($widget, $includeSoldOut = false, array $soldOutCondition = [])
    {
        // ── Sale: Badge ────────────────────────────────────
        $widget->start_controls_section(
            'sale_badge_settings_section',
            [
                'label' => esc_html__('Badge', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Bold group title so the panel reads as two clearly-labelled blocks
        // (Sale Badge … Sold Out Badge) instead of one long list.
        $widget->add_control(
            'sale_badge_group_heading',
            [
                'label' => esc_html__('Sale Badge', 'fluent-cart'),
                'type'  => Controls_Manager::HEADING,
            ]
        );

        $widget->add_control(
            'show_sale_badge',
            [
                'label'        => esc_html__('Enable', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart'),
                'label_off'    => esc_html__('Hide', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $widget->add_control(
            'sale_badge_text',
            [
                'label'       => esc_html__('Badge Text', 'fluent-cart'),
                'type'        => Controls_Manager::TEXT,
                'default'     => esc_html__('Sale!', 'fluent-cart'),
                'description' => esc_html__('Text shown when not using percentage mode.', 'fluent-cart'),
                'condition'   => ['show_sale_badge' => 'yes'],
            ]
        );

        $widget->add_control(
            'show_percentage',
            [
                'label'        => esc_html__('Show Discount Percentage', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => ['show_sale_badge' => 'yes'],
            ]
        );

        $widget->add_control(
            'sale_percentage_text',
            [
                'label'       => esc_html__('Percentage Format', 'fluent-cart'),
                'type'        => Controls_Manager::TEXT,
                'default'     => '-{percent}%',
                'description' => esc_html__('Use {percent} as placeholder. E.g., "-{percent}% OFF"', 'fluent-cart'),
                'condition'   => [
                    'show_sale_badge' => 'yes',
                    'show_percentage' => 'yes',
                ],
            ]
        );

        $widget->add_control(
            'sale_price_source',
            [
                'label'       => esc_html__('Price Source', 'fluent-cart'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'default_variant',
                'options'     => [
                    'default_variant' => esc_html__('Default Variant', 'fluent-cart'),
                    'best_discount'   => esc_html__('Best Discount (All Variants)', 'fluent-cart'),
                ],
                'description' => esc_html__('Where to check the sale price from.', 'fluent-cart'),
                'condition'   => ['show_sale_badge' => 'yes'],
            ]
        );

        // ── Sale: Position & Style (same panel, heading divider) ────
        $widget->add_control(
            'sale_badge_ps_heading',
            [
                'label'     => esc_html__('Position & Style', 'fluent-cart'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => ['show_sale_badge' => 'yes'],
            ]
        );

        $widget->add_control(
            'sale_badge_style',
            [
                'label'     => esc_html__('Badge Style', 'fluent-cart'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'badge',
                'options'   => self::styleChoices(),
                'condition' => ['show_sale_badge' => 'yes'],
            ]
        );

        $widget->add_control(
            'sale_badge_position',
            [
                'label'     => esc_html__('Position', 'fluent-cart'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'top-left',
                'options'   => self::positionChoices(),
                'condition' => ['show_sale_badge' => 'yes'],
            ]
        );

        // ── Sold Out (Products only): stays in the SAME "Badge" panel, a
        // separator sets it off from the Sale controls. Gated by the shop's
        // allow_out_of_stock filter via $soldOutCondition, so the whole Sold
        // Out group only surfaces when out-of-stock cards can appear. ──────
        if ($includeSoldOut) {
            // Dependent controls: on when the toggle is on AND (if gated) the
            // shop shows out-of-stock cards.
            $soDependent = array_merge(['show_sold_out_badge' => 'yes'], $soldOutCondition);

            // Matching bold group title, split off from the Sale block above.
            $widget->add_control(
                'sold_out_badge_group_heading',
                [
                    'label'     => esc_html__('Sold Out Badge', 'fluent-cart'),
                    'type'      => Controls_Manager::HEADING,
                    'separator' => 'before',
                    'condition' => $soldOutCondition,
                ]
            );

            $widget->add_control(
                'show_sold_out_badge',
                [
                    'label'        => esc_html__('Enable', 'fluent-cart'),
                    'type'         => Controls_Manager::SWITCHER,
                    'label_on'     => esc_html__('Show', 'fluent-cart'),
                    'label_off'    => esc_html__('Hide', 'fluent-cart'),
                    'return_value' => 'yes',
                    'default'      => '',
                    'condition'    => $soldOutCondition,
                ]
            );

            $widget->add_control(
                'sold_out_badge_text',
                [
                    'label'     => esc_html__('Badge Text', 'fluent-cart'),
                    'type'      => Controls_Manager::TEXT,
                    'default'   => esc_html__('Sold Out', 'fluent-cart'),
                    'condition' => $soDependent,
                ]
            );

            $widget->add_control(
                'sold_out_badge_ps_heading',
                [
                    'label'     => esc_html__('Position & Style', 'fluent-cart'),
                    'type'      => Controls_Manager::HEADING,
                    'separator' => 'before',
                    'condition' => $soDependent,
                ]
            );

            $widget->add_control(
                'sold_out_badge_style',
                [
                    'label'     => esc_html__('Badge Style', 'fluent-cart'),
                    'type'      => Controls_Manager::SELECT,
                    'default'   => 'badge',
                    'options'   => self::styleChoices(),
                    'condition' => $soDependent,
                ]
            );

            $widget->add_control(
                'sold_out_badge_position',
                [
                    'label'     => esc_html__('Position', 'fluent-cart'),
                    'type'      => Controls_Manager::SELECT,
                    'default'   => 'top-left',
                    'options'   => self::positionChoices(),
                    'condition' => $soDependent,
                ]
            );
        }

        $widget->end_controls_section();
    }

    /**
     * Style-tab section(s) over the core badge classes. Background + text color
     * carry !important — core's default palette lives on a
     * .fct-sale-badge:not([class*="has-background"]) rule of equal specificity,
     * so a plain override could lose the cascade. Padding / radius / typography
     * beat core's single-class rules on specificity alone, no !important.
     *
     * @param \Elementor\Widget_Base $widget
     * @param bool $includeSoldOut
     * @param array $soldOutCondition Extra display condition merged into the
     *        Sold Out style section (kept in step with the content panel).
     * @return void
     */
    public static function registerBadgeStyleControls($widget, $includeSoldOut = false, array $soldOutCondition = [])
    {
        self::badgeStyleSection(
            $widget,
            'sale_badge_style_section',
            esc_html__('Sale Badge', 'fluent-cart'),
            '{{WRAPPER}} .fct-sale-badge',
            'sale_badge',
            ['show_sale_badge' => 'yes']
        );

        if ($includeSoldOut) {
            self::badgeStyleSection(
                $widget,
                'sold_out_badge_style_section',
                esc_html__('Sold Out Badge', 'fluent-cart'),
                '{{WRAPPER}} .fct-sold-out-badge',
                'sold_out_badge',
                array_merge(['show_sold_out_badge' => 'yes'], $soldOutCondition)
            );
        }
    }

    /**
     * One badge style section: background, text color, typography, padding,
     * border-radius. $prefix keeps control names unique per badge.
     *
     * @param \Elementor\Widget_Base $widget
     * @param string $sectionId
     * @param string $label
     * @param string $selector
     * @param string $prefix
     * @param array $condition
     * @return void
     */
    private static function badgeStyleSection($widget, $sectionId, $label, $selector, $prefix, array $condition)
    {
        $widget->start_controls_section(
            $sectionId,
            [
                'label'     => $label,
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => $condition,
            ]
        );

        $widget->add_control(
            $prefix . '_background',
            [
                'label'     => esc_html__('Background Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $widget->add_control(
            $prefix . '_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => $prefix . '_typography',
                'selector' => $selector,
            ]
        );

        $widget->add_responsive_control(
            $prefix . '_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_control(
            $prefix . '_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    $selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->end_controls_section();
    }

    /**
     * @return array badge-style value => translated label (Gutenberg order)
     */
    private static function styleChoices()
    {
        return [
            'badge'  => esc_html__('Badge', 'fluent-cart'),
            'ribbon' => esc_html__('Ribbon', 'fluent-cart'),
            'tag'    => esc_html__('Tag', 'fluent-cart'),
        ];
    }

    /**
     * @return array position value => translated label
     */
    private static function positionChoices()
    {
        return [
            'top-left'     => esc_html__('Top Left', 'fluent-cart'),
            'top-right'    => esc_html__('Top Right', 'fluent-cart'),
            'bottom-left'  => esc_html__('Bottom Left', 'fluent-cart'),
            'bottom-right' => esc_html__('Bottom Right', 'fluent-cart'),
        ];
    }
}
