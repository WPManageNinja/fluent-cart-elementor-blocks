<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Divi\Modules\Legacy;

use FluentCart\App\Models\Product;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductCardRender;
use FluentCart\App\Vite;

class ProductCarouselModule extends \ET_Builder_Module
{
    public $slug       = 'fceb_product_carousel';
    public $vb_support = 'partial';

    public function init()
    {
        $this->name = esc_html__('FluentCart Product Carousel', 'fluentcart-elementor-blocks');
        $this->icon = 'N';
    }

    public function get_fields()
    {
        return [
            'product_ids' => [
                'label'           => esc_html__('Product IDs', 'fluentcart-elementor-blocks'),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'description'     => esc_html__('Comma-separated product IDs to display.', 'fluentcart-elementor-blocks'),
                'toggle_slug'     => 'main_content',
            ],
            'slides_to_show' => [
                'label'           => esc_html__('Slides Per View', 'fluentcart-elementor-blocks'),
                'type'            => 'range',
                'option_category' => 'layout',
                'range_settings'  => ['min' => '1', 'max' => '6', 'step' => '1'],
                'default'         => '3',
                'toggle_slug'     => 'main_content',
            ],
            'space_between' => [
                'label'           => esc_html__('Space Between (px)', 'fluentcart-elementor-blocks'),
                'type'            => 'range',
                'option_category' => 'layout',
                'range_settings'  => ['min' => '0', 'max' => '100', 'step' => '1'],
                'default'         => '16',
                'toggle_slug'     => 'main_content',
            ],
            'autoplay' => [
                'label'           => esc_html__('Autoplay', 'fluentcart-elementor-blocks'),
                'type'            => 'yes_no_button',
                'option_category' => 'configuration',
                'options'         => [
                    'off' => esc_html__('No', 'fluentcart-elementor-blocks'),
                    'on'  => esc_html__('Yes', 'fluentcart-elementor-blocks'),
                ],
                'default'         => 'on',
                'toggle_slug'     => 'main_content',
            ],
            'autoplay_speed' => [
                'label'           => esc_html__('Autoplay Speed (ms)', 'fluentcart-elementor-blocks'),
                'type'            => 'range',
                'option_category' => 'configuration',
                'range_settings'  => ['min' => '500', 'max' => '10000', 'step' => '100'],
                'default'         => '3000',
                'show_if'         => ['autoplay' => 'on'],
                'toggle_slug'     => 'main_content',
            ],
            'infinite_loop' => [
                'label'           => esc_html__('Infinite Loop', 'fluentcart-elementor-blocks'),
                'type'            => 'yes_no_button',
                'option_category' => 'configuration',
                'options'         => [
                    'off' => esc_html__('No', 'fluentcart-elementor-blocks'),
                    'on'  => esc_html__('Yes', 'fluentcart-elementor-blocks'),
                ],
                'default'         => 'off',
                'toggle_slug'     => 'main_content',
            ],
            'show_arrows' => [
                'label'           => esc_html__('Show Arrows', 'fluentcart-elementor-blocks'),
                'type'            => 'yes_no_button',
                'option_category' => 'configuration',
                'options'         => [
                    'off' => esc_html__('No', 'fluentcart-elementor-blocks'),
                    'on'  => esc_html__('Yes', 'fluentcart-elementor-blocks'),
                ],
                'default'         => 'on',
                'toggle_slug'     => 'main_content',
            ],
            'show_pagination' => [
                'label'           => esc_html__('Show Pagination', 'fluentcart-elementor-blocks'),
                'type'            => 'yes_no_button',
                'option_category' => 'configuration',
                'options'         => [
                    'off' => esc_html__('No', 'fluentcart-elementor-blocks'),
                    'on'  => esc_html__('Yes', 'fluentcart-elementor-blocks'),
                ],
                'default'         => 'on',
                'toggle_slug'     => 'main_content',
            ],
            'card_elements' => [
                'label'           => esc_html__('Card Elements', 'fluentcart-elementor-blocks'),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'description'     => esc_html__('Comma-separated: image,title,price,button,excerpt', 'fluentcart-elementor-blocks'),
                'default'         => 'image,title,price,button',
                'toggle_slug'     => 'main_content',
            ],
            'price_format' => [
                'label'           => esc_html__('Price Format', 'fluentcart-elementor-blocks'),
                'type'            => 'select',
                'option_category' => 'basic_option',
                'options'         => [
                    'starts_from' => esc_html__('Starts From', 'fluentcart-elementor-blocks'),
                    'range'       => esc_html__('Range', 'fluentcart-elementor-blocks'),
                    'lowest'      => esc_html__('Lowest', 'fluentcart-elementor-blocks'),
                ],
                'default'         => 'starts_from',
                'toggle_slug'     => 'main_content',
            ],
        ];
    }

    public function render($attrs, $content, $render_slug)
    {
        $productIdsRaw = $this->props['product_ids'] ?? '';

        if (empty($productIdsRaw)) {
            return '';
        }

        $productIds = array_map('intval', array_filter(explode(',', $productIdsRaw)));
        if (empty($productIds)) {
            return '';
        }

        AssetLoader::loadProductArchiveAssets();
        $this->loadCarouselAssets();

        $products = Product::query()->whereIn('id', $productIds)->get();
        if ($products->isEmpty()) {
            return '';
        }

        $carouselSettings = [
            'slidesToShow'   => intval($this->props['slides_to_show'] ?? 3),
            'spaceBetween'   => intval($this->props['space_between'] ?? 16),
            'autoplay'       => ($this->props['autoplay'] ?? 'on') === 'on' ? 'yes' : 'no',
            'autoplayDelay'  => intval($this->props['autoplay_speed'] ?? 3000),
            'infinite'       => ($this->props['infinite_loop'] ?? 'off') === 'on' ? 'yes' : 'no',
            'arrows'         => ($this->props['show_arrows'] ?? 'on') === 'on' ? 'yes' : 'no',
            'dots'           => ($this->props['show_pagination'] ?? 'on') === 'on' ? 'yes' : 'no',
            'paginationType' => 'dots',
        ];

        $cardElementsRaw = $this->props['card_elements'] ?? 'image,title,price,button';
        $cardElements = array_map('trim', explode(',', $cardElementsRaw));
        $priceFormat = $this->props['price_format'] ?? 'starts_from';

        ob_start();
        $this->renderCarousel($products, $carouselSettings, $cardElements, $priceFormat);
        return ob_get_clean();
    }

    private function loadCarouselAssets()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $app = \FluentCart\App\App::getInstance();
        $slug = $app->config->get('app.slug');

        Vite::enqueueStaticScript(
            $slug . '-fluentcart-swiper-js',
            'public/lib/swiper/swiper-bundle.min.js',
            []
        );

        Vite::enqueueStaticStyle(
            $slug . '-fluentcart-swiper-css',
            'public/lib/swiper/swiper-bundle.min.css'
        );

        Vite::enqueueStyle(
            'fluentcart-product-carousel',
            'public/carousel/products/style/product-carousel.scss'
        );

        Vite::enqueueScript(
            'fluentcart-product-carousel',
            'public/carousel/products/product-carousel.js',
            [$slug . '-fluentcart-swiper-js']
        );
    }

    private function renderCarousel($products, array $carouselSettings, array $cardElements, string $priceFormat)
    {
        ?>
        <div class="fluent-cart-divi-product-carousel">
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
                        <div class="fct-carousel-controls fct-arrows-md">
                            <div class="swiper-button-prev" aria-label="<?php esc_attr_e('Previous slide', 'fluentcart-elementor-blocks'); ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            </div>
                            <div class="swiper-button-next" aria-label="<?php esc_attr_e('Next slide', 'fluentcart-elementor-blocks'); ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($carouselSettings['dots'] === 'yes') : ?>
                        <div class="fct-carousel-pagination fct-pagination-dots">
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

        foreach ($cardElements as $type) {
            switch ($type) {
                case 'image':
                    $cardRender->renderProductImage();
                    break;
                case 'title':
                    $cardRender->renderTitle('class="fct-product-card-title"', [
                        'isLink' => true,
                        'target' => '_self',
                    ]);
                    break;
                case 'excerpt':
                    $cardRender->renderExcerpt('class="fct-product-card-excerpt"');
                    break;
                case 'price':
                    $cardRender->renderPrices('class="fct-product-card-prices"');
                    break;
                case 'button':
                    $cardRender->showBuyButton();
                    break;
            }
        }
    }
}
