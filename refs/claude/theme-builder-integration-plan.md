# Elementor Pro Theme Builder Integration for FluentCart Single Product

## Context

FluentCart core provides a block template (`ProductPageTemplate`) for single product pages in block themes. Users who build with Elementor Pro have no equivalent — they can't create custom single product templates via Theme Builder. This plan adds that integration, mirroring how WooCommerce integrates with Elementor Pro's Theme Builder.

**End result:** Users can go to **Elementor > Theme Builder > Single**, create a "FluentCart Product" template, design it with product-specific widgets (gallery, price, stock, buy section, etc.), set conditions (all products, by category, specific product), and have it auto-apply on the frontend.

---

## Files to Create

### 1. Document Types (2 files)

**`app/Modules/Integrations/Elementor/Documents/FluentCartProduct.php`**
- Extends `ElementorPro\Modules\ThemeBuilder\Documents\Single_Base`
- Properties: `location => 'single'`, `condition_type => 'fluentcart_product'`
- Type: `'fluentcart-product'`
- Sets `preview_type` to `single/fluent-products` and `preview_id` to first product
- Adds editor panel categories: `fluentcart-elements-single` (Product) and `fluent-cart` (FluentCart)
- `before_get_content()`: sets up product data context via `ProductDataSetup::getProductModel()`
- `enqueue_scripts()`: loads FluentCart frontend CSS/JS in preview mode
- `filter_body_classes()`: adds `fluentcart` body class
- Reference: `/Volumes/Projects/wp/wp-content/plugins/elementor-pro/modules/woocommerce/documents/product.php`

**`app/Modules/Integrations/Elementor/Documents/FluentCartProductPost.php`**
- Extends `Elementor\Core\DocumentTypes\Post`
- Properties: `cpt => ['fluent-products']`
- Adds `fluentcart-elements-single` category to editor panel when editing a specific product with Elementor
- Reference: `/Volumes/Projects/wp/wp-content/plugins/elementor-pro/modules/woocommerce/documents/product-post.php`

### 2. Condition (1 file)

**`app/Modules/Integrations/Elementor/Conditions/FluentCartCondition.php`**
- Extends `ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base`
- Type/name: `'fluentcart_product'`
- Label: "FluentCart"
- `register_sub_conditions()`: registers `Post` sub-condition with `post_type => 'fluent-products'` (this auto-creates "In Category", "In Brand", "By Author" sub-conditions from the CPT's taxonomies)
- `check()`: returns `is_singular('fluent-products')`
- Reference: `/Volumes/Projects/wp/wp-content/plugins/elementor-pro/modules/woocommerce/conditions/woocommerce.php`

### 3. Theme Builder Widgets (8 files)

All in **`app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/`** directory.

**Dual categories:** Each widget belongs to both `fluentcart-elements-single` (Product) and `fluent-cart` (FluentCart) categories, so they appear in Theme Builder templates AND on regular pages.

**Dual-mode product resolution (matches FluentCart core block pattern):**
Each widget follows the same context-detection pattern as FluentCart core's block editors (`ProductTitleBlockEditor`, `BuySectionBlockEditor`, etc.):

```php
// In Theme Builder template or product post context:
//   → auto-detect current product via get_the_ID()
// On a regular page:
//   → user selects a product via ProductSelectControl dropdown

$productId = $settings['product_id'] ?: get_the_ID();
$product = ProductDataSetup::getProductModel($productId);
```

Each widget renders via `ProductRenderer` methods from `fluent-cart/app/Services/Renderer/ProductRenderer.php`.

| File | Widget Name | Slug | Renders Via |
|------|-------------|------|-------------|
| `ProductTitleWidget.php` | Product Title | `fluentcart_product_title` | `ProductRenderer::renderTitle()` |
| `ProductGalleryWidget.php` | Product Gallery | `fluentcart_product_gallery` | `ProductRenderer::renderGallery($args)` |
| `ProductPriceWidget.php` | Product Price | `fluentcart_product_price` | `ProductRenderer::renderPrices()` |
| `ProductStockWidget.php` | Product Stock | `fluentcart_product_stock` | `ProductRenderer::renderStockAvailability()` |
| `ProductExcerptWidget.php` | Product Excerpt | `fluentcart_product_excerpt` | `ProductRenderer::renderExcerpt()` |
| `ProductBuySectionWidget.php` | Product Buy Section | `fluentcart_product_buy_section` | `ProductRenderer::renderBuySection()` |
| `ProductContentWidget.php` | Product Content | `fluentcart_product_content` | `the_content()` (post content) |
| `RelatedProductsWidget.php` | Related Products | `fluentcart_related_products` | `do_shortcode('[fluent_cart_related_products]')` |

**Controls per widget:**
- **All widgets**: Product selector (ProductSelectControl) — hidden in Theme Builder context, shown on regular pages
- **Title**: HTML tag (h1-h6/div/p/span), alignment, typography group control, text color
- **Gallery**: Thumbnail position (bottom/left/right), thumbnail mode
- **Price**: Typography, color, alignment
- **Stock**: Typography, in-stock color, out-of-stock color
- **Excerpt**: Typography, color, alignment
- **Buy Section**: Buy Now button text, Add to Cart button text, style controls
- **Content**: Typography, alignment (minimal)
- **Related Products**: Number of products (if shortcode supports it)

---

## Files to Modify

### 4. ElementorIntegration.php

**File:** `app/Modules/Integrations/Elementor/ElementorIntegration.php`

Add to `register()` method:
```php
// Only if Elementor Pro Theme Builder is available
if (class_exists('\ElementorPro\Modules\ThemeBuilder\Module')) {
    add_action('elementor/documents/register', [$this, 'registerDocuments']);
    add_action('elementor/theme/register_conditions', [$this, 'registerConditions']);
    add_filter('elementor/theme/need_override_location', [$this, 'themeTemplateInclude'], 10, 2);
    add_filter('elementor_pro/utils/get_public_post_types', [$this, 'removeFluentProductsFromGenericConditions']);
}
```

New methods:
- `registerDocuments($documents_manager)` — registers `FluentCartProductPost` and `FluentCartProduct`
- `registerConditions($conditions_manager)` — registers `FluentCartCondition` under `general`
- `themeTemplateInclude($need_override, $location)` — returns `true` when `is_singular('fluent-products') && $location === 'single'`
- `removeFluentProductsFromGenericConditions($post_types)` — removes `fluent-products` from generic Singular conditions to avoid duplication
- `registerThemeBuilderWidgets($widgets_manager)` — registers all 8 theme builder widgets (called from `registerWidgets`)

### 5. Template Loading Coordination

**File:** `app/Modules/Integrations/Elementor/ElementorIntegration.php` (inside `register()`)

Add filter on `fluent_cart/disable_auto_single_product_page` to return `true` when an Elementor Pro Theme Builder template is active for the current product page. This prevents FluentCart core's `TemplateActions::filterSingleProductContent()` from injecting its own product header into `the_content`, which would conflict with the Elementor template.

```php
add_filter('fluent_cart/disable_auto_single_product_page', function($disable) {
    if (!class_exists('\ElementorPro\Modules\ThemeBuilder\Module')) {
        return $disable;
    }
    if (!is_singular('fluent-products')) {
        return $disable;
    }
    $module = \ElementorPro\Modules\ThemeBuilder\Module::instance();
    $documents = $module->get_conditions_manager()->get_documents_for_location('single');
    return !empty($documents) ? true : $disable;
});
```

---

## Implementation Order

1. **Create document classes** — `FluentCartProduct.php`, `FluentCartProductPost.php`
2. **Create condition class** — `FluentCartCondition.php`
3. **Modify ElementorIntegration.php** — add registration hooks for documents, conditions, template override
4. **Add template loading coordination** — `fluent_cart/disable_auto_single_product_page` filter
5. **Create theme builder widgets** — all 8 widgets in `Widgets/ThemeBuilder/`
6. **Register theme builder widgets** — add to `registerWidgets()` in ElementorIntegration

---

## Key Reference Files

| File | Purpose |
|------|---------|
| `elementor-pro/modules/woocommerce/documents/product.php` | WooCommerce's Single Product document — our primary reference |
| `elementor-pro/modules/woocommerce/documents/product-post.php` | WooCommerce's Product Post document |
| `elementor-pro/modules/woocommerce/conditions/woocommerce.php` | WooCommerce's condition class |
| `elementor-pro/modules/theme-builder/documents/single-base.php` | Base class for single post type documents |
| `elementor-pro/modules/theme-builder/conditions/condition-base.php` | Base class for conditions |
| `fluent-cart/app/Services/Renderer/ProductRenderer.php` | All render methods for product elements |
| `fluent-cart/app/Modules/Data/ProductDataSetup.php` | `getProductModel()` for loading product data |
| `fluent-cart/app/Modules/Templating/AssetLoader.php` | `loadSingleProductAssets()` for frontend CSS/JS |
| `fluent-cart/app/Modules/Templating/TemplateActions.php` | `fluent_cart/disable_auto_single_product_page` filter usage |

---

## Verification

1. Activate Elementor Pro + FluentCart + FluentCart Elementor Blocks
2. Go to **Elementor > Theme Builder** — confirm "FluentCart Product" template type appears under "Single"
3. Create a new template — confirm all 8 product widgets appear in the "Product" category in the editor panel
4. Add widgets (gallery, title, price, stock, excerpt, buy section) — confirm they render with preview product data
5. Set condition to "FluentCart > Product > All Products" — save and close
6. Visit a single product page on the frontend — confirm the Elementor template renders instead of default
7. Confirm FluentCart core's default rendering is properly bypassed (no duplicate product header)
