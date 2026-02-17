<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ProductWidgetTrait;

if (!defined('ABSPATH')) {
    exit;
}

class ProductStockWidget extends Widget_Base
{
    use ProductWidgetTrait;

    public function get_name()
    {
        return 'fluentcart_product_stock';
    }

    public function get_title()
    {
        return esc_html__('Product Stock', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-product-stock';
    }

    public function get_categories()
    {
        return ['fluentcart-elements-single', 'fluent-cart'];
    }

    public function get_keywords()
    {
        return ['product', 'stock', 'availability', 'inventory', 'fluent'];
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

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Style', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'stock_typography',
                'selector' => '{{WRAPPER}} .fct-product-stock .fct-stock-status',
            ]
        );

        $this->add_control(
            'in_stock_color',
            [
                'label'     => esc_html__('In Stock Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-product-stock:not(.out-of-stock) .fct-stock-status' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'out_of_stock_color',
            [
                'label'     => esc_html__('Out of Stock Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-product-stock.out-of-stock .fct-stock-status' => 'color: {{VALUE}};',
                ],
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

        AssetLoader::loadSingleProductAssets();

        $renderer = new ProductRenderer($product);

        echo '<div class="fluentcart-product-stock">';
        $renderer->renderStockAvailability();
        echo '</div>';
    }
}
