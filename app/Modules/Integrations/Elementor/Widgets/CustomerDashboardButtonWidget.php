<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\CustomerDashboardButtonRenderer;

class CustomerDashboardButtonWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_customer_dashboard_button';
    }

    public function get_title()
    {
        return esc_html__('Customer Dashboard Button', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-person';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['customer', 'dashboard', 'account', 'login', 'button', 'fluent', 'cart'];
    }

    protected function register_controls()
    {
        $this->registerContentControls();
        $this->registerButtonStyleControls();
        $this->registerIconStyleControls();
    }

    // ──────────────────────────────────────────────────────────
    // Content Controls
    // ──────────────────────────────────────────────────────────

    private function registerContentControls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'display_type',
            [
                'label'   => esc_html__('Display Type', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'button',
                'options' => [
                    'button' => esc_html__('Button', 'fluent-cart'),
                    'link'   => esc_html__('Link', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'       => esc_html__('Label', 'fluent-cart'),
                'type'        => Controls_Manager::TEXT,
                'default'     => esc_html__('My Account', 'fluent-cart'),
                'placeholder' => esc_html__('My Account', 'fluent-cart'),
                'dynamic'     => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'show_icon',
            [
                'label'     => esc_html__('Show Icon', 'fluent-cart'),
                'type'      => Controls_Manager::SWITCHER,
                'label_on'  => esc_html__('Yes', 'fluent-cart'),
                'label_off' => esc_html__('No', 'fluent-cart'),
                'default'   => 'yes',
            ]
        );

        $this->add_control(
            'link_target',
            [
                'label'   => esc_html__('Open In', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => '_self',
                'options' => [
                    '_self'  => esc_html__('Same Tab', 'fluent-cart'),
                    '_blank' => esc_html__('New Tab', 'fluent-cart'),
                ],
            ]
        );

        $this->end_controls_section();
    }

    // ──────────────────────────────────────────────────────────
    // Style Controls — Button
    // ──────────────────────────────────────────────────────────

    private function registerButtonStyleControls()
    {
        $btnSelector      = '{{WRAPPER}} .fct-customer-dashboard-btn, {{WRAPPER}} .fct-customer-dashboard-link';
        $btnHoverSelector = '{{WRAPPER}} .fct-customer-dashboard-btn:hover, {{WRAPPER}} .fct-customer-dashboard-link:hover';

        $this->start_controls_section(
            'button_style_section',
            [
                'label' => esc_html__('Button / Link', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'button_typography',
                'selector' => $btnSelector,
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    $btnSelector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('tabs_button_style');

        // ── Normal ─────────────────────────────────────────────
        $this->start_controls_tab(
            'tab_button_normal',
            [
                'label' => esc_html__('Normal', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $btnSelector => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'button_background',
                'types'    => ['classic', 'gradient'],
                'selector' => $btnSelector,
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'button_border',
                'selector' => $btnSelector,
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    $btnSelector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'button_box_shadow',
                'selector' => $btnSelector,
            ]
        );

        $this->end_controls_tab();

        // ── Hover ──────────────────────────────────────────────
        $this->start_controls_tab(
            'tab_button_hover',
            [
                'label' => esc_html__('Hover', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'button_hover_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $btnHoverSelector => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'button_hover_background',
                'types'    => ['classic', 'gradient'],
                'selector' => $btnHoverSelector,
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'button_hover_border',
                'selector' => $btnHoverSelector,
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'button_hover_box_shadow',
                'selector' => $btnHoverSelector,
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    // ──────────────────────────────────────────────────────────
    // Style Controls — Icon
    // ──────────────────────────────────────────────────────────

    private function registerIconStyleControls()
    {
        $iconSelector = '{{WRAPPER}} .fct-customer-dashboard-icon svg';

        $this->start_controls_section(
            'icon_style_section',
            [
                'label'     => esc_html__('Icon', 'fluent-cart'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_icon' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label'      => esc_html__('Size', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 8, 'max' => 80],
                    'em' => ['min' => 0.5, 'max' => 5],
                ],
                'selectors'  => [
                    $iconSelector => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $iconSelector => 'fill: {{VALUE}}; color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_gap',
            [
                'label'      => esc_html__('Gap (between icon and label)', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 30],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .fct-customer-dashboard-icon' => 'margin-inline-end: {{SIZE}}{{UNIT}};',
                ],
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

        $allowedDisplayTypes = ['button', 'link'];
        $displayType = in_array($settings['display_type'] ?? '', $allowedDisplayTypes, true)
            ? $settings['display_type']
            : 'button';

        $allowedTargets = ['_self', '_blank'];
        $linkTarget = in_array($settings['link_target'] ?? '', $allowedTargets, true)
            ? $settings['link_target']
            : '_self';

        $showIcon = in_array($settings['show_icon'] ?? '', ['yes', 'no'], true)
            ? $settings['show_icon'] === 'yes'
            : true;

        $buttonText = sanitize_text_field($settings['button_text'] ?? '');
        if (empty($buttonText)) {
            $buttonText = __('My Account', 'fluent-cart');
        }

        AssetLoader::markFrontendAssetsRequired();

        $atts = [
            'display_type' => $displayType,
            'button_text'  => $buttonText,
            'show_icon'    => $showIcon,
            'link_target'  => $linkTarget,
            'is_shortcode' => true,
        ];

        (new CustomerDashboardButtonRenderer())->render($atts);
    }
}
