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

    protected $badgeSettings = [];

    public function setCardElements(array $cardElements)
    {
        $this->cardElements = $cardElements;
        $this->clientId = 'el_' . md5(wp_json_encode($cardElements) . wp_unique_id());
    }

    public function setShopLayout(array $shopLayout)
    {
        $this->shopLayout = $shopLayout;
    }

    public function setBadgeSettings(array $badgeSettings)
    {
        $this->badgeSettings = $badgeSettings;
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

        // Auto-scope to the queried term on a product taxonomy archive (parity
        // with the Divi module) — this is what makes the Product Category
        // template work as an archive template: it scopes the grid to the
        // current category/brand instead of listing the whole store.
        $this->applyArchiveScope($mergedTerms, $filters);

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
            'live_filter'              => $this->shortcodeAttributes['live_filter'],
            'enable_filters'           => $enableFilters,
            'custom_filters'           => $customFilters,
        ];
    }

    /**
     * When the shop is rendered on a product category/brand archive (e.g. an
     * Elementor Theme Builder archive template for product-categories), scope
     * the grid to the queried term and hide that taxonomy's own sidebar filter.
     * Ports the Divi ShopApp module's applyArchiveScope so the Category (and
     * Shop) templates behave as archive templates.
     *
     * Only acts inside a real taxonomy archive (is_tax) — plain pages and the
     * main shop are untouched.
     *
     * @param array $mergedTerms taxonomy slug => [term ids] (by ref)
     * @param array $filters     sidebar filter config keyed by taxonomy (by ref)
     * @return void
     */
    private function applyArchiveScope(array &$mergedTerms, array &$filters)
    {
        if (!is_tax(get_object_taxonomies('fluent-products'))) {
            return;
        }

        $queried = get_queried_object();
        if (!($queried instanceof \WP_Term)
            || !in_array($queried->taxonomy, ['product-categories', 'product-brands'], true)) {
            return;
        }

        // Scope the grid to the archive's term (merge with any existing terms
        // for that taxonomy so a preset default filter still narrows within it).
        $existing = (array) Arr::get($mergedTerms, $queried->taxonomy, []);
        $existing[] = (int) $queried->term_id;
        $mergedTerms[$queried->taxonomy] = array_values(array_unique(array_map('intval', $existing)));

        // Hide the archive taxonomy's own filter — the archive already
        // constrains it, and picking a different term would AND with the
        // archive scope and return nothing. Price / the other taxonomy still
        // apply within the archive.
        unset($filters[$queried->taxonomy]);
    }

    private function buildRendererConfig()
    {
        $config = $this->buildQueryConfig();
        $config['card_elements'] = $this->cardElements;
        $config['client_id'] = $this->clientId;
        $config['shop_layout'] = $this->shopLayout;
        $config['badge_settings'] = $this->badgeSettings;
        $config['view_mode'] = $this->shortcodeAttributes['view_mode'];
        $config['product_box_grid_size'] = $this->shortcodeAttributes['product_box_grid_size'];
        $config['price_format'] = $this->shortcodeAttributes['price_format'];
        $config['enable_wildcard_filter'] = $this->shortcodeAttributes['enable_wildcard_filter'];
        $config['enable_wildcard_for_post_content'] = $this->shortcodeAttributes['enable_wildcard_for_post_content'];
        return $config;
    }
}
