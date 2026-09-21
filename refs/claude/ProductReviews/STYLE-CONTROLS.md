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

| Control ID | Type | Default | Notes |
|---|---|---|---|
| `source` / `product_id` | from `ProductWidgetTrait` | `default` | |
| `composition_note` | RAW_HTML | — | Points at the four granular widgets |

**No display controls, deliberately.** Every choice this section offers already lives either in core's review settings or in one of the other widgets. Duplicating them here would give a merchant two places to set the same thing and no way to tell which won.

---

## Style Controls

Three groups, because this widget draws three things:

| Panel | Source |
|---|---|
| Rating Summary | `ProductReviewSummaryWidget::registerSummaryStyleControls()` |
| Write a Review Button | `WriteAReviewButtonWidget::registerCtaStyleControls()` |
| The eight list sections | `ReviewStyleControls::register($this, false)` |

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

## Duplicate rendering

Core hangs its own copy of this section on `the_content`, through `fluent_cart/product/after_product_content`. A page carrying both the Product Content widget and any review widget would therefore show the section twice.

`ElementorIntegration::preventDuplicateProductReviews()` handles it on `template_redirect`: it scans the documents about to render the page for any widget in `ElementorIntegration::REVIEW_WIDGETS`, and if it finds one, removes core's listener from that hook — and only core's, found by instance in the hook's callback table, so third-party listeners survive.

Two decisions worth keeping:

- **Decided before rendering, not during.** The Product Content widget may sit above the review widget, in which case core's copy would already be out before anything could know a second one was coming.
- **Not done through `fluent_cart/single_product_page/show_reviews`.** That filter exists and looks like the obvious lever, but it is the store's visibility policy, which the widgets themselves consult through `ReviewSupport`. Turning it off to suppress core's copy would turn the widgets off with it.

`fluentcart_product_rating` is **not** in that list: a star line is not a review section and must not suppress core's.

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
