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
        $this->name             = esc_html__('FluentCart Mini Cart', 'fluentcart-elementor-blocks');
        $this->icon             = 'N';
        $this->main_css_element = '%%order_class%%';

        $this->settings_modal_toggles = [
            'general' => [
                'toggles' => [
                    'main_content' => esc_html__('Content', 'fluentcart-elementor-blocks'),
                ],
            ],
        ];
    }

    public function get_fields()
    {
        return [];
    }

    public function render($attrs, $content, $render_slug)
    {
        // In Divi Visual Builder, show a static preview
        // The full cart system (CartLoader, CartHelper) can hang in the VB AJAX context
        if ($this->isBuilderContext()) {
            return $this->renderBuilderPreview();
        }

        try {
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

            if (!empty(trim($miniCartHtml))) {
                return sprintf(
                    '<div class="fluent-cart-divi-mini-cart">%s</div>',
                    $miniCartHtml
                );
            }
        } catch (\Throwable $e) {
            // Fall through to preview
        }

        return $this->renderBuilderPreview();
    }

    private function isBuilderContext(): bool
    {
        // Divi Visual Builder frontend iframe
        if (function_exists('et_core_is_fb_enabled') && et_core_is_fb_enabled()) {
            return true;
        }

        // Divi VB query parameter
        if (!empty($_GET['et_fb'])) { // phpcs:ignore WordPress.Security.NonceVerification
            return true;
        }

        // Divi VB AJAX preview
        if (!empty($_POST['action']) && strpos($_POST['action'], 'et_pb_') === 0) { // phpcs:ignore WordPress.Security.NonceVerification
            return true;
        }

        return false;
    }

    private function renderBuilderPreview(): string
    {
        return '<div class="fluent-cart-divi-mini-cart">'
            . '<span style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;padding:8px 12px;">'
            . '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>'
            . '<line x1="3" y1="6" x2="21" y2="6"/>'
            . '<path d="M16 10a4 4 0 0 1-8 0"/>'
            . '</svg>'
            . '<span style="background:#333;color:#fff;border-radius:50%;min-width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;padding:0 4px;">0</span>'
            . '</span>'
            . '</div>';
    }
}
