<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Repeater;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers\ElementorShopAppRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductAttributeSwatches;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\BadgeControls;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ProductWidgetTrait;
use FluentCartElementorBlocks\App\Services\Badges\BadgeRenderer;
use FluentCart\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

class RelatedProductsWidget extends Widget_Base
{
    /** Parts of core's Classic layout its Classic card adds; Swatches keeps this widget's own section. */
    const CLASSIC_PARTS = ['rating', 'quick_view'];

    use ProductWidgetTrait;

    public function get_name()
    {
        return 'fluentcart_related_products';
    }

    public function get_title()
    {
        return esc_html__('Related Products', 'fluent-cart-elementor-blocks');
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
                'label' => esc_html__('Content', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->registerProductSourceControls();
        // A layout card brings core's own swatches, so this widget's Swatches settings show only for Classic.
        $customCardOnly = ProductCardLayouts::registerControl($this);
        ProductCardLayouts::registerMasonryControl($this);
        $this->registerCardElementsControls($customCardOnly);
        $this->add_control(
            'price_format',
            [
                'label'     => esc_html__('Price Format', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'starts_from',
                'options'   => [
                    'starts_from' => esc_html__('Starts From', 'fluent-cart-elementor-blocks'),
                    'range'       => esc_html__('Range', 'fluent-cart-elementor-blocks'),
                ],
                'condition' => ['card_layout' => array_values(array_diff(array_keys(ProductCardLayouts::options()), ['']))],
            ]
        );

        $this->end_controls_section();

        $this->registerSwatchControls($customCardOnly);

        // Sale and Sold Out badges, Product Rating and Quick View, for Classic and every layout.
        BadgeControls::registerBadgeSections($this);
        ProductCardLayouts::registerPartSections($this, self::CLASSIC_PARTS);

        $this->registerHeadingStyleControls();
        $this->registerGridStyleControls();
        $this->registerCardStyleControls();
        $this->registerTitleStyleControls();
        $this->registerPriceStyleControls();
        $this->registerButtonStyleControls();
        BadgeControls::registerBadgeStyleSections($this);
        ProductCardLayouts::registerPartStyleSections($this);
    }

    /**
     * Classic's Card Elements, drawing this widget's own card in core's related grid. A new widget
     * starts as core's Classic layout; one saved before has no list and keeps core's related card.
     */
    private function registerCardElementsControls(array $classicOnly)
    {
        if (!ProductCardLayouts::cardSettingsAvailable()) {
            return;
        }
        $repeater = new Repeater();
        $repeater->add_control('element_type', [
            'label'   => esc_html__('Element', 'fluent-cart-elementor-blocks'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'image',
            'options' => [
                'image'   => esc_html__('Image', 'fluent-cart-elementor-blocks'),
                'title'   => esc_html__('Title', 'fluent-cart-elementor-blocks'),
                'excerpt' => esc_html__('Excerpt', 'fluent-cart-elementor-blocks'),
                'price'   => esc_html__('Price', 'fluent-cart-elementor-blocks'),
                'button'  => esc_html__('Button', 'fluent-cart-elementor-blocks'),
            ] + ProductCardLayouts::classicElementOptions(),
        ]);
        $this->add_control('card_elements', [
            'label'       => esc_html__('Card Elements', 'fluent-cart-elementor-blocks'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'default'     => ProductCardLayouts::classicElementsDefault([]),
            'title_field' => ProductCardLayouts::elementTitleField(),
            'condition'   => $classicOnly,
        ]);
        ProductCardLayouts::registerLayoutElementsControls($this);
    }

    private function registerSwatchControls(array $sectionCondition = [])
    {
        if (!ProductAttributeSwatches::swatchesAvailable()) {
            return;
        }

        $this->start_controls_section(
            'swatches_section',
            [
                'label'     => esc_html__('Swatches', 'fluent-cart-elementor-blocks'),
                'tab'       => Controls_Manager::TAB_CONTENT,
                'condition' => $sectionCondition,
            ]
        );

        // On for a new widget's Card Elements; core's related card reads only a saved choice (render()).
        $this->add_control(
            'show_swatches',
            [
                'label'        => esc_html__('Show Swatches', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => ProductCardLayouts::cardSettingsAvailable() ? 'yes' : '',
            ]
        );

        // Core renders the related cards, so swatches go in at one of its card hooks.
        $this->add_control(
            'swatch_position',
            [
                'label'     => esc_html__('Position', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'price',
                'options'   => [
                    'image' => esc_html__('After Image', 'fluent-cart-elementor-blocks'),
                    'title' => esc_html__('After Title', 'fluent-cart-elementor-blocks'),
                    'price' => esc_html__('After Price', 'fluent-cart-elementor-blocks'),
                ],
                'condition' => ['show_swatches' => 'yes'],
            ]
        );

        ProductAttributeSwatches::registerSwatchControls($this, ['show_swatches' => 'yes'], '{{WRAPPER}} .fct-card-swatch-rows');

        $this->end_controls_section();
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
                'label' => esc_html__('Heading', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'heading_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart-elementor-blocks'),
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
                'label'      => esc_html__('Spacing', 'fluent-cart-elementor-blocks'),
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
                'label' => esc_html__('Grid', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        // Core prints --fct-product-list-columns inline on the list, so a rule can't override it.
        // Set the grid itself, and the count core's card layouts read.
        $this->add_responsive_control(
            'grid_columns',
            [
                'label'      => esc_html__('Columns', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'range'      => [
                    'px' => ['min' => 1, 'max' => 6, 'step' => 1],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .fct-product-list' => 'grid-template-columns: repeat({{SIZE}}, 1fr); --fct-layout-columns: {{SIZE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'grid_gap',
            [
                'label'      => esc_html__('Gap', 'fluent-cart-elementor-blocks'),
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
                'label' => esc_html__('Card', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $card = ProductCardLayouts::cardFrameSelector();

        $this->add_control(
            'card_background',
            [
                'label'     => esc_html__('Background Color', 'fluent-cart-elementor-blocks'),
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
                'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
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
                'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
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
                'label' => esc_html__('Product Title', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $title = '{{WRAPPER}} .fct-product-card-title, {{WRAPPER}} .fct-product-card-title a';

        $this->add_control(
            'title_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $title => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'title_hover_color',
            [
                'label'     => esc_html__('Hover Color', 'fluent-cart-elementor-blocks'),
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
                'label' => esc_html__('Price', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart-elementor-blocks'),
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
                'label'     => esc_html__('Compare Price Color', 'fluent-cart-elementor-blocks'),
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
                'label' => esc_html__('Button', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        // Covers all card button variants: View Options / Buy Now render as
        // fct-product-view-button, but Add To Cart (simple products with
        // modal checkout) uses its own fluent-cart-add-to-cart-button class.
        $bases = [
            '{{WRAPPER}} .fct-product-card .fct-product-view-button',
            '{{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button',
            ProductCardLayouts::layoutButtonSelector(),
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
                'label' => esc_html__('Normal', 'fluent-cart-elementor-blocks'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $buttonText => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'button_background',
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
            'button_tab_hover',
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
                    $buttonHoverText => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'button_hover_background',
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
                'name'      => 'button_border',
                'selector'  => $button,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'button_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
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
        $settings = $this->get_settings_for_display();
        $product = $this->getProduct($settings);

        if (!$product) {
            // Related products need a specific product to relate to, so — unlike
            // Product Info — we do NOT guess the latest product here: "related to
            // what?" would be meaningless. Match core's Gutenberg Related Products
            // block, which prompts the user to select a product (Source → Custom in
            // the widget settings) instead of previewing an arbitrary one.
            // renderPlaceholder is editor-only; the front end renders nothing.
            $this->renderPlaceholder(__('Related products requires a product to be selected in order to display associated items.', 'fluent-cart-elementor-blocks'));
            return;
        }

        AssetLoader::loadSingleProductAssets();

        // On the product page this follows the store's Related Products setting, as core's shortcode does.
        if (is_singular('fluent-products')) {
            $showRelevant = (new \FluentCart\Api\StoreSettings())->get('show_relevant_product_in_single_page') == 'yes';
            if (!apply_filters('fluent_cart/single_product_page/show_relevant_products', $showRelevant, $product->ID)) {
                return;
            }
        }

        $products = \FluentCart\Api\Resource\ShopResource::getSimilarProducts($product->ID, false);
        if (empty($products)) {
            return;
        }

        $cardLayout = ProductCardLayouts::selected($settings);
        $listConfig = ['rating_context' => 'relevant'];
        if ($cardLayout) {
            // Core renders the layout cards with their own badges and swatches; the widget adds neither.
            ProductCardLayouts::prepareCards($products);
            $listConfig['card_layout'] = $cardLayout;
            $listConfig['layout_price_format'] = Arr::get($settings, 'price_format') === 'range' ? 'range' : 'starts_from';
            $listConfig['layout_options'] = ProductCardLayouts::cardOptions($settings, true, true);
        }

        // Classic's Card Elements draw this widget's own card; a widget saved without them keeps core's.
        $cardElements = $cardLayout ? [] : ProductCardLayouts::withClassicPartSettings($this, ProductCardLayouts::classicElements($this, $settings, []), $settings);
        if ($cardElements && Arr::get($settings, 'show_swatches') !== 'yes') {
            $cardElements = array_values(array_filter($cardElements, function ($row) {
                return Arr::get($row, 'element_type') !== 'swatches';
            }));
        }
        if ($cardElements) {
            \FluentCart\App\Modules\Data\ProductDataSetup::setProductsCache($products);
            if (in_array('swatches', array_column($cardElements, 'element_type'), true)) {
                ProductAttributeSwatches::enqueueAssets();
            }
            $listConfig['render_card'] = function ($relatedProduct) use ($cardElements) {
                $cardRender = new \FluentCart\App\Services\Renderer\ProductCardRender($relatedProduct, ['rating_context' => 'relevant']);
                ob_start();
                echo '<article class="fct-product-card" data-fct-product-card>';
                ElementorShopAppRenderer::renderCardElements($cardRender, $cardElements, $relatedProduct);
                echo '</article>';

                return (string) ob_get_clean();
            };
        }

        // Sale and Sold Out badge overlays via core's before/after_image_block hooks, which fire
        // inside each card's renderProductImage. Scoped: removed right after the render.
        $badgeHooks = $cardLayout ? null : BadgeRenderer::cardBadgeClosures($settings, true);
        if ($badgeHooks) {
            add_action('fluent_cart/product/group/before_image_block', $badgeHooks['before'], 10, 1);
            add_action('fluent_cart/product/group/after_image_block', $badgeHooks['after'], 10, 1);
        }

        // Core's related card: swatches go in at the chosen position. Only a saved Show Swatches
        // counts here, since its default is on for a new widget's Card Elements.
        $savedShowSwatches = Arr::get((array) $this->get_data('settings'), 'show_swatches', '');
        $swatchHook = '';
        $swatchCallback = null;
        if (!$cardLayout && !$cardElements && $savedShowSwatches === 'yes' && ProductAttributeSwatches::swatchesAvailable()) {
            ProductAttributeSwatches::enqueueAssets();
            $position = Arr::get($settings, 'swatch_position', '');
            $position = in_array($position, ['image', 'title', 'price'], true) ? $position : 'price';
            $swatchHook = 'fluent_cart/product/group/after_' . $position . '_block';
            $swatchCallback = function ($context) use ($settings) {
                echo ProductAttributeSwatches::render(is_array($context) ? ($context['product'] ?? null) : null, $settings); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            };
            // After the badge closures, which buffer the image block at priority 10.
            add_action($swatchHook, $swatchCallback, 20, 1);
        }

        ob_start();
        (new \FluentCart\App\Services\Renderer\ProductListRenderer(
            $products,
            __('Related Products', 'fluent-cart-elementor-blocks'),
            'fct-similar-product-list-container',
            $listConfig
        ))->render();
        $content = (string) ob_get_clean();

        BadgeRenderer::removeCardBadgeHooks($badgeHooks);
        if ($swatchCallback) {
            remove_action($swatchHook, $swatchCallback, 20);
        }

        if (trim($content) === '') {
            return;
        }

        echo '<div class="fluentcart-related-products">' . $content . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
