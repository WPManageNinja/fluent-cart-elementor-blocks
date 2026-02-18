# ProductTitleWidget Style Controls Reference

## Overview

The ProductTitleWidget (`app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/ProductTitleWidget.php`) renders the product title on single product Theme Builder templates. Exposes a public static method `registerTitleStyleControls()` reused by ProductInfoWidget.

**Widget Slug:** `fluentcart_product_title`
**Category:** `fluentcart-elements-single`, `fluent-cart`
**Icon:** `eicon-product-title`
**Requires:** Elementor Pro Theme Builder

---

## Content Controls

| Section ID | Control ID | Type | Description |
|---|---|---|---|
| `content_section` | `source` | Select | Current Product / Custom (via ProductWidgetTrait) |
| `content_section` | `product_id` | ProductSelectControl | Custom product (conditional on source=custom) |
| `content_section` | `html_tag` | Select | h1–h6, div, span, p (default: h1) |
| `content_section` | `align` | Responsive Choose | left / center / right |

---

## Style Section

**Section ID:** `style_section`
**Static Method:** `registerTitleStyleControls($widget, $selector)`

Default selector: `{{WRAPPER}} .fluentcart-product-title`

| Control ID | Type | CSS Selector |
|---|---|---|
| `title_color` | Color | `$selector` |
| `title_typography` | Typography | `$selector` |

---

## Static Method Usage

Called by **ProductInfoWidget** with custom selector:
```php
ProductTitleWidget::registerTitleStyleControls($this, '{{WRAPPER}} .fct-product-title h1');
```

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Title element (standalone widget) | `.fluentcart-product-title` |
| Title element (inside ProductInfo) | `.fct-product-title h1` |

---

## Revision History

- **2026-02-18**: Initial documentation.
