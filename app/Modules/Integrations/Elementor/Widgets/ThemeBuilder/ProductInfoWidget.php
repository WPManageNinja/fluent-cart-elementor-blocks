<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use FluentCart\Api\Resource\ShopResource;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductListRenderer;
use FluentCart\App\Services\Renderer\ProductRenderer;
use FluentCart\Framework\Support\Arr;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\BadgeControls;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ProductWidgetTrait;
use FluentCartElementorBlocks\App\Services\Badges\BadgeRenderer;

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
        return esc_html__('Product Info', 'fluent-cart-elementor-blocks');
    }

    public function get_icon()
    {
        return 'eicon-single-product fluent-cart-widget-icon';
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
                'label' => esc_html__('Content', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->registerProductSourceControls();

        $this->end_controls_section();

        // Sections Visibility
        $this->start_controls_section(
            'sections_section',
            [
                'label' => esc_html__('Sections', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_gallery',
            [
                'label'        => esc_html__('Gallery', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $summaryRepeater = new Repeater();

        $summaryRepeater->add_control(
            'section_type',
            [
                'label'   => esc_html__('Section', 'fluent-cart-elementor-blocks'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'title',
                'options' => [
                    'title'               => esc_html__('Title', 'fluent-cart-elementor-blocks'),
                    'stock'               => esc_html__('Stock', 'fluent-cart-elementor-blocks'),
                    'sku'                 => esc_html__('SKU', 'fluent-cart-elementor-blocks'),
                    'excerpt'             => esc_html__('Excerpt', 'fluent-cart-elementor-blocks'),
                    'price'               => esc_html__('Price', 'fluent-cart-elementor-blocks'),
                    'package_description' => esc_html__('Package Description', 'fluent-cart-elementor-blocks'),
                    'buy_section'         => esc_html__('Buy Section', 'fluent-cart-elementor-blocks'),
                ],
            ]
        );

        $summaryRepeater->add_control(
            'show',
            [
                'label'        => esc_html__('Visibility', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'summary_sections',
            [
                'label'       => esc_html__('Summary Sections', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $summaryRepeater->get_controls(),
                'default'     => [
                    ['section_type' => 'title', 'show' => 'yes'],
                    ['section_type' => 'stock', 'show' => 'yes'],
                    ['section_type' => 'sku', 'show' => 'yes'],
                    ['section_type' => 'excerpt', 'show' => 'yes'],
                    ['section_type' => 'price', 'show' => 'yes'],
                    ['section_type' => 'package_description', 'show' => 'yes'],
                    ['section_type' => 'buy_section', 'show' => 'yes'],
                ],
                'title_field' => '<# var labels = { title: "' . esc_js(__('Title', 'fluent-cart-elementor-blocks')) . '", stock: "' . esc_js(__('Stock', 'fluent-cart-elementor-blocks')) . '", sku: "' . esc_js(__('SKU', 'fluent-cart-elementor-blocks')) . '", excerpt: "' . esc_js(__('Excerpt', 'fluent-cart-elementor-blocks')) . '", price: "' . esc_js(__('Price', 'fluent-cart-elementor-blocks')) . '", package_description: "' . esc_js(__('Package Description', 'fluent-cart-elementor-blocks')) . '", buy_section: "' . esc_js(__('Buy Section', 'fluent-cart-elementor-blocks')) . '" }; print( labels[ section_type ] || section_type ); #>',
                'prevent_empty' => false,
            ]
        );

        $this->add_control(
            'show_description',
            [
                'label'        => esc_html__('Description', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_related_products',
            [
                'label'        => esc_html__('Related Products', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->end_controls_section();

        $this->registerSaleBadgeContentControls();

        // Gallery Settings
        $this->start_controls_section(
            'gallery_section',
            [
                'label'     => esc_html__('Gallery', 'fluent-cart-elementor-blocks'),
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
                'label' => esc_html__('Title', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        ProductTitleWidget::registerTitleStyleControls($this, '{{WRAPPER}} .fct-product-title h1');

        $this->end_controls_section();

        // Price Style
        $this->start_controls_section(
            'price_style_section',
            [
                'label' => esc_html__('Price', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        ProductPriceWidget::registerPriceStyleControls($this, '{{WRAPPER}} .fct-product-summary');

        $this->end_controls_section();

        // Stock Style
        $this->start_controls_section(
            'stock_style_section',
            [
                'label' => esc_html__('Stock', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        ProductStockWidget::registerStockStyleControls($this, '{{WRAPPER}} .fct-product-stock');

        $this->end_controls_section();

        // SKU Style
        $this->start_controls_section(
            'sku_style_section',
            [
                'label' => esc_html__('SKU', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        ProductSkuWidget::registerSkuStyleControls($this, '{{WRAPPER}} .fct-product-sku');

        $this->end_controls_section();

        // Excerpt Style
        $this->start_controls_section(
            'excerpt_style_section',
            [
                'label' => esc_html__('Excerpt', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        ProductExcerptWidget::registerExcerptStyleControls($this, '{{WRAPPER}} .fct-product-excerpt');

        $this->end_controls_section();

        // Package Description Style — same shared controls the standalone
        // Package Description widget uses.
        $this->start_controls_section(
            'package_description_style_section',
            [
                'label' => esc_html__('Package Description', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        ProductPackageDescriptionWidget::registerPackageDescriptionStyleControls($this, '{{WRAPPER}} .fct-package-description');

        $this->end_controls_section();

        // Buy Now Button Style — own section instead of a combined Buy
        // Section, so each button styles independently.
        $this->start_controls_section(
            'buy_now_button_style_section',
            [
                'label' => esc_html__('Buy Now Button', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        ProductBuySectionWidget::registerSingleButtonColorControls(
            $this,
            'buy_now',
            '',
            [
                '{{WRAPPER}} .fct_buy_section .fluent-cart-direct-checkout-button',
                '{{WRAPPER}} .fct_buy_section .fct-buy-now-btn',
            ]
        );

        $this->end_controls_section();

        // Add To Cart Button Style
        $this->start_controls_section(
            'add_to_cart_button_style_section',
            [
                'label' => esc_html__('Add To Cart Button', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        ProductBuySectionWidget::registerSingleButtonColorControls(
            $this,
            'add_to_cart',
            '',
            [
                '{{WRAPPER}} .fct_buy_section .fluent-cart-add-to-cart-button',
                '{{WRAPPER}} .fct_buy_section .fct-add-to-cart-btn',
            ]
        );

        $this->end_controls_section();

        // Description Style
        $this->start_controls_section(
            'description_style_section',
            [
                'label'     => esc_html__('Description', 'fluent-cart-elementor-blocks'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_description' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'description_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
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

        // Style-tab Sale Badge section over {{WRAPPER}} .fct-sale-badge —
        // reused from the card widgets (corner-agnostic: background, text color,
        // typography, padding, radius). $includeSoldOut false: Product Info is
        // Sale only.
        BadgeControls::registerBadgeStyleControls($this, false);
    }

    /**
     * Sale Badge content controls, laid out to MATCH the Gutenberg Sale Badge
     * inspector (BlockEditor/SaleBadge/Components/InspectorSettings.jsx): a
     * "Badge" panel then a "Position & Style" panel, same labels/help
     * text/option order. Elementor adds the enable switcher first (Gutenberg
     * enables by inserting the block). The live sale-status pill is skipped
     * (an Elementor panel can't render per-product live status).
     *
     * Product Info differs from the card widgets in ONE way: the badge is an
     * INLINE summary row at a configurable SLOT in the details column
     * (below-title / below-excerpt / above-price / below-price / below-package),
     * NOT an image overlay — so "Position" carries slot values, not the corner
     * positions BadgeControls exposes. No Sold Out badge here (matches Divi +
     * Gutenberg: Sold Out is Products/Carousel-only). Default OFF so an existing
     * widget instance is unchanged.
     *
     * @return void
     */
    private function registerSaleBadgeContentControls()
    {
        // ── Badge ──────────────────────────────────────────
        $this->start_controls_section(
            'sale_badge_settings_section',
            [
                'label' => esc_html__('Badge', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_sale_badge',
            [
                'label'        => esc_html__('Sale Badge', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'sale_badge_text',
            [
                'label'       => esc_html__('Badge Text', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::TEXT,
                'default'     => esc_html__('Sale!', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Text shown when not using percentage mode.', 'fluent-cart-elementor-blocks'),
                'condition'   => ['show_sale_badge' => 'yes'],
            ]
        );

        $this->add_control(
            'show_percentage',
            [
                'label'        => esc_html__('Show Discount Percentage', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => ['show_sale_badge' => 'yes'],
            ]
        );

        $this->add_control(
            'sale_percentage_text',
            [
                'label'       => esc_html__('Percentage Format', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::TEXT,
                'default'     => '-{percent}%',
                'description' => esc_html__('Use {percent} as placeholder. E.g., "-{percent}% OFF"', 'fluent-cart-elementor-blocks'),
                'condition'   => [
                    'show_sale_badge' => 'yes',
                    'show_percentage' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'sale_price_source',
            [
                'label'       => esc_html__('Price Source', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'default_variant',
                'options'     => [
                    'default_variant' => esc_html__('Default Variant', 'fluent-cart-elementor-blocks'),
                    'best_discount'   => esc_html__('Best Discount (All Variants)', 'fluent-cart-elementor-blocks'),
                ],
                'description' => esc_html__('Where to check the sale price from.', 'fluent-cart-elementor-blocks'),
                'condition'   => ['show_sale_badge' => 'yes'],
            ]
        );

        // ── Position & Style (same panel, heading divider) ──────────
        $this->add_control(
            'sale_badge_ps_heading',
            [
                'label'     => esc_html__('Position & Style', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => ['show_sale_badge' => 'yes'],
            ]
        );

        $this->add_control(
            'sale_badge_style',
            [
                'label'     => esc_html__('Badge Style', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'badge',
                'options'   => [
                    'badge'  => esc_html__('Badge', 'fluent-cart-elementor-blocks'),
                    'ribbon' => esc_html__('Ribbon', 'fluent-cart-elementor-blocks'),
                    'tag'    => esc_html__('Tag', 'fluent-cart-elementor-blocks'),
                ],
                'condition' => ['show_sale_badge' => 'yes'],
            ]
        );

        // Product Info uses SLOT values (inline placement in the details
        // column), not the corner positions the card widgets use.
        $this->add_control(
            'sale_badge_position',
            [
                'label'     => esc_html__('Position', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'below-title',
                'options'   => [
                    'below-title'   => esc_html__('Below Title', 'fluent-cart-elementor-blocks'),
                    'below-excerpt' => esc_html__('Below Excerpt', 'fluent-cart-elementor-blocks'),
                    'above-price'   => esc_html__('Above Price', 'fluent-cart-elementor-blocks'),
                    'below-price'   => esc_html__('Below Price', 'fluent-cart-elementor-blocks'),
                    'below-package' => esc_html__('Below Package Description', 'fluent-cart-elementor-blocks'),
                ],
                'condition' => ['show_sale_badge' => 'yes'],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $product = $this->getProduct($settings);

        $isEditor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        if (!$product) {
            // No product selected and none in context. In the editor, preview
            // with a real product (the latest published one) and render through
            // the normal path — the same "real data, never fabricated" approach
            // Elementor Pro's WooCommerce single-product widgets use. On the
            // front end we stay silent; if the store has no products at all, the
            // notice is shown instead.
            if ($isEditor) {
                $product = $this->getPreviewProduct();
            }

            if (!$product) {
                $this->renderPlaceholder(__('Please select a product or use this widget inside a product template.', 'fluent-cart-elementor-blocks'));
                return;
            }
        }

        if ($isEditor) {
            // In editor, only load CSS — skip JS assets to prevent Elementor re-render interference
            AssetLoader::enqueueProductInfoFrontendStyles();
        } else {
            AssetLoader::loadSingleProductAssets();
            AssetLoader::enqueueProductInfoFrontendStyles();
        }

        $renderer = new ProductRenderer($product);

        $showGallery         = $settings['show_gallery'] === 'yes';
        $showDescription     = $settings['show_description'] === 'yes';
        $showRelatedProducts = $settings['show_related_products'] === 'yes';

        echo '<div class="fluentcart-product-info">';
        echo '<div class="fct-single-product-page" data-fluent-cart-single-product-page>';
        echo '<div class="fct-single-product-page-row">';

        if ($showGallery) {
            // Mirror ProductGalleryWidget::render() exactly — the same control
            // set is registered here via registerGalleryContentControls(), so
            // every key it registers has to be forwarded to the renderer.
            $renderer->renderGallery([
                'thumb_position'    => $settings['thumb_position'] ?: 'bottom',
                'thumbnail_mode'    => ProductGalleryWidget::GALLERY_THUMBNAIL_MODE,
                'scrollable_thumbs' => !empty($settings['scrollable_thumbs']) ? 'yes' : 'no',
                'max_thumbnails'    => !empty($settings['max_thumbnails']) ? (int) $settings['max_thumbnails'] : null,
            ]);
        }

        echo '<div class="fct-product-summary">';

        foreach ($this->getOrderedSummarySections($settings) as $section) {
            $type = $section['type'];

            // Inline Sale badge slots — emitted at their anchor section
            // REGARDLESS of that section's own show toggle (mirrors Divi's
            // badgeAt placement, which renders the row even when the anchor
            // element is hidden). 'above-price' fires before the price markup;
            // the 'below-*' slots fire after their section markup.
            if ($type === 'price') {
                $this->renderSaleBadgeSlot($product, $settings, 'above-price');
            }

            if ($section['show']) {
                switch ($type) {
                    case 'title':
                        $renderer->renderTitle();
                        break;
                    case 'stock':
                        $renderer->renderStockAvailability();
                        break;
                    case 'sku':
                        $renderer->renderSku();
                        break;
                    case 'excerpt':
                        $renderer->renderExcerpt();
                        break;
                    case 'price':
                        $renderer->renderPrices();
                        break;
                    case 'package_description':
                        $renderer->renderPackageDescription();
                        break;
                    case 'buy_section':
                        $renderer->renderBuySection();
                        break;
                }
            }

            switch ($type) {
                case 'title':
                    $this->renderSaleBadgeSlot($product, $settings, 'below-title');
                    break;
                case 'excerpt':
                    $this->renderSaleBadgeSlot($product, $settings, 'below-excerpt');
                    break;
                case 'price':
                    $this->renderSaleBadgeSlot($product, $settings, 'below-price');
                    break;
                case 'package_description':
                    $this->renderSaleBadgeSlot($product, $settings, 'below-package');
                    break;
            }
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
                    __('Related Products', 'fluent-cart-elementor-blocks'),
                    'fct-similar-product-list-container'
                ))->render();
            }
        }

        echo '</div>'; // .fluentcart-product-info
    }

    /**
     * Render the inline Sale badge for a given slot — the Elementor mirror of
     * Divi's ProductInfoModule::badgeAt(). Emits nothing unless the Sale badge
     * is enabled AND its configured position matches $slot. Calls
     * BadgeRenderer::sale() with badgePosition '' so the badge is INLINE (no
     * absolute corner class) and flows as a normal summary row, wrapped like
     * Divi's fct-divi-pi-badge-row. The badge HTML is already escaped inside
     * BadgeRenderer::sale().
     *
     * @param \FluentCart\App\Models\Product $product
     * @param array  $settings
     * @param string $slot below-title|below-excerpt|above-price|below-price|below-package
     * @return void
     */
    private function renderSaleBadgeSlot($product, array $settings, $slot)
    {
        if (Arr::get($settings, 'show_sale_badge', '') !== 'yes') {
            return;
        }

        if (Arr::get($settings, 'sale_badge_position', 'below-title') !== $slot) {
            return;
        }

        $html = BadgeRenderer::sale($product, [
            'badgeText'      => Arr::get($settings, 'sale_badge_text', __('Sale!', 'fluent-cart-elementor-blocks')),
            'showPercentage' => Arr::get($settings, 'show_percentage', '') === 'yes',
            'percentageText' => Arr::get($settings, 'sale_percentage_text', '-{percent}%'),
            'priceSource'    => Arr::get($settings, 'sale_price_source', 'default_variant'),
            'badgeStyle'     => Arr::get($settings, 'sale_badge_style', 'badge'),
            'badgePosition'  => '', // inline (no absolute corner class) — flows as a normal row
        ]);

        if ($html === '') {
            return;
        }

        echo '<div class="fct-elementor-pi-badge-row" style="margin:8px 0;display:flex;gap:8px;flex-wrap:wrap">'
            . $html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in BadgeRenderer::sale()
            . '</div>';
    }

    private function getOrderedSummarySections($settings)
    {
        $defaultOrder = [
            'title',
            'stock',
            'sku',
            'excerpt',
            'price',
            'package_description',
            'buy_section',
        ];

        $rawSettings = $this->get_data('settings');
        $userSavedRepeater = is_array($rawSettings)
            && !empty($rawSettings['summary_sections'])
            && is_array($rawSettings['summary_sections']);

        if ($userSavedRepeater) {
            $ordered = [];
            $seen = [];
            foreach ($settings['summary_sections'] as $row) {
                $type = isset($row['section_type']) ? $row['section_type'] : '';
                if (!$type || isset($seen[$type]) || !in_array($type, $defaultOrder, true)) {
                    continue;
                }
                $seen[$type] = true;
                $ordered[] = [
                    'type' => $type,
                    'show' => (isset($row['show']) ? $row['show'] : 'yes') === 'yes',
                ];
            }
            return $ordered;
        }

        $hasLegacyKey = is_array($rawSettings) && (
            isset($rawSettings['show_title'])
            || isset($rawSettings['show_stock'])
            || isset($rawSettings['show_sku'])
            || isset($rawSettings['show_excerpt'])
            || isset($rawSettings['show_price'])
            || isset($rawSettings['show_package_description'])
            || isset($rawSettings['show_buy_section'])
        );

        $ordered = [];
        foreach ($defaultOrder as $type) {
            if ($hasLegacyKey) {
                $legacyKey = 'show_' . $type;
                $show = (isset($rawSettings[$legacyKey]) ? $rawSettings[$legacyKey] : 'yes') === 'yes';
            } else {
                $show = true;
            }
            $ordered[] = [
                'type' => $type,
                'show' => $show,
            ];
        }
        return $ordered;
    }
}
