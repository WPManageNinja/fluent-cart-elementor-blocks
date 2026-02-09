<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Divi\Modules\Legacy;

use FluentCart\App\Models\Product;
use FluentCart\App\Models\ProductVariation;
use FluentCart\App\Services\Renderer\ProductRenderer;
use FluentCart\App\Modules\Templating\AssetLoader;

class AddToCartModule extends \ET_Builder_Module
{
    public $slug       = 'fceb_add_to_cart';
    public $vb_support = 'partial';

    public function init()
    {
        $this->name             = esc_html__('FluentCart Add to Cart', 'fluentcart-elementor-blocks');
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
                'default'         => esc_html__('Add to Cart', 'fluentcart-elementor-blocks'),
                'toggle_slug'     => 'main_content',
            ],
        ];
    }

    public function render($attrs, $content, $render_slug)
    {
        $variantId = $this->props['variant_id'] ?? '';

        if (empty($variantId)) {
            return '<div class="fluent-cart-divi-add-to-cart"><p>' . esc_html__('Please enter a Product Variation ID.', 'fluentcart-elementor-blocks') . '</p></div>';
        }

        try {
            $variation = ProductVariation::query()->find($variantId);
            if (!$variation) {
                return '<div class="fluent-cart-divi-add-to-cart"><p>' . esc_html__('Variation not found.', 'fluentcart-elementor-blocks') . '</p></div>';
            }

            $product = Product::query()->find($variation->post_id);
            if (!$product) {
                return '<div class="fluent-cart-divi-add-to-cart"><p>' . esc_html__('Product not found.', 'fluentcart-elementor-blocks') . '</p></div>';
            }

            AssetLoader::loadAddToCartCss();

            $attributes = [
                'variant_ids'  => [$variantId],
                'text'         => $this->props['button_text'] ?? esc_html__('Add to Cart', 'fluentcart-elementor-blocks'),
                'is_shortcode' => true,
            ];

            ob_start();
            (new ProductRenderer($product, ['default_variation_id' => $variantId]))->renderAddToCartButtonBlock($attributes);
            $html = ob_get_clean();

            return sprintf('<div class="fluent-cart-divi-add-to-cart">%s</div>', $html);
        } catch (\Throwable $e) {
            return '<div class="fluent-cart-divi-add-to-cart"><p>' . esc_html__('Add to Cart', 'fluentcart-elementor-blocks') . '</p></div>';
        }
    }
}
