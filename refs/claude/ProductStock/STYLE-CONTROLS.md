# ProductStockWidget Style Controls Reference

## Overview

The ProductStockWidget (`app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/ProductStockWidget.php`) renders stock availability status (In Stock / Out of Stock). Exposes `registerStockStyleControls()` reused by ProductInfoWidget.

**Widget Slug:** `fluentcart_product_stock`
**Category:** `fluentcart-elements-single`, `fluent-cart`
**Icon:** `eicon-product-stock`
**Requires:** Elementor Pro Theme Builder

---

## Content Controls

| Section ID | Control ID | Type | Description |
|---|---|---|---|
| `content_section` | `source` | Select | Current Product / Custom (via ProductWidgetTrait) |
| `content_section` | `product_id` | ProductSelectControl | Custom product (conditional on source=custom) |

---

## Style Section

**Section ID:** `style_section`
**Static Method:** `registerStockStyleControls($widget, $selector)`

Default selector: `{{WRAPPER}} .fct-product-stock`

| Control ID | Type | CSS Selector |
|---|---|---|
| `stock_typography` | Typography | `$selector .fct-stock-status` |
| `in_stock_color` | Color | `$selector:not(.out-of-stock) .fct-stock-status` |
| `out_of_stock_color` | Color | `$selector.out-of-stock .fct-stock-status` |

---

## Static Method Usage

Called by **ProductInfoWidget** with same selector:
```php
ProductStockWidget::registerStockStyleControls($this, '{{WRAPPER}} .fct-product-stock');
```

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Stock wrapper (standalone) | `.fluentcart-product-stock` (render wrapper) |
| Stock container (from core) | `.fct-product-stock` |
| Stock status text | `.fct-stock-status` |
| Out of stock modifier | `.out-of-stock` (added to `.fct-product-stock`) |

---

## Revision History

- **2026-02-18**: Initial documentation.
