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

class ProductPackageDescriptionWidget extends Widget_Base
{
    use ProductWidgetTrait;

    public function get_name()
    {
        return 'fluentcart_product_package_description';
    }

    public function get_title()
    {
        return esc_html__('Product Package Description', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-info-box';
    }

    public function get_categories()
    {
        return ['fluent-cart-product'];
    }

    public function get_keywords()
    {
        return ['product', 'package', 'shipping', 'weight', 'dimensions', 'fluent'];
    }

    public static function registerPackageDescriptionStyleControls($widget, $selector = '{{WRAPPER}} .fct-package-description')
    {
        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'package_label_typography',
                'label'    => esc_html__('Label Typography', 'fluent-cart'),
                'selector' => $selector . ' .fct-package-description__table th',
            ]
        );

        $widget->add_control(
            'package_label_color',
            [
                'label'     => esc_html__('Label Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-package-description__table th' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'package_value_typography',
                'label'    => esc_html__('Value Typography', 'fluent-cart'),
                'selector' => $selector . ' .fct-package-description__table td',
            ]
        );

        $widget->add_control(
            'package_value_color',
            [
                'label'     => esc_html__('Value Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-package-description__table td' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'package_border_color',
            [
                'label'     => esc_html__('Row Border Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-package-description__table th, ' . $selector . ' .fct-package-description__table td' => 'border-bottom-color: {{VALUE}};',
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
            'show_name',
            [
                'label'        => esc_html__('Show Package Name', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'separator'    => 'before',
            ]
        );

        $this->add_control(
            'show_type',
            [
                'label'        => esc_html__('Show Package Type', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_dimensions',
            [
                'label'        => esc_html__('Show Dimensions', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_product_weight',
            [
                'label'        => esc_html__('Show Product Weight', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_total_weight',
            [
                'label'        => esc_html__('Show Shipping Weight', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'package_style_section',
            [
                'label' => esc_html__('Style', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        static::registerPackageDescriptionStyleControls($this);

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

        $showName          = ($settings['show_name'] ?? 'yes') === 'yes';
        $showType          = ($settings['show_type'] ?? 'yes') === 'yes';
        $showDimensions    = ($settings['show_dimensions'] ?? 'yes') === 'yes';
        $showProductWeight = ($settings['show_product_weight'] ?? 'yes') === 'yes';
        $showTotalWeight   = ($settings['show_total_weight'] ?? 'yes') === 'yes';

        $renderer = new ProductRenderer($product);

        echo '<div class="fluentcart-product-package-description">';
        $renderer->renderPackageDescription('', $showName, $showType, $showDimensions, $showProductWeight, $showTotalWeight);
        echo '</div>';
    }
}
