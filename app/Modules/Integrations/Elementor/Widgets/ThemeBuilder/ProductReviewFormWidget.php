<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductReviewRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ReviewWidgetTrait;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Review Form — the form itself, on the page, with no button in front of it.
 *
 * Mirrors the fluent-cart/product-review-form block. The difference from the
 * Write a Review Button widget is one renderer option: container 'none'. That
 * is what makes this the form rather than a trigger — no CTA is drawn and
 * nothing is hidden behind an overlay. It is what a dedicated "write a review"
 * page is built from, which is also where the Button widget's link mode sends
 * people.
 */
class ProductReviewFormWidget extends Widget_Base
{
    use ReviewWidgetTrait;

    const LAYOUTS = ['inline', 'steps'];
    const DEFAULT_STAR_COLOR = '#f59e0b';

    public function get_name()
    {
        return 'fluentcart_product_review_form';
    }

    public function get_title()
    {
        return esc_html__('Review Form', 'fluent-cart-elementor-blocks');
    }

    public function get_icon()
    {
        return 'eicon-form-horizontal fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['review', 'form', 'write', 'rating', 'submit', 'fluent'];
    }

    public function get_style_depends()
    {
        AssetLoader::loadReviewSubmissionFormAssets();

        return [];
    }

    /**
     * @param \Elementor\Widget_Base $widget
     * @param string $selector
     */
    public static function registerFormStyleControls($widget, $selector = '{{WRAPPER}} .fct-review-form')
    {
        // ── Questions / labels ────────────────────────────
        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'form_label_typography',
                'label'    => esc_html__('Question Typography', 'fluent-cart-elementor-blocks'),
                'selector' => $selector . ' .fct-review-step-question',
            ]
        );

        $widget->add_control(
            'form_label_color',
            [
                'label'     => esc_html__('Question Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-review-step-question' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'form_field_gap',
            [
                'label'      => esc_html__('Field Spacing', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => ['px' => ['min' => 0, 'max' => 64]],
                'selectors'  => [
                    $selector . ' .fct-review-form-field' => 'margin-block-end: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // ── Inputs ────────────────────────────────────────
        // The inputs carry no fct- class of their own, so they are reached
        // through the form. Kept to input/textarea deliberately: a bare
        // descendant selector would also catch the file input inside the
        // photo upload zone, which is visually hidden and must stay so.
        $inputs = $selector . ' input[type="text"], '
            . $selector . ' input[type="email"], '
            . $selector . ' textarea';

        $widget->add_control(
            'form_input_heading',
            [
                'label'     => esc_html__('Inputs', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'form_input_typography',
                'selector' => $inputs,
            ]
        );

        $widget->add_control(
            'form_input_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$inputs => 'color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'form_input_background',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$inputs => 'background-color: {{VALUE}};'],
            ]
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'form_input_border',
                'selector' => $inputs,
            ]
        );

        $widget->add_control(
            'form_input_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    $inputs => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'form_input_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    $inputs => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // ── Star picker ───────────────────────────────────
        $widget->add_control(
            'form_stars_heading',
            [
                'label'     => esc_html__('Star Picker', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $widget->add_responsive_control(
            'form_star_size',
            [
                'label'      => esc_html__('Star Size', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 12, 'max' => 72],
                ],
                'selectors'  => [
                    $selector . ' .fct-star-selector .fct-star-svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // ── Submit ────────────────────────────────────────
        $submit = $selector . ' .fct-review-submit-btn';

        $widget->add_control(
            'form_submit_heading',
            [
                'label'     => esc_html__('Submit Button', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'form_submit_typography',
                'selector' => $submit,
            ]
        );

        $widget->start_controls_tabs('form_submit_tabs');

        $widget->start_controls_tab(
            'form_submit_normal',
            ['label' => esc_html__('Normal', 'fluent-cart-elementor-blocks')]
        );

        $widget->add_control(
            'form_submit_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$submit => 'color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'form_submit_background',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$submit => 'background-color: {{VALUE}};'],
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'form_submit_hover',
            ['label' => esc_html__('Hover', 'fluent-cart-elementor-blocks')]
        );

        $widget->add_control(
            'form_submit_color_hover',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$submit . ':hover' => 'color: {{VALUE}};'],
            ]
        );

        $widget->add_control(
            'form_submit_background_hover',
            [
                'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$submit . ':hover' => 'background-color: {{VALUE}};'],
            ]
        );

        $widget->end_controls_tab();
        $widget->end_controls_tabs();

        $widget->add_control(
            'form_submit_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    $submit => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator'  => 'before',
            ]
        );

        $widget->add_responsive_control(
            'form_submit_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    $submit => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
            'layout',
            [
                'label'       => esc_html__('Layout', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'inline',
                'options'     => [
                    'inline' => esc_html__('All fields at once', 'fluent-cart-elementor-blocks'),
                    'steps'  => esc_html__('Step by step', 'fluent-cart-elementor-blocks'),
                ],
                'description' => esc_html__('Step by step asks one question per screen, which suits a dedicated review page.', 'fluent-cart-elementor-blocks'),
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'star_color',
            [
                'label'   => esc_html__('Star Color', 'fluent-cart-elementor-blocks'),
                'type'    => Controls_Manager::COLOR,
                'default' => self::DEFAULT_STAR_COLOR,
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'form_style_section',
            [
                'label' => esc_html__('Form', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        static::registerFormStyleControls($this);

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

        AssetLoader::loadReviewSubmissionFormAssets();

        $layout = (string) ($settings['layout'] ?? 'inline');
        if (!in_array($layout, self::LAYOUTS, true)) {
            $layout = 'inline';
        }

        $starColor = sanitize_hex_color((string) ($settings['star_color'] ?? '')) ?: self::DEFAULT_STAR_COLOR;

        ob_start();
        (new ProductReviewRenderer($product->ID, [
            // What makes this the form and not a trigger.
            'container' => 'none',
            'layout'    => $layout,
            'starColor' => $starColor,
        ]))->renderForm();
        $content = ob_get_clean();

        if (trim($content) === '') {
            // Core decided this visitor may not review — not a verified buyer,
            // or not logged in under a mode that requires it. The storefront
            // shows nothing; the editor is told, because a blank canvas here
            // reads as a broken widget.
            $this->renderPlaceholder(
                esc_html__('This visitor cannot review this product, so the form is not shown. Check the review permission mode in FluentCart → Settings.', 'fluent-cart-elementor-blocks')
            );
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer output, escaped at source
        echo '<div class="fluentcart-product-review-form fct-review-form-block">' . $content . '</div>';
    }
}
