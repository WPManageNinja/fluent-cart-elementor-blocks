<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use FluentCart\App\Hooks\Handlers\ShortCodes\ShopAppHandler;
use FluentCart\App\Modules\Templating\AssetLoader;

class ShopAppWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_shop_app';
    }

    public function get_title()
    {
        return esc_html__('Products', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-products';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['products', 'shop', 'store', 'commerce', 'fluent', 'grid', 'list'];
    }

    protected function register_controls()
    {
        $this->registerContentControls();
        $this->registerFilterControls();
        $this->registerStyleControls();
    }

    private function registerContentControls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('General Settings', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'per_page',
            [
                'label'   => esc_html__('Products Per Page', 'fluent-cart'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 10,
                'min'     => 1,
                'max'     => 100,
                'step'    => 1,
            ]
        );

        $this->add_control(
            'view_mode',
            [
                'label'   => esc_html__('View Mode', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => esc_html__('Grid', 'fluent-cart'),
                    'list' => esc_html__('List', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'product_box_grid_size',
            [
                'label'   => esc_html__('Grid Columns', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => '4',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
            ]
        );

        $this->add_control(
            'paginator',
            [
                'label'   => esc_html__('Pagination Type', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'scroll',
                'options' => [
                    'scroll'  => esc_html__('Infinite Scroll', 'fluent-cart'),
                    'numbers' => esc_html__('Page Numbers', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'price_format',
            [
                'label'   => esc_html__('Price Format', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'starts_from',
                'options' => [
                    'starts_from' => esc_html__('Starts From', 'fluent-cart'),
                    'range'       => esc_html__('Range', 'fluent-cart'),
                    'lowest'      => esc_html__('Lowest', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'order_by',
            [
                'label'   => esc_html__('Order By', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'ID',
                'options' => [
                    'ID'    => esc_html__('ID', 'fluent-cart'),
                    'name'  => esc_html__('Name', 'fluent-cart'),
                    'price' => esc_html__('Price', 'fluent-cart'),
                    'date'  => esc_html__('Date', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'order_type',
            [
                'label'   => esc_html__('Order', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'DESC' => esc_html__('Descending', 'fluent-cart'),
                    'ASC'  => esc_html__('Ascending', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'use_default_style',
            [
                'label'        => esc_html__('Use Default Style', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    private function registerFilterControls()
    {
        $this->start_controls_section(
            'filter_section',
            [
                'label' => esc_html__('Filter Settings', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'enable_filter',
            [
                'label'        => esc_html__('Enable Filter', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'live_filter',
            [
                'label'        => esc_html__('Live Filter', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => esc_html__('Apply filters instantly without a submit button.', 'fluent-cart'),
                'condition'    => [
                    'enable_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'enable_wildcard_filter',
            [
                'label'        => esc_html__('Enable Search Filter', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'enable_filter' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'enable_wildcard_for_post_content',
            [
                'label'        => esc_html__('Search in Post Content', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => [
                    'enable_filter'          => 'yes',
                    'enable_wildcard_filter' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerStyleControls()
    {
        // Product Card Style
        $this->start_controls_section(
            'card_style_section',
            [
                'label' => esc_html__('Product Card', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'card_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .fct-product-card',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .fct-product-card',
            ]
        );

        $this->add_control(
            'card_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .fct-product-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_box_shadow',
                'selector' => '{{WRAPPER}} .fct-product-card',
            ]
        );

        $this->add_responsive_control(
            'card_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .fct-product-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Product Title Style
        $this->start_controls_section(
            'title_style_section',
            [
                'label' => esc_html__('Product Title', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .fct-product-card-title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-product-card-title'   => 'color: {{VALUE}};',
                    '{{WRAPPER}} .fct-product-card-title a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'title_hover_color',
            [
                'label'     => esc_html__('Hover Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-product-card-title:hover'   => 'color: {{VALUE}};',
                    '{{WRAPPER}} .fct-product-card-title a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Product Price Style
        $this->start_controls_section(
            'price_style_section',
            [
                'label' => esc_html__('Product Price', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'price_typography',
                'selector' => '{{WRAPPER}} .fct-product-card-prices',
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-product-card-prices' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Button Style
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => esc_html__('Product Button', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'product_button_typography',
                'selector' => '{{WRAPPER}} .fct-product-card .fct-button',
            ]
        );

        $this->start_controls_tabs('tabs_product_button_style');

        // Normal State
        $this->start_controls_tab(
            'tab_product_button_normal',
            [
                'label' => esc_html__('Normal', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'product_button_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-product-card .fct-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'product_button_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .fct-product-card .fct-button',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'product_button_border',
                'selector' => '{{WRAPPER}} .fct-product-card .fct-button',
            ]
        );

        $this->add_control(
            'product_button_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .fct-product-card .fct-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'product_button_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .fct-product-card .fct-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_tab();

        // Hover State
        $this->start_controls_tab(
            'tab_product_button_hover',
            [
                'label' => esc_html__('Hover', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'product_button_hover_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-product-card .fct-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'product_button_hover_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .fct-product-card .fct-button:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'product_button_hover_border',
                'selector' => '{{WRAPPER}} .fct-product-card .fct-button:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'product_button_hover_box_shadow',
                'selector' => '{{WRAPPER}} .fct-product-card .fct-button:hover',
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $isEditor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        AssetLoader::loadProductArchiveAssets();
        // Build shortcode attributes from widget settings
        $shortcodeAtts = [
            'per_page'                         => $settings['per_page'] ?? 10,
            'view_mode'                        => $settings['view_mode'] ?? 'grid',
            'paginator'                        => $settings['paginator'] ?? 'scroll',
            'price_format'                     => $settings['price_format'] ?? 'starts_from',
            'order_type'                       => $settings['order_type'] ?? 'DESC',
            'product_box_grid_size'            => $settings['product_box_grid_size'] ?? 4,
            'product_grid_size'                => $settings['product_box_grid_size'] ?? 4,
            'use_default_style'                => ($settings['use_default_style'] ?? '') === 'yes' ? 1 : 0,
            'enable_filter'                    => ($settings['enable_filter'] ?? '') === 'yes' ? 1 : 0,
            'live_filter'                      => ($settings['live_filter'] ?? '') === 'yes' ? 1 : 0,
            'enable_wildcard_filter'           => ($settings['enable_wildcard_filter'] ?? '') === 'yes' ? 1 : 0,
            'enable_wildcard_for_post_content' => ($settings['enable_wildcard_for_post_content'] ?? '') === 'yes' ? 1 : 0,
        ];

        // Build a transient cache key based on the relevant settings
        $cacheKey = 'fce_shop_app_' . md5(wp_json_encode($shortcodeAtts));

        if (!$isEditor) {
            $cached = get_transient($cacheKey);
            if ($cached) {
                echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                return;
            }
        }



        $handler = new ShopAppHandler();
        $output  = $handler->handelShortcodeCall($shortcodeAtts);

        $html = '<div class="fluent-cart-elementor-shop-app">' . $output . '</div>';

        if (!$isEditor) {
            set_transient($cacheKey, $html, 4 * HOUR_IN_SECONDS);
        }

        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
