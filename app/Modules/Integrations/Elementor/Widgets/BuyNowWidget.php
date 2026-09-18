<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use FluentCart\App\Models\Product;
use FluentCart\App\Models\ProductVariation;
use FluentCart\App\Services\Renderer\ProductRenderer;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls\ProductVariationSelectControl;

class BuyNowWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_buy_now';
    }

    public function get_title()
    {
        return esc_html__('Buy Now Button', 'fluent-cart-elementor-blocks');
    }

    public function get_icon()
    {
        return 'eicon-e-button fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['cart', 'button', 'product', 'commerce', 'fluent', 'buy', 'checkout'];
    }

    protected function register_controls()
    {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'fluent-cart-elementor-blocks'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'variant_id',
            [
                'label' => esc_html__('Select Product Variation', 'fluent-cart-elementor-blocks'),
                'type' => (new ProductVariationSelectControl())->get_type(),
                'label_block' => true,
                'description' => esc_html__('Search and select the product variation (Non-subscription only).', 'fluent-cart-elementor-blocks'),
                'default' => '',
                'placeholder' => esc_html__('Search for a variation...', 'fluent-cart-elementor-blocks'),
            ]
        );

        $this->add_control(
            'text',
            [
                'label'       => esc_html__('Button Text', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::TEXT,
                'default'     => esc_html__('Buy Now', 'fluent-cart-elementor-blocks'),
                'placeholder' => esc_html__('Buy Now', 'fluent-cart-elementor-blocks'),
                'dynamic'     => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'enable_modal_checkout',
            [
                'label'        => esc_html__('Enable Modal Checkout', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Button Style', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'button_typography',
                'selector' => '{{WRAPPER}} .wp-block-button__link',
            ]
        );

        $this->start_controls_tabs('tabs_button_style');

        // Normal State
        $this->start_controls_tab(
            'tab_button_normal',
            [
                'label' => esc_html__('Normal', 'fluent-cart-elementor-blocks'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wp-block-button__link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'button_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .wp-block-button__link',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'button_border',
                'selector' => '{{WRAPPER}} .wp-block-button__link',
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                // No default: inherit WordPress core's button radius
                // (:where(.wp-block-button__link){border-radius:9999px}) so the
                // button matches the Gutenberg Buy Now block out of the box.
                // Setting a default here would override that pill. User-overridable.
                'selectors'  => [
                    '{{WRAPPER}} .wp-block-button__link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .wp-block-button__link',
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .wp-block-button__link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_margin',
            [
                'label'      => esc_html__('Margin', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .wp-block-button__link' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_tab();

        // Hover State
        $this->start_controls_tab(
            'tab_button_hover',
            [
                'label' => esc_html__('Hover', 'fluent-cart-elementor-blocks'),
            ]
        );

        $this->add_control(
            'button_hover_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wp-block-button__link:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'button_hover_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .wp-block-button__link:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'button_hover_border',
                'selector' => '{{WRAPPER}} .wp-block-button__link:hover',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'button_hover_box_shadow',
                'selector' => '{{WRAPPER}} .wp-block-button__link:hover',
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    protected function render()
    {

        $settings = $this->get_settings_for_display();
        $variantId = $settings['variant_id'];

        // No variant selected (or it no longer exists / its product is gone).
        // Like core's Gutenberg Buy Now block — which always renders the button
        // and treats the variant as optional config — show the button anyway in
        // the editor so the design is visible and every Style control has a
        // target. The front end renders nothing: a Buy Now button with no variant
        // has nothing to check out.
        $variation = !empty($variantId) ? ProductVariation::query()->find($variantId) : null;
        $product = $variation ? Product::query()->find($variation->post_id) : null;

        if (!$product) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                AssetLoader::loadAddToCartCss();
                $this->renderFallbackButton($settings['text']);
            }
            return;
        }

        // Load assets
        AssetLoader::loadAddToCartCss();

        $attributes = [
            'variant_ids'           => [$variantId],
            'text'                  => $settings['text'],
            'enable_modal_checkout' => $settings['enable_modal_checkout'] === 'yes',
            'is_shortcode'          => true, 
        ];

        ?>
        <div class="fluent-cart-elementor-buy-now">
            <?php
            (new ProductRenderer($product, ['default_variation_id' => $variantId]))->renderBuyNowButtonBlock($attributes);
            ?>
        </div>
        <?php
    }

    /**
     * Editor-only fallback button, shown when no valid variant is selected yet.
     * Mirrors core's Gutenberg Buy Now block, where the button always renders and
     * the variant is optional config. Uses the same wp-block-button__link anchor
     * the real button outputs so the Style controls apply. Non-functional until a
     * variant is chosen.
     *
     * @param string $text
     * @return void
     */
    private function renderFallbackButton($text)
    {
        $text = ($text !== null && $text !== '') ? $text : __('Buy Now', 'fluent-cart-elementor-blocks');

        echo '<div class="fluent-cart-elementor-buy-now">';
        echo '<a class="wp-block-button__link wp-element-button" role="button">' . esc_html($text) . '</a>';
        echo '</div>';
    }
}
