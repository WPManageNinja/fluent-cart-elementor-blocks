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

class ProductSkuWidget extends Widget_Base
{
    use ProductWidgetTrait;

    public function get_name()
    {
        return 'fluentcart_product_sku';
    }

    public function get_title()
    {
        return esc_html__('Product SKU', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-product-meta';
    }

    public function get_categories()
    {
        return ['fluent-cart-product'];
    }

    public function get_keywords()
    {
        return ['product', 'sku', 'identifier', 'code', 'fluent'];
    }

    public static function registerSkuStyleControls($widget, $selector = '{{WRAPPER}} .fct-product-sku')
    {
        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'sku_label_typography',
                'label'    => esc_html__('Label Typography', 'fluent-cart'),
                'selector' => $selector . ' .fct-product-sku__label',
            ]
        );

        $widget->add_control(
            'sku_label_color',
            [
                'label'     => esc_html__('Label Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-product-sku__label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'sku_value_typography',
                'label'    => esc_html__('Value Typography', 'fluent-cart'),
                'selector' => $selector . ' .fct-product-sku__value',
            ]
        );

        $widget->add_control(
            'sku_value_color',
            [
                'label'     => esc_html__('Value Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-product-sku__value' => 'color: {{VALUE}};',
                ],
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
            'show_label',
            [
                'label'        => esc_html__('Show Label', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'separator'    => 'before',
            ]
        );

        $this->add_control(
            'custom_label',
            [
                'label'       => esc_html__('Custom Label', 'fluent-cart'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => esc_html__('SKU:', 'fluent-cart'),
                'condition'   => [
                    'show_label' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'sku_style_section',
            [
                'label' => esc_html__('Style', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        static::registerSkuStyleControls($this);

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

        $showLabel = in_array($settings['show_label'] ?? 'yes', ['yes', 'no'], true)
            ? $settings['show_label']
            : 'yes';

        $label = sanitize_text_field($settings['custom_label'] ?? '');

        $renderer = new ProductRenderer($product);

        echo '<div class="fluentcart-product-sku">';
        $renderer->renderSku('', $showLabel === 'yes', $label);
        echo '</div>';
    }
}