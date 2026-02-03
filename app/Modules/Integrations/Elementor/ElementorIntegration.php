<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor;


use FluentCart\App\Helpers\Helper;
use FluentCart\App\Services\Renderer\MiniCartRenderer;
use FluentCart\App\Services\Renderer\ProductCardRender;
use FluentCart\Framework\Support\Arr;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls\ProductSelectControl;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls\ProductVariationSelectControl;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers\ElementorShopAppRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\AddToCartWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\BuyNowWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\MiniCartWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ProductCarouselWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ProductCategoriesListWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ShopAppWidget;
use FluentCartElementorBlocks\App\Utils\Enqueuer\Enqueue;

class ElementorIntegration
{
    public function register()
    {
        if (!defined('ELEMENTOR_VERSION')) {
            return;
        }

        \add_action('elementor/widgets/register', [$this, 'registerWidgets']);
        \add_action('elementor/controls/register', [$this, 'registerControls']);
        \add_action('elementor/editor/after_enqueue_scripts', [$this, 'enqueueEditorScripts']);

        \add_filter('fluent_cart/products_views/preload_collection_elementor', [$this, 'preloadProductCollectionsAjax'], 10, 2);
    }

    public function registerWidgets($widgets_manager)
    {
        $widgets_manager->register(new AddToCartWidget());
        $widgets_manager->register(new BuyNowWidget());
        if(class_exists(MiniCartRenderer::class)){
            $widgets_manager->register(new MiniCartWidget());
        }
        $widgets_manager->register(new ShopAppWidget());
        $widgets_manager->register(new ProductCarouselWidget());
        $widgets_manager->register(new ProductCategoriesListWidget());
    }

    public function registerControls($controls_manager)
    {
        $controls_manager->register(new ProductVariationSelectControl());
        $controls_manager->register(new ProductSelectControl());
    }

    public function preloadProductCollectionsAjax($view, $args)
    {
        $products = Arr::get($args, 'products', []);
        $clientId = Arr::get($args, 'client_id', '');

        $cardElements = get_transient('fc_el_collection_' . $clientId);
        if (!$cardElements) {
            return $view;
        }

        ob_start();
        $isFirst = true;

        foreach ($products as $product) {
            $product->setAppends(['view_url', 'has_subscription']);
            $cardRender = new ProductCardRender($product, []);

            $providerAttr = '';
            if ($isFirst) {
                $providerAttr = 'data-template-provider="elementor" data-fluent-client-id="' . esc_attr($clientId) . '"';
                $isFirst = false;
            }
            ?>
            <article data-fluent-cart-shop-app-single-product data-fct-product-card=""
                     class="fct-product-card"
                    <?php echo $providerAttr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                     aria-label="<?php echo esc_attr(sprintf(
                             __('%s product card', 'fluent-cart'), $product->post_title));
                     ?>">
                <?php ElementorShopAppRenderer::renderCardElements($cardRender, $cardElements); ?>
            </article>
            <?php
        }

        return ob_get_clean();
    }

    public function enqueueEditorScripts()
    {
        $restInfo = Helper::getRestInfo();

        Enqueue::script(
            'fluent-cart-elementor-editor',
            'elementor/product-variation-select-control.js',
            ['elementor-editor', 'jquery'],
            FLUENTCART_VERSION,
            true
        );

        Enqueue::script(
            'fluent-cart-elementor-product-select',
            'elementor/product-select-control.js',
            ['elementor-editor', 'jquery'],
            FLUENTCART_VERSION,
            true
        );

        \wp_localize_script('fluent-cart-elementor-editor', 'fluentCartElementor', [
            'restUrl' => \trailingslashit($restInfo['url']),
            'nonce' => $restInfo['nonce']
        ]);
    }
}
