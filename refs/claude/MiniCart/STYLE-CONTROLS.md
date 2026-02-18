# MiniCartWidget Style Controls Reference

## Overview

The MiniCartWidget (`app/Modules/Integrations/Elementor/Widgets/MiniCartWidget.php`) renders the FluentCart mini cart trigger button with cart item count. Renders via FluentCart core's `MiniCartRenderer::renderMiniCart()`.

**Widget Slug:** `fluent_cart_mini_cart`

---

## Content Controls

No content controls — the widget auto-detects the current cart state.

---

## Style Section Registration Order

```php
protected function register_controls()
{
    // 1. Cart Icon Style — single section with Normal/Hover tabs
}
```

---

## 1. Cart Icon Style

**Section ID:** `style_section`
**Tabs:** Normal / Hover

**Trigger Selector:** `{{WRAPPER}} .fluent_cart_mini_cart_trigger`

### Normal Tab

| Control ID | Type | CSS Selector |
|---|---|---|
| `cart_typography` | Typography | `{{WRAPPER}} .fluent_cart_mini_cart_trigger` |
| `cart_text_color` | Color | `{{WRAPPER}} .fluent_cart_mini_cart_trigger` |
| `cart_icon_color` | Color | `{{WRAPPER}} .fluent_cart_mini_cart_trigger svg` (color + fill) |
| `cart_background` | Background | `{{WRAPPER}} .fluent_cart_mini_cart_trigger` |
| `cart_border` | Border | `{{WRAPPER}} .fluent_cart_mini_cart_trigger` |
| `cart_border_radius` | Dimensions | `{{WRAPPER}} .fluent_cart_mini_cart_trigger` |
| `cart_box_shadow` | Box Shadow | `{{WRAPPER}} .fluent_cart_mini_cart_trigger` |
| `cart_padding` | Responsive Dimensions | `{{WRAPPER}} .fluent_cart_mini_cart_trigger` |
| `cart_margin` | Responsive Dimensions | `{{WRAPPER}} .fluent_cart_mini_cart_trigger` |

### Hover Tab

| Control ID | Type | CSS Selector |
|---|---|---|
| `cart_hover_text_color` | Color | `{{WRAPPER}} .fluent_cart_mini_cart_trigger:hover` |
| `cart_hover_icon_color` | Color | `{{WRAPPER}} .fluent_cart_mini_cart_trigger:hover svg` (color + fill) |
| `cart_hover_background` | Background | `{{WRAPPER}} .fluent_cart_mini_cart_trigger:hover` |
| `cart_hover_border` | Border | `{{WRAPPER}} .fluent_cart_mini_cart_trigger:hover` |
| `cart_hover_box_shadow` | Box Shadow | `{{WRAPPER}} .fluent_cart_mini_cart_trigger:hover` |

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Elementor wrapper | `.fluent-cart-elementor-mini-cart` |
| Cart trigger button | `.fluent_cart_mini_cart_trigger` |
| Cart trigger SVG icon | `.fluent_cart_mini_cart_trigger svg` |

**Note:** The trigger class uses **underscores** (`.fluent_cart_mini_cart_trigger`), not hyphens. This matches FluentCart core's `MiniCartRenderer` output.

---

## Revision History

- **2026-02-18**: Initial documentation.
