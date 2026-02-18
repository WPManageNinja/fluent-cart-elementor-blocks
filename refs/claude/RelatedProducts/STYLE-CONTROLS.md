# RelatedProductsWidget Style Controls Reference

## Overview

The RelatedProductsWidget (`app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/RelatedProductsWidget.php`) renders related products for the current product using the `[fluent_cart_related_products]` shortcode. No style controls — styling comes from the shortcode's own CSS.

**Widget Slug:** `fluentcart_related_products`
**Category:** `fluentcart-elements-single`, `fluent-cart`
**Icon:** `eicon-products-related`
**Requires:** Elementor Pro Theme Builder

---

## Content Controls

| Section ID | Control ID | Type | Description |
|---|---|---|---|
| `content_section` | `source` | Select | Current Product / Custom (via ProductWidgetTrait) |
| `content_section` | `product_id` | ProductSelectControl | Custom product (conditional on source=custom) |

---

## Style Section

**No style controls.** Related products rendering is delegated to FluentCart core's `[fluent_cart_related_products]` shortcode.

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Related products wrapper | `.fluentcart-related-products` |

---

## Revision History

- **2026-02-18**: Initial documentation.
