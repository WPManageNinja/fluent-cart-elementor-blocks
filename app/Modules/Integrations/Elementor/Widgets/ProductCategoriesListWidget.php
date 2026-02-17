<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use FluentCart\App\Services\Renderer\ProductCategoriesListRenderer;
class ProductCategoriesListWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_product_categories_list';
    }

    public function get_title()
    {
        return esc_html__('Product Categories List', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-bullet-list';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['categories', 'list', 'dropdown', 'taxonomy', 'fluent', 'products'];
    }

    public function get_style_depends()
    {
        $this->registerAssets();

        $app = \FluentCart\App\App::getInstance();
        $slug = $app->config->get('app.slug');

        return [
            $slug . '-product-categories-list',
        ];
    }

    public function get_script_depends()
    {
        $this->registerAssets();

        $app = \FluentCart\App\App::getInstance();
        $slug = $app->config->get('app.slug');

        return [
            $slug . '-product-categories-list-js',
        ];
    }

    private function registerAssets()
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        $app = \FluentCart\App\App::getInstance();
        $slug = $app->config->get('app.slug');

        // Use FluentCart core's Vite since these assets live in the core plugin
        \FluentCart\App\Vite::enqueueStyle(
            $slug . '-product-categories-list',
            'public/product-categories-list/product-categories-list.scss'
        );

        \FluentCart\App\Vite::enqueueScript(
            $slug . '-product-categories-list-js',
            'public/product-categories-list/product-categories-list.js'
        );
    }

    protected function register_controls()
    {
        $this->registerSettingsControls();
        $this->registerListStyleControls();
        $this->registerCountStyleControls();
        $this->registerDropdownStyleControls();
    }

    private function registerSettingsControls()
    {
        $this->start_controls_section(
            'settings_section',
            [
                'label' => esc_html__('Settings', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'display_style',
            [
                'label'   => esc_html__('Display Style', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'list',
                'options' => [
                    'list'     => esc_html__('List', 'fluent-cart'),
                    'dropdown' => esc_html__('Dropdown', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'show_product_count',
            [
                'label'        => esc_html__('Show Product Count', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => esc_html__('Display the number of products in each category.', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'show_hierarchy',
            [
                'label'        => esc_html__('Show Hierarchy', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => esc_html__('Display child categories nested under their parents.', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'show_empty',
            [
                'label'        => esc_html__('Show Empty Categories', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => esc_html__('Display categories even if they have no products.', 'fluent-cart'),
            ]
        );

        $this->end_controls_section();
    }

    private function registerListStyleControls()
    {
        $this->start_controls_section(
            'list_style_section',
            [
                'label'     => esc_html__('List Items', 'fluent-cart'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'display_style' => 'list',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'list_typography',
                'selector' => '{{WRAPPER}} .fct-category-link',
            ]
        );

        $this->add_control(
            'list_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-category-link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'list_hover_color',
            [
                'label'     => esc_html__('Hover Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-category-link:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'list_item_spacing',
            [
                'label'      => esc_html__('Item Spacing', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 50],
                    'em' => ['min' => 0, 'max' => 3],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .fct-category-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'list_child_indent',
            [
                'label'      => esc_html__('Child Indent', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 100],
                    'em' => ['min' => 0, 'max' => 5],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .fct-categories-children' => 'padding-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerCountStyleControls()
    {
        $this->start_controls_section(
            'count_style_section',
            [
                'label'     => esc_html__('Product Count', 'fluent-cart'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_product_count' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'count_typography',
                'selector' => '{{WRAPPER}} .fct-category-count',
            ]
        );

        $this->add_control(
            'count_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-category-count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerDropdownStyleControls()
    {
        $this->start_controls_section(
            'dropdown_style_section',
            [
                'label'     => esc_html__('Dropdown', 'fluent-cart'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'display_style' => 'dropdown',
                ],
            ]
        );

        $this->add_control(
            'dropdown_select_heading',
            [
                'label' => esc_html__('Select Field', 'fluent-cart'),
                'type'  => Controls_Manager::HEADING,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'dropdown_typography',
                'selector' => '{{WRAPPER}} .fct-categories-dropdown',
            ]
        );

        $this->add_control(
            'dropdown_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-categories-dropdown' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dropdown_background',
            [
                'label'     => esc_html__('Background', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-categories-dropdown' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'dropdown_border',
                'selector' => '{{WRAPPER}} .fct-categories-dropdown',
            ]
        );

        $this->add_control(
            'dropdown_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .fct-categories-dropdown' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'dropdown_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .fct-categories-dropdown' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'dropdown_button_heading',
            [
                'label'     => esc_html__('Go Button', 'fluent-cart'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->start_controls_tabs('tabs_dropdown_button_style');

        $this->start_controls_tab(
            'tab_dropdown_button_normal',
            [
                'label' => esc_html__('Normal', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'dropdown_button_color',
            [
                'label'     => esc_html__('Icon Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-categories-go-btn'     => 'color: {{VALUE}};',
                    '{{WRAPPER}} .fct-categories-go-btn svg' => 'stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dropdown_button_background',
            [
                'label'     => esc_html__('Background', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-categories-go-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_dropdown_button_hover',
            [
                'label' => esc_html__('Hover', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'dropdown_button_hover_color',
            [
                'label'     => esc_html__('Icon Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-categories-go-btn:hover'     => 'color: {{VALUE}};',
                    '{{WRAPPER}} .fct-categories-go-btn:hover svg' => 'stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dropdown_button_hover_background',
            [
                'label'     => esc_html__('Background', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-categories-go-btn:hover' => 'background-color: {{VALUE}};',
                ],
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

        $this->registerAssets();

        $atts = [
            'display_style'      => $settings['display_style'] ?? 'list',
            'show_product_count' => ($settings['show_product_count'] ?? 'yes') === 'yes',
            'show_hierarchy'     => ($settings['show_hierarchy'] ?? 'yes') === 'yes',
            'show_empty'         => ($settings['show_empty'] ?? '') === 'yes',
            'is_shortcode'       => true, // Skip block wrapper attributes
        ];

        $editorClass = $isEditor ? ' fct-elementor-preview' : '';

        if ($isEditor) {
            ?>
            <style>
                .fct-elementor-preview a,
                .fct-elementor-preview .fct-categories-go-btn {
                    pointer-events: none;
                }
            </style>
            <?php
        }

        $renderer = new ProductCategoriesListRenderer();

        echo '<div class="fluent-cart-elementor-categories-list' . esc_attr($editorClass) . '">';
        $renderer->render($atts);
        echo '</div>';
    }
}