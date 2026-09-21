# ProductRatingWidget Style Controls Reference

## Overview

The ProductRatingWidget (`app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/ProductRatingWidget.php`) renders a product's star rating and review count, drawn from the cached aggregate in `detail.other_info`. It is the Elementor mirror of the `fluent-cart/product-rating` block: same thresholds, same store kill switch, same renderer. Exposes `registerRatingStyleControls()` for composite widgets.

**Widget Slug:** `fluentcart_product_rating`
**Category:** `fluent-cart` (available on any page, not only product templates)
**Icon:** `eicon-rating fluent-cart-widget-icon`
**Requires:** a FluentCart with the review feature; the Product Reviews module switched on

---

## Availability gate

Unlike every other widget in this addon, this one can be absent. `ReviewWidgetTrait` (which wraps `ProductWidgetTrait`) hides it from the panel when `ReviewSupport::isSupportedByCore()` is false — a FluentCart old enough to have no review classes. When the classes exist but the module is off, the widget stays listed and prints the reason on the editor canvas instead.

| Condition | Panel | Canvas | Front end |
|---|---|---|---|
| Core has no review classes | hidden | — | — |
| Module switched off | shown | "module is switched off" notice | nothing |
| `show_rating_in_shop` = no | shown | "ratings are switched off" notice | nothing |
| No product resolved | shown | "select a product" notice | nothing |
| Thresholds not met | shown | "does not meet the minimum" notice | nothing |

Front end is always silent. A shopper sees an absent section, never a diagnostic.

---

## Core Renderer

**Method:** `ProductCardRender::renderStarRatingBlock($wrapperAttributes)`

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$wrapperAttributes` | string | `''` | Raw HTML attributes for the wrapper div. The widget passes `class="fct-product-card-rating"`, matching what the block gets from `get_block_wrapper_attributes()`. |

**Generated HTML:**
```html
<div class="fluentcart-product-rating">
  <div class="fct-product-card-rating" aria-label="Rated 3.83 out of 5 based on 6 reviews">
    <span class="fct-product-card-stars" aria-hidden="true">
      <span class="fct-star fct-star-filled">&#9733;</span>
      <span class="fct-star fct-star-half">
        <span class="fct-star-half-empty">&#9733;</span>
        <span class="fct-star-half-fill">&#9733;</span>
      </span>
      <span class="fct-star fct-star-empty">&#9733;</span>
    </span>
    <span class="fct-product-card-review-count">(6)</span>
  </div>
</div>
```

**Data source:** `detail.other_info` → `average_rating`, `review_count`. The canonical aggregate `recalculateProductRatings()` maintains. Never recounted here — a recount could disagree with the number printed beside the stars.

**Assets:** `AssetLoader::loadSingleProductAssets()`, in both `get_style_depends()` and `render()`.

---

## Content Controls

| Control ID | Type | Default | Notes |
|---|---|---|---|
| `source` | SELECT | `default` | From `ProductWidgetTrait`. `default` = current product, `custom` = picker |
| `product_id` | ProductSelectControl | `''` | Shown when `source=custom` |
| `min_review_count` | NUMBER (0–1000) | `0` | Hide until the product has this many reviews. Mirrors the block's `minReviewCount` |
| `min_average_rating` | NUMBER (0–5, step 0.1) | `0` | Hide when the average is below this. Mirrors `minAverageRating`; clamped to 5 at render, as the block does |

---

## Style Controls — `registerRatingStyleControls($widget, $selector = '{{WRAPPER}} .fct-product-card-rating')`

| Control ID | Type | Selector |
|---|---|---|
| `rating_star_color` | COLOR | `.fct-star-filled`, `.fct-star-half-fill` → `color` |
| `rating_empty_star_color` | COLOR | `.fct-star-empty`, `.fct-star-half-empty` → `color` |
| `rating_star_size` | SLIDER (px/em/rem), responsive | `.fct-product-card-stars` → `font-size` |
| `rating_star_gap` | SLIDER (px/em), responsive | `.fct-star` → `margin-inline-end` |
| `rating_count_typography` | Group_Control_Typography | `.fct-product-card-review-count` |
| `rating_count_color` | COLOR | `.fct-product-card-review-count` → `color` |
| `rating_alignment` | CHOOSE, responsive | wrapper → `display:flex; justify-content` |

Stars are text glyphs (`&#9733;`), so size is `font-size`, not width. The half star is two stacked spans — colour both halves or the split disappears.

---

## CSS Selector Map

| Class | Element |
|---|---|
| `.fluentcart-product-rating` | Widget wrapper (addon) |
| `.fct-product-card-rating` | Renderer wrapper, carries the `aria-label` |
| `.fct-product-card-stars` | Star row, `aria-hidden` |
| `.fct-star-filled` / `.fct-star-empty` | Whole stars |
| `.fct-star-half` / `.fct-star-half-fill` / `.fct-star-half-empty` | Half star parts |
| `.fct-product-card-review-count` | `(6)` |

---

## Parity notes

- The block reads `show_rating_in_relevant` when it sits in a related-products loop, reached through block context. Elementor has no context, so the widget always reads `show_rating_in_shop` — a widget the merchant placed by hand is the shop-side question.
- The accessible label comes from the renderer and is not themeable, by design: it is the only thing a screen reader gets, since the star row is `aria-hidden`.
