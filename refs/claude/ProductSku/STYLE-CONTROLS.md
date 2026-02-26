# ProductSkuWidget Style Controls Reference

## Overview

The ProductSkuWidget (`app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/ProductSkuWidget.php`) renders the product SKU value with an optional label (e.g., "SKU: PRD-001"). SKU is stored per variant and updates dynamically via core JS when a visitor selects a different variant. Exposes `registerSkuStyleControls()` reused by ProductInfoWidget.

**Widget Slug:** `fluentcart_product_sku`
**Category:** `fluent-cart`
**Icon:** `eicon-product-meta`
**Requires:** Elementor Pro Theme Builder

---

## Core Renderer

**Method:** `ProductRenderer::renderSku($wrapper_attributes, $showLabel, $label, $variant)`

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$wrapper_attributes` | string | `''` | HTML attributes for inner wrapper div |
| `$showLabel` | bool | `true` | Whether to display the label |
| `$label` | string | `''` (falls back to translatable "SKU:") | Custom label text |
| `$variant` | ProductVariation\|null | `null` (uses first variant) | Specific variant to render |

**Generated HTML:**
```html
<div class="fct-product-sku">
  <div {wrapper_attributes}>
    <span class="fct-product-sku__label">SKU:</span>
    <span class="fct-product-sku__value" data-fluent-cart-product-sku>PRD-001</span>
  </div>
</div>
```

**Behavior:** Returns early (no output) if variant has no SKU. Core JS hides `.fct-product-sku` wrapper when variant with empty SKU is selected.

**Assets:** `AssetLoader::loadSingleProductAssets()`

---

## Content Controls

| Section ID | Control ID | Type | Default | Description |
|---|---|---|---|---|
| `content_section` | `source` | Select | `current` | Current Product / Custom (via ProductWidgetTrait) |
| `content_section` | `product_id` | ProductSelectControl | — | Custom product (conditional on source=custom) |
| `content_section` | `show_label` | Switcher | `yes` | Show/hide the SKU label |
| `content_section` | `custom_label` | Text | `''` (core default: "SKU:") | Custom label text (conditional on show_label=yes) |

---

## Style Section

**Section ID:** `sku_style_section`
**Static Method:** `registerSkuStyleControls($widget, $selector)`

Default selector: `{{WRAPPER}} .fct-product-sku`

| Control ID | Type | CSS Selector | Description |
|---|---|---|---|
| `sku_label_typography` | Typography | `$selector .fct-product-sku__label` | Label font styling |
| `sku_label_color` | Color | `$selector .fct-product-sku__label` | Label text color |
| `sku_value_typography` | Typography | `$selector .fct-product-sku__value` | SKU value font styling |
| `sku_value_color` | Color | `$selector .fct-product-sku__value` | SKU value text color |

---

## Static Method Usage

Can be consumed by **ProductInfoWidget** (not yet integrated):
```php
ProductSkuWidget::registerSkuStyleControls($this, '{{WRAPPER}} .fct-product-sku');
```

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Render wrapper (Elementor) | `.fluentcart-product-sku` |
| SKU container (from core) | `.fct-product-sku` |
| Label text | `.fct-product-sku__label` |
| SKU value | `.fct-product-sku__value` |
| JS target attribute | `[data-fluent-cart-product-sku]` |

**Adjacent to Stock:** When `.fct-product-stock + .fct-product-sku`, core CSS renders them inline with a pipe (`|`) separator.

---

## Gutenberg Block Reference

Core's `ProductSkuBlockEditor` supports these controls (for parity reference):
- Show/hide label, custom label text
- Typography (size, line height, family, weight, style, transform, letter spacing)
- Color (text + background)
- Spacing (margin + padding)
- Border (color, radius, style, width) + shadow
- Alignment (left, center, right)

---

## Revision History

- **2026-02-26**: Initial documentation and implementation. Review passed (25/33, 4 N/A, doc fixes applied).