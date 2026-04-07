# SearchBarWidget — Style Controls Reference

**File:** `app/Modules/Integrations/Elementor/Widgets/SearchBarWidget.php`
**Widget slug:** `fluent_cart_search_bar`
**Renderer:** `FluentCart\App\Services\Renderer\SearchBarRenderer`

---

## Renderer HTML Structure

```html
<div class="fluent-cart-search-bar-app-wrapper" role="search"
     data-fluent-cart-search-bar-app-wrapper
     data-url-mode="..."
     data-link-with-shop-app="...">

    <div class="fluent-cart-search-bar-app-wrapper-header-wrap">

        <!-- Category filter (conditional on category_mode) -->
        <div class="fluent-cart-search-bar-app-wrapper-select-container">
            <select id="fluent-cart-search-category"
                    data-fluent-cart-search-bar-app-taxonomy name="termId">
                <option value="">Select Category</option>
                ...
            </select>
        </div>

        <!-- Search input -->
        <div class="fluent-cart-search-bar-app-input-wrap">
            <div class="fluent-cart-search-bar-app-input-search"> <!-- SVG search icon --> </div>
            <input class="fluent-cart-search-bar-app-input"
                   data-fluent-cart-search-bar type="text" />
            <button class="fluent-cart-search-bar-app-input-clear"
                    data-fluent-cart-search-clear type="button"> <!-- SVG clear icon --> </button>
        </div>
    </div>

    <!-- Results dropdown (hidden until JS populates it) -->
    <div class="fluent-cart-search-bar-app-wrapper-result-wrap" style="display: none;">
        <h5>Suggestions</h5>
        <ul class="fluent-cart-search-bar-app-list-wrapper"
            data-fluent-cart-search-bar-lists-wrapper role="list">
            <li data-fluent-cart-search-bar-lists-list-item role="listitem">
                <a href="...">Product Title</a>
            </li>
        </ul>
    </div>
</div>
```

---

## Content Controls

| Control ID           | Type     | Default | Description                                |
|----------------------|----------|---------|--------------------------------------------|
| `show_category_filter` | SWITCHER | `yes`   | Shows the category `<select>` dropdown     |
| `url_mode`           | SELECT   | `''`    | `''` = same tab, `'new-tab'` = new tab     |
| `link_with_shop_app` | SWITCHER | `''`    | Links results to a Shop App widget on page |

---

## Style Sections

### 1. Search Bar Wrapper (`wrapper_style_section`)

Targets `.fluent-cart-search-bar-app-wrapper`

| Control ID              | Type       | CSS Property                  |
|-------------------------|------------|-------------------------------|
| `wrapper_background`    | Background | background                    |
| `wrapper_border`        | Border     | border                        |
| `wrapper_border_radius` | DIMENSIONS | border-radius                 |
| `wrapper_box_shadow`    | Box Shadow | box-shadow                    |
| `wrapper_padding`       | DIMENSIONS | padding (responsive)          |

---

### 2. Search Input (`input_style_section`)

#### Normal Tab
Targets `.fluent-cart-search-bar-app-input-wrap` (container) and `.fluent-cart-search-bar-app-input` (text input)

| Control ID               | Type       | Selector                                     | CSS Property      |
|--------------------------|------------|----------------------------------------------|-------------------|
| `input_background`       | Background | `.fluent-cart-search-bar-app-input-wrap`     | background        |
| `input_text_color`       | COLOR      | `.fluent-cart-search-bar-app-input`          | color             |
| `input_placeholder_color`| COLOR      | `.fluent-cart-search-bar-app-input::placeholder` | color         |
| `input_icon_color`       | COLOR      | `.fluent-cart-search-bar-app-input-search svg` | stroke, color   |
| `input_typography`       | Typography | `.fluent-cart-search-bar-app-input`          | font-*            |
| `input_border`           | Border     | `.fluent-cart-search-bar-app-input-wrap`     | border            |
| `input_border_radius`    | DIMENSIONS | `.fluent-cart-search-bar-app-input-wrap`     | border-radius     |
| `input_padding`          | DIMENSIONS | `.fluent-cart-search-bar-app-input`          | padding (responsive)|

#### Focus Tab
Targets `:focus-within` on the wrap container, `:focus` on the input

| Control ID               | Type       | Selector                                               |
|--------------------------|------------|--------------------------------------------------------|
| `input_focus_background` | Background | `.fluent-cart-search-bar-app-input-wrap:focus-within`  |
| `input_focus_text_color` | COLOR      | `.fluent-cart-search-bar-app-input:focus`              |
| `input_focus_border`     | Border     | `.fluent-cart-search-bar-app-input-wrap:focus-within`  |

---

### 3. Category Dropdown (`category_style_section`)

Only visible in Elementor panel when `show_category_filter = yes`.
Targets `.fluent-cart-search-bar-app-wrapper-select-container select`

| Control ID               | Type       | CSS Property           |
|--------------------------|------------|------------------------|
| `category_background`    | Background | background             |
| `category_text_color`    | COLOR      | color                  |
| `category_typography`    | Typography | font-*                 |
| `category_border`        | Border     | border                 |
| `category_border_radius` | DIMENSIONS | border-radius          |
| `category_padding`       | DIMENSIONS | padding (responsive)   |

---

### 4. Results Dropdown (`results_style_section`)

Targets `.fluent-cart-search-bar-app-wrapper-result-wrap` and its children.
Note: The results div is `display:none` by default and is shown by the search JS.

| Control ID                | Type       | Selector                                              | CSS Property  |
|---------------------------|------------|-------------------------------------------------------|---------------|
| `results_background`      | Background | `.fluent-cart-search-bar-app-wrapper-result-wrap`    | background    |
| `results_border`          | Border     | `.fluent-cart-search-bar-app-wrapper-result-wrap`    | border        |
| `results_border_radius`   | DIMENSIONS | `.fluent-cart-search-bar-app-wrapper-result-wrap`    | border-radius |
| `results_box_shadow`      | Box Shadow | `.fluent-cart-search-bar-app-wrapper-result-wrap`    | box-shadow    |
| `results_item_color`      | COLOR      | `.fluent-cart-search-bar-app-list-wrapper li a`      | color         |
| `results_item_hover_color`| COLOR      | `li:hover a` / `li:hover`                            | color / bg    |
| `results_item_typography` | Typography | `.fluent-cart-search-bar-app-list-wrapper li a`      | font-*        |

---

## Asset Registration

Assets are registered once with a static `$registered` guard in `registerSearchBarAssets()`:

```php
\FluentCart\App\Vite::enqueueStyle(
    'fluentcart-search-bar-app',
    'public/search-bar-app/style/style.scss'
);

\FluentCart\App\Vite::enqueueScript(
    'fluentcart-search-bar-app',
    'public/search-bar-app/SearchBarApp.js',
    ['jquery']
)->with([
    'fluentcart_search_bar_vars' => [
        'rest' => Helper::getRestInfo(),
    ],
]);
```

These are the same assets enqueued by `SearchBarShortCode` via `CanEnqueue` trait.
Handle names match so WordPress de-duplicates them automatically if both are loaded.

`get_style_depends()` and `get_script_depends()` both return `['fluentcart-search-bar-app']`
so the Elementor editor preview iframe receives the assets.

---

## Renderer Config Keys

| Key                  | Type    | Description                                           |
|----------------------|---------|-------------------------------------------------------|
| `url_mode`           | string  | `''` or `'new-tab'` — result link target              |
| `category_mode`      | bool    | `true` renders the category `<select>` dropdown       |
| `termData`           | array   | `[['termId' => int, 'termName' => string], ...]`      |
| `link_with_shop_app` | bool    | Passes `data-link-with-shop-app` attribute to wrapper |

---

## Security Checklist

- `show_category_filter` — validated via `in_array()` whitelist `['yes', '']`
- `url_mode` — validated via `in_array()` whitelist `['', 'new-tab']`, then `sanitize_text_field()`
- `link_with_shop_app` — validated via `in_array()` whitelist `['yes', '']`
- No numeric settings — no `absint()`/min/max needed
- All renderer output is escaped within `SearchBarRenderer` itself (uses `esc_attr`, `esc_html`, `esc_url`)
- Renderer output is not double-escaped by the widget
