<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ProductWidgetTrait;

if (!defined('ABSPATH')) {
    exit;
}

class RelatedProductsWidget extends Widget_Base
{
    use ProductWidgetTrait;

    public function get_name()
    {
        return 'fluentcart_related_products';
    }

    public function get_title()
    {
        return esc_html__('Related Products', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-container-grid fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['product', 'related', 'similar', 'carousel', 'fluent'];
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

        $this->end_controls_section();

        $this->registerHeadingStyleControls();
        $this->registerGridStyleControls();
        $this->registerCardStyleControls();
        $this->registerTitleStyleControls();
        $this->registerPriceStyleControls();
        $this->registerButtonStyleControls();
    }

    /* ------------------------------------------------------------------ */
    /* Style tab — selectors over core's stable fct-product-list-* /       */
    /* fct-product-card-* classes; !important where core scss colors the   */
    /* element directly (see the Order Receipt widget for the pattern).    */
    /* ------------------------------------------------------------------ */

    private function registerHeadingStyleControls()
    {
        $this->start_controls_section(
            'style_heading',
            [
                'label' => esc_html__('Heading', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'heading_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-product-list-heading' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'heading_typography',
                'selector' => '{{WRAPPER}} .fct-product-list-heading',
            ]
        );

        $this->add_responsive_control(
            'heading_spacing',
            [
                'label'      => esc_html__('Spacing', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 80],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .fct-product-list-heading' => 'margin-bottom: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerGridStyleControls()
    {
        $this->start_controls_section(
            'style_grid',
            [
                'label' => esc_html__('Grid', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        // Core's list grid is driven by its own CSS variable — set that
        // instead of fighting its grid-template rule.
        $this->add_responsive_control(
            'grid_columns',
            [
                'label'      => esc_html__('Columns', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'range'      => [
                    'px' => ['min' => 1, 'max' => 6, 'step' => 1],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .fct-product-list' => '--fct-product-list-columns: {{SIZE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'grid_gap',
            [
                'label'      => esc_html__('Gap', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 60],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .fct-product-list' => 'gap: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerCardStyleControls()
    {
        $this->start_controls_section(
            'style_card',
            [
                'label' => esc_html__('Card', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $card = '{{WRAPPER}} .fct-product-card';

        $this->add_control(
            'card_background',
            [
                'label'     => esc_html__('Background Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $card => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'card_border',
                'selector' => $card,
            ]
        );

        $this->add_responsive_control(
            'card_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    $card => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_box_shadow',
                'selector' => $card,
            ]
        );

        $this->add_responsive_control(
            'card_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    $card => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerTitleStyleControls()
    {
        $this->start_controls_section(
            'style_title',
            [
                'label' => esc_html__('Product Title', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $title = '{{WRAPPER}} .fct-product-card-title, {{WRAPPER}} .fct-product-card-title a';

        $this->add_control(
            'title_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $title => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'title_hover_color',
            [
                'label'     => esc_html__('Hover Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-product-card-title:hover, {{WRAPPER}} .fct-product-card-title a:hover' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .fct-product-card-title',
            ]
        );

        $this->end_controls_section();
    }

    private function registerPriceStyleControls()
    {
        $this->start_controls_section(
            'style_price',
            [
                'label' => esc_html__('Price', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-item-price' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'price_typography',
                'selector' => '{{WRAPPER}} .fct-item-price',
            ]
        );

        $this->add_control(
            'compare_price_color',
            [
                'label'     => esc_html__('Compare Price Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'separator' => 'before',
                'selectors' => [
                    '{{WRAPPER}} .fct-compare-price' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerButtonStyleControls()
    {
        $this->start_controls_section(
            'style_button',
            [
                'label' => esc_html__('Button', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        // Covers all card button variants: View Options / Buy Now render as
        // fct-product-view-button, but Add To Cart (simple products with
        // modal checkout) uses its own fluent-cart-add-to-cart-button class.
        $bases = [
            '{{WRAPPER}} .fct-product-card .fct-product-view-button',
            '{{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button',
        ];
        $hoverBases = array_map(function ($base) {
            return $base . ':hover';
        }, $bases);
        $withText = function (array $selectors) {
            $texts = array_map(function ($base) {
                return $base . ' .fct-button-text';
            }, $selectors);

            return implode(', ', array_merge($selectors, $texts));
        };

        $button = implode(', ', $bases);
        $buttonText = $withText($bases);
        $buttonHover = implode(', ', $hoverBases);
        $buttonHoverText = $withText($hoverBases);

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'button_typography',
                'selector' => $buttonText,
            ]
        );

        $this->start_controls_tabs('button_tabs');

        $this->start_controls_tab(
            'button_tab_normal',
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
                    $buttonText => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'button_background',
            [
                'label'     => esc_html__('Background Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $button => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'button_tab_hover',
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
                    $buttonHoverText => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'button_hover_background',
            [
                'label'     => esc_html__('Background Color', 'fluent-cart'),
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
                'name'      => 'button_border',
                'selector'  => $button,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'button_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    $button => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
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
        $settings = $this->get_settings_for_display();
        $product = $this->getProduct($settings);

        if (!$product) {
            // No product selected and none in context. In the editor, preview
            // with a real product (the latest published one) and render its
            // actual related products — the same "real data, never fabricated"
            // approach Elementor Pro's WooCommerce single-product widgets use. On
            // the front end we stay silent; if the store has no products at all,
            // the notice is shown instead.
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                $product = $this->getPreviewProduct();
            }

            if (!$product) {
                $this->renderPlaceholder(__('Please select a product or use this widget inside a product template.', 'fluent-cart'));
                return;
            }
        }

        AssetLoader::loadSingleProductAssets();

        $shortcodeAtts = 'id="' . (int) $product->ID . '"';

        $content = do_shortcode('[fluent_cart_related_products ' . $shortcodeAtts . ']');

        if (trim((string) $content) === '') {
            return;
        }

        echo '<div class="fluentcart-related-products">' . $content . '</div>';
    }
}
