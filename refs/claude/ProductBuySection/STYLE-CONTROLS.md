# ProductBuySectionWidget Style Controls Reference

## Overview

The ProductBuySectionWidget (`app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/ProductBuySectionWidget.php`) renders the variant selector + buy/add-to-cart buttons on single product Theme Builder templates. Exposes `registerBuySectionStyleControls()` reused by ProductInfoWidget.

**Widget Slug:** `fluentcart_product_buy_section`
**Category:** `fluentcart-elements-single`, `fluent-cart`
**Icon:** `eicon-product-add-to-cart`
**Requires:** Elementor Pro Theme Builder

---

## Content Controls

| Section ID | Control ID | Type | Description |
|---|---|---|---|
| `content_section` | `source` | Select | Current Product / Custom (via ProductWidgetTrait) |
| `content_section` | `product_id` | ProductSelectControl | Custom product (conditional on source=custom) |

---

## Style Section

**Section ID:** `button_style_section`
**Static Method:** `registerBuySectionStyleControls($widget, $selector)`
**Tabs:** Normal / Hover

Default selector: `{{WRAPPER}} .fct_buy_section`

The method targets multiple button classes within the buy section:
```php
$btnParts = [
    ' .wp-block-button__link',
    ' .fct-buy-now-btn',
    ' .fct-add-to-cart-btn',
    ' .fluent-cart-direct-checkout-button',
    ' .fluent-cart-add-to-cart-button',
];
```

| Control ID | Type | CSS Selector |
|---|---|---|
| `button_typography` | Typography | All button classes combined |
| `button_text_color` | Color | All button classes (normal tab) |
| `button_background` | Background | All button classes (normal tab) |
| `button_border` | Border | All button classes (normal tab) |
| `button_hover_text_color` | Color | All button classes `:hover` (hover tab) |
| `button_hover_background` | Background | All button classes `:hover` (hover tab) |
| `button_hover_border` | Border | All button classes `:hover` (hover tab) |
| `button_border_radius` | Dimensions | All button classes (outside tabs) |
| `button_padding` | Responsive Dimensions | All button classes (outside tabs) |

---

## Static Method Usage

Called by **ProductInfoWidget** with same selector:
```php
ProductBuySectionWidget::registerBuySectionStyleControls($this, '{{WRAPPER}} .fct_buy_section');
```

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Buy section wrapper (standalone) | `.fluentcart-product-buy-section` |
| Buy section container (from core) | `.fct_buy_section` (note: underscore) |
| WP block button | `.wp-block-button__link` |
| Buy now button | `.fct-buy-now-btn` |
| Add to cart button | `.fct-add-to-cart-btn` |
| Direct checkout button | `.fluent-cart-direct-checkout-button` |
| Add to cart button (alt) | `.fluent-cart-add-to-cart-button` |

**Note:** `.fct_buy_section` uses underscores, matching checkout convention. Button sub-classes use hyphens.

---

## Revision History

- **2026-02-18**: Initial documentation.
