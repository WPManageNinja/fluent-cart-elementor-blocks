# AddToCartWidget Style Controls Reference

## Overview

The AddToCartWidget (`app/Modules/Integrations/Elementor/Widgets/AddToCartWidget.php`) renders a single "Add to Cart" button for a specific product variation. Renders via FluentCart core's `ProductRenderer::renderAddToCartButtonBlock()`.

**Widget Slug:** `fluent_cart_add_to_cart`

---

## Content Controls

| Section | Control ID | Type | Description |
|---|---|---|---|
| Content | `variant_id` | ProductVariationSelectControl | Variation selector (non-subscription only) |
| Content | `text` | Text | Button text, default "Add to Cart" |

---

## Style Section Registration Order

```php
protected function register_controls()
{
    // Content Section
    // 1. Button Style — single section with Normal/Hover tabs
}
```

---

## 1. Button Style

**Section ID:** `style_section`
**Tabs:** Normal / Hover

**Button Selector:** `{{WRAPPER}} .wp-block-button__link`

### Normal Tab

| Control ID | Type | CSS Selector |
|---|---|---|
| `button_typography` | Typography | `{{WRAPPER}} .wp-block-button__link` |
| `button_text_color` | Color | `{{WRAPPER}} .wp-block-button__link` |
| `button_background` | Background | `{{WRAPPER}} .wp-block-button__link` |
| `button_border` | Border | `{{WRAPPER}} .wp-block-button__link` |
| `button_border_radius` | Dimensions | `{{WRAPPER}} .wp-block-button__link` |
| `button_box_shadow` | Box Shadow | `{{WRAPPER}} .wp-block-button__link` |
| `button_padding` | Responsive Dimensions | `{{WRAPPER}} .wp-block-button__link` |
| `button_margin` | Responsive Dimensions | `{{WRAPPER}} .wp-block-button__link` |

### Hover Tab

| Control ID | Type | CSS Selector |
|---|---|---|
| `button_hover_text_color` | Color | `{{WRAPPER}} .wp-block-button__link:hover` |
| `button_hover_background` | Background | `{{WRAPPER}} .wp-block-button__link:hover` |
| `button_hover_border` | Border | `{{WRAPPER}} .wp-block-button__link:hover` |
| `button_hover_box_shadow` | Box Shadow | `{{WRAPPER}} .wp-block-button__link:hover` |

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Elementor wrapper | `.fluent-cart-elementor-add-to-cart` |
| Button (from core renderer) | `.wp-block-button__link` |

**Note:** The button uses WordPress block button markup (`.wp-block-button__link`) as rendered by FluentCart core's `ProductRenderer::renderAddToCartButtonBlock()`.

---

## Revision History

- **2026-02-18**: Initial documentation.
