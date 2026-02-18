# Theme Builder Widgets — Index

## Overview

The Theme Builder widgets (`app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/`) are designed for Elementor Pro Theme Builder templates on single FluentCart product pages. All widgets use `ProductWidgetTrait` for product source resolution (Current Product / Custom).

**Category:** `fluentcart-elements-single` (+ `fluent-cart`)

**Requires:** Elementor Pro with Theme Builder module

### Individual Widget Docs

- [`refs/claude/ProductTitle/STYLE-CONTROLS.md`](../ProductTitle/STYLE-CONTROLS.md)
- [`refs/claude/ProductGallery/STYLE-CONTROLS.md`](../ProductGallery/STYLE-CONTROLS.md)
- [`refs/claude/ProductPrice/STYLE-CONTROLS.md`](../ProductPrice/STYLE-CONTROLS.md)
- [`refs/claude/ProductStock/STYLE-CONTROLS.md`](../ProductStock/STYLE-CONTROLS.md)
- [`refs/claude/ProductExcerpt/STYLE-CONTROLS.md`](../ProductExcerpt/STYLE-CONTROLS.md)
- [`refs/claude/ProductBuySection/STYLE-CONTROLS.md`](../ProductBuySection/STYLE-CONTROLS.md)
- [`refs/claude/ProductContent/STYLE-CONTROLS.md`](../ProductContent/STYLE-CONTROLS.md)
- [`refs/claude/ProductInfo/STYLE-CONTROLS.md`](../ProductInfo/STYLE-CONTROLS.md)
- [`refs/claude/RelatedProducts/STYLE-CONTROLS.md`](../RelatedProducts/STYLE-CONTROLS.md)

---

## Widget Registry

| Widget Class | Slug | Icon | Has Style Controls | Has Static Methods |
|---|---|---|---|---|
| ProductTitleWidget | `fluentcart_product_title` | `eicon-product-title` | Yes | `registerTitleStyleControls()` |
| ProductGalleryWidget | `fluentcart_product_gallery` | `eicon-product-images` | No | `registerGalleryContentControls()` |
| ProductPriceWidget | `fluentcart_product_price` | `eicon-product-price` | Yes | `registerPriceStyleControls()` |
| ProductStockWidget | `fluentcart_product_stock` | `eicon-product-stock` | Yes | `registerStockStyleControls()` |
| ProductExcerptWidget | `fluentcart_product_excerpt` | `eicon-product-description` | Yes | `registerExcerptStyleControls()` |
| ProductBuySectionWidget | `fluentcart_product_buy_section` | `eicon-product-add-to-cart` | Yes | `registerBuySectionStyleControls()` |
| ProductContentWidget | `fluentcart_product_content` | `eicon-product-description` | Yes | No |
| ProductInfoWidget | `fluentcart_product_info` | `eicon-single-product` | Yes (composite) | No |
| RelatedProductsWidget | `fluentcart_related_products` | `eicon-products-related` | No | No |

---

## Shared Trait: ProductWidgetTrait

**File:** `Traits/ProductWidgetTrait.php`

All Theme Builder widgets use this trait for:

| Method | Description |
|---|---|
| `registerProductSourceControls()` | Adds Source (default/custom) + ProductSelectControl |
| `getProduct($settings)` | Resolves product from context or custom selection |
| `renderPlaceholder($message)` | Shows placeholder in editor mode |

---

## ProductTitleWidget

**Slug:** `fluentcart_product_title`
**Static Method:** `registerTitleStyleControls($widget, $selector)`

### Content Controls

| Control ID | Type | Description |
|---|---|---|
| `source` | Select | Current Product / Custom |
| `product_id` | ProductSelectControl | Custom product (conditional) |
| `html_tag` | Select | h1–h6, div, span, p |
| `align` | Responsive Choose | left / center / right |

### Style Section

**Section ID:** `style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `title_color` | Color | `$selector` (default: `{{WRAPPER}} .fluentcart-product-title`) |
| `title_typography` | Typography | `$selector` |

---

## ProductGalleryWidget

**Slug:** `fluentcart_product_gallery`
**Static Method:** `registerGalleryContentControls($widget)` (content only, no style controls)

### Content Controls

| Control ID | Type | Description |
|---|---|---|
| `source` | Select | Current Product / Custom |
| `product_id` | ProductSelectControl | Custom product (conditional) |
| `thumb_position` | Select | bottom / left / right / top |
| `thumbnail_mode` | Select | all / horizontal / vertical |

### Style Section

No style controls.

---

## ProductPriceWidget

**Slug:** `fluentcart_product_price`
**Static Method:** `registerPriceStyleControls($widget, $selector)`

### Content Controls

| Control ID | Type | Description |
|---|---|---|
| `source` | Select | Current Product / Custom |
| `product_id` | ProductSelectControl | Custom product (conditional) |
| `align` | Responsive Choose | left / center / right |

### Style Section

**Section ID:** `style_section`

Price selectors are composite: `$selector, $selector .fct-price-range, $selector .fct-product-prices, $selector .fct-max-price, $selector .fct-min-price`

| Control ID | Type | CSS Selector |
|---|---|---|
| `price_color` | Color | `$priceSelectors` (default: `{{WRAPPER}} .fluentcart-product-price` + sub-elements) |
| `price_typography` | Typography | `$priceSelectors` |

---

## ProductStockWidget

**Slug:** `fluentcart_product_stock`
**Static Method:** `registerStockStyleControls($widget, $selector)`

### Content Controls

| Control ID | Type | Description |
|---|---|---|
| `source` | Select | Current Product / Custom |
| `product_id` | ProductSelectControl | Custom product (conditional) |

### Style Section

**Section ID:** `style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `stock_typography` | Typography | `$selector .fct-stock-status` (default: `{{WRAPPER}} .fct-product-stock .fct-stock-status`) |
| `in_stock_color` | Color | `$selector:not(.out-of-stock) .fct-stock-status` |
| `out_of_stock_color` | Color | `$selector.out-of-stock .fct-stock-status` |

---

## ProductExcerptWidget

**Slug:** `fluentcart_product_excerpt`
**Static Method:** `registerExcerptStyleControls($widget, $selector)`

### Content Controls

| Control ID | Type | Description |
|---|---|---|
| `source` | Select | Current Product / Custom |
| `product_id` | ProductSelectControl | Custom product (conditional) |
| `align` | Responsive Choose | left / center / right / justify |

### Style Section

**Section ID:** `style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `excerpt_color` | Color | `$selector` (default: `{{WRAPPER}} .fluentcart-product-excerpt`) |
| `excerpt_typography` | Typography | `$selector` |

---

## ProductBuySectionWidget

**Slug:** `fluentcart_product_buy_section`
**Static Method:** `registerBuySectionStyleControls($widget, $selector)`
**Tabs:** Normal / Hover

### Content Controls

| Control ID | Type | Description |
|---|---|---|
| `source` | Select | Current Product / Custom |
| `product_id` | ProductSelectControl | Custom product (conditional) |

### Style Section

**Section ID:** `button_style_section`

Button selectors target multiple button classes within `$selector`:
```
.wp-block-button__link, .fct-buy-now-btn, .fct-add-to-cart-btn,
.fluent-cart-direct-checkout-button, .fluent-cart-add-to-cart-button
```

| Control ID | Type | CSS Selector |
|---|---|---|
| `button_typography` | Typography | `$btnSelector` (all button classes) |
| `button_text_color` | Color | All button classes (normal tab) |
| `button_background` | Background | `$btnSelector` (normal tab) |
| `button_border` | Border | `$btnSelector` (normal tab) |
| `button_hover_text_color` | Color | All button classes `:hover` (hover tab) |
| `button_hover_background` | Background | `$btnHoverSelector` (hover tab) |
| `button_hover_border` | Border | `$btnHoverSelector` (hover tab) |
| `button_border_radius` | Dimensions | All button classes (outside tabs) |
| `button_padding` | Responsive Dimensions | All button classes (outside tabs) |

---

## ProductContentWidget

**Slug:** `fluentcart_product_content`
**No static methods**

### Content Controls

| Control ID | Type | Description |
|---|---|---|
| `source` | Select | Current Product / Custom |
| `product_id` | ProductSelectControl | Custom product (conditional) |
| `align` | Responsive Choose | left / center / right / justify |

### Style Section

**Section ID:** `style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `content_color` | Color | `{{WRAPPER}} .fluentcart-product-content` |
| `content_typography` | Typography | `{{WRAPPER}} .fluentcart-product-content` |

---

## ProductInfoWidget (Composite)

**Slug:** `fluentcart_product_info`
**No static methods** — consumes other widgets' static methods

A composite widget that combines gallery, title, stock, excerpt, price, and buy section into a single full product info layout.

### Content Controls

| Section | Control ID | Type | Description |
|---|---|---|---|
| Content | `source` | Select | Current Product / Custom |
| Content | `product_id` | ProductSelectControl | Custom product (conditional) |
| Sections | `show_gallery` | Switcher | Default yes |
| Sections | `show_title` | Switcher | Default yes |
| Sections | `show_stock` | Switcher | Default yes |
| Sections | `show_excerpt` | Switcher | Default yes |
| Sections | `show_price` | Switcher | Default yes |
| Sections | `show_buy_section` | Switcher | Default yes |
| Gallery | `thumb_position` | Select | Via `ProductGalleryWidget::registerGalleryContentControls()` |
| Gallery | `thumbnail_mode` | Select | Via `ProductGalleryWidget::registerGalleryContentControls()` |

### Style Sections (all conditional on respective show_* toggle)

| Section ID | Label | Static Method | Selector |
|---|---|---|---|
| `title_style_section` | Title | `ProductTitleWidget::registerTitleStyleControls()` | `{{WRAPPER}} .fct-product-title h1` |
| `price_style_section` | Price | `ProductPriceWidget::registerPriceStyleControls()` | `{{WRAPPER}} .fct-product-summary` |
| `stock_style_section` | Stock | `ProductStockWidget::registerStockStyleControls()` | `{{WRAPPER}} .fct-product-stock` |
| `excerpt_style_section` | Excerpt | `ProductExcerptWidget::registerExcerptStyleControls()` | `{{WRAPPER}} .fct-product-excerpt` |
| `buy_section_style_section` | Buy Section | `ProductBuySectionWidget::registerBuySectionStyleControls()` | `{{WRAPPER}} .fct_buy_section` |

---

## RelatedProductsWidget

**Slug:** `fluentcart_related_products`
**No static methods, no style controls**

### Content Controls

| Control ID | Type | Description |
|---|---|---|
| `source` | Select | Current Product / Custom |
| `product_id` | ProductSelectControl | Custom product (conditional) |

Renders via `[fluent_cart_related_products]` shortcode.

---

## Key CSS Selector Patterns

### Single Product Page Classes (from ProductRenderer)

| Element | CSS Class |
|---------|-----------|
| Single product page wrapper | `.fct-single-product-page` |
| Product page row | `.fct-single-product-page-row` |
| Product summary | `.fct-product-summary` |
| Product title | `.fct-product-title` |
| Product stock | `.fct-product-stock` |
| Stock status text | `.fct-stock-status` |
| Out of stock modifier | `.out-of-stock` |
| Product excerpt | `.fct-product-excerpt` |
| Price range | `.fct-price-range` |
| Product prices | `.fct-product-prices` |
| Min price | `.fct-min-price` |
| Max price | `.fct-max-price` |
| Buy section | `.fct_buy_section` |
| Buy now button | `.fct-buy-now-btn` |
| Add to cart button | `.fct-add-to-cart-btn` |
| Direct checkout button | `.fluent-cart-direct-checkout-button` |
| Add to cart button (alt) | `.fluent-cart-add-to-cart-button` |
| WP block button | `.wp-block-button__link` |

### Elementor Widget Wrapper Classes

| Widget | CSS Class |
|--------|-----------|
| Product Title | `.fluentcart-product-title` |
| Product Gallery | `.fluentcart-product-gallery` |
| Product Price | `.fluentcart-product-price` |
| Product Stock | `.fluentcart-product-stock` (from core) |
| Product Excerpt | `.fluentcart-product-excerpt` |
| Product Buy Section | `.fluentcart-product-buy-section` |
| Product Content | `.fluentcart-product-content` |
| Product Info | `.fluentcart-product-info` |
| Related Products | `.fluentcart-related-products` |

---

## Revision History

- **2026-02-18**: Initial documentation for all 9 Theme Builder widgets.
