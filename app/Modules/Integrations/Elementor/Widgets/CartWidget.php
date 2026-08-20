<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use FluentCart\App\Modules\Templating\AssetLoader;

/**
 * Full shopping-cart widget — renders FluentCart core's [fluent_cart_cart]
 * (item rows, quantity steppers, totals, and the empty-cart state). A thin
 * wrapper like the Divi Cart module: all markup, styling and behaviour come
 * from core, so the template stays portable and always reflects the live cart.
 */
class CartWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_cart';
    }

    public function get_title()
    {
        return esc_html__('Cart', 'fluent-cart-elementor-blocks');
    }

    public function get_icon()
    {
        return 'eicon-basket-medium fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['cart', 'shopping cart', 'basket', 'checkout', 'commerce', 'fluent'];
    }

    public function get_style_depends()
    {
        // Enqueue the cart CSS/JS so the widget renders correctly in the
        // editor preview iframe (on the front end the shortcode enqueues them).
        AssetLoader::loadCartAssets();

        return [];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_cart',
            [
                'label' => esc_html__('Cart', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'cart_info',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('Renders the FluentCart shopping cart — item rows, quantities, totals and the empty-cart state. Styling and behaviour come from FluentCart core.', 'fluent-cart-elementor-blocks'),
                'content_classes' => 'elementor-descriptor',
            ]
        );

        $this->end_controls_section();

        $this->registerItemRowStyleControls();
        $this->registerCheckoutButtonStyleControls();
    }

    /* ------------------------------------------------------------------ */
    /* Style tab — Divi Cart module parity (Item Row + Checkout Button     */
    /* design groups) over core's stable cart classes. !important where    */
    /* core scss colors elements directly.                                 */
    /* ------------------------------------------------------------------ */

    private function registerItemRowStyleControls()
    {
        $this->start_controls_section(
            'style_item_row',
            [
                'label' => esc_html__('Item Row', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $row = '{{WRAPPER}} .fct-cart-item';

        $this->add_control(
            'row_background',
            [
                'label'     => esc_html__('Background Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $row => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'row_border',
                'selector' => $row,
            ]
        );

        $this->add_responsive_control(
            'row_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    $row => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'row_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    $row => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'row_spacing',
            [
                'label'      => esc_html__('Row Spacing', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'selectors'  => [
                    $row . ':not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerCheckoutButtonStyleControls()
    {
        $this->start_controls_section(
            'style_checkout_button',
            [
                'label' => esc_html__('Checkout Button', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $button = '{{WRAPPER}} .fct-cart-page .checkout-button';
        $buttonHover = '{{WRAPPER}} .fct-cart-page .checkout-button:hover';

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'checkout_button_typography',
                'selector' => $button,
            ]
        );

        $this->start_controls_tabs('checkout_button_tabs');

        $this->start_controls_tab(
            'checkout_button_tab_normal',
            ['label' => esc_html__('Normal', 'fluent-cart-elementor-blocks')]
        );

        $this->add_control(
            'checkout_button_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $button => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'checkout_button_background',
            [
                'label'     => esc_html__('Background Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $button => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'checkout_button_tab_hover',
            ['label' => esc_html__('Hover', 'fluent-cart-elementor-blocks')]
        );

        $this->add_control(
            'checkout_button_hover_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $buttonHover => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'checkout_button_hover_background',
            [
                'label'     => esc_html__('Background Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $buttonHover => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'checkout_button_border',
                'selector'  => $button,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'checkout_button_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    $button => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'checkout_button_box_shadow',
                'selector' => $button,
            ]
        );

        $this->add_responsive_control(
            'checkout_button_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    $button => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        // Same delegation as the [fluent_cart_cart] shortcode / the Divi Cart
        // module — core renders the item rows and the empty-cart state.
        echo do_shortcode('[fluent_cart_cart]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
