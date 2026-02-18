<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ProductWidgetTrait;

if (!defined('ABSPATH')) {
    exit;
}

class ProductBuySectionWidget extends Widget_Base
{
    use ProductWidgetTrait;

    public function get_name()
    {
        return 'fluentcart_product_buy_section';
    }

    public function get_title()
    {
        return esc_html__('Product Buy Section', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-product-add-to-cart';
    }

    public function get_categories()
    {
        return ['fluentcart-elements-single', 'fluent-cart'];
    }

    public function get_keywords()
    {
        return ['product', 'buy', 'cart', 'purchase', 'variants', 'fluent'];
    }

    public static function registerBuySectionStyleControls($widget, $selector = '{{WRAPPER}} .fct_buy_section')
    {
        $btnParts = [
            ' .wp-block-button__link',
            ' .fct-buy-now-btn',
            ' .fct-add-to-cart-btn',
            ' .fluent-cart-direct-checkout-button',
            ' .fluent-cart-add-to-cart-button',
        ];

        $btnSelector = implode(', ', array_map(fn($part) => $selector . $part, $btnParts));
        $btnHoverSelector = implode(', ', array_map(fn($part) => $selector . $part . ':hover', $btnParts));

        $buildBtnSelectors = function ($css) use ($selector, $btnParts) {
            $selectors = [];
            foreach ($btnParts as $part) {
                $selectors[$selector . $part] = $css;
            }
            return $selectors;
        };

        $buildBtnHoverSelectors = function ($css) use ($selector, $btnParts) {
            $selectors = [];
            foreach ($btnParts as $part) {
                $selectors[$selector . $part . ':hover'] = $css;
            }
            return $selectors;
        };

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'button_typography',
                'selector' => $btnSelector,
            ]
        );

        $widget->start_controls_tabs('button_style_tabs');

        $widget->start_controls_tab(
            'button_normal_tab',
            ['label' => esc_html__('Normal', 'fluent-cart')]
        );

        $widget->add_control(
            'button_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => $buildBtnSelectors('color: {{VALUE}};'),
            ]
        );

        $widget->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'button_background',
                'types'    => ['classic', 'gradient'],
                'selector' => $btnSelector,
            ]
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'button_border',
                'selector' => $btnSelector,
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'button_hover_tab',
            ['label' => esc_html__('Hover', 'fluent-cart')]
        );

        $widget->add_control(
            'button_hover_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => $buildBtnHoverSelectors('color: {{VALUE}};'),
            ]
        );

        $widget->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'button_hover_background',
                'types'    => ['classic', 'gradient'],
                'selector' => $btnHoverSelector,
            ]
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'button_hover_border',
                'selector' => $btnHoverSelector,
            ]
        );

        $widget->end_controls_tab();
        $widget->end_controls_tabs();

        $widget->add_control(
            'button_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'separator'  => 'before',
                'selectors'  => $buildBtnSelectors('border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'),
            ]
        );

        $widget->add_responsive_control(
            'button_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => $buildBtnSelectors('padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'),
            ]
        );
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->registerProductSourceControls();

        $this->end_controls_section();

        // Button Style Section
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => esc_html__('Buttons', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        static::registerBuySectionStyleControls($this);

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $product = $this->getProduct($settings);

        if (!$product) {
            $this->renderPlaceholder(__('Please select a product or use this widget inside a product template.', 'fluent-cart'));
            return;
        }

        AssetLoader::loadSingleProductAssets();

        $renderer = new ProductRenderer($product);

        echo '<div class="fluentcart-product-buy-section">';
        $renderer->renderBuySection();
        echo '</div>';
    }
}
