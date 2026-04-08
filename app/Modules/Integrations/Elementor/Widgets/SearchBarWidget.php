<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use FluentCart\App\CPT\FluentProducts;
use FluentCart\App\Helpers\Helper;
use FluentCart\App\Services\Renderer\SearchBarRenderer;
use FluentCart\Framework\Support\Collection;

class SearchBarWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_search_bar';
    }

    public function get_title()
    {
        return esc_html__('Product Search Bar', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-search';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['search', 'products', 'bar', 'fluent', 'cart'];
    }

    public function get_style_depends()
    {
        $this->registerSearchBarAssets();

        return [
            'fluentcart-search-bar-app',
        ];
    }

    public function get_script_depends()
    {
        $this->registerSearchBarAssets();

        return [
            'fluentcart-search-bar-app',
        ];
    }

    private function registerSearchBarAssets()
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        \FluentCart\App\Vite::enqueueStyle(
            'fluentcart-search-bar-app',
            'public/search-bar-app/style/style.scss'
        );

        \FluentCart\App\Vite::enqueueScript(
            'fluentcart-search-bar-app',
            'public/search-bar-app/SearchBarApp.js',
            ['jquery']
        )->with([
            'fluentcart_search_bar_vars' => [
                'rest' => Helper::getRestInfo(),
            ],
        ]);
    }

    protected function register_controls()
    {
        $this->registerContentControls();
        $this->registerStyleControls();
    }

    private function registerContentControls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Search Bar', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_category_filter',
            [
                'label'        => esc_html__('Show Category Filter', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => esc_html__('Display a category dropdown alongside the search input.', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'url_mode',
            [
                'label'   => esc_html__('Link Target', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => '',
                'options' => [
                    ''        => esc_html__('Same Tab', 'fluent-cart'),
                    'new-tab' => esc_html__('New Tab', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'link_with_shop_app',
            [
                'label'        => esc_html__('Link With Shop App', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => esc_html__('When enabled, search results will update a Shop App widget on the same page instead of navigating.', 'fluent-cart'),
            ]
        );

        $this->end_controls_section();
    }

    private function registerStyleControls()
    {
        // ── Wrapper ─────────────────────────────────────────────────
        $this->start_controls_section(
            'wrapper_style_section',
            [
                'label' => esc_html__('Search Bar Wrapper', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'wrapper_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'wrapper_border',
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper',
            ]
        );

        $this->add_control(
            'wrapper_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'wrapper_box_shadow',
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper',
            ]
        );

        $this->add_responsive_control(
            'wrapper_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ── Input ────────────────────────────────────────────────────
        $this->start_controls_section(
            'input_style_section',
            [
                'label' => esc_html__('Search Input', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('tabs_input_style');

        // Normal
        $this->start_controls_tab(
            'tab_input_normal',
            [
                'label' => esc_html__('Normal', 'fluent-cart'),
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'input_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-input-wrap',
            ]
        );

        $this->add_control(
            'input_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-input' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_placeholder_color',
            [
                'label'     => esc_html__('Placeholder Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-input::placeholder' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_icon_color',
            [
                'label'     => esc_html__('Search Icon Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-input-search svg' => 'stroke: {{VALUE}}; color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'input_typography',
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-input',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'input_border',
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-input-wrap',
            ]
        );

        $this->add_control(
            'input_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-input-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'input_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_tab();

        // Focus
        $this->start_controls_tab(
            'tab_input_focus',
            [
                'label' => esc_html__('Focus', 'fluent-cart'),
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'input_focus_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-input-wrap:focus-within',
            ]
        );

        $this->add_control(
            'input_focus_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-input:focus' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'input_focus_border',
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-input-wrap:focus-within',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // ── Category Dropdown ────────────────────────────────────────
        $this->start_controls_section(
            'category_style_section',
            [
                'label'     => esc_html__('Category Dropdown', 'fluent-cart'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_category_filter' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'category_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper-select-container select',
            ]
        );

        $this->add_control(
            'category_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper-select-container select' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'category_typography',
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper-select-container select',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'category_border',
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper-select-container select',
            ]
        );

        $this->add_control(
            'category_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper-select-container select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'category_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper-select-container select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ── Results Dropdown ─────────────────────────────────────────
        $this->start_controls_section(
            'results_style_section',
            [
                'label' => esc_html__('Results Dropdown', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'results_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper-result-wrap',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'results_border',
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper-result-wrap',
            ]
        );

        $this->add_control(
            'results_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper-result-wrap' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'results_box_shadow',
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-wrapper-result-wrap',
            ]
        );

        $this->add_control(
            'results_item_color',
            [
                'label'     => esc_html__('Item Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-list-wrapper li a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'results_item_hover_color',
            [
                'label'     => esc_html__('Item Hover Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fluent-cart-search-bar-app-list-wrapper li:hover a' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .fluent-cart-search-bar-app-list-wrapper li:hover'   => 'background-color: {{VALUE}}1a;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'results_item_typography',
                'selector' => '{{WRAPPER}} .fluent-cart-search-bar-app-list-wrapper li a',
            ]
        );

        $this->end_controls_section();
    }

    // ──────────────────────────────────────────────────────────
    // Render
    // ──────────────────────────────────────────────────────────

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        // Validate settings
        $showCategoryFilter = in_array(
            $settings['show_category_filter'] ?? '',
            ['yes', ''],
            true
        ) ? ($settings['show_category_filter'] ?? '') : '';

        $urlMode = in_array(
            $settings['url_mode'] ?? '',
            ['', 'new-tab'],
            true
        ) ? sanitize_text_field($settings['url_mode'] ?? '') : '';

        $linkWithShopApp = in_array(
            $settings['link_with_shop_app'] ?? '',
            ['yes', ''],
            true
        ) ? ($settings['link_with_shop_app'] ?? '') : '';

        // Enqueue search bar assets
        $this->registerSearchBarAssets();

        // Build termData for category dropdown
        $termData = [];
        if ($showCategoryFilter === 'yes') {
            $termData = $this->getTermsData();
        }

        // Build config for renderer
        $config = [
            'url_mode'           => $urlMode,
            'category_mode'      => $showCategoryFilter === 'yes',
            'termData'           => $termData,
            'link_with_shop_app' => $linkWithShopApp === 'yes',
        ];

        (new SearchBarRenderer($config))->render();
    }

    private function getTermsData(): array
    {
        $options = get_categories([
            'taxonomy'  => 'product-categories',
            'post_type' => FluentProducts::CPT_NAME,
        ]);

        return Collection::make($options)->map(function ($meta) {
            return [
                'termId'   => $meta->term_id,
                'termName' => $meta->name,
            ];
        })->toArray();
    }
}
