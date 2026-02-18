# ProductExcerptWidget Style Controls Reference

## Overview

The ProductExcerptWidget (`app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/ProductExcerptWidget.php`) renders the product short description/excerpt on single product Theme Builder templates. Exposes `registerExcerptStyleControls()` reused by ProductInfoWidget.

**Widget Slug:** `fluentcart_product_excerpt`
**Category:** `fluentcart-elements-single`, `fluent-cart`
**Icon:** `eicon-product-description`
**Requires:** Elementor Pro Theme Builder

---

## Content Controls

| Section ID | Control ID | Type | Description |
|---|---|---|---|
| `content_section` | `source` | Select | Current Product / Custom (via ProductWidgetTrait) |
| `content_section` | `product_id` | ProductSelectControl | Custom product (conditional on source=custom) |
| `content_section` | `align` | Responsive Choose | left / center / right / justify |

---

## Style Section

**Section ID:** `style_section`
**Static Method:** `registerExcerptStyleControls($widget, $selector)`

Default selector: `{{WRAPPER}} .fluentcart-product-excerpt`

| Control ID | Type | CSS Selector |
|---|---|---|
| `excerpt_color` | Color | `$selector` |
| `excerpt_typography` | Typography | `$selector` |

---

## Static Method Usage

Called by **ProductInfoWidget** with custom selector:
```php
ProductExcerptWidget::registerExcerptStyleControls($this, '{{WRAPPER}} .fct-product-excerpt');
```

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Excerpt wrapper (standalone) | `.fluentcart-product-excerpt` |
| Excerpt wrapper (inside ProductInfo) | `.fct-product-excerpt` |

---

## Revision History

- **2026-02-18**: Initial documentation.
