<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Divi\Modules\Legacy;

use FluentCart\App\Hooks\Cart\CartLoader;
use FluentCart\App\Helpers\CartHelper;
use FluentCart\App\Services\Renderer\MiniCartRenderer;
use FluentCart\App\Modules\Templating\AssetLoader;

class MiniCartModule extends \ET_Builder_Module
{
    public $slug       = 'fceb_mini_cart';
    public $vb_support = 'partial';

    public function init()
    {
        $this->name = esc_html__('FluentCart Mini Cart', 'fluentcart-elementor-blocks');
        $this->icon = 'N';
    }

    public function get_fields()
    {
        return [];
    }

    public function render($attrs, $content, $render_slug)
    {
        (new CartLoader())->registerDependency();
        AssetLoader::loadMiniCartAssets();

        $cart = CartHelper::getCart(null, false);
        $itemCount = 0;
        $cartData = [];

        if ($cart) {
            $cartData = $cart->cart_data ?? [];
            $itemCount = count($cartData);
        }

        $miniCartRenderer = new MiniCartRenderer($cartData, [
            'item_count' => $itemCount,
        ]);

        $renderAttributes = [
            'is_shortcode'  => true,
            'button_class'  => 'fluent_cart_mini_cart_trigger',
        ];

        ob_start();
        $miniCartRenderer->renderMiniCart($renderAttributes);
        $miniCartHtml = ob_get_clean();

        return sprintf(
            '<div class="fluent-cart-divi-mini-cart">%s</div>',
            $miniCartHtml
        );
    }
}
