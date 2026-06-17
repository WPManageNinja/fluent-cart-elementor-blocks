<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use FluentCart\App\Hooks\Cart\CartLoader;
use FluentCart\App\Helpers\CartHelper;
use FluentCart\Api\Resource\FrontendResource\CartResource;
use FluentCart\App\Services\Renderer\CartDrawerRenderer;
use FluentCart\App\Services\Renderer\MiniCartRenderer;
use FluentCart\Framework\Support\Arr;
use FluentCart\App\Modules\Templating\AssetLoader;

class MiniCartWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_mini_cart';
    }

    public function get_title()
    {
        return esc_html__('Mini Cart (FluentCart)', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-cart-light';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['cart', 'mini cart', 'commerce', 'fluent'];
    }

    public function get_style_depends()
    {
        // Register+enqueue FluentCart mini cart styles so they're
        // available in the Elementor editor preview iframe.
        AssetLoader::loadMiniCartAssets();

        $app = \FluentCart\App\App::getInstance();
        $slug = $app->config->get('app.slug');

        return [
            $slug . '-mini-cart',
        ];
    }

    protected function register_controls()
    {
        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Cart Icon Style', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'cart_typography',
                'selector' => '{{WRAPPER}} .fluent_cart_mini_cart_trigger',
            ]
        );

        $this->start_controls_tabs('tabs_cart_style');

        // Normal State
        $this->start_controls_tab(
            'tab_cart_normal',
            [
                'label' => esc_html__('Normal', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'cart_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fluent_cart_mini_cart_trigger' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'cart_icon_color',
            [
                'label'     => esc_html__('Icon Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fluent_cart_mini_cart_trigger svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'cart_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .fluent_cart_mini_cart_trigger',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'cart_border',
                'selector' => '{{WRAPPER}} .fluent_cart_mini_cart_trigger',
            ]
        );

        $this->add_control(
            'cart_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .fluent_cart_mini_cart_trigger' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'cart_box_shadow',
                'selector' => '{{WRAPPER}} .fluent_cart_mini_cart_trigger',
            ]
        );

        $this->add_responsive_control(
            'cart_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .fluent_cart_mini_cart_trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'cart_margin',
            [
                'label'      => esc_html__('Margin', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .fluent_cart_mini_cart_trigger' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_tab();

        // Hover State
        $this->start_controls_tab(
            'tab_cart_hover',
            [
                'label' => esc_html__('Hover', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'cart_hover_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fluent_cart_mini_cart_trigger:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'cart_hover_icon_color',
            [
                'label'     => esc_html__('Icon Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fluent_cart_mini_cart_trigger:hover svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'cart_hover_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .fluent_cart_mini_cart_trigger:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'cart_hover_border',
                'selector' => '{{WRAPPER}} .fluent_cart_mini_cart_trigger:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'cart_hover_box_shadow',
                'selector' => '{{WRAPPER}} .fluent_cart_mini_cart_trigger:hover',
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    protected function render()
    {


        (new CartLoader())->registerDependency();
        AssetLoader::loadMiniCartAssets();

        $cart = CartHelper::getCart(null, false);
        $itemCount = 0;
        $cartData = [];

        if ($cart) {
            $cartData= $cart->cart_data ?? [];
            $itemCount = count($cartData);
        }

        $miniCartRenderer = new MiniCartRenderer($cartData, [
            'item_count' => $itemCount
        ]);
        
        $attributes = [
                'is_shortcode' => true,
                'button_class' => 'fluent_cart_mini_cart_trigger',
        ];
        $settings = $this->get_settings_for_display();
        //ds($settings);

        ?>
        <div class="fluent-cart-elementor-mini-cart">
            <?php
            $miniCartRenderer->renderMiniCart($attributes);
            ?>
        </div>
        <?php
    }
}
