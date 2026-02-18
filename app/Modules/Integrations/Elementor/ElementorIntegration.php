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
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\CheckoutWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\MiniCartWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ProductCardWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ProductCarouselWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ProductCategoriesListWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ShopAppWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\ProductTitleWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\ProductGalleryWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\ProductPriceWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\ProductStockWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\ProductExcerptWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\ProductBuySectionWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\ProductContentWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\ProductInfoWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\RelatedProductsWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Documents\FluentCartProduct;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Documents\FluentCartProductPost;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Conditions\FluentCartCondition;
use FluentCartElementorBlocks\App\Utils\Enqueuer\Enqueue;

class ElementorIntegration
{
    public function register()
    {
        if (!defined('ELEMENTOR_VERSION')) {
            return;
        }

        \add_action('elementor/elements/categories_registered', [$this, 'registerCategories']);
        \add_action('elementor/widgets/register', [$this, 'registerWidgets']);
        \add_action('elementor/controls/register', [$this, 'registerControls']);
        \add_action('elementor/editor/after_enqueue_scripts', [$this, 'enqueueEditorScripts']);

        \add_filter('fluent_cart/products_views/preload_collection_elementor', [$this, 'preloadProductCollectionsAjax'], 10, 2);

        // Theme Builder integration (requires Elementor Pro)
        if (class_exists('\ElementorPro\Modules\ThemeBuilder\Module')) {
            \add_action('elementor/documents/register', [$this, 'registerDocuments']);
            \add_action('elementor/theme/register_conditions', [$this, 'registerConditions']);
            \add_filter('elementor/theme/need_override_location', [$this, 'themeTemplateInclude'], 10, 2);
            \add_filter('elementor_pro/utils/get_public_post_types', [$this, 'removeFluentProductsFromGenericConditions']);

            // Disable FluentCart core's auto single product rendering when a Theme Builder template is active
            \add_filter('fluent_cart/disable_auto_single_product_page', [$this, 'maybeDisableAutoSingleProduct']);
        }
    }

    public function registerCategories($elements_manager)
    {
        $elements_manager->add_category('fluent-cart', [
            'title' => esc_html__('FluentCart', 'fluent-cart'),
            'icon'  => 'fa fa-shopping-cart',
        ]);

        $elements_manager->add_category('fluentcart-elements-single', [
            'title' => esc_html__('FluentCart Product', 'fluent-cart'),
            'icon'  => 'fa fa-shopping-cart',
        ]);
    }

    public function registerWidgets($widgets_manager)
    {
        $widgets_manager->register(new AddToCartWidget());
        $widgets_manager->register(new BuyNowWidget());
        $widgets_manager->register(new MiniCartWidget());
        $widgets_manager->register(new ShopAppWidget());
        $widgets_manager->register(new ProductCardWidget());
        $widgets_manager->register(new ProductCarouselWidget());
        $widgets_manager->register(new ProductCategoriesListWidget());
        $widgets_manager->register(new CheckoutWidget());

        // Theme Builder product widgets
        $widgets_manager->register(new ProductTitleWidget());
        $widgets_manager->register(new ProductGalleryWidget());
        $widgets_manager->register(new ProductPriceWidget());
        $widgets_manager->register(new ProductStockWidget());
        $widgets_manager->register(new ProductExcerptWidget());
        $widgets_manager->register(new ProductBuySectionWidget());
        $widgets_manager->register(new ProductContentWidget());
        $widgets_manager->register(new ProductInfoWidget());
        $widgets_manager->register(new RelatedProductsWidget());
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

    /**
     * Register document types for Theme Builder.
     */
    public function registerDocuments($documents_manager)
    {
        $documents_manager->register_document_type('fluentcart-product-post', FluentCartProductPost::class);
        $documents_manager->register_document_type('fluentcart-product', FluentCartProduct::class);
    }

    /**
     * Register conditions for Theme Builder.
     */
    public function registerConditions($conditions_manager)
    {
        $condition = new FluentCartCondition();

        $conditions_manager->get_condition('general')->register_sub_condition($condition);
    }

    /**
     * Tell Elementor Pro to override the template for single fluent-products pages.
     */
    public function themeTemplateInclude($need_override, $location)
    {
        if (is_singular('fluent-products') && $location === 'single') {
            return true;
        }

        return $need_override;
    }

    /**
     * Remove fluent-products from the generic Singular conditions to avoid duplication,
     * since we have our own dedicated FluentCart condition.
     */
    public function removeFluentProductsFromGenericConditions($post_types)
    {
        unset($post_types['fluent-products']);

        return $post_types;
    }

    /**
     * Disable FluentCart core's auto single product page rendering
     * when an Elementor Pro Theme Builder template is active.
     */
    public function maybeDisableAutoSingleProduct($disable)
    {
        if (!is_singular('fluent-products')) {
            return $disable;
        }

        $module = \ElementorPro\Modules\ThemeBuilder\Module::instance();
        $documents = $module->get_conditions_manager()->get_documents_for_location('single');

        return !empty($documents) ? true : $disable;
    }
}
