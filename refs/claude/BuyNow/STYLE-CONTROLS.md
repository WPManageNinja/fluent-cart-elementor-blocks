# BuyNowWidget Style Controls Reference

## Overview

The BuyNowWidget (`app/Modules/Integrations/Elementor/Widgets/BuyNowWidget.php`) renders a "Buy Now" button for a specific product variation with optional modal checkout. Renders via FluentCart core's `ProductRenderer::renderBuyNowButtonBlock()`.

**Widget Slug:** `fluent_cart_buy_now`

---

## Content Controls

| Section | Control ID | Type | Description |
|---|---|---|---|
| Content | `variant_id` | ProductVariationSelectControl | Variation selector |
| Content | `text` | Text | Button text, default "Buy Now" |
| Content | `enable_modal_checkout` | Switcher | Enable modal checkout, default off |

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
| Elementor wrapper | `.fluent-cart-elementor-buy-now` |
| Button (from core renderer) | `.wp-block-button__link` |

**Note:** Identical control structure to AddToCartWidget. Both use WordPress block button markup (`.wp-block-button__link`).

---

## Revision History

- **2026-02-18**: Initial documentation.
