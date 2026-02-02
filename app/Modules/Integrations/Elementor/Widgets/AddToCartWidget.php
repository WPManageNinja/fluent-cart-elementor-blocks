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

class AddToCartWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_add_to_cart';
    }

    public function get_title()
    {
        return esc_html__('Add to Cart', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-cart';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['cart', 'button', 'product', 'commerce', 'fluent'];
    }

    protected function register_controls()
    {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'fluent-cart'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'variant_id',
            [
                'label' => esc_html__('Select Product Variation', 'fluent-cart'),
                'type' => (new ProductVariationSelectControl())->get_type(),
                'label_block' => true,
                'description' => esc_html__('Search and select the product variation.', 'fluent-cart'),
                'default' => '',
                'placeholder' => esc_html__('Search for a variation...', 'fluent-cart'),
                'query_params' => [
                        'subscription_status' => 'not_subscribable'
                ]
            ]
        );

        $this->add_control(
            'text',
            [
                'label'       => esc_html__('Button Text', 'fluent-cart'),
                'type'        => Controls_Manager::TEXT,
                'default'     => esc_html__('Add to Cart', 'fluent-cart'),
                'placeholder' => esc_html__('Add to Cart', 'fluent-cart'),
                'dynamic'     => [
                    'active' => true,
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Button Style', 'fluent-cart'),
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
                'label' => esc_html__('Normal', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
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
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
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
                'label'      => esc_html__('Padding', 'fluent-cart'),
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
                'label'      => esc_html__('Margin', 'fluent-cart'),
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
                'label' => esc_html__('Hover', 'fluent-cart'),
            ]
        );

        $this->add_control(
            'button_hover_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
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

        if (empty($variantId)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="fluent-cart-placeholder" style="text-align:center; padding: 20px; background: #f0f0f1; border: 1px dashed #ccc;">';
                echo '<p>' . esc_html__('Please enter a Product Variant ID.', 'fluent-cart') . '</p>';
                echo '</div>';
            }
            return;
        }

        $variation = ProductVariation::query()->find($variantId);


        if (!$variation) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="fluent-cart-placeholder" style="text-align:center; padding: 20px; background: #f0f0f1; border: 1px dashed #ccc;">';
                echo '<p>' . esc_html__('Invalid Variant ID. Product not found.', 'fluent-cart') . '</p>';
                echo '</div>';
            }
            return;
        }


        $product = Product::query()->find($variation->post_id);

        if (!$product) {
            return;
        }



        // Load assets
        AssetLoader::loadAddToCartCss();

        $attributes = [
            'variant_ids'  => [$variantId],
            'text'         => $settings['text'],
            'is_shortcode' => true, // Use shortcode mode to avoid Gutenberg wrapper conflict, but we style manually
        ];

        // The ProductRenderer renders the button. 
        // We wrap it to ensure our selectors match if needed, but the selectors target .wp-block-button__link directly
        // which is what ProductRenderer outputs.

        ?>
        <div class="fluent-cart-elementor-add-to-cart">
            <?php
            (new ProductRenderer($product, ['default_variation_id' => $variantId]))->renderAddToCartButtonBlock($attributes);
            ?>
        </div>
        <?php
    }
}
