<?php

namespace FluentCartElementorBlocks\App\Services\Badges;

use FluentCart\App\Helpers\Helper;
use FluentCart\App\Models\Product;
use FluentCart\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Badge state (sale / sold-out) for the FluentCart Elementor widgets' Sale /
 * Sold Out badge overlays — computed WITHOUT core changes.
 *
 * Bridge-first: if FluentCart core ever ships a shared badge service
 * (\FluentCart\App\Services\ProductBadgeService), every answer is delegated
 * to it so all builders converge on one source with no addon release. Until
 * then the local fallback replicates the STOREFRONT's own default-variation
 * selection (ProductRenderer::__construct — stored default validated against
 * eligible variants, first-by-serial fallback, active-only for advanced
 * variations), so a badge never advertises a discount on a variant the
 * shopper isn't shown. Keep the fallback in sync with core's ProductRenderer.
 */
class BadgeState
{
    /**
     * @param Product $product Expects variants (and detail) loaded.
     * @param string $priceSource 'default_variant' | 'best_discount'
     * @return array{is_on_sale: bool, discount_percent: int}
     */
    public static function saleState(Product $product, $priceSource = 'default_variant')
    {
        if (class_exists('\FluentCart\App\Services\ProductBadgeService')) {
            $state = \FluentCart\App\Services\ProductBadgeService::saleState($product, $priceSource);

            return [
                'is_on_sale'       => !empty($state['is_on_sale']),
                'discount_percent' => (int) Arr::get($state, 'discount_percent', 0),
            ];
        }

        $state = ['is_on_sale' => false, 'discount_percent' => 0];

        if (!$product->variants || $product->variants->isEmpty()) {
            return $state;
        }

        if ($priceSource === 'best_discount') {
            foreach (self::eligibleVariants($product) as $variant) {
                $discount = self::discountPercent($variant);
                if ($discount > 0) {
                    $state['is_on_sale'] = true;
                    if ($discount > $state['discount_percent']) {
                        $state['discount_percent'] = $discount;
                    }
                }
            }

            return $state;
        }

        $variant = self::resolveDefaultVariant($product);

        if ($variant) {
            $discount = self::discountPercent($variant);
            if ($discount > 0) {
                $state['is_on_sale'] = true;
                $state['discount_percent'] = $discount;
            }
        }

        return $state;
    }

    /**
     * @param Product $product Expects detail loaded.
     * @return bool
     */
    public static function isOutOfStock(Product $product)
    {
        if (class_exists('\FluentCart\App\Services\ProductBadgeService')) {
            return (bool) \FluentCart\App\Services\ProductBadgeService::isOutOfStock($product);
        }

        if (!$product->detail) {
            return false;
        }

        return $product->detail->stock_availability === Helper::OUT_OF_STOCK;
    }

    /**
     * The variation the storefront highlights first — mirrors core
     * ProductRenderer: stored detail.default_variation_id when it points at
     * an ELIGIBLE variant, otherwise the first eligible variant by
     * serial_index with NULL serial pushed last (a legacy/unordered row must
     * not outrank an explicitly ordered one).
     *
     * @param Product $product
     * @return \FluentCart\App\Models\ProductVariation|null
     */
    private static function resolveDefaultVariant(Product $product)
    {
        $eligible = self::eligibleVariants($product);

        if ($eligible->isEmpty()) {
            return null;
        }

        $storedId = $product->detail ? $product->detail->default_variation_id : null;

        if ($storedId) {
            $stored = $eligible->firstWhere('id', $storedId);
            if ($stored) {
                return $stored;
            }
        }

        return $eligible
            ->sortBy(function ($variant) {
                return is_null($variant->serial_index)
                    ? PHP_INT_MAX
                    : (int) $variant->serial_index;
            })
            ->first();
    }

    /**
     * Variants the storefront selector actually exposes: active-only for
     * advanced variations (AdvancedVariationRenderer skips inactive
     * combinations), all variants otherwise.
     *
     * @param Product $product
     * @return \FluentCart\Framework\Support\Collection
     */
    private static function eligibleVariants(Product $product)
    {
        $isAdvanced = $product->detail
            && $product->detail->variation_type === Helper::PRODUCT_TYPE_ADVANCE_VARIATION;

        return $isAdvanced
            ? $product->variants->where('item_status', 'active')
            : $product->variants;
    }

    /**
     * Discount percent for one variant, clamped 0-100. 0 = not on sale.
     *
     * @param \FluentCart\App\Models\ProductVariation $variant
     * @return int
     */
    private static function discountPercent($variant)
    {
        if (!($variant->compare_price > $variant->item_price && $variant->compare_price > 0)) {
            return 0;
        }

        return (int) max(0, min(100, round((($variant->compare_price - $variant->item_price) / $variant->compare_price) * 100)));
    }
}
