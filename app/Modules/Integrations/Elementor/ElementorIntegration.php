<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor;

use FluentCart\App\Modules\Integrations\Elementor\Widgets\AddToCartWidget;
use FluentCart\App\Modules\Integrations\Elementor\Widgets\BuyNowWidget;
use FluentCart\App\Modules\Integrations\Elementor\Controls\ProductSelectControl;
use FluentCart\App\Helpers\Helper;
use FluentCart\App\Vite;

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
    }

    public function registerControls($controls_manager)
    {
        if (class_exists('\FluentCart\App\Modules\Integrations\Elementor\Controls\ProductSelectControl')) {
            $controls_manager->register(new ProductSelectControl());
        }
    }

    public function enqueueEditorScripts()
    {
        $restInfo = Helper::getRestInfo();

        Vite::enqueueScript(
            'fluent-cart-elementor-editor',
            'elementor/editor.js',
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
