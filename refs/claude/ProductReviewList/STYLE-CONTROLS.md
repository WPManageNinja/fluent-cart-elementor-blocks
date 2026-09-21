# ProductReviewListWidget Style Controls Reference

## Overview

The reviews themselves, with the row composed field by field. Mirrors the `fluent-cart/product-review-list` block **and its sixteen inner blocks**.

**Widget Slug:** `fluentcart_product_review_list`
**Category:** `fluent-cart`
**Icon:** `eicon-post-list fluent-cart-widget-icon`
**Requires:** a FluentCart with the review feature; Product Reviews module on

---

## Why sixteen blocks became controls

In Gutenberg this is one block holding sixteen more: a count, filter chips and a sort control above, a Review Item carrying eleven per-review fields, and a pager below.

Those sixteen are **composition primitives, not features**. They exist because Gutenberg repeats a row by nesting blocks fed from block context. Elementor has no block context, and its only repeat-a-design mechanism is Pro's Loop Builder, which is bound to `WP_Query` and cannot iterate the `fct_product_reviews` custom table. Sixteen panel widgets that only work when dragged inside another widget would be broken by design.

So the fields are a repeater, and the header and pager pieces are switches. Full reasoning in `dev-docs/product-reviews/plan.md` §2.

---

## Two row layouts, and why

**Standard (default)** emits the list block with **no inner blocks at all**. That
is precisely what core draws its own storefront row for — `ProductReviewRenderer`
renders `.fct-reviews-list-header` (count, chips and sort in one row) and
`ReviewListRenderer` draws each review with the avatar, name, stars and badge on
a single line. Verified identical to `ProductReviewRenderer::render()` element
for element, differing only by the block's own wrapper div.

**Choose fields** emits the composed tree below. Each field is its own block and
each block is a sibling `<div>`, so the fields stack one per line. That is
Gutenberg's behaviour too, and it is the price of per-field control: in the block
editor you would wrap them in a Row block to regroup them, and Elementor has no
equivalent to place inside a widget.

The standard layout still honours the field switches, the star colour, the chips,
the sort control, the rating floor and the pager — core takes them through
`fluent_cart/review/renderer_options`, the same route `OrderReviewRenderer` uses
to hand the renderer an order grant. The block carries no per-field attributes by
design, because in Gutenberg a field is turned off by deleting its block.

| | Standard | Choose fields |
|---|---|---|
| Row shape | name line, as everywhere else | one field per line |
| Field control | 4 switches (name, date, badge, reply) | all 11, reorderable |
| Header | core's one-row header | count / chips / sort as separate blocks |
| Matches | the storefront and the all-in-one widget | a composed Gutenberg list |

---

## How it renders — the block tree

The widget does **not** reimplement the eleven field renderers. It builds the parsed block tree its controls describe and hands it to `render_block()`:

```php
render_block([
  'blockName' => 'fluent-cart/product-review-list',
  'attrs'     => [ /* viewMode, gridColumns, sliderSettings, sort, perPage, … */ ],
  'innerBlocks' => [
      ['blockName' => 'fluent-cart/review-list-count',   …],
      ['blockName' => 'fluent-cart/review-list-filter',  …],
      ['blockName' => 'fluent-cart/review-list-sorting', 'attrs' => ['defaultSort' => 'created_at-DESC'], …],
      ['blockName' => 'fluent-cart/review-item',
       'attrs' => ['wp_client_id' => 'elementor-<element id>', 'minRating' => 0],
       'innerBlocks' => [ /* one per enabled field, in repeater order */ ]],
      ['blockName' => 'fluent-cart/review-list-pagination', …],
  ],
  'innerHTML' => '', 'innerContent' => [null, null, …],
]);
```

This is the same move `OrderReviewRenderer` makes for the Write a Review block, and for the same reason: the block pipeline carries attribute validation, wrapper attributes, supports and every `render_block` filter an add-on registered. Pro's votes, photos and reply threads arrive with **no code in this addon at all**.

### Two details that are load-bearing

**`innerContent` must be one `null` per child.** That is how the parser represents "a block whose content is entirely its children", and `serialize_block()` needs it to round-trip the row when core stores the composition. Get it wrong and the stored row loses its fields.

**`wp_client_id` on the Review Item is required for AJAX.** Core hashes it with the serialised row into a composition token, stores the row under it, and prints it as `data-client-id`. The reviews endpoint resolves that token through `ProductReviewListBlockEditor::savedRowRenderer()` so a sort, filter or page redraws the **composed** row. Without it the token is empty and every interaction after first paint silently falls back to core's fixed row. Elementor's element id is the natural source: stable for the life of the widget, distinct per instance.

Verified: a rendered widget prints `data-client-id="elementor-<id>-<hash>"` and `savedRowRenderer()` returns a callable for it.

---

## Content Controls

### Content
| Control ID | Type | Default | Notes |
|---|---|---|---|
| `source` / `product_id` | from `ProductWidgetTrait` | `default` | The widget resolves the product, then tells the block via `query_type=custom`, so the editor preview works on a canvas with no product context |
| `per_page` | NUMBER 0–100 | `0` | 0 uses the store setting |
| `min_rating` | NUMBER 0–5 | `0` | Becomes the Review Item's `minRating` |

### Layout
| Control ID | Type | Default | Condition |
|---|---|---|---|
| `view_mode` | SELECT list/grid/slider | `list` | |
| `grid_columns` | NUMBER 1–6 | `2` | grid or slider |
| `slider_arrows` | SWITCHER | `yes` | slider |
| `slider_arrows_size` | SELECT sm/md/lg | `md` | slider + arrows |
| `slider_autoplay` | SWITCHER | off | slider |
| `slider_autoplay_delay` | NUMBER 1000–30000 | `3000` | slider + autoplay |
| `slider_infinite` | SWITCHER | off | slider |

Slider settings are read whatever the view mode, so switching to slider and back does not lose them.

### Header
| Control ID | Type | Default | Becomes |
|---|---|---|---|
| `show_count` | SWITCHER | `yes` | `review-list-count` block |
| `show_filter` | SWITCHER | `yes` | `review-list-filter` block |
| `show_sorting` | SWITCHER | `yes` | `review-list-sorting` block |
| `default_sort_by` | SELECT created_at/rating | `created_at` | list attr + the sorting block's `defaultSort` |
| `default_sort_order` | SELECT DESC/ASC | `DESC` | same |

### Review Row
| Control ID | Type | Default | Notes |
|---|---|---|---|
| `row_layout` | SELECT standard/custom | `standard` | Standard draws core's own row; custom composes it field by field |
| `row_fields` | REPEATER of `field` | all eleven, in storefront order | Order is display order. Remove a row to hide that field. Shown when `row_layout=custom` |
| `show_reviewer_name` / `show_review_date` / `show_verified` / `show_view_reply` | SWITCHER | `yes` | Shown when `row_layout=standard`; reach the renderer through its options filter |
| `star_color` | COLOR | `#f59e0b` | Applied to the `rating` field block |
| `content_max_words` | NUMBER 0–500 | `0` | Applied to the `content` field block as `maxWords`; 0 omits the attribute |

Field values → blocks: `avatar`, `author_name`, `verified_badge`, `variation_title`, `rating`, `date`, `title`, `content`, `photos`, `votes`, `reply` → `fluent-cart/review-item-<kebab>`.

Guards: an unknown field value is skipped, and a field listed twice renders once. The repeater permits both; the row should not.

### Pagination
| Control ID | Type | Default | Condition |
|---|---|---|---|
| `show_pagination` | SWITCHER | `yes` | |
| `pagination_type` | SELECT numbers/fraction/bullets | `numbers` | shown |
| `pagination_justify` | CHOOSE | `''` | shown |

---

## Style Controls — `ReviewStyleControls::register($widget, $hasViewModes = true)`

Shared with the other review-section widget so the two cannot drift, the same
reason `BadgeControls` exists. Eight sections:

| Section | Controls | Key selectors |
|---|---|---|
| Review List | Space Between Reviews, Grid & Slider Gap | `.fct-reviews-list .fct-review-item`, `.fct-reviews-list--grid` |
| Header | count typography/colour, chip typography + Normal/Active colours and radius, sort select colours | `.fct-reviews-section-title`, `.fct-reviews-filter-chips .fct-filter-chip`, `.fct-reviews-sort select` |
| Review Card | background, border, radius, padding, box shadow | `.fct-review-item` |
| Reviewer | avatar size/radius, name typography/colour, verified badge, variation | `.fct-review-avatar-circle`, `.fct-review-item-author`, `.fct-review-verified`, `.fct-review-item-variant` |
| Stars | size, filled colour, empty colour | `.fct-review-item-stars .fct-star` |
| Review Content | title, text, date typography and colour | `.fct-review-item-title`, `.fct-review-item-content`, `.fct-review-item-date` |
| Photos & Actions | photo size/radius, reply button typography and colours | `.fct-review-media-thumb`, `.fct-review-item .fct-review-view-replies` |
| Pagination | typography, Normal/Active/Hover colours, radius, alignment | `.fct-reviews-page-btn`, `.fct-reviews-page-nav` |

### Three things about core's stylesheet that shape these selectors

Getting any of them wrong produces a control that silently does nothing.

**The pager is `!important`.** `.fct-reviews-page-btn` and `.fct-reviews-page-nav`
set border, background, radius, font and colour with `!important`, so core
survives themes that restyle every button. An ordinary declaration loses to that
however specific the selector, so the Pagination controls emit `!important`
themselves. Nothing else here needs it. The sort `select` is the one other place
core does this, and its three colour controls match.

**Some of core's rules are two classes deep.** `.fct-reviews-filter-chips
.fct-filter-chip`, `.fct-review-item-stars .fct-star` and `.fct-review-item
.fct-review-view-replies` are all (0,2,0), which a bare `{{WRAPPER}}
.fct-filter-chip` only ties. Ties resolve by source order, which is not ours to
rely on, so those controls name the parent too and win at (0,3,0).

**The grid gap is baked into the column math.** `.fct-reviews-list--grid`
computes `grid-template-columns` with the 12px gap hardcoded inside a `calc()`.
Setting `gap` alone leaves that calc subtracting 12px, so columns no longer fit
their track and the last one wraps. The gap control therefore restates
`grid-template-columns` with the chosen value. Verified in generated CSS:

```css
.elementor-element-wlist .fct-reviews-list--grid{
  gap:28px;
  grid-template-columns:repeat(auto-fit, minmax(min(260px, 100%),
    calc((100% - (var(--fct-review-columns, 2) - 1) * 28px) / var(--fct-review-columns, 2))));
}
```

Load order was checked on a real page: core's `reviews.css` is enqueued before
Elementor's per-post CSS, so even a tie would fall our way.

---

## CSS Selector Map

| Class | Element |
|---|---|
| `.fluentcart-product-review-list` | Widget wrapper (addon) |
| `.fct-review-list-block` | The list block's own wrapper |
| `.wp-block-fluent-cart-review-list-count` | Count heading |
| `.fct-reviews-filter-chips` | Star filter chips |
| `.fct-reviews-sort` | Sort select |
| `.fct-reviews-list` | The rows element the storefront script refills |
| `.fct-review-item` | One review card, carrying `data-review-id` |
| `.fct-review-item-author`, `-date`, `-title`, `-content`, `-stars`, `-variant` | Fields |
| `.fct-review-block-avatar`, `.fct-review-verified` | Avatar, verified badge |
| `.fct-review-media-gallery`, `.fct-review-votes-slot`, `.fct-review-reply-action` | Photos, votes, reply (Pro-decorated) |
| `.fct-reviews-pagination` | Pager |

---

## Editor states

| Condition | Canvas |
|---|---|
| Module off / core too old | reason from `ReviewSupport::unavailableReason()` |
| No product | "select a product" |
| Reviews off for this product | "reviews are turned off for this product" |
| No approved reviews | "this product has no approved reviews yet" |
