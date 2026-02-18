# Reorderable Shop Layout Sections via Repeater

## Overview

Added a **Shop Layout** repeater control that lets users reorder/remove top-level shop sections (view switcher, sort dropdown, filter sidebar, product grid, paginator) from the Elementor editor. This mirrors the existing **Card Elements** repeater that reorders product card internals.

## Section Types

| element_type    | Renders                                              |
|-----------------|------------------------------------------------------|
| `view_switcher` | Grid/list toggle buttons + filter toggle button      |
| `sort_by`       | Sort dropdown (A-Z, price, date)                     |
| `filter`        | Filter sidebar (search, taxonomy filters, buttons)   |
| `product_grid`  | Product card grid (uses card_elements repeater)      |
| `paginator`     | Pagination controls (only when type is "numbers")    |

Default order: `view_switcher` -> `sort_by` -> `filter` -> `product_grid` -> `paginator`

## Structural Constraint

The frontend JS (filters, pagination, sort) depends on a specific HTML structure. The `fct-products-wrapper-inner` div carries `data-*` attributes that the JS hooks into. The repeater reorders sections **within their structural groups**:

- **Before wrapper div**: `view_switcher`, `sort_by` — can be reordered or removed
- **Inside wrapper div** (`fct-products-wrapper-inner`): `filter`, `product_grid` — can be reordered (e.g. filter after grid) or removed
- **After wrapper div**: `paginator` — can be removed

This grouping is enforced in `ElementorShopAppRenderer::render()` regardless of the repeater order. Sections are sorted into their group based on type, but the relative order within each group follows the repeater order.

## Data Flow

```
ShopAppWidget::render()
  -> extracts shop_layout from Elementor settings
  -> calls $handler->setShopLayout($shopLayout)
  -> ElementorShopAppHandler stores it
  -> buildRendererConfig() includes shop_layout in config array
  -> ElementorShopAppRenderer reads it from config in constructor
  -> render() groups sections and renders in order
```

## Files Modified (Elementor Plugin)

### `app/Modules/Integrations/Elementor/Widgets/ShopAppWidget.php`
- Added `registerShopLayoutControls()` — defines the Repeater control with 5 section types
- Updated `register_controls()` to call it
- Updated `render()` to extract `shop_layout` from settings, pass to handler, and include in cache key
- **(2026-02-18)** Refactored `registerStyleControls()` into 9 separate methods. Fixed button selectors from `.fct-button` (non-existent) to `.fct-product-view-button, .fluent-cart-add-to-cart-button`. Added 5 new style sections: Grid Layout, Product Excerpt, Filter, Pagination. Enhanced existing sections. See `refs/claude/shopapp/STYLE-CONTROLS.md` for full reference.

### `app/Modules/Integrations/Elementor/Renderers/ElementorShopAppHandler.php`
- Added `$shopLayout` property
- Added `setShopLayout(array $shopLayout)` setter
- Updated `buildRendererConfig()` to include `shop_layout`

### `app/Modules/Integrations/Elementor/Renderers/ElementorShopAppRenderer.php`
- Added `$shopLayout` property, read from config in constructor
- Overrode `render()` — groups layout items into before/inside/after wrapper zones
- Added `renderLayoutSection()` — dispatches section type to the correct render method
- Added `renderViewSwitcherOnly()` — wraps view switcher buttons in container div
- Added `renderSortByOnly()` — wraps sort dropdown in container div (only if filter enabled)
- Added `renderProductGrid()` — renders the product list container with cards or no-products message

## Parent FluentCart Files (Read-Only References)

These files live in the **fluent-cart** core plugin (not our Elementor plugin). They are the parent classes we extend and call into. Do not modify these directly.

### `fluent-cart/app/Services/Renderer/ShopAppRenderer.php`
The base renderer class. Key methods we call from the override:
- `render()` — the original hardcoded layout (fallback when shop_layout is empty)
- `renderViewSwitcherButton()` — grid/list toggle + filter toggle SVG buttons
- `renderSortByFilter()` — sort dropdown with options (A-Z, price, date)
- `renderFilter($renderer)` — filter sidebar wrapper, takes a `ProductFilterRender` instance
- `renderProduct()` — iterates products and renders cards
- `renderPaginator()` — pagination controls (result wrapper + page selector)

Key protected properties used:
- `$viewMode`, `$isFilterEnabled`, `$per_page`, `$order_type`, `$liveFilter`
- `$paginator`, `$defaultFilters`, `$productBoxGridSize`, `$products`, `$filters`

### `fluent-cart/app/Services/Renderer/RenderHelper.php`
- `RenderHelper::renderAtts($attributes)` — outputs HTML attributes from an associative array

### `fluent-cart/app/Services/Renderer/ProductFilterRender.php`
- Instantiated with `$this->filters`; passed to `renderFilter()` for search + taxonomy filter UI

### `fluent-cart/app/Services/Renderer/ProductRenderer.php`
- `ProductRenderer::renderNoProductFound()` — renders the "no products" empty state

### `fluent-cart/app/Services/Renderer/ProductCardRender.php`
- Used by `renderCardWithLayout()` to render individual product cards with the card_elements repeater order

### `fluent-cart/app/Modules/Templating/AssetLoader.php`
- `AssetLoader::loadProductArchiveAssets()` — enqueues CSS/JS for the product archive

## AJAX Rendering Architecture

When a user filters, sorts, or paginates, the page does **not** do a full reload. FluentCart's frontend JS sends an AJAX request and only the product cards are re-rendered server-side. This section explains **why** we use transients, a WordPress filter hook, and `data-*` attributes to make this work — and what a new page builder integration must implement.

### The Problem

On initial page load the full shop HTML is rendered by our `ElementorShopAppRenderer::render()` — view switcher, filter sidebar, product grid, paginator — all in one pass. But when the user interacts (e.g. clicks page 2, applies a filter, changes sort order), FluentCart's core JS only needs **new product card HTML** to swap into the existing DOM. The surrounding layout (filter sidebar, paginator shell, sort dropdown) stays in place and is updated by JS directly.

The core FluentCart AJAX endpoint (`ShopController::getProductViews`) has no knowledge of Elementor widget settings. It doesn't know our card_elements repeater order. So we need a way to:

1. **Tell the server** which page builder rendered the initial HTML (so it knows who to ask for re-rendering)
2. **Persist the widget config** across requests (the AJAX call doesn't carry Elementor settings)
3. **Hook into the core's rendering pipeline** to return our custom card HTML instead of the default

### How It Works — Step by Step

#### 1. Initial Render: Stamp data attributes on the first product card

`ElementorShopAppRenderer::renderCardWithLayout()` adds two `data-*` attributes to the **first** product card only:

```html
<article data-fct-product-card
         data-template-provider="elementor"
         data-fluent-client-id="el_abc123..."
         ...>
```

- `data-template-provider="elementor"` — identifies which builder rendered this
- `data-fluent-client-id="el_abc123..."` — unique ID tied to this widget's card_elements config

File: `ElementorShopAppRenderer.php` — `renderCardWithLayout()` (the `$providerAttr` logic)

#### 2. Initial Render: Save card_elements to a transient keyed by client ID

In the `ElementorShopAppRenderer` constructor:

```php
set_transient(
    'fc_el_collection_' . $this->clientId,
    $this->cardElements,
    48 * HOUR_IN_SECONDS
);
```

This persists the card element order (image, title, price, button, etc.) so the AJAX callback can retrieve it later without access to the Elementor widget settings.

File: `ElementorShopAppRenderer.php` — `__construct()`

#### 3. User Interaction: Frontend JS reads attributes and sends AJAX

When the user filters/paginates/sorts, FluentCart's `Paginator.js` reads the data attributes from the first `[data-fct-product-card]` element:

```js
// fluent-cart/resources/public/product-page/Paginator/Paginator.js
const firstElement = this.container.querySelector("[data-fct-product-card]");
if (firstElement) {
    query.client_id = firstElement.getAttribute('data-fluent-client-id');
    query.template_provider = firstElement.getAttribute('data-template-provider');
}
```

It sends a GET request to `{rest_url}/public/product-views` with these as query params alongside filters, sort, page, etc.

Similarly, `ScrollPaginator.js` reads `data-fluent-cart-cursor` and `data-template-provider` for infinite scroll pagination.

Files (core, read-only):
- `fluent-cart/resources/public/product-page/Paginator/Paginator.js` (lines ~207-217)
- `fluent-cart/resources/public/product-page/Paginator/ScrollPaginator.js` (lines ~37-48)

#### 4. Server: Core dispatches to the builder-specific filter

`ShopController::getProductViews()` constructs a dynamic filter hook based on `template_provider`:

```php
// fluent-cart/app/Http/Controllers/ShopController.php (line ~83)
$preLoadedView = apply_filters(
    'fluent_cart/products_views/preload_collection_' . $templateProvider,
    '',
    [
        'client_id'   => $clientId,
        'products'    => $products,
        'total'       => $total,
        'requestData' => $request->all(),
    ]
);
```

When `template_provider` is `"elementor"`, the hook becomes `fluent_cart/products_views/preload_collection_elementor`. If the filter returns non-empty HTML, the controller uses it. Otherwise it falls back to default rendering.

File (core, read-only): `fluent-cart/app/Http/Controllers/ShopController.php`

#### 5. Our Plugin: Hook callback re-renders cards with saved config

`ElementorIntegration` registers the filter:

```php
// ElementorIntegration.php
add_filter(
    'fluent_cart/products_views/preload_collection_elementor',
    [$this, 'preloadProductCollectionsAjax'],
    10, 2
);
```

The callback retrieves the saved card_elements from the transient, then loops over the new product set rendering each card using `ElementorShopAppRenderer::renderCardElements()`:

```php
public function preloadProductCollectionsAjax($view, $args)
{
    $clientId = Arr::get($args, 'client_id', '');
    $cardElements = get_transient('fc_el_collection_' . $clientId);

    if (!$cardElements) {
        return $view; // fallback to default rendering
    }

    ob_start();
    foreach ($args['products'] as $product) {
        // render <article> with card elements in custom order
        ElementorShopAppRenderer::renderCardElements($cardRender, $cardElements);
    }
    return ob_get_clean();
}
```

File: `ElementorIntegration.php` — `preloadProductCollectionsAjax()`

#### 6. JS swaps the HTML

The controller returns `{ products: { views: "<article>...</article>..." } }`. FluentCart's JS replaces the contents of `[data-fluent-cart-shop-app-product-list]` with the new HTML. The surrounding layout (filter, paginator, sort) is untouched.

### Why Shop Layout Doesn't Affect AJAX

The shop_layout repeater controls which **top-level sections** appear (view switcher, sort, filter sidebar, product grid, paginator). On AJAX, only the **product cards inside the grid** are replaced. The filter sidebar, paginator shell, sort dropdown — all stay in the DOM and are manipulated directly by JS. So the AJAX callback only needs `card_elements`, not `shop_layout`.

### Transient Summary

| Transient Key                        | Contents           | Set Where                              | Read Where                                | TTL       |
|--------------------------------------|--------------------|----------------------------------------|-------------------------------------------|-----------|
| `fc_el_collection_{clientId}`        | `card_elements`    | `ElementorShopAppRenderer::__construct` | `ElementorIntegration::preloadProductCollectionsAjax` | 48 hours  |
| `fce_shop_app_{hash}`               | Full widget HTML   | `ShopAppWidget::render()`              | `ShopAppWidget::render()` (cache hit)     | 4 hours   |

- The **collection transient** bridges initial render and AJAX — it persists the card element order so the AJAX callback can reproduce the same card layout without Elementor context.
- The **widget HTML transient** is a page-level cache for the entire widget output (all sections). Its key includes `shop_layout` + `card_elements` + shortcode atts so any config change busts the cache.

### What a New Builder (e.g. Divi) Must Implement for AJAX

1. **Stamp `data-template-provider="divi"` and `data-fluent-client-id="{id}"` on the first product card** during initial render. Without these, the JS won't send builder-specific info on AJAX calls.

2. **Save card_elements (and any other per-card config) to a transient** keyed by the client ID during initial render. The AJAX callback runs in a REST API context with no access to builder settings.

3. **Register a filter** on `fluent_cart/products_views/preload_collection_divi` that retrieves the transient and renders product cards in the custom order.

4. **No shop_layout transient needed** — the AJAX callback only renders cards, not the full layout. The layout sections stay in the DOM.

### Core Files Involved in AJAX (Read-Only)

| File | Role |
|------|------|
| `fluent-cart/app/Http/Controllers/ShopController.php` | AJAX endpoint, dispatches `preload_collection_{provider}` filter |
| `fluent-cart/app/Http/Routes/frontend_routes.php` | Route: `GET /public/product-views` -> `ShopController::getProductViews` |
| `fluent-cart/resources/public/product-page/Paginator/Paginator.js` | Reads `data-template-provider` + `data-fluent-client-id` from first card, sends with AJAX |
| `fluent-cart/resources/public/product-page/Paginator/ScrollPaginator.js` | Same for infinite scroll; also reads `data-fluent-cart-cursor` |

## Adapting for Divi Builder

When building the Divi equivalent, the same architecture applies:

1. **Divi Module** (equivalent of `ShopAppWidget`) — define a shop_layout field (Divi uses its own field types, not Elementor Repeater). Extract the layout config and pass to the handler.
2. **Divi Handler** (equivalent of `ElementorShopAppHandler`) — can likely reuse the same handler class or create a `DiviShopAppHandler` extending `ShopAppHandler`. Needs `setCardElements()`, `setShopLayout()`, and the same `buildRendererConfig()` pattern.
3. **Divi Renderer** (equivalent of `ElementorShopAppRenderer`) — extend `ShopAppRenderer`, override `render()` with the same structural grouping logic. The `renderLayoutSection()`, `renderViewSwitcherOnly()`, `renderSortByOnly()`, and `renderProductGrid()` methods can be extracted into a shared trait or base class to avoid duplication.

The structural constraint (before/inside/after wrapper grouping) is inherent to the FluentCart JS and applies regardless of page builder.
