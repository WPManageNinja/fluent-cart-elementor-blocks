<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Repeater;
use FluentCart\App\Models\Product;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductCardRender;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls\ProductSelectControl;

class ProductCardWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_product_card';
    }

    public function get_title()
    {
        return esc_html__('Product Card', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-image-box';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['product', 'card', 'item', 'fluent', 'commerce'];
    }

    public function get_style_depends()
    {
        AssetLoader::loadProductArchiveAssets();

        return [
            'fluentcart-product-card-page-css',
            'fluentcart-single-product-css',
            'fluentcart-add-to-cart-btn-css',
            'fluentcart-direct-checkout-btn-css',
        ];
    }

    protected function register_controls()
    {
        $this->registerContentControls();
        $this->registerCardLayoutControls();
        $this->registerStyleControls();
    }

    private function registerContentControls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Product', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'product_id',
            [
                'label'       => esc_html__('Select Product', 'fluent-cart'),
                'type'        => (new ProductSelectControl())->get_type(),
                'multiple'    => false,
                'label_block' => true,
                'description' => esc_html__('Search and select a product to display.', 'fluent-cart'),
                'default'     => '',
                'placeholder' => esc_html__('Search for a product...', 'fluent-cart'),
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

        $this->end_controls_section();
    }

    private function registerCardLayoutControls()
    {
        $this->start_controls_section(
            'card_layout_section',
            [
                'label' => esc_html__('Card Layout', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'element_type',
            [
                'label'   => esc_html__('Element', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'image',
                'options' => [
                    'image'   => esc_html__('Image', 'fluent-cart'),
                    'title'   => esc_html__('Title', 'fluent-cart'),
                    'excerpt' => esc_html__('Excerpt', 'fluent-cart'),
                    'price'   => esc_html__('Price', 'fluent-cart'),
                    'button'  => esc_html__('Button', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'card_elements',
            [
                'label'       => esc_html__('Card Elements', 'fluent-cart'),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [
                    ['element_type' => 'image'],
                    ['element_type' => 'title'],
                    ['element_type' => 'price'],
                    ['element_type' => 'button'],
                ],
                'title_field' => '{{{ element_type.charAt(0).toUpperCase() + element_type.slice(1) }}}',
            ]
        );

        $this->end_controls_section();
    }

    private function registerStyleControls()
    {
        $this->start_controls_section(
            'card_style_section',
            [
                'label' => esc_html__('Product Card', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        static::registerCardStyleControls($this, '{{WRAPPER}} .fct-product-card');
        $this->end_controls_section();

        $this->start_controls_section(
            'image_style_section',
            [
                'label' => esc_html__('Product Image', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        static::registerCardImageStyleControls($this, '{{WRAPPER}} .fct-product-card-image');
        $this->end_controls_section();

        $this->start_controls_section(
            'title_style_section',
            [
                'label' => esc_html__('Product Title', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        static::registerCardTitleStyleControls($this, '{{WRAPPER}} .fct-product-card-title');
        $this->end_controls_section();

        $this->start_controls_section(
            'excerpt_style_section',
            [
                'label' => esc_html__('Product Excerpt', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        static::registerCardExcerptStyleControls($this, '{{WRAPPER}} .fct-product-card-excerpt');
        $this->end_controls_section();

        $this->start_controls_section(
            'price_style_section',
            [
                'label' => esc_html__('Product Price', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        static::registerCardPriceStyleControls($this, '{{WRAPPER}} .fct-product-card-prices');
        $this->end_controls_section();

        $btnSelector = '{{WRAPPER}} .fct-product-card .fct-product-view-button, {{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button';
        $btnHoverSelector = '{{WRAPPER}} .fct-product-card .fct-product-view-button:hover, {{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button:hover';

        $this->start_controls_section(
            'button_style_section',
            [
                'label' => esc_html__('Product Button', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        static::registerCardButtonStyleControls($this, $btnSelector, $btnHoverSelector);
        $this->end_controls_section();
    }

    // ──────────────────────────────────────────────────────────
    // Public static style methods — callable by any widget
    // ──────────────────────────────────────────────────────────

    /**
     * Card container: background, border, radius, shadow, padding.
     */
    public static function registerCardStyleControls($widget, $selector)
    {
        $widget->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'card_background',
                'types'    => ['classic', 'gradient'],
                'selector' => $selector,
            ]
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'card_border',
                'selector' => $selector,
            ]
        );

        $widget->add_control(
            'card_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    $selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_box_shadow',
                'selector' => $selector,
            ]
        );

        $widget->add_responsive_control(
            'card_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
    }

    /**
     * Product image: height, object-fit, border, radius, shadow, padding.
     */
    public static function registerCardImageStyleControls($widget, $selector)
    {
        $wrapSelector = str_replace('-image', '-image-wrap', $selector);

        $widget->add_responsive_control(
            'image_height',
            [
                'label'      => esc_html__('Height', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'vh'],
                'range'      => [
                    'px' => ['min' => 50, 'max' => 800],
                    'em' => ['min' => 3, 'max' => 50],
                    'vh' => ['min' => 5, 'max' => 100],
                ],
                'selectors'  => [
                    $selector => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_control(
            'image_object_fit',
            [
                'label'     => esc_html__('Object Fit', 'fluent-cart'),
                'type'      => Controls_Manager::SELECT,
                'default'   => '',
                'options'   => [
                    ''        => esc_html__('Default', 'fluent-cart'),
                    'cover'   => esc_html__('Cover', 'fluent-cart'),
                    'contain' => esc_html__('Contain', 'fluent-cart'),
                    'fill'    => esc_html__('Fill', 'fluent-cart'),
                ],
                'selectors' => [
                    $selector => 'object-fit: {{VALUE}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'image_border',
                'selector' => $selector,
            ]
        );

        $widget->add_control(
            'image_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    $selector     => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    $wrapSelector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'image_box_shadow',
                'selector' => $selector,
            ]
        );

        $widget->add_responsive_control(
            'image_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    $wrapSelector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
    }

    /**
     * Product title: typography, color, hover color, spacing.
     */
    public static function registerCardTitleStyleControls($widget, $selector)
    {
        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'selector' => $selector,
            ]
        );

        $widget->add_control(
            'title_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector          => 'color: {{VALUE}};',
                    $selector . ' a'   => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'title_hover_color',
            [
                'label'     => esc_html__('Hover Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ':hover'   => 'color: {{VALUE}};',
                    $selector . ' a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'title_spacing',
            [
                'label'      => esc_html__('Spacing', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 50],
                ],
                'selectors'  => [
                    $selector => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
    }

    /**
     * Product excerpt: typography, color, spacing.
     */
    public static function registerCardExcerptStyleControls($widget, $selector)
    {
        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'excerpt_typography',
                'selector' => $selector,
            ]
        );

        $widget->add_control(
            'excerpt_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'excerpt_spacing',
            [
                'label'      => esc_html__('Spacing', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 50],
                ],
                'selectors'  => [
                    $selector => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
    }

    /**
     * Product price: typography, color, compare price color, spacing.
     */
    public static function registerCardPriceStyleControls($widget, $selector)
    {
        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'price_typography',
                'selector' => $selector,
            ]
        );

        $widget->add_control(
            'price_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'compare_price_color',
            [
                'label'     => esc_html__('Compare Price Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $selector . ' .fct-compare-price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'price_spacing',
            [
                'label'      => esc_html__('Spacing', 'fluent-cart'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 50],
                ],
                'selectors'  => [
                    $selector => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
    }

    /**
     * Product button: typography, full-width, normal/hover tabs (colors, bg, border, radius, padding, shadow).
     */
    public static function registerCardButtonStyleControls($widget, $btnSelector, $btnHoverSelector)
    {
        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'product_button_typography',
                'selector' => $btnSelector,
            ]
        );

        $widget->add_control(
            'product_button_width',
            [
                'label'     => esc_html__('Full Width', 'fluent-cart'),
                'type'      => Controls_Manager::SWITCHER,
                'label_on'  => esc_html__('Yes', 'fluent-cart'),
                'label_off' => esc_html__('No', 'fluent-cart'),
                'selectors' => [
                    $btnSelector => 'width: 100%; text-align: center;',
                ],
            ]
        );

        $widget->start_controls_tabs('tabs_product_button_style');

        // Normal State
        $widget->start_controls_tab(
            'tab_product_button_normal',
            [
                'label' => esc_html__('Normal', 'fluent-cart'),
            ]
        );

        $widget->add_control(
            'product_button_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $btnSelector => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'product_button_background',
                'types'    => ['classic', 'gradient'],
                'selector' => $btnSelector,
            ]
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'product_button_border',
                'selector' => $btnSelector,
            ]
        );

        $widget->add_control(
            'product_button_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [
                    $btnSelector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'product_button_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    $btnSelector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $widget->end_controls_tab();

        // Hover State
        $widget->start_controls_tab(
            'tab_product_button_hover',
            [
                'label' => esc_html__('Hover', 'fluent-cart'),
            ]
        );

        $widget->add_control(
            'product_button_hover_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $btnHoverSelector => 'color: {{VALUE}};',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'product_button_hover_background',
                'types'    => ['classic', 'gradient'],
                'selector' => $btnHoverSelector,
            ]
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'product_button_hover_border',
                'selector' => $btnHoverSelector,
            ]
        );

        $widget->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'product_button_hover_box_shadow',
                'selector' => $btnHoverSelector,
            ]
        );

        $widget->end_controls_tab();
        $widget->end_controls_tabs();
    }

    // ──────────────────────────────────────────────────────────
    // Render
    // ──────────────────────────────────────────────────────────

    protected function render()
    {
        $settings  = $this->get_settings_for_display();
        $productId = $settings['product_id'] ?? '';
        $isEditor  = \Elementor\Plugin::$instance->editor->is_edit_mode();

        if (empty($productId)) {
            if ($isEditor) {
                $this->renderPlaceholder();
            }
            return;
        }

        AssetLoader::loadProductArchiveAssets();

        $product = Product::query()->find($productId);

        if (!$product) {
            if ($isEditor) {
                $this->renderPlaceholder(esc_html__('Product not found.', 'fluent-cart'));
            }
            return;
        }

        $cardElements = $settings['card_elements'] ?? [
            ['element_type' => 'image'],
            ['element_type' => 'title'],
            ['element_type' => 'price'],
            ['element_type' => 'button'],
        ];

        $priceFormat = $settings['price_format'] ?? 'starts_from';

        $cardRender = new ProductCardRender($product, [
            'price_format' => $priceFormat,
        ]);

        ?>
        <article class="fct-product-card" data-fct-product-card>
            <?php
            foreach ($cardElements as $element) {
                $type = $element['element_type'] ?? '';

                switch ($type) {
                    case 'image':
                        $cardRender->renderProductImage();
                        break;

                    case 'title':
                        $wrapperAttr = 'class="fct-product-card-title"';
                        $cardRender->renderTitle($wrapperAttr, [
                            'isLink' => true,
                            'target' => '_self',
                        ]);
                        break;

                    case 'excerpt':
                        $wrapperAttr = 'class="fct-product-card-excerpt"';
                        $cardRender->renderExcerpt($wrapperAttr);
                        break;

                    case 'price':
                        $wrapperAttr = 'class="fct-product-card-prices"';
                        $cardRender->renderPrices($wrapperAttr);
                        break;

                    case 'button':
                        $cardRender->showBuyButton();
                        break;
                }
            }
            ?>
        </article>
        <?php
    }

    private function renderPlaceholder(string $message = '')
    {
        if (empty($message)) {
            $message = esc_html__('Please select a product to display.', 'fluent-cart');
        }
        ?>
        <div class="fluent-cart-placeholder" style="text-align:center; padding: 40px 20px; background: #f0f0f1; border: 1px dashed #ccc; border-radius: 4px;">
            <div style="font-size: 48px; margin-bottom: 10px;">
                <i class="eicon-image-box"></i>
            </div>
            <p style="margin: 0; color: #666;"><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }
}
