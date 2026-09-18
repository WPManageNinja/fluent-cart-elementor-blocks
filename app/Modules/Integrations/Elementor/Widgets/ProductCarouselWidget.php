<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use FluentCart\App\Models\Product;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductCardRender;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls\ProductSelectControl;
use FluentCartElementorBlocks\App\Services\Badges\BadgeRenderer;
use FluentCartElementorBlocks\App\Utils\Enqueuer\Enqueue;

class ProductCarouselWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_product_carousel';
    }

    public function get_title()
    {
        return esc_html__('Product Carousel', 'fluent-cart-elementor-blocks');
    }

    public function get_icon()
    {
        return 'eicon-carousel fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['products', 'carousel', 'slider', 'gallery', 'fluent', 'swiper'];
    }

    public function get_style_depends()
    {
        AssetLoader::loadProductArchiveAssets();
        $this->registerCarouselAssets();

        $app = \FluentCart\App\App::getInstance();
        $slug = $app->config->get('app.slug');

        return [
            'fluentcart-product-card-page-css',
            $slug . '-fluentcart-swiper-css',
            'fluentcart-product-carousel',
        ];
    }

    public function get_script_depends()
    {
        $this->registerCarouselAssets();

        $app = \FluentCart\App\App::getInstance();
        $slug = $app->config->get('app.slug');

        return [
            $slug . '-fluentcart-swiper-js',
            'fluentcart-product-carousel',
            'fluentcart-product-carousel-elementor',
        ];
    }

    private function registerCarouselAssets()
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        $app = \FluentCart\App\App::getInstance();
        $slug = $app->config->get('app.slug');

        // Use FluentCart core's Vite since these assets live in the core plugin
        \FluentCart\App\Vite::enqueueStaticScript(
            $slug . '-fluentcart-swiper-js',
            'public/lib/swiper/swiper-bundle.min.js',
            []
        );

        \FluentCart\App\Vite::enqueueStaticStyle(
            $slug . '-fluentcart-swiper-css',
            'public/lib/swiper/swiper-bundle.min.css'
        );

        \FluentCart\App\Vite::enqueueStyle(
            'fluentcart-product-carousel',
            'public/carousel/products/style/product-carousel.scss'
        );

        \FluentCart\App\Vite::enqueueScript(
            'fluentcart-product-carousel',
            'public/carousel/products/product-carousel.js',
            [$slug . '-fluentcart-swiper-js']
        );

        // Register Elementor frontend handler for editor re-initialization
        Enqueue::script(
            'fluentcart-product-carousel-elementor',
            'elementor/product-carousel-elementor.js',
            ['jquery', 'fluentcart-product-carousel'],
            FLUENTCART_VERSION,
            true
        );
    }

    protected function register_controls()
    {
        $this->registerProductSelectionControls();
        $this->registerCarouselSettingsControls();
        $this->registerCardLayoutControls();
        BadgeControls::registerBadgeContentControls($this, false);
        $this->registerStyleControls();
        BadgeControls::registerBadgeStyleControls($this, false);
    }

    private function registerStyleControls()
    {
        $this->start_controls_section(
            'card_style_section',
            [
                'label' => esc_html__('Product Card', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        ProductCardWidget::registerCardStyleControls($this, '{{WRAPPER}} .fct-product-card');
        $this->end_controls_section();

        $this->start_controls_section(
            'image_style_section',
            [
                'label' => esc_html__('Product Image', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        ProductCardWidget::registerCardImageStyleControls($this, '{{WRAPPER}} .fct-product-card-image');
        $this->end_controls_section();

        $this->start_controls_section(
            'title_style_section',
            [
                'label' => esc_html__('Product Title', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        ProductCardWidget::registerCardTitleStyleControls($this, '{{WRAPPER}} .fct-product-card-title');
        $this->end_controls_section();

        $this->start_controls_section(
            'excerpt_style_section',
            [
                'label' => esc_html__('Product Excerpt', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        ProductCardWidget::registerCardExcerptStyleControls($this, '{{WRAPPER}} .fct-product-card-excerpt');
        $this->end_controls_section();

        $this->start_controls_section(
            'price_style_section',
            [
                'label' => esc_html__('Product Price', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        ProductCardWidget::registerCardPriceStyleControls($this, '{{WRAPPER}} .fct-product-card-prices');
        $this->end_controls_section();

        $btnSelector = '{{WRAPPER}} .fct-product-card .fct-product-view-button, {{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button';
        $btnHoverSelector = '{{WRAPPER}} .fct-product-card .fct-product-view-button:hover, {{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button:hover';

        $this->start_controls_section(
            'button_style_section',
            [
                'label' => esc_html__('Product Button', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );
        ProductCardWidget::registerCardButtonStyleControls($this, $btnSelector, $btnHoverSelector);
        $this->end_controls_section();

        $this->registerNavigationStyleControls();
        $this->registerPaginationStyleControls();
    }

    private function registerProductSelectionControls()
    {
        $this->start_controls_section(
            'product_selection_section',
            [
                'label' => esc_html__('Products', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'product_ids',
            [
                'label'       => esc_html__('Select Products', 'fluent-cart-elementor-blocks'),
                'type'        => (new ProductSelectControl())->get_type(),
                'multiple'    => true,
                'label_block' => true,
                'description' => esc_html__('Search and select the products to display in the carousel.', 'fluent-cart-elementor-blocks'),
                'default'     => [],
                'placeholder' => esc_html__('Search for products...', 'fluent-cart-elementor-blocks'),
            ]
        );

        $this->end_controls_section();
    }

    private function registerCarouselSettingsControls()
    {
        $this->start_controls_section(
            'carousel_settings_section',
            [
                'label' => esc_html__('Carousel Settings', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'slides_to_show',
            [
                'label'   => esc_html__('Slides Per View', 'fluent-cart-elementor-blocks'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 3,
                'tablet_default' => 2,
                'mobile_default' => 1,
                'min'     => 1,
                'max'     => 6,
                'step'    => 1,
            ]
        );

        $this->add_control(
            'space_between',
            [
                'label'   => esc_html__('Space Between (px)', 'fluent-cart-elementor-blocks'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 16,
                'min'     => 0,
                'max'     => 100,
            ]
        );

        $this->add_control(
            'autoplay',
            [
                'label'        => esc_html__('Autoplay', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'autoplay_speed',
            [
                'label'     => esc_html__('Autoplay Speed (ms)', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 3000,
                'min'       => 500,
                'max'       => 10000,
                'step'      => 100,
                'condition' => [
                    'autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'infinite_loop',
            [
                'label'        => esc_html__('Infinite Loop', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => '',
            ]
        );

        $this->add_control(
            'show_arrows',
            [
                'label'        => esc_html__('Show Arrows', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'arrow_size',
            [
                'label'     => esc_html__('Arrow Size', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'md',
                'options'   => [
                    'sm' => esc_html__('Small', 'fluent-cart-elementor-blocks'),
                    'md' => esc_html__('Medium', 'fluent-cart-elementor-blocks'),
                    'lg' => esc_html__('Large', 'fluent-cart-elementor-blocks'),
                ],
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_pagination',
            [
                'label'        => esc_html__('Show Pagination', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'pagination_type',
            [
                'label'     => esc_html__('Pagination Type', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'dots',
                'options'   => [
                    'dots'     => esc_html__('Dots', 'fluent-cart-elementor-blocks'),
                    'fraction' => esc_html__('Fraction', 'fluent-cart-elementor-blocks'),
                    'progress' => esc_html__('Progress Bar', 'fluent-cart-elementor-blocks'),
                ],
                'condition' => [
                    'show_pagination' => 'yes',
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
                'label' => esc_html__('Card Layout', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'element_type',
            [
                'label'   => esc_html__('Element', 'fluent-cart-elementor-blocks'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'image',
                'options' => [
                    'image'   => esc_html__('Image', 'fluent-cart-elementor-blocks'),
                    'title'   => esc_html__('Title', 'fluent-cart-elementor-blocks'),
                    'excerpt' => esc_html__('Excerpt', 'fluent-cart-elementor-blocks'),
                    'price'   => esc_html__('Price', 'fluent-cart-elementor-blocks'),
                    'button'  => esc_html__('Button', 'fluent-cart-elementor-blocks'),
                ],
            ]
        );

        $this->add_control(
            'card_elements',
            [
                'label'       => esc_html__('Card Elements', 'fluent-cart-elementor-blocks'),
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

        $this->add_control(
            'price_format',
            [
                'label'   => esc_html__('Price Format', 'fluent-cart-elementor-blocks'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'starts_from',
                'options' => [
                    'starts_from' => esc_html__('Starts From', 'fluent-cart-elementor-blocks'),
                    'range'       => esc_html__('Range', 'fluent-cart-elementor-blocks'),
                    'lowest'      => esc_html__('Lowest', 'fluent-cart-elementor-blocks'),
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerNavigationStyleControls()
    {
        $this->start_controls_section(
            'navigation_style_section',
            [
                'label'     => esc_html__('Navigation Arrows', 'fluent-cart-elementor-blocks'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'arrow_color',
            [
                'label'     => esc_html__('Arrow Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .swiper-button-prev, {{WRAPPER}} .swiper-button-next' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .swiper-button-prev svg, {{WRAPPER}} .swiper-button-next svg' => 'stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'arrow_background',
            [
                'label'     => esc_html__('Arrow Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .swiper-button-prev, {{WRAPPER}} .swiper-button-next' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'arrow_hover_color',
            [
                'label'     => esc_html__('Hover Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .swiper-button-prev:hover, {{WRAPPER}} .swiper-button-next:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .swiper-button-prev:hover svg, {{WRAPPER}} .swiper-button-next:hover svg' => 'stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'arrow_hover_background',
            [
                'label'     => esc_html__('Hover Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .swiper-button-prev:hover, {{WRAPPER}} .swiper-button-next:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerPaginationStyleControls()
    {
        $this->start_controls_section(
            'pagination_style_section',
            [
                'label'     => esc_html__('Pagination', 'fluent-cart-elementor-blocks'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_pagination' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'pagination_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .swiper-pagination-bullet' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .swiper-pagination-progressbar' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'pagination_active_color',
            [
                'label'     => esc_html__('Active Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .swiper-pagination-progressbar-fill' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'pagination_size',
            [
                'label'      => esc_html__('Size', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => ['min' => 5, 'max' => 20],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'pagination_type' => 'dots',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $productIds = $settings['product_ids'] ?? [];
        $isEditor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        if (empty($productIds) || !is_array($productIds)) {
            // No products selected. In the editor, show the loading skeleton (like
            // core's Gutenberg Product Carousel block) so the layout is visible the
            // moment the widget is dropped in. The front end renders nothing.
            if ($isEditor) {
                $this->renderSkeleton($settings);
            }
            return;
        }

        // Load assets (also registered in get_style_depends/get_script_depends for editor)
        AssetLoader::loadProductArchiveAssets();
        $this->registerCarouselAssets();

        // Fetch products
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get();

        if ($products->isEmpty()) {
            if ($isEditor) {
                $this->renderPlaceholder(esc_html__('No products found for the selected IDs.', 'fluent-cart-elementor-blocks'));
            }
            return;
        }

        // Build carousel settings
        $carouselSettings = $this->buildCarouselSettings($settings);

        // Get card elements
        $cardElements = $settings['card_elements'] ?? [
            ['element_type' => 'image'],
            ['element_type' => 'title'],
            ['element_type' => 'price'],
            ['element_type' => 'button'],
        ];

        $priceFormat = $settings['price_format'] ?? 'starts_from';

        // Sale + Sold Out badge overlays via core's before/after_image_block
        // hooks (fire inside renderProductImage). The closures buffer each
        // slide's image and re-wrap it so the badge overlays the IMAGE, not the
        // whole card. Scoped: removed right after the carousel render.
        $badgeHooks = BadgeRenderer::cardBadgeClosures($settings, false);
        if ($badgeHooks) {
            add_action('fluent_cart/product/group/before_image_block', $badgeHooks['before'], 10, 1);
            add_action('fluent_cart/product/group/after_image_block', $badgeHooks['after'], 10, 1);
        }

        // Render carousel
        $this->renderCarousel($products, $carouselSettings, $cardElements, $priceFormat);

        BadgeRenderer::removeCardBadgeHooks($badgeHooks);
    }

    private function buildCarouselSettings(array $settings): array
    {
        return [
            'slidesToShow'    => intval($settings['slides_to_show'] ?? 3),
            'spaceBetween'    => intval($settings['space_between'] ?? 16),
            'autoplay'        => ($settings['autoplay'] ?? '') === 'yes' ? 'yes' : 'no',
            'autoplayDelay'   => intval($settings['autoplay_speed'] ?? 3000),
            'infinite'        => ($settings['infinite_loop'] ?? '') === 'yes' ? 'yes' : 'no',
            'arrows'          => ($settings['show_arrows'] ?? '') === 'yes' ? 'yes' : 'no',
            'arrowsSize'      => $settings['arrow_size'] ?? 'md',
            'dots'            => ($settings['show_pagination'] ?? '') === 'yes' ? 'yes' : 'no',
            'paginationType'  => $settings['pagination_type'] ?? 'dots',
        ];
    }

    /**
     * Editor-only loading skeleton shown when no products are selected, mirroring
     * core's Gutenberg Product Carousel block (spinner + "Loading products…" +
     * shimmer cards). Card count follows the Slides To Show setting. Styles come
     * from the preview-only carousel-skeleton.css (enqueued via
     * elementor/preview/enqueue_styles); the CSS variables set inline drive the
     * per-slide width and the staggered fade-in, exactly as the block does.
     *
     * @param array $settings
     * @return void
     */
    private function renderSkeleton(array $settings)
    {
        $slidesToShow = intval($settings['slides_to_show'] ?? 3);
        if ($slidesToShow < 1) {
            $slidesToShow = 3;
        }

        echo '<div class="fluent-cart-carousel-skeleton">';
        echo '<div class="fluent-cart-empty">';
        echo '<div class="fct-loading-header"><div class="fct-loading-spinner"></div><span>' . esc_html__('Loading products...', 'fluent-cart-elementor-blocks') . '</span></div>';
        echo '<div class="dummy-grid" style="--fct-slides-per-view: ' . (int) $slidesToShow . ';">';

        for ($i = 0; $i < $slidesToShow; $i++) {
            echo '<div class="dummy-card" style="--card-index: ' . (int) $i . ';">'
                . '<div class="dummy-image skeleton"></div>'
                . '<div class="dummy-content">'
                . '<div class="dummy-title skeleton"></div>'
                . '<div class="dummy-excerpt skeleton"></div>'
                . '<div class="dummy-price skeleton"></div>'
                . '</div>'
                . '<div class="dummy-btn-wrap"><div class="dummy-btn"></div></div>'
                . '</div>';
        }

        echo '</div>'; // .dummy-grid
        echo '</div>'; // .fluent-cart-empty
        echo '</div>'; // .fluent-cart-carousel-skeleton
    }

    private function renderPlaceholder(string $message = '')
    {
        if (empty($message)) {
            $message = esc_html__('Please select products to display in the carousel.', 'fluent-cart-elementor-blocks');
        }
        ?>
        <div class="fluent-cart-placeholder" style="text-align:center; padding: 40px 20px; background: #f0f0f1; border: 1px dashed #ccc; border-radius: 4px;">
            <div style="font-size: 48px; margin-bottom: 10px;">
                <i class="eicon-carousel"></i>
            </div>
            <p style="margin: 0; color: #666;"><?php echo esc_html($message); ?></p>
        </div>
        <?php
    }

    private function renderCarousel($products, array $carouselSettings, array $cardElements, string $priceFormat)
    {
        $arrowsSize = $carouselSettings['arrowsSize'] ?? 'md';
        $paginationType = $carouselSettings['paginationType'] ?? 'dots';
        $isEditor = \Elementor\Plugin::$instance->editor->is_edit_mode();
        $editorClass = $isEditor ? ' fct-elementor-preview' : '';
        ?>
        <?php if ($isEditor) : ?>
        <style>
            .fct-elementor-preview a,
            .fct-elementor-preview button,
            .fct-elementor-preview .fct-product-view-button,
            .fct-elementor-preview .fluent-cart-add-to-cart-button,
            .fct-elementor-preview [data-fct-product-card] {
                pointer-events: none;
            }
            .fct-elementor-preview .swiper-button-prev,
            .fct-elementor-preview .swiper-button-next,
            .fct-elementor-preview .swiper-pagination {
                pointer-events: auto;
            }
        </style>
        <?php endif; ?>
        <div class="fluent-cart-elementor-product-carousel<?php echo esc_attr($editorClass); ?>">
            <div class="fct-product-carousel-wrapper">
                <div class="swiper fct-product-carousel"
                     data-fluent-cart-product-carousel
                     data-carousel-settings="<?php echo esc_attr(wp_json_encode($carouselSettings)); ?>">
                    <div class="swiper-wrapper">
                        <?php foreach ($products as $product) : ?>
                            <div class="swiper-slide">
                                <article class="fct-product-card" data-fct-product-card>
                                    <?php $this->renderCardElements($product, $cardElements, $priceFormat); ?>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($carouselSettings['arrows'] === 'yes') : ?>
                        <div class="fct-carousel-controls fct-arrows-<?php echo esc_attr($arrowsSize); ?>">
                            <div class="swiper-button-prev" aria-label="<?php esc_attr_e('Previous slide', 'fluent-cart-elementor-blocks'); ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                            </div>
                            <div class="swiper-button-next" aria-label="<?php esc_attr_e('Next slide', 'fluent-cart-elementor-blocks'); ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($carouselSettings['dots'] === 'yes') : ?>
                        <div class="fct-carousel-pagination fct-pagination-<?php echo esc_attr($paginationType); ?>">
                            <div class="swiper-pagination"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function renderCardElements($product, array $cardElements, string $priceFormat)
    {
        $cardRender = new ProductCardRender($product, [
            'price_format' => $priceFormat,
        ]);

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
    }
}