<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support;

use FluentCart\App\Helpers\CartHelper;
use FluentCart\App\Helpers\Helper;
use FluentCart\App\Models\Cart;
use FluentCart\App\Models\Product;
use FluentCart\App\Models\ProductVariation;
use FluentCart\App\Services\Renderer\CartRenderer;
use FluentCart\App\Services\Renderer\CheckoutRenderer;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The Checkout widget's editor preview for a Custom Product checkout. The live page is drawn by core
 * (the widget's own layout controls do not apply), so this renders core's checkout on a sample cart
 * with the product selector rows in the summary.
 */
class InlineCheckoutPreview
{
    /**
     * @param array $attributes The checkout_mode, variant_ids and selection_mode attributes.
     */
    public static function render(array $attributes): string
    {
        $cart = self::cart($attributes);

        ob_start();

        if (!$cart) {
            (new CartRenderer())->renderEmpty();

            return (string) ob_get_clean();
        }

        $items = self::itemsHtml($attributes);
        $filter = function ($html, $args) use ($cart, $items) {
            if ($html === null && ($args['cart'] ?? null) === $cart && ($args['context'] ?? '') === 'items') {
                return $items;
            }

            return $html;
        };

        add_filter('fluent_cart/checkout/cart_summary_html', $filter, 5, 2);

        try {
            (new CheckoutRenderer($cart))->render();
        } finally {
            remove_filter('fluent_cart/checkout/cart_summary_html', $filter, 5);
        }

        $html = (string) ob_get_clean();

        // Core's script enables the button once the form is ready; scripts do not run in the editor.
        return (string) preg_replace('/(<button\b[^>]*\bid="fluent_cart_order_btn"[^>]*?)\s+disabled(?=[\s>])/', '$1', $html, 1);
    }

    /**
     * An unsaved cart for the summary totals: the first chosen variation, else the newest product's.
     * Never the shopper's or admin's cart. Null when the store has no products.
     */
    protected static function cart(array $attributes): ?Cart
    {
        $ids = self::variationIds($attributes);
        $variation = $ids ? ProductVariation::query()->find($ids[0]) : null;

        if (!$variation) {
            $product = Product::query()->where('post_status', 'publish')->orderBy('ID', 'desc')->first();
            $variation = $product ? $product->variants()->first() : null;
        }

        return $variation ? CartHelper::generateCartFromVariation($variation, 1) : null;
    }

    /**
     * Pro's real option list for the chosen variations, else static sample rows in Pro's markup.
     */
    protected static function itemsHtml(array $attributes): string
    {
        $html = self::proItemsHtml($attributes);

        return $html !== null ? $html : self::sampleItemsHtml($attributes);
    }

    protected static function proItemsHtml(array $attributes): ?string
    {
        $configClass = '\\FluentCartPro\\App\\Modules\\InlineCheckout\\Config\\InlineCheckoutConfig';
        $rendererClass = '\\FluentCartPro\\App\\Modules\\InlineCheckout\\Renderer\\InlineCheckoutItemsRenderer';

        if (!class_exists($configClass) || !class_exists($rendererClass) || !self::variationIds($attributes)) {
            return null;
        }

        $config = $configClass::normalize($attributes, 'elementor');
        $variations = $configClass::resolveVariations($config);

        if (!$variations) {
            return null;
        }

        // The renderer reads the selection off the cart, so seed the first row as selected.
        $cart = new Cart();
        $cart->cart_data = [['object_id' => (int) $variations[0]->id]];

        return (new $rendererClass($cart, $config, $variations))->getItemsHtml();
    }

    protected static function sampleItemsHtml(array $attributes): string
    {
        $isSingle = ($attributes['selection_mode'] ?? 'single') !== 'multiple';
        $rows = self::sampleRows($attributes);
        $label = $isSingle
            ? __('Choose an option', 'fluent-cart-elementor-blocks')
            : __('Choose one or more options', 'fluent-cart-elementor-blocks');

        ob_start();
        ?>
        <div class="fct-inline-items <?php echo $isSingle ? 'fct-inline-items-single' : 'fct-inline-items-multiple'; ?>" role="<?php echo $isSingle ? 'radiogroup' : 'group'; ?>" aria-label="<?php echo esc_attr($label); ?>">
            <?php foreach ($rows as $index => $row) : ?>
                <label class="fct-inline-item<?php echo $index === 0 ? ' is-selected' : ''; ?>">
                    <input class="fct-inline-item-input" type="<?php echo $isSingle ? 'radio' : 'checkbox'; ?>" name="fct_inline_preview<?php echo $isSingle ? '' : '_' . (int) $index; ?>" <?php echo $index === 0 ? 'checked' : ''; ?> />
                    <span class="fct-inline-item-media" aria-hidden="true"><img src="<?php echo esc_url($row['image']); ?>" alt="" /></span>
                    <span class="fct-inline-item-content">
                        <span class="fct-inline-item-title"><?php echo esc_html($row['title']); ?></span>
                        <?php if ($row['subtitle'] !== '') : ?>
                            <span class="fct-inline-item-variant"><?php echo esc_html($row['subtitle']); ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="fct-inline-item-price"><?php echo esc_html($row['price']); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * The chosen variations when there are any, otherwise two sample rows.
     */
    protected static function sampleRows(array $attributes): array
    {
        $ids = self::variationIds($attributes);
        $rows = [];

        if ($ids) {
            $variations = ProductVariation::query()->with('product')->whereIn('id', $ids)->get()->keyBy('id');

            foreach ($ids as $id) {
                $variation = $variations->get($id);
                if (!$variation) {
                    continue;
                }

                $product = $variation->product;
                $title = $product ? (string) $product->post_title : (string) $variation->variation_title;
                $subtitle = $product ? (string) $variation->variation_title : '';

                $rows[] = [
                    'title'    => $title,
                    'subtitle' => $subtitle === $title ? '' : $subtitle,
                    'price'    => Helper::toDecimal($variation->item_price),
                    'image'    => (string) ($variation->thumbnail ?: ($product ? $product->thumbnail : '')) ?: 'https://placehold.co/600x400',
                ];
            }
        }

        if ($rows) {
            return $rows;
        }

        return [
            ['title' => 'Sample Product', 'subtitle' => 'Premium Plan', 'price' => '$200.00', 'image' => 'https://placehold.co/600x400'],
            ['title' => 'Another Product', 'subtitle' => 'Basic Plan', 'price' => '$300.00', 'image' => 'https://placehold.co/600x400'],
        ];
    }

    protected static function variationIds(array $attributes): array
    {
        return array_slice(array_values(array_filter(array_map('absint', (array) ($attributes['variant_ids'] ?? [])))), 0, 20);
    }
}
