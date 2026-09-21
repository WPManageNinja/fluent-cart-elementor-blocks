<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Style controls for the review list, shared by the Product Review List widget
 * and the all-in-one Product Reviews widget. One place so the two cannot drift,
 * the same reason BadgeControls exists.
 *
 * Three things about core's stylesheet shape these selectors, and getting any
 * of them wrong produces a control that silently does nothing:
 *
 * 1. THE PAGER IS !important. `.fct-reviews-page-btn` and `.fct-reviews-page-nav`
 *    set border, background, radius, font and colour with !important, to survive
 *    themes that style every button on the page. A normal Elementor declaration
 *    loses to that however specific the selector, so the pager controls emit
 *    !important themselves. Nothing else here needs it.
 *
 * 2. SOME OF CORE'S RULES ARE TWO CLASSES DEEP. `.fct-reviews-filter-chips
 *    .fct-filter-chip`, `.fct-review-item-stars .fct-star` and
 *    `.fct-review-item .fct-review-view-replies` are all (0,2,0), which a bare
 *    `{{WRAPPER}} .fct-filter-chip` only ties. A tie is decided by source order,
 *    which is not ours to rely on, so those controls name the parent too and win
 *    outright at (0,3,0).
 *
 * 3. THE GRID GAP IS BAKED INTO THE COLUMN MATH. `.fct-reviews-list--grid`
 *    computes `grid-template-columns` with the 12px gap hardcoded inside a
 *    calc(). Setting `gap` alone leaves that calc subtracting 12px, so the
 *    columns no longer fit their track and the last one wraps. The gap control
 *    therefore rewrites `grid-template-columns` with the same value.
 */
class ReviewStyleControls
{
    /**
     * Every review-list style section, in reading order.
     *
     * @param \Elementor\Widget_Base $widget
     * @param bool $hasViewModes Whether this widget can render the grid and
     *        slider views. The all-in-one widget always draws core's default
     *        section, which is list only, so offering it a grid gap control
     *        would be offering a control that can never do anything.
     */
    public static function registerReviewStyleControls($widget, bool $hasViewModes = true)
    {
        self::registerListSection($widget, $hasViewModes);
        self::registerHeaderSection($widget);
        self::registerCardSection($widget);
        self::registerReviewerSection($widget);
        self::registerRatingSection($widget);
        self::registerContentSection($widget);
        self::registerActionsSection($widget);
        self::registerPaginationSection($widget);
    }

    // ── List ──────────────────────────────────────────────

    protected static function registerListSection($widget, bool $hasViewModes = true)
    {
        $widget->start_controls_section(
            'review_list_style_section',
            [
                'label' => esc_html__('Review List', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $widget->add_responsive_control(
            'review_row_gap',
            [
                'label'       => esc_html__('Space Between Reviews', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Applies to the list view.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => ['px', 'em', 'rem'],
                'range'       => ['px' => ['min' => 0, 'max' => 80]],
                'selectors'   => [
                    // Core spaces list rows with margin-bottom on the card and
                    // zeroes it on the last one, so only the margin moves here.
                    '{{WRAPPER}} .fct-reviews-list .fct-review-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        if (!$hasViewModes) {
            $widget->end_controls_section();

            return;
        }

        $widget->add_responsive_control(
            'review_grid_gap',
            [
                'label'       => esc_html__('Grid & Slider Gap', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Applies to the grid and slider views.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SLIDER,
                'size_units'  => ['px'],
                'range'       => ['px' => ['min' => 0, 'max' => 80]],
                'selectors'   => [
                    // gap alone is not enough: core's grid-template-columns
                    // subtracts a hardcoded 12px per gutter inside a calc(), so
                    // the track math has to be restated with the chosen value or
                    // the last column wraps.
                    '{{WRAPPER}} .fct-reviews-list--grid' =>
                        'gap: {{SIZE}}{{UNIT}};'
                        . ' grid-template-columns: repeat(auto-fit, minmax(min(260px, 100%),'
                        . ' calc((100% - (var(--fct-review-columns, 2) - 1) * {{SIZE}}{{UNIT}}) / var(--fct-review-columns, 2))));',
                    '{{WRAPPER}} .fct-reviews-list--slider .swiper-slide' => 'padding-inline: calc({{SIZE}}{{UNIT}} / 2);',
                ],
            ]
        );

        $widget->end_controls_section();
    }

    // ── Header ────────────────────────────────────────────

    protected static function registerHeaderSection($widget)
    {
        $widget->start_controls_section(
            'review_header_style_section',
            [
                'label' => esc_html__('Header', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'review_count_typography',
                'label'    => esc_html__('Review Count Typography', 'fluent-cart-elementor-blocks'),
                'selector' => '{{WRAPPER}} .fct-reviews-section-title',
            ]
        );

        $widget->add_control(
            'review_count_color',
            [
                'label'     => esc_html__('Review Count Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-reviews-section-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'review_chips_heading',
            [
                'label'     => esc_html__('Filter Chips', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        // (0,3,0) — core's own rule is .fct-reviews-filter-chips .fct-filter-chip,
        // which a single-class selector would only tie.
        $chip = '{{WRAPPER}} .fct-reviews-filter-chips .fct-filter-chip';

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'review_chip_typography',
                'selector' => $chip,
            ]
        );

        $widget->start_controls_tabs('review_chip_tabs');

        $widget->start_controls_tab('review_chip_normal', ['label' => esc_html__('Normal', 'fluent-cart-elementor-blocks')]);

        $widget->add_control(
            'review_chip_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$chip => 'color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'review_chip_bg',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$chip => 'background-color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'review_chip_border_color',
            [
                'label'     => esc_html__('Border Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$chip => 'border-color: {{VALUE}};'],
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab('review_chip_active', ['label' => esc_html__('Active', 'fluent-cart-elementor-blocks')]);

        $widget->add_control(
            'review_chip_color_active',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$chip . '.active' => 'color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'review_chip_bg_active',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$chip . '.active' => 'background-color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'review_chip_border_color_active',
            [
                'label'     => esc_html__('Border Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$chip . '.active' => 'border-color: {{VALUE}};'],
            ]
        );

        $widget->end_controls_tab();
        $widget->end_controls_tabs();

        $widget->add_control(
            'review_chip_radius',
            [
                'label'      => esc_html__('Chip Radius', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    $chip => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator'  => 'before',
            ]
        );

        $widget->add_control(
            'review_sort_heading',
            [
                'label'     => esc_html__('Sort Control', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'review_sort_typography',
                'selector' => '{{WRAPPER}} .fct-reviews-sort select',
            ]
        );

        $widget->add_control(
            'review_sort_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    // The select is styled with !important in core, for the same
                    // reason the pager is: themes restyle every form control.
                    '{{WRAPPER}} .fct-reviews-sort select' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $widget->add_control(
            'review_sort_bg',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-reviews-sort select' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $widget->add_control(
            'review_sort_border_color',
            [
                'label'     => esc_html__('Border Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-reviews-sort select' => 'border-color: {{VALUE}} !important;',
                ],
            ]
        );

        $widget->end_controls_section();
    }

    // ── Card ──────────────────────────────────────────────

    protected static function registerCardSection($widget)
    {
        $widget->start_controls_section(
            'review_card_style_section',
            [
                'label' => esc_html__('Review Card', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $card = '{{WRAPPER}} .fct-review-item';

        $widget->add_control(
            'review_card_bg',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$card => 'background-color: {{VALUE}};'],
            ]
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'review_card_border',
                'selector' => $card,
            ]
        );

        $widget->add_control(
            'review_card_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    $card => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'review_card_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    $card => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'review_card_shadow',
                'selector' => $card,
            ]
        );

        $widget->end_controls_section();
    }

    // ── Reviewer ──────────────────────────────────────────

    protected static function registerReviewerSection($widget)
    {
        $widget->start_controls_section(
            'review_reviewer_style_section',
            [
                'label' => esc_html__('Reviewer', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $widget->add_responsive_control(
            'review_avatar_size',
            [
                'label'      => esc_html__('Avatar Size', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 16, 'max' => 120]],
                'selectors'  => [
                    '{{WRAPPER}} .fct-review-avatar-circle' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_control(
            'review_avatar_radius',
            [
                'label'      => esc_html__('Avatar Radius', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    // The photo is a child of the circle; both have to round or
                    // a square photo pokes out of a rounded frame.
                    '{{WRAPPER}} .fct-review-avatar-circle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .fct-review-avatar-photo'  => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'review_author_typography',
                'label'     => esc_html__('Name Typography', 'fluent-cart-elementor-blocks'),
                'selector'  => '{{WRAPPER}} .fct-review-item-author',
                'separator' => 'before',
            ]
        );

        $widget->add_control(
            'review_author_color',
            [
                'label'     => esc_html__('Name Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-review-item-author' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'review_verified_heading',
            [
                'label'     => esc_html__('Verified Badge', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'review_verified_typography',
                'selector' => '{{WRAPPER}} .fct-review-verified',
            ]
        );

        $widget->add_control(
            'review_verified_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}} .fct-review-verified' => 'color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'review_verified_bg',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}} .fct-review-verified' => 'background-color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'review_variant_heading',
            [
                'label'     => esc_html__('Variation Reviewed', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'review_variant_typography',
                'selector' => '{{WRAPPER}} .fct-review-item-variant',
            ]
        );

        $widget->add_control(
            'review_variant_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}} .fct-review-item-variant' => 'color: {{VALUE}};'],
            ]
        );

        $widget->end_controls_section();
    }

    // ── Rating ────────────────────────────────────────────

    protected static function registerRatingSection($widget)
    {
        $widget->start_controls_section(
            'review_stars_style_section',
            [
                'label' => esc_html__('Stars', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $widget->add_responsive_control(
            'review_star_size',
            [
                'label'      => esc_html__('Star Size', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 8, 'max' => 48],
                    'em' => ['min' => 0.5, 'max' => 4, 'step' => 0.1],
                ],
                'selectors'  => [
                    // The star SVG is sized in em, so the lever is font-size on
                    // the star. (0,3,0) to beat core's .fct-review-item-stars
                    // .fct-star.
                    '{{WRAPPER}} .fct-review-item-stars .fct-star' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_control(
            'review_star_color',
            [
                'label'       => esc_html__('Filled Star Color', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Overrides the Star Color set in the Content tab.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::COLOR,
                'selectors'   => [
                    // The SVG fills with currentColor, so colour is the lever.
                    '{{WRAPPER}} .fct-review-item-stars .fct-star-filled' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'review_star_empty_color',
            [
                'label'     => esc_html__('Empty Star Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-review-item-stars .fct-star-empty' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_section();
    }

    // ── Content ───────────────────────────────────────────

    protected static function registerContentSection($widget)
    {
        $widget->start_controls_section(
            'review_content_style_section',
            [
                'label' => esc_html__('Review Content', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'review_title_typography',
                'label'    => esc_html__('Title Typography', 'fluent-cart-elementor-blocks'),
                'selector' => '{{WRAPPER}} .fct-review-item-title',
            ]
        );

        $widget->add_control(
            'review_title_color',
            [
                'label'     => esc_html__('Title Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}} .fct-review-item-title' => 'color: {{VALUE}};'],
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'review_text_typography',
                'label'     => esc_html__('Text Typography', 'fluent-cart-elementor-blocks'),
                'selector'  => '{{WRAPPER}} .fct-review-item-content',
                'separator' => 'before',
            ]
        );

        $widget->add_control(
            'review_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}} .fct-review-item-content' => 'color: {{VALUE}};'],
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'review_date_typography',
                'label'     => esc_html__('Date Typography', 'fluent-cart-elementor-blocks'),
                'selector'  => '{{WRAPPER}} .fct-review-item-date',
                'separator' => 'before',
            ]
        );

        $widget->add_control(
            'review_date_color',
            [
                'label'     => esc_html__('Date Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => ['{{WRAPPER}} .fct-review-item-date' => 'color: {{VALUE}};'],
            ]
        );

        $widget->end_controls_section();
    }

    // ── Photos and actions ────────────────────────────────

    protected static function registerActionsSection($widget)
    {
        $widget->start_controls_section(
            'review_actions_style_section',
            [
                'label' => esc_html__('Photos & Actions', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $widget->add_responsive_control(
            'review_photo_size',
            [
                'label'      => esc_html__('Photo Size', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 24, 'max' => 200]],
                'selectors'  => [
                    '{{WRAPPER}} .fct-review-media-thumb' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_control(
            'review_photo_radius',
            [
                'label'      => esc_html__('Photo Radius', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .fct-review-media-thumb' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_control(
            'review_reply_heading',
            [
                'label'     => esc_html__('Reply Button', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        // (0,3,0): core styles this as .fct-review-item .fct-review-view-replies.
        $reply = '{{WRAPPER}} .fct-review-item .fct-review-view-replies';

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'review_reply_typography',
                'selector' => $reply,
            ]
        );

        $widget->add_control(
            'review_reply_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$reply => 'color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'review_reply_bg',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$reply => 'background-color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'review_reply_border_color',
            [
                'label'     => esc_html__('Border Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$reply => 'border-color: {{VALUE}};'],
            ]
        );

        $widget->end_controls_section();
    }

    // ── Pagination ────────────────────────────────────────

    protected static function registerPaginationSection($widget)
    {
        $widget->start_controls_section(
            'review_pagination_style_section',
            [
                'label' => esc_html__('Pagination', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        // Everything in this section carries !important. Core sets the pager's
        // border, background, radius, font and colour that way so a theme that
        // restyles every button cannot break it — which means an ordinary
        // declaration here would lose no matter how specific the selector.
        $btn = '{{WRAPPER}} .fct-reviews-page-btn';
        $nav = '{{WRAPPER}} .fct-reviews-page-nav';

        $widget->add_control(
            'review_pager_note',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('Pagination styles are emitted with !important, because FluentCart sets the pager that way to survive themes that restyle every button.', 'fluent-cart-elementor-blocks'),
                'content_classes' => 'elementor-descriptor',
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'review_pager_typography',
                'selector' => $btn . ', ' . $nav,
            ]
        );

        $widget->start_controls_tabs('review_pager_tabs');

        $widget->start_controls_tab('review_pager_normal', ['label' => esc_html__('Normal', 'fluent-cart-elementor-blocks')]);

        $widget->add_control(
            'review_pager_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $btn => 'color: {{VALUE}} !important;',
                    $nav => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $widget->add_control(
            'review_pager_bg',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$btn => 'background-color: {{VALUE}} !important;'],
            ]
        );

        $widget->add_control(
            'review_pager_border_color',
            [
                'label'     => esc_html__('Border Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$btn => 'border-color: {{VALUE}} !important;'],
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab('review_pager_active_tab', ['label' => esc_html__('Active', 'fluent-cart-elementor-blocks')]);

        $widget->add_control(
            'review_pager_color_active',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$btn . '.active' => 'color: {{VALUE}} !important;'],
            ]
        );

        $widget->add_control(
            'review_pager_bg_active',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$btn . '.active' => 'background-color: {{VALUE}} !important;'],
            ]
        );

        $widget->add_control(
            'review_pager_border_color_active',
            [
                'label'     => esc_html__('Border Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$btn . '.active' => 'border-color: {{VALUE}} !important;'],
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab('review_pager_hover', ['label' => esc_html__('Hover', 'fluent-cart-elementor-blocks')]);

        $widget->add_control(
            'review_pager_color_hover',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $btn . ':hover' => 'color: {{VALUE}} !important;',
                    $nav . ':hover' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $widget->add_control(
            'review_pager_bg_hover',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$btn . ':hover' => 'background-color: {{VALUE}} !important;'],
            ]
        );

        $widget->add_control(
            'review_pager_border_color_hover',
            [
                'label'     => esc_html__('Border Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$btn . ':hover' => 'border-color: {{VALUE}} !important;'],
            ]
        );

        $widget->end_controls_tab();
        $widget->end_controls_tabs();

        $widget->add_control(
            'review_pager_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    $btn => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
                'separator'  => 'before',
            ]
        );

        $widget->add_responsive_control(
            'review_pager_align',
            [
                'label'     => esc_html__('Alignment', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'fluent-cart-elementor-blocks'),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center'     => [
                        'title' => esc_html__('Center', 'fluent-cart-elementor-blocks'),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'flex-end'   => [
                        'title' => esc_html__('Right', 'fluent-cart-elementor-blocks'),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .fct-reviews-pagination-inner' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $widget->end_controls_section();
    }
}
