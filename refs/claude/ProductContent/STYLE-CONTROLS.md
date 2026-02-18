# ProductContentWidget Style Controls Reference

## Overview

The ProductContentWidget (`app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/ProductContentWidget.php`) renders the full product post content (long description) on single product Theme Builder templates. No static methods — controls are local only.

**Widget Slug:** `fluentcart_product_content`
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

| Control ID | Type | CSS Selector |
|---|---|---|
| `content_color` | Color | `{{WRAPPER}} .fluentcart-product-content` |
| `content_typography` | Typography | `{{WRAPPER}} .fluentcart-product-content` |

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Content wrapper | `.fluentcart-product-content` |

**Note:** Content is rendered via `apply_filters('the_content', $post->post_content)`, so inner elements follow standard WordPress content markup.

---

## Revision History

- **2026-02-18**: Initial documentation.
