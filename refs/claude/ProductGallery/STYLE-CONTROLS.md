# ProductGalleryWidget Style Controls Reference

## Overview

The ProductGalleryWidget (`app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/ProductGalleryWidget.php`) renders the product image gallery with thumbnails. Exposes `registerGalleryContentControls()` (content controls only — no style controls). Renders via FluentCart core's `ProductRenderer::renderGallery()`.

**Widget Slug:** `fluentcart_product_gallery`
**Category:** `fluentcart-elements-single`, `fluent-cart`
**Icon:** `eicon-product-images`
**Requires:** Elementor Pro Theme Builder

---

## Content Controls

| Section ID | Control ID | Type | Description |
|---|---|---|---|
| `content_section` | `source` | Select | Current Product / Custom (via ProductWidgetTrait) |
| `content_section` | `product_id` | ProductSelectControl | Custom product (conditional on source=custom) |
| `content_section` | `thumb_position` | Select | bottom / left / right / top (default: bottom) |
| `content_section` | `thumbnail_mode` | Select | all / horizontal / vertical (default: all) |
| `content_section` | `scrollable_thumbs` | Switcher | Enable/disable thumbnail scrolling (return_value: 'yes', default: '') |
| `content_section` | `max_thumbnails` | Number | Max visible thumbnails; empty = no limit. Excess images behind "See More" button. (min: 1) |

---

## Style Section

**No style controls.** Gallery styling comes from FluentCart core CSS.

---

## Static Method Usage

`registerGalleryContentControls($widget)` is called by **ProductInfoWidget** for its gallery content settings:
```php
ProductGalleryWidget::registerGalleryContentControls($this);
```

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Gallery wrapper (standalone) | `.fluentcart-product-gallery` |

---

## Revision History

- **2026-02-19**: Added `scrollable_thumbs` (Switcher) and `max_thumbnails` (Number) controls. Core handles rendering/CSS/JS; Elementor just passes values through.
- **2026-02-18**: Initial documentation.
