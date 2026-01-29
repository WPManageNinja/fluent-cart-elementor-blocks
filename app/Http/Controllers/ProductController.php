<?php

namespace FluentCartElementorBlocks\App\Http\Controllers;

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

        $search = Arr::get($data, 'search', '');
        $includeIds = Arr::get($data, 'include_ids', []);

        $productsQuery = Product::query();
        $subscription_status = Arr::get($data,'subscription_status');

        if($subscription_status === 'not_subscribable'){
            $productsQuery = $productsQuery->with(['variants' => function ($query) use ($includeIds) {
                $query->where('payment_type', '!=', 'subscription');
            }]);
        }else{
            $productsQuery = $productsQuery->with(['variants']);
        }


        if ($search) {
            $productsQuery->where('post_title', 'like', '%' . $search . '%');
        }

        $productsQuery->limit(20);

        $products = $productsQuery->get();

        $pushedVariationIds = [];
        $formattedProducts = [];


        foreach ($products as $product) {
            $formatted = [
                'value' => 'product_' . $product->ID,
                'label' => $product->post_title,
            ];

            $variants = $product->variants;

            $children = [];
            foreach ($variants as $variant) {
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
                ->with('product')
                ->get();

            foreach ($leftVariants as $variant) {
                if ($subscription_status == 'not_subscribable' && $variant->payment_type === 'subscription') {
                    continue;
                }
                $product = $variant->product;
                if (!$product) {
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