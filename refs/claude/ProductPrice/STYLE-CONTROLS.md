# ProductPriceWidget Style Controls Reference

## Overview

The ProductPriceWidget (`app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/ProductPriceWidget.php`) renders the product price (including price ranges) on single product Theme Builder templates. Exposes `registerPriceStyleControls()` reused by ProductInfoWidget.

**Widget Slug:** `fluentcart_product_price`
**Category:** `fluentcart-elements-single`, `fluent-cart`
**Icon:** `eicon-product-price`
**Requires:** Elementor Pro Theme Builder

---

## Content Controls

| Section ID | Control ID | Type | Description |
|---|---|---|---|
| `content_section` | `source` | Select | Current Product / Custom (via ProductWidgetTrait) |
| `content_section` | `product_id` | ProductSelectControl | Custom product (conditional on source=custom) |
| `content_section` | `align` | Responsive Choose | left / center / right |

---

## Style Section

**Section ID:** `style_section`
**Static Method:** `registerPriceStyleControls($widget, $selector)`

Default selector: `{{WRAPPER}} .fluentcart-product-price`

The method builds a composite selector for all price sub-elements:
```php
$priceSelectors = $selector . ', '
    . $selector . ' .fct-price-range, '
    . $selector . ' .fct-product-prices, '
    . $selector . ' .fct-max-price, '
    . $selector . ' .fct-min-price';
```

| Control ID | Type | CSS Selector |
|---|---|---|
| `price_color` | Color | `$priceSelectors` |
| `price_typography` | Typography | `$priceSelectors` |

---

## Static Method Usage

Called by **ProductInfoWidget** with custom selector:
```php
ProductPriceWidget::registerPriceStyleControls($this, '{{WRAPPER}} .fct-product-summary');
```

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Price wrapper (standalone) | `.fluentcart-product-price` |
| Price range | `.fct-price-range` |
| Product prices | `.fct-product-prices` |
| Min price | `.fct-min-price` |
| Max price | `.fct-max-price` |

---

## Revision History

- **2026-02-18**: Initial documentation.
