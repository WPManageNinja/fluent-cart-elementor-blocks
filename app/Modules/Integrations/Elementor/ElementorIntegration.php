<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor;


use FluentCart\App\Helpers\Helper;
use FluentCart\App\Services\Renderer\MiniCartRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls\ProductSelectControl;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\AddToCartWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\BuyNowWidget;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\MiniCartWidget;
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
    }

    public function registerWidgets($widgets_manager)
    {
        $widgets_manager->register(new AddToCartWidget());
        $widgets_manager->register(new BuyNowWidget());
        if(class_exists(MiniCartRenderer::class)){
            $widgets_manager->register(new MiniCartWidget());
        }
        $widgets_manager->register(new ShopAppWidget());

    }

    public function registerControls($controls_manager)
    {
        $controls_manager->register(new ProductSelectControl());
    }

    public function enqueueEditorScripts()
    {
        $restInfo = Helper::getRestInfo();


        Enqueue::script(
            'fluent-cart-elementor-editor',
            'elementor/product-select-controll.js',
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
