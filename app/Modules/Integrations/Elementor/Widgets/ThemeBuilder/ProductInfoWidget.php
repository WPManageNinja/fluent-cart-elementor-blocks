<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use FluentCart\Api\Resource\ShopResource;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductListRenderer;
use FluentCart\App\Services\Renderer\ProductRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ProductWidgetTrait;

if (!defined('ABSPATH')) {
    exit;
}

class ProductInfoWidget extends Widget_Base
{
    use ProductWidgetTrait;

    public function get_name()
    {
        return 'fluentcart_product_info';
    }

    public function get_title()
    {
        return esc_html__('Product Info', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-single-product';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['product', 'info', 'information', 'single', 'details', 'fluent'];
    }

    protected function register_controls()
    {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->registerProductSourceControls();

        $this->end_controls_section();

        // Sections Visibility
        $this->start_controls_section(
            'sections_section',
            [
                'label' => esc_html__('Sections', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_gallery',
            [
                'label'        => esc_html__('Gallery', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart'),
                'label_off'    => esc_html__('Hide', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label'        => esc_html__('Title', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart'),
                'label_off'    => esc_html__('Hide', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_stock',
            [
                'label'        => esc_html__('Stock', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart'),
                'label_off'    => esc_html__('Hide', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_sku',
            [
                'label'        => esc_html__('SKU', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart'),
                'label_off'    => esc_html__('Hide', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_excerpt',
            [
                'label'        => esc_html__('Excerpt', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart'),
                'label_off'    => esc_html__('Hide', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_price',
            [
                'label'        => esc_html__('Price', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart'),
                'label_off'    => esc_html__('Hide', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_buy_section',
            [
                'label'        => esc_html__('Buy Section', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart'),
                'label_off'    => esc_html__('Hide', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_description',
            [
                'label'        => esc_html__('Description', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart'),
                'label_off'    => esc_html__('Hide', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_related_products',
            [
                'label'        => esc_html__('Related Products', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart'),
                'label_off'    => esc_html__('Hide', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();

        // Gallery Settings
        $this->start_controls_section(
            'gallery_section',
            [
                'label'     => esc_html__('Gallery', 'fluent-cart'),
                'tab'       => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'show_gallery' => 'yes',
                ],
            ]
        );

        ProductGalleryWidget::registerGalleryContentControls($this);

        $this->end_controls_section();

        // Title Style
        $this->start_controls_section(
            'title_style_section',
            [
                'label'     => esc_html__('Title', 'fluent-cart'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        ProductTitleWidget::registerTitleStyleControls($this, '{{WRAPPER}} .fct-product-title h1');

        $this->end_controls_section();

        // Price Style
        $this->start_controls_section(
            'price_style_section',
            [
                'label'     => esc_html__('Price', 'fluent-cart'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_price' => 'yes',
                ],
            ]
        );

        ProductPriceWidget::registerPriceStyleControls($this, '{{WRAPPER}} .fct-product-summary');

        $this->end_controls_section();

        // Stock Style
        $this->start_controls_section(
            'stock_style_section',
            [
                'label'     => esc_html__('Stock', 'fluent-cart'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_stock' => 'yes',
                ],
            ]
        );

        ProductStockWidget::registerStockStyleControls($this, '{{WRAPPER}} .fct-product-stock');

        $this->end_controls_section();

        // SKU Style
        $this->start_controls_section(
            'sku_style_section',
            [
                'label'     => esc_html__('SKU', 'fluent-cart'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_sku' => 'yes',
                ],
            ]
        );

        ProductSkuWidget::registerSkuStyleControls($this, '{{WRAPPER}} .fct-product-sku');

        $this->end_controls_section();

        // Excerpt Style
        $this->start_controls_section(
            'excerpt_style_section',
            [
                'label'     => esc_html__('Excerpt', 'fluent-cart'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_excerpt' => 'yes',
                ],
            ]
        );

        ProductExcerptWidget::registerExcerptStyleControls($this, '{{WRAPPER}} .fct-product-excerpt');

        $this->end_controls_section();

        // Buy Section Style
        $this->start_controls_section(
            'buy_section_style_section',
            [
                'label'     => esc_html__('Buy Section', 'fluent-cart'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_buy_section' => 'yes',
                ],
            ]
        );

        ProductBuySectionWidget::registerBuySectionStyleControls($this, '{{WRAPPER}} .fct_buy_section');

        $this->end_controls_section();

        // Description Style
        $this->start_controls_section(
            'description_style_section',
            [
                'label'     => esc_html__('Description', 'fluent-cart'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_description' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'description_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-product-description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'description_typography',
                'selector' => '{{WRAPPER}} .fct-product-description',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $product = $this->getProduct($settings);

        if (!$product) {
            $this->renderPlaceholder(__('Please select a product or use this widget inside a product template.', 'fluent-cart'));
            return;
        }

        $isEditor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        if ($isEditor) {
            // In editor, only load CSS — skip JS assets to prevent Elementor re-render interference
            AssetLoader::enqueueProductInfoFrontendStyles();
        } else {
            AssetLoader::loadSingleProductAssets();
            AssetLoader::enqueueProductInfoFrontendStyles();
        }

        $renderer = new ProductRenderer($product);

        $showGallery    = $settings['show_gallery'] === 'yes';
        $showTitle      = $settings['show_title'] === 'yes';
        $showStock      = $settings['show_stock'] === 'yes';
        $showSku        = $settings['show_sku'] === 'yes';
        $showExcerpt    = $settings['show_excerpt'] === 'yes';
        $showPrice      = $settings['show_price'] === 'yes';
        $showBuySection    = $settings['show_buy_section'] === 'yes';
        $showDescription      = $settings['show_description'] === 'yes';
        $showRelatedProducts  = $settings['show_related_products'] === 'yes';

        echo '<div class="fluentcart-product-info">';
        echo '<div class="fct-single-product-page" data-fluent-cart-single-product-page>';
        echo '<div class="fct-single-product-page-row">';

        if ($showGallery) {
            $renderer->renderGallery([
                'thumb_position' => $settings['thumb_position'] ?: 'bottom',
                'thumbnail_mode' => $settings['thumbnail_mode'] ?: 'all',
            ]);
        }

        echo '<div class="fct-product-summary">';

        if ($showTitle) {
            $renderer->renderTitle();
        }

        if ($showStock) {
            $renderer->renderStockAvailability();
        }

        if ($showSku) {
            $renderer->renderSku();
        }

        if ($showExcerpt) {
            $renderer->renderExcerpt();
        }

        if ($showPrice) {
            $renderer->renderPrices();
        }

        if ($showBuySection) {
            $renderer->renderBuySection();
        }

        echo '</div>'; // .fct-product-summary
        echo '</div>'; // .fct-single-product-page-row

        if ($showDescription) {
            if ($isEditor) {
                // In editor, render without the_content filter to avoid Elementor re-entry
                $post = get_post($product->ID);
                if ($post && !empty($post->post_content)) {
                    echo '<div class="fct-product-description">';
                    echo wp_kses_post(wpautop($post->post_content));
                    echo '</div>';
                }
            } else {
                $renderer->renderDescription();
            }
        }

        echo '</div>'; // .fct-single-product-page

        if ($showRelatedProducts) {
            $products = ShopResource::getSimilarProducts($product->ID, false);
            if (!empty($products)) {
                (new ProductListRenderer(
                    $products,
                    __('Related Products', 'fluent-cart'),
                    'fct-similar-product-list-container'
                ))->render();
            }
        }

        echo '</div>'; // .fluentcart-product-info
    }
}
