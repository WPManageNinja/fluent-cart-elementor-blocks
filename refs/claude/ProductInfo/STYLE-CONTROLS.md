# ProductInfoWidget Style Controls Reference

## Overview

The ProductInfoWidget (`app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/ProductInfoWidget.php`) is a **composite widget** that combines gallery, title, stock, excerpt, price, and buy section into a full single product layout. It consumes static methods from 5 other ThemeBuilder widgets — it has no static methods of its own.

**Widget Slug:** `fluentcart_product_info`
**Category:** `fluentcart-elements-single`, `fluent-cart`
**Icon:** `eicon-single-product`
**Requires:** Elementor Pro Theme Builder

---

## Content Controls

| Section ID | Control ID | Type | Description |
|---|---|---|---|
| `content_section` | `source` | Select | Current Product / Custom (via ProductWidgetTrait) |
| `content_section` | `product_id` | ProductSelectControl | Custom product (conditional on source=custom) |
| `sections_section` | `show_gallery` | Switcher | Default yes |
| `sections_section` | `show_title` | Switcher | Default yes |
| `sections_section` | `show_stock` | Switcher | Default yes |
| `sections_section` | `show_excerpt` | Switcher | Default yes |
| `sections_section` | `show_price` | Switcher | Default yes |
| `sections_section` | `show_buy_section` | Switcher | Default yes |
| `gallery_section` | `thumb_position` | Select | Via `ProductGalleryWidget::registerGalleryContentControls()` |
| `gallery_section` | `thumbnail_mode` | Select | Via `ProductGalleryWidget::registerGalleryContentControls()` |

---

## Style Sections

All style sections are conditional on their respective `show_*` toggle.

### 1. Title

**Section ID:** `title_style_section`
**Condition:** `show_title = yes`
**Via:** `ProductTitleWidget::registerTitleStyleControls($this, '{{WRAPPER}} .fct-product-title h1')`

| Control ID | Type | CSS Selector |
|---|---|---|
| `title_color` | Color | `{{WRAPPER}} .fct-product-title h1` |
| `title_typography` | Typography | `{{WRAPPER}} .fct-product-title h1` |

### 2. Price

**Section ID:** `price_style_section`
**Condition:** `show_price = yes`
**Via:** `ProductPriceWidget::registerPriceStyleControls($this, '{{WRAPPER}} .fct-product-summary')`

| Control ID | Type | CSS Selector |
|---|---|---|
| `price_color` | Color | Composite: `{{WRAPPER}} .fct-product-summary` + `.fct-price-range`, `.fct-product-prices`, `.fct-max-price`, `.fct-min-price` |
| `price_typography` | Typography | Same composite selector |

### 3. Stock

**Section ID:** `stock_style_section`
**Condition:** `show_stock = yes`
**Via:** `ProductStockWidget::registerStockStyleControls($this, '{{WRAPPER}} .fct-product-stock')`

| Control ID | Type | CSS Selector |
|---|---|---|
| `stock_typography` | Typography | `{{WRAPPER}} .fct-product-stock .fct-stock-status` |
| `in_stock_color` | Color | `{{WRAPPER}} .fct-product-stock:not(.out-of-stock) .fct-stock-status` |
| `out_of_stock_color` | Color | `{{WRAPPER}} .fct-product-stock.out-of-stock .fct-stock-status` |

### 4. Excerpt

**Section ID:** `excerpt_style_section`
**Condition:** `show_excerpt = yes`
**Via:** `ProductExcerptWidget::registerExcerptStyleControls($this, '{{WRAPPER}} .fct-product-excerpt')`

| Control ID | Type | CSS Selector |
|---|---|---|
| `excerpt_color` | Color | `{{WRAPPER}} .fct-product-excerpt` |
| `excerpt_typography` | Typography | `{{WRAPPER}} .fct-product-excerpt` |

### 5. Buy Section

**Section ID:** `buy_section_style_section`
**Condition:** `show_buy_section = yes`
**Via:** `ProductBuySectionWidget::registerBuySectionStyleControls($this, '{{WRAPPER}} .fct_buy_section')`

| Control ID | Type | CSS Selector |
|---|---|---|
| `button_typography` | Typography | All button classes in `.fct_buy_section` |
| `button_text_color` | Color | Normal tab |
| `button_background` | Background | Normal tab |
| `button_border` | Border | Normal tab |
| `button_hover_text_color` | Color | Hover tab |
| `button_hover_background` | Background | Hover tab |
| `button_hover_border` | Border | Hover tab |
| `button_border_radius` | Dimensions | Outside tabs |
| `button_padding` | Responsive Dimensions | Outside tabs |

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Info widget wrapper | `.fluentcart-product-info` |
| Single product page | `.fct-single-product-page` |
| Product page row | `.fct-single-product-page-row` |
| Product summary | `.fct-product-summary` |
| Product title | `.fct-product-title` |
| Product stock | `.fct-product-stock` |
| Product excerpt | `.fct-product-excerpt` |
| Buy section | `.fct_buy_section` |

---

## Revision History

- **2026-02-18**: Initial documentation.
