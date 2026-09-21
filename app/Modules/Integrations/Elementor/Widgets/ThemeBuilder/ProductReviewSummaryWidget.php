<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductReviewRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ReviewWidgetTrait;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Review Summary — the average, the total, and the five-star breakdown bars.
 *
 * Mirrors the fluent-cart/product-review-summary block, which in Gutenberg is
 * only insertable inside a Review Summary Group. That group exists to provide
 * the product to its children through block context; an Elementor Container
 * does the same job with no widget of its own, so the group does not survive
 * the port and this widget resolves its own product.
 */
class ProductReviewSummaryWidget extends Widget_Base
{
    use ReviewWidgetTrait;

    /** Core's own default, repeated so the control shows the real starting colour. */
    const DEFAULT_STAR_COLOR = '#f59e0b';

    public function get_name()
    {
        return 'fluentcart_product_review_summary';
    }

    public function get_title()
    {
        return esc_html__('Review Summary', 'fluent-cart-elementor-blocks');
    }

    public function get_icon()
    {
        return 'eicon-review fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['review', 'rating', 'summary', 'breakdown', 'average', 'stars', 'fluent'];
    }

    public function get_style_depends()
    {
        AssetLoader::loadSingleProductAssets();

        return [];
    }

    /**
     * @param \Elementor\Widget_Base $widget
     * @param string $selector
     */
    public static function registerSummaryStyleControls($widget, $selector = '{{WRAPPER}} .fct-reviews-summary')
    {
        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'summary_average_typography',
                'label'    => esc_html__('Average Typography', 'fluent-cart-elementor-blocks'),
                'selector' => $selector . ' .fct-reviews-average-number',
            ]
        );

        $widget->add_control(
            'summary_average_color',
            [
                'label'     => esc_html__('Average Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-reviews-average-number' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'summary_max_color',
            [
                'label'     => esc_html__('Out-of-five Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-reviews-average-max' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'summary_total_typography',
                'label'     => esc_html__('Total Typography', 'fluent-cart-elementor-blocks'),
                'selector'  => $selector . ' .fct-reviews-total',
                'separator' => 'before',
            ]
        );

        $widget->add_control(
            'summary_total_color',
            [
                'label'     => esc_html__('Total Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-reviews-total' => 'color: {{VALUE}};',
                ],
            ]
        );

        // ── Breakdown bars ────────────────────────────────
        $widget->add_control(
            'summary_bars_heading',
            [
                'label'     => esc_html__('Breakdown Bars', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $widget->add_control(
            'summary_bar_fill_color',
            [
                'label'     => esc_html__('Bar Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-reviews-bar-fill' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'summary_bar_track_color',
            [
                'label'     => esc_html__('Bar Track Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-reviews-bar-track' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'summary_bar_height',
            [
                'label'      => esc_html__('Bar Height', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => ['min' => 2, 'max' => 32],
                ],
                'selectors'  => [
                    $selector . ' .fct-reviews-bar-track' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'summary_bar_row_gap',
            [
                'label'      => esc_html__('Row Spacing', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 32],
                ],
                'selectors'  => [
                    $selector . ' .fct-reviews-bar-row' => 'margin-block-end: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'summary_bar_label_typography',
                'label'    => esc_html__('Bar Label Typography', 'fluent-cart-elementor-blocks'),
                'selector' => $selector . ' .fct-reviews-bar-label, ' . $selector . ' .fct-reviews-bar-count',
            ]
        );

        $widget->add_control(
            'summary_bar_label_color',
            [
                'label'     => esc_html__('Bar Label Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-reviews-bar-label' => 'color: {{VALUE}};',
                    $selector . ' .fct-reviews-bar-count' => 'color: {{VALUE}};',
                ],
            ]
        );
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->registerProductSourceControls();

        $this->add_control(
            'star_color',
            [
                'label'       => esc_html__('Star Color', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Colours the stars in the summary. Matches the Star Color setting on the Gutenberg block.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::COLOR,
                'default'     => self::DEFAULT_STAR_COLOR,
                'separator'   => 'before',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'summary_style_section',
            [
                'label' => esc_html__('Summary', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        static::registerSummaryStyleControls($this);

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        if ($this->renderReviewsUnavailable()) {
            return;
        }

        $product = $this->getProduct($settings);

        if (!$product && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $product = $this->getPreviewProduct();
        }

        if (!$product) {
            $this->renderPlaceholder(
                esc_html__('Please select a product or use this widget inside a product template.', 'fluent-cart-elementor-blocks')
            );
            return;
        }

        if ($this->renderReviewsUnavailable($product->ID)) {
            return;
        }

        AssetLoader::loadSingleProductAssets();

        // The renderer sanitises the colour itself and falls back to its own
        // default, so an emptied control returns the stock colour rather than
        // an unstyled star.
        $starColor = sanitize_hex_color((string) ($settings['star_color'] ?? '')) ?: self::DEFAULT_STAR_COLOR;

        ob_start();
        (new ProductReviewRenderer($product->ID, [
            'starColor' => $starColor,
        ]))->renderSummarySection();
        $content = ob_get_clean();

        if (trim($content) === '') {
            // No reviews yet. Silent on the front end; the editor says so,
            // because an empty canvas with no explanation reads as a broken
            // widget.
            $this->renderPlaceholder(
                esc_html__('This product has no approved reviews yet, so the summary is empty.', 'fluent-cart-elementor-blocks')
            );
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer output, escaped at source
        echo '<div class="fluentcart-product-review-summary fct-review-summary-block">' . $content . '</div>';
    }
}
