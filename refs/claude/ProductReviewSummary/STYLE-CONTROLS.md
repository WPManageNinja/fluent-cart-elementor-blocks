# ProductReviewSummaryWidget Style Controls Reference

## Overview

Renders the average rating, the review total and the five-star breakdown bars for one product. Mirrors the `fluent-cart/product-review-summary` block.

**Widget Slug:** `fluentcart_product_review_summary`
**Category:** `fluent-cart`
**Icon:** `eicon-review fluent-cart-widget-icon`
**Requires:** a FluentCart with the review feature; Product Reviews module on

In Gutenberg this block is only insertable inside a Review Summary Group, which exists to hand the product down through block context. An Elementor Container does that job with no widget of its own, so **the group does not survive the port** and this widget resolves its own product. See `dev-docs/product-reviews/plan.md` §2.

---

## Core Renderer

**Method:** `ProductReviewRenderer::renderSummarySection()`, options via the constructor.

```php
(new ProductReviewRenderer($product->ID, ['starColor' => '#f59e0b']))->renderSummarySection();
```

The renderer calls `shouldRenderReviews()` itself and emits nothing when the product hides reviews, so an empty string back is a legitimate answer, not a failure.

**Generated HTML (abridged):**
```html
<div class="fluentcart-product-review-summary">
  <div class="fct-product-reviews-section fct-reviews-summary-only"
       data-fluent-cart-review-summary data-post-id="15582"
       style="--fct-star-color: #e11d48">
    <div class="fct-reviews-summary" data-reviews-summary>
      <div class="fct-reviews-summary-left">
        <div class="fct-reviews-average">
          <span class="fct-reviews-average-number">3.83</span>
          <span class="fct-reviews-average-max">/ 5</span>
        </div>
        <div class="fct-reviews-stars-display">…</div>
        <div class="fct-reviews-total">6 reviews</div>
      </div>
      <div class="fct-reviews-summary-right">
        <div class="fct-reviews-bar-row">
          <span class="fct-reviews-bar-label">5</span>
          <span class="fct-reviews-bar-track"><span class="fct-reviews-bar-fill"></span></span>
          <span class="fct-reviews-bar-count">3</span>
        </div>
        …
      </div>
    </div>
  </div>
</div>
```

**Star colour** is a CSS custom property (`--fct-star-color`) on the section, emitted only when it differs from the default. **Assets:** `AssetLoader::loadSingleProductAssets()`.

---

## Content Controls

| Control ID | Type | Default | Notes |
|---|---|---|---|
| `source` | SELECT | `default` | From `ProductWidgetTrait` |
| `product_id` | ProductSelectControl | `''` | Shown when `source=custom` |
| `star_color` | COLOR | `#f59e0b` | Mirrors the block's `starColor`. Re-sanitised with `sanitize_hex_color()`; an emptied control falls back to core's default rather than leaving stars unstyled |

---

## Style Controls — `registerSummaryStyleControls($widget, $selector = '{{WRAPPER}} .fct-reviews-summary')`

| Control ID | Type | Selector |
|---|---|---|
| `summary_average_typography` | Typography | `.fct-reviews-average-number` |
| `summary_average_color` | COLOR | `.fct-reviews-average-number` → `color` |
| `summary_max_color` | COLOR | `.fct-reviews-average-max` → `color` |
| `summary_total_typography` | Typography | `.fct-reviews-total` |
| `summary_total_color` | COLOR | `.fct-reviews-total` → `color` |
| `summary_bar_fill_color` | COLOR | `.fct-reviews-bar-fill` → `background-color` |
| `summary_bar_track_color` | COLOR | `.fct-reviews-bar-track` → `background-color` |
| `summary_bar_height` | SLIDER px, responsive | `.fct-reviews-bar-track` → `height` |
| `summary_bar_row_gap` | SLIDER px/em, responsive | `.fct-reviews-bar-row` → `margin-block-end` |
| `summary_bar_label_typography` | Typography | `.fct-reviews-bar-label`, `.fct-reviews-bar-count` |
| `summary_bar_label_color` | COLOR | both, → `color` |

Star colour is **not** a style control — it is content, because the renderer emits it as a CSS variable the whole section inherits, including stars the style panel cannot reach.

---

## Editor states

| Condition | Canvas |
|---|---|
| Module off / core too old | reason from `ReviewSupport::unavailableReason()` |
| No product | "select a product" |
| Reviews off for this product | "reviews are turned off for this product" |
| No approved reviews yet | "no approved reviews yet, so the summary is empty" |

Front end renders nothing in every one of those cases.
