# ProductReviewsWidget Style Controls Reference

## Overview

The whole review section in one widget: the rating summary, the Write a Review call to action, and the list beneath them. Mirrors the `fluent-cart/product-reviews` block on its **emptied** path, where the block stops being a container and draws the section itself to the renderer's own defaults.

**Widget Slug:** `fluentcart_product_reviews`
**Category:** `fluent-cart`
**Icon:** `eicon-testimonial fluent-cart-widget-icon`
**Requires:** a FluentCart with the review feature; Product Reviews module on

This is the one to reach for when a product page just needs reviews on it. The four widgets beside it are for laying the same pieces out by hand. It is what the bundled `fc-single-product` template uses.

---

## Core Renderer

```php
(new ProductReviewRenderer($product->ID, []))->render();
```

No options, deliberately: the renderer's own defaults are what the emptied block draws and what the shortcode draws. One section, one look.

**Assets:** `AssetLoader::loadSingleProductAssets()`.

---

## Content Controls

Nine sections. The widget once had none of these — the note below records why,
and why that stopped being true.

| Section | What it answers | Controls |
|---|---|---|
| Content | which product, and which layout | `source` / `product_id`, the layout picker, `composition_note` |
| Rating Summary | whether it shows, and where | `show_summary`, `summary_mode` |
| Write a Review Button | the CTA and the form behind it | `container`, `layout`, the three button texts |
| Review List | which reviews, and their shape | `min_rating`, `photos_only`, `content_max_words`, `view_mode`, `grid_columns` |
| Review Card | what a review shows, and in what order | the ten `show_*` toggles, `photos_first`, `rating_first`, `badge_last`, hidden `item_class` |
| Attachments | the photographs inside a card | `media_visible`, `media_more`, `media_full_width`, `media_backdrop`, `media_flush`, `media_width`, `media_height` |
| Slider | the eight `slider_*` settings | shown only for `view_mode = slider`, the section carrying that condition for all of them |
| Header | count, chips, sorting | `show_count`, `show_filter`, `show_sorting`, `default_sort` |
| Pagination | how pages are drawn | `pagination_type`, `per_page` |

**This used to say "no display controls, deliberately"**, on the grounds that
every choice already lived in core's settings or another widget. That held while
a layout was a name consulted at render. It stopped holding when a layout became
a set of settings written onto the widget: a layout can only write settings that
exist, and twelve of these had no control, which is why Photo Strip could not be
reproduced by hand. See §10 of `dev-docs/product-reviews/plan.md`.

The layout picker is `ReviewLayoutPresetControl`. Choosing a layout writes these
settings; nothing reads the layout's name at render.

---

## Style Controls

Three groups, because this widget draws three things:

| Panel | Source |
|---|---|
| Rating Summary | `ProductReviewSummaryWidget::registerSummaryStyleControls()` |
| Write a Review Button | `WriteAReviewButtonWidget::registerCtaStyleControls()` |
| The eight list sections | `ReviewStyleControls::registerReviewStyleControls($this, false)` |

`false` is load-bearing: this widget always draws core's default section, which
is **list view only**, so the Grid & Slider Gap control would be a control that
can never do anything. It is omitted here and present on the Review List widget.
Found by auditing every styled class against real rendered markup.

The Pagination section **is** kept, even though a product with few reviews shows
no pager: core's default page length is 20, and a store that lowers it (or a
product with more reviews) renders one. Verified by rendering this widget with
the store page length at 2, which produced `.fct-reviews-page-btn` and
`.fct-reviews-page-nav`.

See `refs/claude/ProductReviewList/STYLE-CONTROLS.md` for the full section table
and the three stylesheet constraints behind the selectors.

---

## Core rendering

The review widgets render their configured review content alongside FluentCart's normal product-content rendering. The Elementor integration does not suppress FluentCart's core review section.

---

## CSS Selector Map

| Class | Element |
|---|---|
| `.fluentcart-product-reviews` | Widget wrapper (addon) |
| `.fct-product-reviews-section` | The section |
| `.fct-reviews-summary` | Summary, as in the Review Summary widget |
| `.fct-review-cta-btn` | Write a Review trigger |
| `.fct-reviews-list` / `.fct-review-item` | The list and its rows |

---

## Editor states

| Condition | Canvas |
|---|---|
| Module off / core too old | reason from `ReviewSupport::unavailableReason()` |
| No product | "select a product" |
| Reviews off for this product | "reviews are turned off for this product" |
| Nothing to draw | "there is nothing to show for this product yet" |
