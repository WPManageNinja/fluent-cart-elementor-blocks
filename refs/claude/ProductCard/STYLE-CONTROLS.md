# ProductCardWidget Style Controls Reference

## Overview

The ProductCardWidget (`app/Modules/Integrations/Elementor/Widgets/ProductCardWidget.php`) is a standalone single-product card widget that also serves as the **shared style controls provider** for ShopAppWidget and ProductCarouselWidget via 6 public static methods.

**Widget Slug:** `fluent_cart_product_card`

---

## Architecture: Shared Static Methods

ProductCardWidget exposes 6 `public static` methods that register style controls on any widget instance. The calling widget manages `start_controls_section()` / `end_controls_section()` — the static methods only register controls within a section.

```php
ProductCardWidget::registerCardStyleControls($widget, $selector)
ProductCardWidget::registerCardImageStyleControls($widget, $selector)
ProductCardWidget::registerCardTitleStyleControls($widget, $selector)
ProductCardWidget::registerCardExcerptStyleControls($widget, $selector)
ProductCardWidget::registerCardPriceStyleControls($widget, $selector)
ProductCardWidget::registerCardButtonStyleControls($widget, $btnSelector, $btnHoverSelector)
```

**Consumers:**
- `ProductCardWidget` (itself)
- `ShopAppWidget` (replaces 6 private methods)
- `ProductCarouselWidget` (replaces 5 private methods)

---

## Content Controls

| Section | Control ID | Type | Description |
|---|---|---|---|
| Product | `product_id` | ProductSelectControl | Single product selector |
| Product | `price_format` | Select | starts_from / range / lowest |
| Card Layout | `card_elements` | Repeater | Reorderable: image, title, excerpt, price, button |

---

## Style Section Registration Order

```php
private function registerStyleControls()
{
    // 1. Product Card — static::registerCardStyleControls()
    // 2. Product Image — static::registerCardImageStyleControls()
    // 3. Product Title — static::registerCardTitleStyleControls()
    // 4. Product Excerpt — static::registerCardExcerptStyleControls()
    // 5. Product Price — static::registerCardPriceStyleControls()
    // 6. Product Button — static::registerCardButtonStyleControls()
}
```

---

## 1. Product Card

**Section ID:** `card_style_section`
**Static Method:** `registerCardStyleControls($widget, $selector)`

| Control ID | Type | CSS Selector |
|---|---|---|
| `card_background` | Background | `$selector` |
| `card_border` | Border | `$selector` |
| `card_border_radius` | Dimensions | `$selector` |
| `card_box_shadow` | Box Shadow | `$selector` |
| `card_padding` | Responsive Dimensions | `$selector` |

**Default selector:** `{{WRAPPER}} .fct-product-card`

## 2. Product Image

**Section ID:** `image_style_section`
**Static Method:** `registerCardImageStyleControls($widget, $selector)`

Derives wrap selector: `$wrapSelector = str_replace('-image', '-image-wrap', $selector)`

| Control ID | Type | CSS Selector |
|---|---|---|
| `image_height` | Responsive Slider | `$selector` |
| `image_object_fit` | Select | `$selector` |
| `image_border` | Border | `$selector` |
| `image_border_radius` | Dimensions | `$selector`, `$wrapSelector` |
| `image_box_shadow` | Box Shadow | `$selector` |
| `image_padding` | Responsive Dimensions | `$wrapSelector` |

**Default selector:** `{{WRAPPER}} .fct-product-card-image`

## 3. Product Title

**Section ID:** `title_style_section`
**Static Method:** `registerCardTitleStyleControls($widget, $selector)`

| Control ID | Type | CSS Selector |
|---|---|---|
| `title_typography` | Typography | `$selector` |
| `title_color` | Color | `$selector`, `$selector a` |
| `title_hover_color` | Color | `$selector:hover`, `$selector a:hover` |
| `title_spacing` | Responsive Slider | `$selector` (margin-bottom) |

**Default selector:** `{{WRAPPER}} .fct-product-card-title`

## 4. Product Excerpt

**Section ID:** `excerpt_style_section`
**Static Method:** `registerCardExcerptStyleControls($widget, $selector)`

| Control ID | Type | CSS Selector |
|---|---|---|
| `excerpt_typography` | Typography | `$selector` |
| `excerpt_color` | Color | `$selector` |
| `excerpt_spacing` | Responsive Slider | `$selector` (margin-bottom) |

**Default selector:** `{{WRAPPER}} .fct-product-card-excerpt`

## 5. Product Price

**Section ID:** `price_style_section`
**Static Method:** `registerCardPriceStyleControls($widget, $selector)`

| Control ID | Type | CSS Selector |
|---|---|---|
| `price_typography` | Typography | `$selector` |
| `price_color` | Color | `$selector` |
| `compare_price_color` | Color | `$selector .fct-compare-price` |
| `price_spacing` | Responsive Slider | `$selector` (margin-bottom) |

**Default selector:** `{{WRAPPER}} .fct-product-card-prices`

## 6. Product Button

**Section ID:** `button_style_section`
**Static Method:** `registerCardButtonStyleControls($widget, $btnSelector, $btnHoverSelector)`
**Tabs:** Normal / Hover

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

**Default selectors:**
```
$btnSelector = '{{WRAPPER}} .fct-product-card .fct-product-view-button, {{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button'
$btnHoverSelector = '{{WRAPPER}} .fct-product-card .fct-product-view-button:hover, {{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button:hover'
```

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Product card | `.fct-product-card` |
| Product card image wrap | `.fct-product-card-image-wrap` |
| Product card image | `.fct-product-card-image` |
| Product card title | `.fct-product-card-title` |
| Product card excerpt | `.fct-product-card-excerpt` |
| Product card prices | `.fct-product-card-prices` |
| Compare price (strikethrough) | `.fct-compare-price` |
| View Options / Buy Now button | `.fct-product-view-button` |
| Add to Cart button | `.fluent-cart-add-to-cart-button` |

---

## Revision History

- **2026-02-18**: Created as shared style controls provider. 6 public static methods consumed by ShopAppWidget, ProductCarouselWidget, and itself.
