# ProductReviewFormWidget Style Controls Reference

## Overview

The review form itself, inline on the page, with no button in front of it. Mirrors the `fluent-cart/product-review-form` block.

**Widget Slug:** `fluentcart_product_review_form`
**Category:** `fluent-cart`
**Icon:** `eicon-form-horizontal fluent-cart-widget-icon`
**Requires:** a FluentCart with the review feature; Product Reviews module on

The only difference from the Write a Review Button widget is one renderer option: `container => 'none'`. That is what makes this the form rather than a trigger — no CTA is drawn and nothing is hidden behind an overlay. It is what a dedicated review page is built from, which is also where the Button widget's link mode sends people.

---

## Core Renderer

```php
(new ProductReviewRenderer($product->ID, [
    'container' => 'none',
    'layout'    => 'inline|steps',
    'starColor' => '#f59e0b',
]))->renderForm();
```

**Assets:** `AssetLoader::loadReviewSubmissionFormAssets()` — the form's own bundle. This widget is the whole reason a review page exists; it has no gallery, product card or review list to serve, and loading their scripts would cost every visitor to that page for nothing.

**Empty output is a legitimate answer.** Core decides whether this visitor may review at all — the store's permission mode, verified-buyer status, login state. When it says no, the renderer returns nothing.

---

## Content Controls

| Control ID | Type | Default | Notes |
|---|---|---|---|
| `source` / `product_id` | from `ProductWidgetTrait` | `default` | |
| `layout` | SELECT | `inline` | `inline` shows every field at once; `steps` asks one question per screen, which suits a dedicated review page |
| `star_color` | COLOR | `#f59e0b` | Re-sanitised with `sanitize_hex_color()`, falling back to core's default |

---

## Style Controls — `registerFormStyleControls($widget, $selector = '{{WRAPPER}} .fct-review-form')`

| Control ID | Type | Selector |
|---|---|---|
| `form_label_typography` | Typography | `.fct-review-step-question` |
| `form_label_color` | COLOR | `.fct-review-step-question` → `color` |
| `form_field_gap` | SLIDER px/em, responsive | `.fct-review-form-field` → `margin-block-end` |
| `form_input_typography` | Typography | inputs (see below) |
| `form_input_color` | COLOR | inputs → `color` |
| `form_input_background` | COLOR | inputs → `background-color` |
| `form_input_border` | Group_Control_Border | inputs |
| `form_input_radius` | DIMENSIONS px/% | inputs → `border-radius` |
| `form_input_padding` | DIMENSIONS, responsive | inputs → `padding` |
| `form_star_size` | SLIDER px/em, responsive | `.fct-star-selector .fct-star-svg` → `width`/`height` |
| `form_submit_typography` | Typography | `.fct-review-submit-btn` |
| `form_submit_color` / `_background` | COLOR (Normal) | `.fct-review-submit-btn` |
| `form_submit_color_hover` / `_background_hover` | COLOR (Hover) | `.fct-review-submit-btn:hover` |
| `form_submit_radius` | DIMENSIONS px/% | `.fct-review-submit-btn` → `border-radius` |
| `form_submit_padding` | DIMENSIONS, responsive | `.fct-review-submit-btn` → `padding` |

**The "inputs" selector** is spelled out rather than using a bare descendant selector:

```
.fct-review-form input[type="text"], .fct-review-form input[type="email"], .fct-review-form textarea
```

A bare `.fct-review-form input` would also catch `.fct-review-file-input` inside the photo upload zone, which is visually hidden by design and must stay so.

**Star size** targets the SVG, not a font size: the picker draws real SVG stars, unlike the display stars elsewhere in the feature which are text glyphs.

---

## CSS Selector Map

| Class | Element |
|---|---|
| `.fluentcart-product-review-form` | Widget wrapper (addon) |
| `.fct-review-form` | The form |
| `.fct-review-standalone` / `-footer` | Standalone (container `none`) shell and its footer |
| `.fct-review-form-field` | One field row |
| `.fct-review-step-question` | The field's question/label |
| `.fct-star-selector` / `.fct-star-svg` / `.fct-star-tooltip` | Star picker |
| `.fct-review-upload-zone` / `-area` / `-previews` | Photo upload (Pro) |
| `.fct-review-submit-btn` | Submit |
| `.fct-char-count`, `.fct-success`, `.fct-danger`, `.fct-warning`, `.fct-info` | Counters and messages |

---

## Editor states

| Condition | Canvas |
|---|---|
| Module off / core too old | reason from `ReviewSupport::unavailableReason()` |
| No product | "select a product" |
| Reviews off for this product | "reviews are turned off for this product" |
| Visitor may not review | "check the review permission mode in FluentCart → Settings" |
