<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Divi\Modules\Legacy;

use FluentCart\App\Helpers\CartHelper;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\CartRenderer;
use FluentCart\App\Services\Renderer\CheckoutRenderer;
use FluentCart\App\Vite;
use FluentCart\Framework\Support\Arr;

class CheckoutModule extends \ET_Builder_Module
{
    public $slug       = 'fceb_checkout';
    public $vb_support = 'partial';

    public function init()
    {
        $this->name = esc_html__('FluentCart Checkout', 'fluentcart-elementor-blocks');
        $this->icon = 'N';
    }

    public function get_fields()
    {
        return [
            'layout_type' => [
                'label'           => esc_html__('Layout', 'fluentcart-elementor-blocks'),
                'type'            => 'select',
                'option_category' => 'layout',
                'options'         => [
                    'two-column' => esc_html__('Two Column', 'fluentcart-elementor-blocks'),
                    'one-column' => esc_html__('One Column', 'fluentcart-elementor-blocks'),
                ],
                'default'         => 'two-column',
                'toggle_slug'     => 'main_content',
            ],
        ];
    }

    public function render($attrs, $content, $render_slug)
    {
        $this->loadCheckoutStyles();
        AssetLoader::loadCheckoutAssets();

        $cart = CartHelper::getCart();

        if (!$cart || empty(Arr::get($cart, 'cart_data', []))) {
            ob_start();
            (new CartRenderer())->renderEmpty();
            $emptyHtml = ob_get_clean();

            return sprintf(
                '<div class="fluent-cart-divi-checkout"><div class="fce-checkout-empty-cart">%s</div></div>',
                $emptyHtml
            );
        }

        $checkoutRenderer = new CheckoutRenderer($cart);
        $layoutType = $this->props['layout_type'] ?? 'two-column';

        ob_start();
        $checkoutRenderer->render([
            'layout' => $layoutType,
        ]);
        $html = ob_get_clean();

        return sprintf('<div class="fluent-cart-divi-checkout">%s</div>', $html);
    }

    private function loadCheckoutStyles()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        AssetLoader::loadCartAssets();

        Vite::enqueueStyle(
            'fce-checkout-page-css',
            'public/checkout/style/checkout.scss'
        );

        Vite::enqueueStyle(
            'fce-checkout-select-css',
            'public/components/select/style/style.scss'
        );
    }
}
