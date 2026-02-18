<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ProductWidgetTrait;

if (!defined('ABSPATH')) {
    exit;
}

class ProductTitleWidget extends Widget_Base
{
    use ProductWidgetTrait;

    public function get_name()
    {
        return 'fluentcart_product_title';
    }

    public function get_title()
    {
        return esc_html__('Product Title', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-product-title';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['product', 'title', 'heading', 'name', 'fluent'];
    }

    public static function registerTitleStyleControls($widget, $selector = '{{WRAPPER}} .fluentcart-product-title')
    {
        $widget->add_control(
            'title_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'selector' => $selector,
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

        $this->add_control(
            'html_tag',
            [
                'label'   => esc_html__('HTML Tag', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'h1',
                'options' => [
                    'h1'   => 'H1',
                    'h2'   => 'H2',
                    'h3'   => 'H3',
                    'h4'   => 'H4',
                    'h5'   => 'H5',
                    'h6'   => 'H6',
                    'div'  => 'div',
                    'span' => 'span',
                    'p'    => 'p',
                ],
            ]
        );

        $this->add_responsive_control(
            'align',
            [
                'label'     => esc_html__('Alignment', 'fluent-cart'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'left'   => ['title' => esc_html__('Left', 'fluent-cart'), 'icon' => 'eicon-text-align-left'],
                    'center' => ['title' => esc_html__('Center', 'fluent-cart'), 'icon' => 'eicon-text-align-center'],
                    'right'  => ['title' => esc_html__('Right', 'fluent-cart'), 'icon' => 'eicon-text-align-right'],
                ],
                'selectors' => [
                    '{{WRAPPER}} .fluentcart-product-title' => 'text-align: {{VALUE}};',
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

        static::registerTitleStyleControls($this);

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

        $tag = $settings['html_tag'] ?: 'h1';
        $allowed_tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p'];
        if (!in_array($tag, $allowed_tags)) {
            $tag = 'h1';
        }

        printf(
            '<%1$s class="fluentcart-product-title">%2$s</%1$s>',
            $tag,
            esc_html($product->post_title)
        );
    }
}
