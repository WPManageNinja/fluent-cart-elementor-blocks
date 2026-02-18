<?php

namespace FluentCartElementorBlocks\App\Http\Controllers;

use FluentCart\App\Helpers\Helper;
use FluentCart\App\Models\Product;
use FluentCart\App\Models\ProductVariation;
use FluentCart\Framework\Http\Request\Request;
use FluentCart\Framework\Support\Arr;

class ProductController extends Controller
{
    public function searchProductVariantOptions(Request $request): array
    {
        $data = $request->getSafe([
            'include_ids.*' => 'intval',
            'search'        => 'sanitize_text_field',
            'scopes.*'      => 'sanitize_text_field',
            'subscription_status' => 'sanitize_text_field',
        ]);

        $subscription_status = Arr::get($data, 'subscription_status');
        $search = Arr::get($data, 'search', '');
        $includeIds = Arr::get($data, 'include_ids', []);

        $productsQuery = Product::query()
            ->where('post_status', 'publish');

        $productsQuery->with(['detail', 'variants' => function ($query) use ($subscription_status) {
            if ($subscription_status === 'not_subscribable') {
                $query->where('payment_type', '!=', 'subscription');
            }
        }]);

        $scopes = Arr::get($data, 'scopes', []);
        if ($scopes) {
            $productsQuery = $productsQuery->scopes($scopes);
        }

        if ($search) {
            $productsQuery->where(function ($query) use ($search, $subscription_status) {
                $query->where('post_title', 'like', '%' . $search . '%')
                    ->orWhereHas('variants', function ($query) use ($search, $subscription_status) {
                        $query->where('variation_title', 'like', "%$search%");
                        if ($subscription_status === 'not_subscribable') {
                            $query->where('payment_type', '!=', 'subscription');
                        }
                    });
            });
        }

        $productsQuery->limit(20);

        $products = $productsQuery->get();

        $pushedVariationIds = [];
        $formattedProducts = [];

        foreach ($products as $product) {
            $detail = $product->detail;
            if ($detail && $detail->manage_stock && $detail->stock_availability !== Helper::IN_STOCK) {
                continue;
            }

            $formatted = [
                'value' => 'product_' . $product->ID,
                'label' => $product->post_title,
            ];

            $variants = $product->variants;

            $children = [];
            foreach ($variants as $variant) {
                if ($variant->manage_stock && $variant->stock_status !== Helper::IN_STOCK) {
                    continue;
                }
                $pushedVariationIds[] = $variant->id;
                $children[] = [
                    'value' => $variant->id,
                    'label' => $variant->variation_title,
                ];
            }

            if (!$children) {
                continue;
            }

            $formatted['children'] = $children;
            $formattedProducts[$product->ID] = $formatted;
        }

        $leftVariationIds = array_diff($includeIds, $pushedVariationIds);

        if ($leftVariationIds) {
            $leftVariants = ProductVariation::query()
                ->whereIn('id', $leftVariationIds)
                ->with(['product' => function ($query) {
                    $query->where('post_status', 'publish');
                }, 'product.detail'])
                ->get();

            foreach ($leftVariants as $variant) {
                if ($subscription_status == 'not_subscribable' && $variant->payment_type === 'subscription') {
                    continue;
                }
                if ($variant->manage_stock && $variant->stock_status !== Helper::IN_STOCK) {
                    continue;
                }
                $product = $variant->product;
                if (!$product) {
                    continue;
                }
                $detail = $product->detail;
                if ($detail && $detail->manage_stock && $detail->stock_availability !== Helper::IN_STOCK) {
                    continue;
                }
                if (isset($formattedProducts[$product->ID])) {
                    $formattedProducts[$product->ID]['children'][] = [
                        'value' => $variant->id,
                        'label' => $variant->variation_title,
                    ];
                } else {
                    $formattedProducts[$product->ID] = [
                        'value'    => 'product_' . $product->ID,
                        'label'    => $product->post_title,
                        'children' => [
                            [
                                'value' => $variant->id,
                                'label' => $variant->variation_title,
                            ]
                        ]
                    ];
                }
            }
        }

        $products = array_values($formattedProducts);

        // sort the products by label
        usort($products, function ($a, $b) {
            return strcmp($a['label'], $b['label']);
        });

        return [
            'products' => $products
        ];
    }
}
