<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Divi\Modules\Legacy;

use FluentCart\App\Models\Product;
use FluentCart\App\Models\ProductVariation;
use FluentCart\App\Services\Renderer\ProductRenderer;
use FluentCart\App\Modules\Templating\AssetLoader;

class BuyNowModule extends \ET_Builder_Module
{
    public $slug       = 'fceb_buy_now';
    public $vb_support = 'partial';

    public function init()
    {
        $this->name             = esc_html__('FluentCart Buy Now', 'fluentcart-elementor-blocks');
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
        return [
            'variant_id' => [
                'label'           => esc_html__('Product Variation ID', 'fluentcart-elementor-blocks'),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'description'     => esc_html__('Enter the product variation ID.', 'fluentcart-elementor-blocks'),
                'toggle_slug'     => 'main_content',
            ],
            'button_text' => [
                'label'           => esc_html__('Button Text', 'fluentcart-elementor-blocks'),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'default'         => esc_html__('Buy Now', 'fluentcart-elementor-blocks'),
                'toggle_slug'     => 'main_content',
            ],
            'enable_modal_checkout' => [
                'label'           => esc_html__('Enable Modal Checkout', 'fluentcart-elementor-blocks'),
                'type'            => 'yes_no_button',
                'option_category' => 'basic_option',
                'options'         => [
                    'off' => esc_html__('No', 'fluentcart-elementor-blocks'),
                    'on'  => esc_html__('Yes', 'fluentcart-elementor-blocks'),
                ],
                'default'         => 'off',
                'toggle_slug'     => 'main_content',
            ],
        ];
    }

    public function render($attrs, $content, $render_slug)
    {
        $variantId = $this->props['variant_id'] ?? '';

        if (empty($variantId)) {
            return '<div class="fluent-cart-divi-buy-now"><p>' . esc_html__('Please enter a Product Variation ID.', 'fluentcart-elementor-blocks') . '</p></div>';
        }

        try {
            $variation = ProductVariation::query()->find($variantId);
            if (!$variation) {
                return '<div class="fluent-cart-divi-buy-now"><p>' . esc_html__('Variation not found.', 'fluentcart-elementor-blocks') . '</p></div>';
            }

            $product = Product::query()->find($variation->post_id);
            if (!$product) {
                return '<div class="fluent-cart-divi-buy-now"><p>' . esc_html__('Product not found.', 'fluentcart-elementor-blocks') . '</p></div>';
            }

            AssetLoader::loadAddToCartCss();

            $attributes = [
                'variant_ids'           => [$variantId],
                'text'                  => $this->props['button_text'] ?? esc_html__('Buy Now', 'fluentcart-elementor-blocks'),
                'enable_modal_checkout' => ($this->props['enable_modal_checkout'] ?? 'off') === 'on',
                'is_shortcode'          => true,
            ];

            ob_start();
            (new ProductRenderer($product, ['default_variation_id' => $variantId]))->renderBuyNowButtonBlock($attributes);
            $html = ob_get_clean();

            return sprintf('<div class="fluent-cart-divi-buy-now">%s</div>', $html);
        } catch (\Throwable $e) {
            return '<div class="fluent-cart-divi-buy-now"><p>' . esc_html__('Buy Now', 'fluentcart-elementor-blocks') . '</p></div>';
        }
    }
}
