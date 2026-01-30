<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers;

use FluentCart\App\Models\Product;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductCardRender;
use FluentCart\App\Services\Renderer\ProductRenderer;
use FluentCart\App\Services\Renderer\RenderHelper;
use FluentCart\App\Services\Renderer\ShopAppRenderer;
use FluentCart\Framework\Pagination\CursorPaginator;
use FluentCart\Framework\Support\Arr;

class ElementorShopAppRenderer extends ShopAppRenderer
{
    protected $cardElements = [];

    protected $shopLayout = [];

    protected $clientId = '';

    public function __construct($products = [], $config = [])
    {
        $this->cardElements = Arr::get($config, 'card_elements', []);
        $this->clientId = Arr::get($config, 'client_id', '');
        $this->shopLayout = Arr::get($config, 'shop_layout', []);

        if ($this->clientId && !empty($this->cardElements)) {
            set_transient(
                'fc_el_collection_' . $this->clientId,
                $this->cardElements,
                48 * HOUR_IN_SECONDS
            );
        }

        parent::__construct($products, $config);
    }

    public function render()
    {
        AssetLoader::loadProductArchiveAssets();

        $layoutTypes = array_column($this->shopLayout, 'element_type');

        // If no shop layout configured, fall back to parent render
        if (empty($layoutTypes)) {
            parent::render();
            return;
        }

        // Group sections by structural position
        $wrapperInnerTypes = ['filter', 'product_grid'];
        $beforeWrapper = [];
        $insideWrapper = [];
        $afterWrapper  = [];

        foreach ($layoutTypes as $type) {
            if (in_array($type, $wrapperInnerTypes, true)) {
                $insideWrapper[] = $type;
            } elseif ($type === 'paginator') {
                $afterWrapper[] = $type;
            } else {
                $beforeWrapper[] = $type;
            }
        }

        $isFullWidth = !$this->isFilterEnabled ? ' fct-full-container-width ' : '';
        $filterRenderer = new \FluentCart\App\Services\Renderer\ProductFilterRender($this->filters);

        $wrapperAttributes = [
            'class'                                  => 'fct-products-wrapper-inner mode-' . $this->viewMode . $isFullWidth,
            'data-fluent-cart-product-wrapper-inner'  => '',
            'data-per-page'                          => $this->per_page,
            'data-order-type'                        => $this->order_type,
            'data-live-filter'                       => $this->liveFilter,
            'data-paginator'                         => $this->paginator,
            'data-default-filters'                   => wp_json_encode($this->defaultFilters),
        ];
        ?>
        <div class="fct-products-wrapper" data-fluent-cart-shop-app data-fluent-cart-product-wrapper role="main" aria-label="<?php esc_attr_e('Products', 'fluent-cart'); ?>">
            <?php
            // Render before-wrapper sections (view_switcher, sort_by)
            foreach ($beforeWrapper as $type) {
                $this->renderLayoutSection($type, $filterRenderer);
            }

            if (!empty($insideWrapper)) {
            ?>
            <div <?php RenderHelper::renderAtts($wrapperAttributes); ?>>
                <?php
                foreach ($insideWrapper as $type) {
                    $this->renderLayoutSection($type, $filterRenderer);
                }
                ?>
            </div>
            <?php
            }

            // Render after-wrapper sections (paginator)
            foreach ($afterWrapper as $type) {
                $this->renderLayoutSection($type, $filterRenderer);
            }
            ?>
        </div>
        <?php
    }

    private function renderLayoutSection($type, $filterRenderer)
    {
        switch ($type) {
            case 'view_switcher':
                $this->renderViewSwitcherOnly();
                break;
            case 'sort_by':
                $this->renderSortByOnly();
                break;
            case 'filter':
                $this->renderFilter($filterRenderer);
                break;
            case 'product_grid':
                $this->renderProductGrid();
                break;
            case 'paginator':
                if ($this->paginator === 'numbers') {
                    $this->renderPaginator();
                }
                break;
        }
    }

    /**
     * Render just the view switcher buttons (grid/list toggle + filter toggle),
     * wrapped in the standard container div.
     */
    private function renderViewSwitcherOnly()
    {
        ?>
        <div class="fct-shop-view-switcher-wrap">
            <?php $this->renderViewSwitcherButton(); ?>
        </div>
        <?php
    }

    /**
     * Render just the sort-by dropdown, wrapped in the standard container div.
     */
    private function renderSortByOnly()
    {
        if ($this->isFilterEnabled) {
            ?>
            <div class="fct-shop-view-switcher-wrap">
                <?php $this->renderSortByFilter(); ?>
            </div>
            <?php
        }
    }

    /**
     * Render the product grid container with product cards or no-products message.
     */
    private function renderProductGrid()
    {
        ?>
        <div class="fct-products-container grid-columns-<?php echo esc_attr($this->productBoxGridSize); ?>"
             data-fluent-cart-shop-app-product-list
             role="list"
             aria-label="<?php esc_attr_e('Product list', 'fluent-cart'); ?>"
        >
            <?php
            if ($this->products->count() !== 0) {
                $this->renderProduct();
            } else {
                ProductRenderer::renderNoProductFound();
            }
            ?>
        </div>
        <?php
    }

    public function renderProduct()
    {
        $products = $this->products;

        $cursor = '';
        if ($products instanceof CursorPaginator) {
            $cursor = wp_parse_args(wp_parse_url($products->nextPageUrl(), PHP_URL_QUERY));
        }

        foreach ($products as $index => $product) {
            $cursorAttr = '';
            if ($index === 0) {
                $cursorAttr = Arr::get($cursor, 'cursor', '');
            }

            $this->renderCardWithLayout($product, $cursorAttr, $index === 0);
        }
    }

    private function renderCardWithLayout(Product $product, $cursorAttr = '', $isFirst = false)
    {
        $cardRender = new ProductCardRender($product, ['cursor' => $cursorAttr]);

        $cursorData = '';
        if ($cursorAttr) {
            $cursorData = 'data-fluent-cart-cursor="' . esc_attr($cursorAttr) . '"';
        }

        $providerAttr = '';
        if ($isFirst && $this->clientId) {
            $providerAttr = 'data-template-provider="elementor" data-fluent-client-id="' . esc_attr($this->clientId) . '"';
        }
        ?>
        <article data-fluent-cart-shop-app-single-product data-fct-product-card=""
                 class="fct-product-card"
                <?php echo $cursorData; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo $providerAttr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                 aria-label="<?php echo esc_attr(sprintf(
                         __('%s product card', 'fluent-cart'), $product->post_title));
                 ?>">
            <?php static::renderCardElements($cardRender, $this->cardElements); ?>
        </article>
        <?php
    }

    /**
     * Render card elements in the given order.
     * Shared between initial render and AJAX preload callback.
     */
    public static function renderCardElements(ProductCardRender $cardRender, array $cardElements)
    {
        foreach ($cardElements as $element) {
            $type = Arr::get($element, 'element_type', '');
            switch ($type) {
                case 'image':
                    $cardRender->renderProductImage();
                    break;
                case 'title':
                    $cardRender->renderTitle();
                    break;
                case 'excerpt':
                    $cardRender->renderExcerpt();
                    break;
                case 'price':
                    $cardRender->renderPrices();
                    break;
                case 'button':
                    $cardRender->showBuyButton();
                    break;
            }
        }
    }
}
