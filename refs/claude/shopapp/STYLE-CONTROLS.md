# ShopAppWidget (Products) Style Controls Reference

## Overview

The ShopAppWidget (`app/Modules/Integrations/Elementor/Widgets/ShopAppWidget.php`) provides **9 style sections** with **45+ individual controls** for comprehensive product shop styling.

**Widget Slug:** `fluent_cart_shop_app`

---

## Style Section Registration Order

```php
private function registerStyleControls()
{
    $this->registerProductCardStyleControls();    // 1. Product Card
    $this->registerGridStyleControls();           // 2. Grid Layout
    $this->registerProductImageStyleControls();   // 3. Product Image
    $this->registerProductTitleStyleControls();   // 4. Product Title
    $this->registerProductExcerptStyleControls(); // 5. Product Excerpt
    $this->registerProductPriceStyleControls();   // 6. Product Price
    $this->registerProductButtonStyleControls();  // 7. Product Button
    $this->registerFilterStyleControls();         // 8. Filter
    $this->registerPaginationStyleControls();     // 9. Pagination
}
```

---

## 1. Product Card

**Section ID:** `card_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `card_background` | Background | `{{WRAPPER}} .fct-product-card` |
| `card_border` | Border | `{{WRAPPER}} .fct-product-card` |
| `card_border_radius` | Dimensions | `{{WRAPPER}} .fct-product-card` |
| `card_box_shadow` | Box Shadow | `{{WRAPPER}} .fct-product-card` |
| `card_padding` | Responsive Dimensions | `{{WRAPPER}} .fct-product-card` |

## 2. Grid Layout

**Section ID:** `grid_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `grid_column_gap` | Responsive Slider | `{{WRAPPER}} .fct-products-container` |
| `grid_row_gap` | Responsive Slider | `{{WRAPPER}} .fct-products-container` |

## 3. Product Image

**Section ID:** `image_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `image_height` | Responsive Slider | `{{WRAPPER}} .fct-product-card-image` |
| `image_object_fit` | Select | `{{WRAPPER}} .fct-product-card-image` |
| `image_border` | Border | `{{WRAPPER}} .fct-product-card-image` |
| `image_border_radius` | Dimensions | `{{WRAPPER}} .fct-product-card-image`, `{{WRAPPER}} .fct-product-card-image-wrap` |
| `image_box_shadow` | Box Shadow | `{{WRAPPER}} .fct-product-card-image` |
| `image_padding` | Responsive Dimensions | `{{WRAPPER}} .fct-product-card-image-wrap` |

## 4. Product Title

**Section ID:** `title_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `title_typography` | Typography | `{{WRAPPER}} .fct-product-card-title` |
| `title_color` | Color | `{{WRAPPER}} .fct-product-card-title`, `{{WRAPPER}} .fct-product-card-title a` |
| `title_hover_color` | Color | `{{WRAPPER}} .fct-product-card-title:hover`, `{{WRAPPER}} .fct-product-card-title a:hover` |
| `title_spacing` | Responsive Slider | `{{WRAPPER}} .fct-product-card-title` (margin-bottom) |

## 5. Product Excerpt

**Section ID:** `excerpt_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `excerpt_typography` | Typography | `{{WRAPPER}} .fct-product-card-excerpt` |
| `excerpt_color` | Color | `{{WRAPPER}} .fct-product-card-excerpt` |
| `excerpt_spacing` | Responsive Slider | `{{WRAPPER}} .fct-product-card-excerpt` (margin-bottom) |

## 6. Product Price

**Section ID:** `price_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `price_typography` | Typography | `{{WRAPPER}} .fct-product-card-prices` |
| `price_color` | Color | `{{WRAPPER}} .fct-product-card-prices` |
| `compare_price_color` | Color | `{{WRAPPER}} .fct-product-card-prices .fct-compare-price` |
| `price_spacing` | Responsive Slider | `{{WRAPPER}} .fct-product-card-prices` (margin-bottom) |

## 7. Product Button

**Section ID:** `button_style_section`
**Tabs:** Normal / Hover

**Button Selectors:**
```php
$btnSelector = '{{WRAPPER}} .fct-product-card .fct-product-view-button, {{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button';
$btnHoverSelector = '{{WRAPPER}} .fct-product-card .fct-product-view-button:hover, {{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button:hover';
```

| Control ID | Type | CSS Selector |
|---|---|---|
| `product_button_typography` | Typography | `$btnSelector` |
| `product_button_width` | Switcher | `$btnSelector` (width: 100%) |
| `product_button_text_color` | Color | `$btnSelector` (normal tab) |
| `product_button_background` | Background | `$btnSelector` (normal tab) |
| `product_button_border` | Border | `$btnSelector` (normal tab) |
| `product_button_border_radius` | Dimensions | `$btnSelector` (normal tab) |
| `product_button_padding` | Responsive Dimensions | `$btnSelector` (normal tab) |
| `product_button_hover_text_color` | Color | `$btnHoverSelector` (hover tab) |
| `product_button_hover_background` | Background | `$btnHoverSelector` (hover tab) |
| `product_button_hover_border` | Border | `$btnHoverSelector` (hover tab) |
| `product_button_hover_box_shadow` | Box Shadow | `$btnHoverSelector` (hover tab) |

## 8. Filter

**Section ID:** `filter_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `filter_heading_typography` | Typography | `{{WRAPPER}} .fct-shop-filter-form .item-heading` |
| `filter_heading_color` | Color | `{{WRAPPER}} .fct-shop-filter-form .item-heading` |
| `filter_checkbox_color` | Color | `{{WRAPPER}} .fct-shop-checkbox` |
| `filter_search_bg` | Color | `{{WRAPPER}} .fct-shop-product-search .fct-shop-input` |
| `filter_search_border_color` | Color | `{{WRAPPER}} .fct-shop-product-search .fct-shop-input` |
| `filter_apply_btn_heading` | Heading | N/A (separator) |
| `filter_apply_btn_color` | Color | `{{WRAPPER}} .fct-shop-apply-filter-button` |
| `filter_apply_btn_bg` | Color | `{{WRAPPER}} .fct-shop-apply-filter-button` |

## 9. Pagination

**Section ID:** `pagination_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `pagination_typography` | Typography | `{{WRAPPER}} .fct-shop-paginator` |
| `pagination_color` | Color | `{{WRAPPER}} .fct-shop-paginator`, `{{WRAPPER}} .fct-shop-paginator-pager button` |
| `pagination_active_color` | Color | `{{WRAPPER}} .fct-shop-paginator-pager .active button` |
| `pagination_active_bg` | Color | `{{WRAPPER}} .fct-shop-paginator-pager .active button` |
| `pagination_spacing` | Responsive Slider | `{{WRAPPER}} .fct-shop-paginator` (margin-top) |

---

## Key CSS Selector Patterns

All shop selectors use **hyphens** (not underscores like checkout).

### Core HTML Classes (from ProductCardRender / ShopAppRenderer)

| Element | CSS Class |
|---------|-----------|
| Products wrapper | `.fct-products-wrapper` |
| Products container (grid) | `.fct-products-container` |
| Product card | `.fct-product-card` |
| Product card image wrap | `.fct-product-card-image-wrap` |
| Product card image | `.fct-product-card-image` |
| Product card title | `.fct-product-card-title` |
| Product card excerpt | `.fct-product-card-excerpt` |
| Product card prices | `.fct-product-card-prices` |
| Compare price (strikethrough) | `.fct-compare-price` |
| View Options / Buy Now button | `.fct-product-view-button` |
| Add to Cart button | `.fluent-cart-add-to-cart-button` |
| Shop toolbar | `.fct-shop-toolbar` |
| View switcher | `.fct-shop-view-switcher` |
| Filter form | `.fct-shop-filter-form` |
| Filter item | `.fct-shop-filter-item` |
| Filter heading | `.item-heading` |
| Filter checkbox | `.fct-shop-checkbox` |
| Product search | `.fct-shop-product-search` |
| Search input | `.fct-shop-input` |
| Apply filter button | `.fct-shop-apply-filter-button` |
| Paginator | `.fct-shop-paginator` |
| Paginator pager | `.fct-shop-paginator-pager` |

### Important: Button Class Names

The product card buttons do **NOT** use `.fct-button`. That class does not exist in the rendered HTML.

- **View Options / Buy Now**: `.fct-product-view-button` (rendered by `ProductCardRender::renderBuyButton()`)
- **Add to Cart**: `.fluent-cart-add-to-cart-button` (rendered by `ProductCardRender::renderBuyButton()`)

The same button selectors apply to `ProductCarouselWidget.php`.

---

## Revision History

- **2026-01-30**: Initial implementation with 4 style sections (Product Card, Product Image, Product Title, Product Price, Product Button)
- **2026-02-18**: Expanded to 9 style sections. Fixed critical button selector bug (`.fct-button` -> `.fct-product-view-button, .fluent-cart-add-to-cart-button`). Added Grid Layout, Product Excerpt, Filter, Pagination sections. Enhanced existing sections with title_spacing, compare_price_color, price_spacing, product_button_width controls. Refactored from monolithic `registerStyleControls()` to 9 separate methods. Same button selector fix applied to `ProductCarouselWidget.php`.
