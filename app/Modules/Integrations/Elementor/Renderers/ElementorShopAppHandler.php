<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers;

use FluentCart\Api\Resource\ShopResource;
use FluentCart\App\Helpers\Helper;
use FluentCart\App\Hooks\Handlers\ShortCodes\ShopAppHandler;
use FluentCart\Framework\Support\Arr;

class ElementorShopAppHandler extends ShopAppHandler
{
    protected $cardElements = [];

    protected $shopLayout = [];

    protected $clientId = '';

    public function setCardElements(array $cardElements)
    {
        $this->cardElements = $cardElements;
        $this->clientId = 'el_' . md5(wp_json_encode($cardElements) . wp_unique_id());
    }

    public function setShopLayout(array $shopLayout)
    {
        $this->shopLayout = $shopLayout;
    }

    public function renderView()
    {
        ob_start();
        (new ElementorShopAppRenderer(
            $this->getProducts(),
            $this->buildRendererConfig()
        ))->render();
        return ob_get_clean();
    }

    private function getProducts()
    {
        $params = $this->buildQueryConfig();
        $products = ShopResource::get($params);

        return [
            'products' => ($products['products']->setCollection(
                $products['products']->getCollection()->transform(function ($product) {
                    $product->setAppends(['view_url', 'has_subscription']);
                    return $product;
                })
            )),
            'total' => $products['total']
        ];
    }

    private function buildQueryConfig()
    {
        $paginatorMethod = $this->shortcodeAttributes['paginator'] === 'numbers' ? 'simple' : 'cursor';

        $defaultFilters = $this->shortcodeAttributes['default_filters'];
        $customFilters = $this->shortcodeAttributes['custom_filters'];

        $filters = $this->shortcodeAttributes['filters'];
        $enableFilters = Arr::get($filters, 'enabled', false) === true;

        $allowOutOfStock = Arr::get($defaultFilters, 'enabled', false) === true &&
            Arr::get($defaultFilters, 'allow_out_of_stock', false) === true;

        if (Arr::get($defaultFilters, 'enabled') != 1) {
            $defaultFilters = [];
        }

        $status = ["post_status" => ["column" => "post_status", "operator" => "in", "value" => ["publish"]]];

        $urlTerms = Helper::parseTermIdsForFilter($this->urlFilters);
        $defaultTerms = Helper::parseTermIdsForFilter($defaultFilters);
        $mergedTerms = Helper::mergeTermIdsForFilter($defaultTerms, $urlTerms);

        $filters = array_merge($filters, $this->urlFilters);

        return [
            "select"                   => '*',
            "with"                     => ['detail', 'variants', 'categories', 'licensesMeta'],
            "selected_status"          => true,
            "status"                   => $status,
            "shop_app_default_filters" => $defaultFilters,
            "default_filters"          => $defaultFilters,
            "taxonomy_filters"         => $mergedTerms,
            "paginate"                 => $this->shortcodeAttributes['per_page'],
            "per_page"                 => $this->shortcodeAttributes['per_page'],
            'filters'                  => $filters,
            'paginate_using'           => $paginatorMethod,
            'pagination_type'          => $paginatorMethod,
            'allow_out_of_stock'       => $allowOutOfStock,
            'order_type'               => $this->shortcodeAttributes['order_type'],
            'live_filter'              => $this->shortcodeAttributes['live_filter'],
            'enable_filters'           => $enableFilters,
            'custom_filters'           => $customFilters,
        ];
    }

    private function buildRendererConfig()
    {
        $config = $this->buildQueryConfig();
        $config['card_elements'] = $this->cardElements;
        $config['client_id'] = $this->clientId;
        $config['shop_layout'] = $this->shopLayout;
        $config['view_mode'] = $this->shortcodeAttributes['view_mode'];
        $config['product_box_grid_size'] = $this->shortcodeAttributes['product_box_grid_size'];
        $config['price_format'] = $this->shortcodeAttributes['price_format'];
        return $config;
    }
}
