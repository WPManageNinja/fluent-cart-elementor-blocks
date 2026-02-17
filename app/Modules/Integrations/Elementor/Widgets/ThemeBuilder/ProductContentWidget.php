<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ProductWidgetTrait;

if (!defined('ABSPATH')) {
    exit;
}

class ProductContentWidget extends Widget_Base
{
    use ProductWidgetTrait;

    public function get_name()
    {
        return 'fluentcart_product_content';
    }

    public function get_title()
    {
        return esc_html__('Product Content', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-product-description';
    }

    public function get_categories()
    {
        return ['fluentcart-elements-single', 'fluent-cart'];
    }

    public function get_keywords()
    {
        return ['product', 'content', 'description', 'body', 'fluent'];
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

        $this->add_responsive_control(
            'align',
            [
                'label'     => esc_html__('Alignment', 'fluent-cart'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'left'    => ['title' => esc_html__('Left', 'fluent-cart'), 'icon' => 'eicon-text-align-left'],
                    'center'  => ['title' => esc_html__('Center', 'fluent-cart'), 'icon' => 'eicon-text-align-center'],
                    'right'   => ['title' => esc_html__('Right', 'fluent-cart'), 'icon' => 'eicon-text-align-right'],
                    'justify' => ['title' => esc_html__('Justified', 'fluent-cart'), 'icon' => 'eicon-text-align-justify'],
                ],
                'selectors' => [
                    '{{WRAPPER}} .fluentcart-product-content' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Style', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'content_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fluentcart-product-content' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'content_typography',
                'selector' => '{{WRAPPER}} .fluentcart-product-content',
            ]
        );

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

        // Set up post data for the_content()
        $post = get_post($product->ID);
        if (!$post) {
            return;
        }

        echo '<div class="fluentcart-product-content">';
        echo apply_filters('the_content', $post->post_content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }
}
