# Product Reviews for Elementor — implementation plan

Status: **shipped. R0–R8 merged into `develop`, along with the layout work
below.** Written 2026-09-18 and implemented the same day; the layout model was
rebuilt on 2026-09-28 and this plan updated with it.

Core has shipped reviews, which §6 records as the blocker. PRs #84–#87 are
merged.

| PR | Scope | State |
|---|---|---|
| R0 | `ReviewSupport` gate + `ReviewWidgetTrait` | done |
| R1 | Product Rating widget | done |
| R2 | Review Summary widget | done |
| R3 | Write a Review Button widget | done |
| R4 | Review Form widget | done |
| R5 | Product Review List widget (block-tree composition) | done |
| R6 | Product Reviews all-in-one widget | done |
| R7 | Reviews in `fc-single-product` | done |
| R8 | Per-widget docs, reference guide, changelog | done |

Style panels for the List and the all-in-one landed after the first pass
(`ReviewStyleControls`, eight sections, shared by both).

§10 describes what changed after that, and it is the part to read first: a
layout is no longer a name consulted at render but a set of settings written
onto the widget, which is how the block editor has always worked and is what
made the two builders agree.

Brings FluentCart's product review and rating feature to the Elementor addon:
widgets in the panel, and reviews actually rendering inside Theme Builder
single-product templates, which they do not today.

---

## 1. What core has

23 review blocks, all in **fluent-cart free**. None in Pro. Registered in PHP
(`register_block_type()`), not `block.json`, gated on
`ModuleSettings::isActive('reviews')` at `app/Hooks/actions.php:111-119`.

### 7 top-level blocks

| Block | Attributes | Renderer |
|---|---|---|
| `product-reviews` | `query_type`, `product_id` | `ProductReviewRenderer::render()` |
| `product-review-list` | `query_type`, `product_id`, `viewMode`, `gridColumns`, `sliderSettings`, `showSortControls`, `defaultSortBy`, `defaultSortOrder`, `perPage` | `ProductReviewRenderer::render()` with composed `rows_renderer` |
| `product-review-summary-group` | `query_type`, `product_id` | container only |
| `product-review-summary` | `query_type`, `product_id`, `starColor` | `renderSummarySection()` |
| `product-review-form` | `query_type`, `product_id`, `layout`, `starColor` | `renderForm()` |
| `write-a-review-button` | `query_type`, `product_id`, `container`, `layout`, `linkUrl`, `linkTarget`, 3 button texts | `renderWriteReviewCta()` + `renderForm()` |
| `product-rating` | `query_type`, `product_id`, `minReviewCount`, `minAverageRating` | `ProductCardRender::renderStarRatingBlock()` |

### 16 inner blocks of `product-review-list`

All in `ProductReviewList/InnerBlocks/InnerBlocks.php`.

- **Header/footer**, ancestor = the list: `review-list-count`, `review-list-filter`,
  `review-list-sorting` (`defaultSort`), `review-list-pagination`
  (`justify`, `paginationType`, `perPage`).
- **Row container**: `review-item` (`minRating`).
- **Per-review fields**, ancestor = `review-item`, reading block context
  `fluent-cart/review`: `avatar`, `author-name`, `verified-badge`,
  `variation-title`, `rating` (`starColor`), `date`, `title`,
  `content` (`maxWords`), `photos`, `votes`, `reply`.

Pro (`fluent-cart-pro/app/Modules/Reviews/`) adds photo uploads, helpful votes
and reply threads by decorating the free markup. It registers no blocks, so it
needs no Elementor work of its own — the Pro features arrive through the same
rendered markup.

---

## 2. How many widgets — **6, not 23**

A literal 1:1 port is the wrong shape and should not be attempted.

- The **16 inner blocks are composition primitives, not features.** They exist
  because Gutenberg composes a repeating row from nested blocks fed by block
  context. Elementor has no block context, and its only repeat-a-design
  mechanism is Elementor **Pro**'s Loop Builder, which is bound to `WP_Query`
  and cannot iterate a custom table. Shipping 16 panel widgets that only work
  when dragged inside another widget is not achievable and not idiomatic.
  They become **controls** on the Review List widget instead.
- **`product-review-summary-group` is a pure container** providing context to
  its child. A native Elementor Container does that job, so it collapses.

| # | Widget | Slug | Mirrors |
|---|---|---|---|
| 1 | Product Rating | `fluentcart_product_rating` | `product-rating` |
| 2 | Review Summary | `fluentcart_product_review_summary` | `product-review-summary` (+ group) |
| 3 | Write a Review Button | `fluentcart_write_a_review_button` | `write-a-review-button` |
| 4 | Review Form | `fluentcart_product_review_form` | `product-review-form` |
| 5 | Product Review List | `fluentcart_product_review_list` | `product-review-list` + its 16 inner blocks |
| 6 | Product Reviews | `fluentcart_product_reviews` | `product-reviews` (all-in-one) |

**Every setting is preserved.** The count changes, the capability does not.

### Parity is verified, not assumed

Each widget and the block it mirrors were rendered for the same product and
compared as a browser sees them: the ordered sequence of elements and their
`fct-` classes. All six match exactly.

| Widget | Elements | Verdict |
|---|---|---|
| Product Rating | 10 | identical |
| Review Summary | 59 | identical |
| Write a Review Button | 56 | identical |
| Review Form | 48 | identical |
| Product Review List | 185 | identical (choose-fields layout) |
| Product Reviews (all-in-one) | 312 | identical |

Same elements, same classes, same order, same stylesheet, so the same UI. Each
widget also carries the class its block's wrapper uses — `fct-review-summary-block`,
`fct-write-a-review-button-block`, `fct-review-form-block`, `fct-product-reviews-block`
— so a theme or add-on selector written against the block reaches the widget too.

Two demo pages exist on the dev site for a visual check: `/review-blocks-gutenberg/`
and `/review-widgets-elementor/`, each carrying all six.

**The list has two row layouts.** Markup parity with a *composed* Gutenberg list
was never the whole question, because a composed list stacks each field on its
own line — every field is a sibling div, and regrouping them needs a wrapper
Gutenberg gets from a Row block and Elementor cannot place inside a widget. So
the widget defaults to **Standard**, which emits the list block empty and lets
core draw the row it draws on the storefront, name line and all. Verified
identical to `ProductReviewRenderer::render()`. **Choose fields** keeps the
composed tree for anyone who wants per-field control.

---

## 3. How the Review List reaches parity

Widgets 1–4 and 6 are thin wrappers over `ProductReviewRenderer`, exactly like
the 25 existing widgets wrap `ProductRenderer`.

Widget 5 is the hard one, and it has a proven answer already in core:
**build a parsed block tree from the Elementor controls and hand it to
`render_block()`**.

Precedent: `OrderReviewRenderer::564-605` renders the real
`fluent-cart/write-a-review-button` block through `render_block()` rather than
calling the renderer, with a docblock explaining why — the block pipeline
carries attribute validation, wrapper attributes, supports, and every
`render_block` filter an add-on registered.

Verified this works for the list: `ProductReviewListBlockEditor:491-498`
composes its `rows_renderer` from `$block->inner_blocks[*]->parsed_block`, and
`render_block()` populates `inner_blocks` from a nested `innerBlocks` array.

```php
render_block([
    'blockName' => 'fluent-cart/product-review-list',
    'attrs'     => $listAttrs,          // from Elementor controls
    'innerBlocks' => [
        ['blockName' => 'fluent-cart/review-list-count', ...],
        ['blockName' => 'fluent-cart/review-item', 'attrs' => ['minRating' => $n],
         'innerBlocks' => [ /* one entry per enabled field toggle, in order */ ]],
        ['blockName' => 'fluent-cart/review-list-pagination', ...],
    ],
    'innerHTML' => '', 'innerContent' => [],
]);
```

What this buys:

- Identical markup to a Gutenberg-built page, so one stylesheet serves both.
- Pro's votes, photos and reply threads work with no addon code.
- Field order is a repeater control, so users reorder rows like they reorder
  `ProductCardWidget` elements today.
- Core changes to any field renderer reach Elementor for free.

The alternative — reimplementing 11 field renderers in the addon — diverges the
moment core changes one, and re-creates the bug class the `OrderReviewRenderer`
docblock warns about. Do not do it.

---

## 4. Control mapping for Widget 5

| Section | Controls | Source |
|---|---|---|
| Query | `source` (default/custom) + product picker, `perPage`, `minRating` | trait + list/`review-item` attrs |
| Layout | `viewMode` (list/grid/slider), `gridColumns`, slider `autoplay`/`autoplayDelay`/`arrows`/`arrowsSize`/`infinite` | list attrs |
| Header | show count, show filter chips, show sorting, `defaultSortBy`, `defaultSortOrder` | the 3 header inner blocks |
| Review row | repeater of 11 field toggles in display order, plus `starColor` and `maxWords` | the 11 field inner blocks |
| Pagination | show, `paginationType`, `justify` | `review-list-pagination` |
| Style | stars, author, date, title, content, badge, card | new |

---

## 5. Single-product template support — two real gaps

**Gap A: reviews never render in a Theme Builder product template.**
Core attaches reviews to `the_content` (`TemplateActions.php:38-56`, firing
`fluent_cart/product/after_product_content`). The addon never fires that hook.
The bundled `fc-single-product` template contains exactly two widgets,
`fluentcart_product_info` and `fluentcart_related_products`, and
`ProductInfoWidget:610` renders the description via `renderDescription()`, not
`the_content`. So a site using the bundled template shows no reviews at all,
today, with reviews fully configured. This is a live defect in the shipped
template library, independent of the new widgets.

**Gap B: double render.** If a user adds the `Product Content` widget
(`ProductContentWidget:121` runs `apply_filters('the_content', …)`), core
appends the whole review section inside that widget. Once a Review List widget
also exists on the page, reviews render twice.

Fix both together:

1. A request-scoped guard — the first review widget to render marks reviews as
   emitted, and the addon short-circuits core's `after_product_content` listener
   for the rest of the request. Prefer core's existing
   `fluent_cart/single_product_page/show_reviews` filter over a new one.
2. Add a reviews section to `fc-single-product/template.json`, bump
   `TEMPLATES_VERSION` so `TemplateSeeder` re-seeds.
3. Re-check the parked mobile-gap issue on that template while it is open
   (see `dev-docs/template-library/handoff.md`).

---

## 6. Blocker: core has not shipped reviews

`ProductReviewService`, every review renderer and all 23 blocks exist **only on
`product-review-ratings-feature`**. They are not on `develop`, and core's
shipped version is 1.6.4. The addon cannot call classes no released core has.

Required before any of this merges:

- Reviews merge to `develop` and ship in a core release.
- The addon gates on it: `class_exists(ProductReviewRenderer::class)` plus a
  `version_compare()` against that release, and the widgets stay hidden
  otherwise. The addon has **no** core-version gate at all today, so this is new
  infrastructure worth adding properly.

Building against the feature branch locally is fine. Merging is not.

---

## 7. Phasing

Each phase is one PR against `main`, stacked in order.

| PR | Scope | Depends on |
|---|---|---|
| R0 | Core-version gate + `ReviewControls.php` shared control class (`BadgeControls` pattern) + `reviewsAvailable()` helper | core release |
| R1 | Product Rating widget — simplest, validates the gate end to end | R0 |
| R2 | Review Summary widget | R1 |
| R3 | Write a Review Button widget (drawer/modal/link containers) | R2 |
| R4 | Review Form widget (inline/steps layouts) | R3 |
| R5 | Product Review List widget — block-tree composition, field repeater, list/grid/slider | R4 |
| R6 | Product Reviews all-in-one widget | R5 |
| R7 | Double-render guard + `fc-single-product` template reviews section + `TEMPLATES_VERSION` bump | R6 |
| R8 | `refs/claude/<Widget>/STYLE-CONTROLS.md` ×6, `WIDGET-REFERENCE-GUIDE.md` rows, `.pot`, changelog, release | R7 |

**Style panels for the List and the all-in-one** are in
`Widgets/ReviewStyleControls.php`, shared by both so they cannot drift. Eight
sections: list spacing, header, card, reviewer, stars, content, photos and
actions, pagination. Three properties of core's stylesheet decide whether a
control does anything, and all three are handled: the pager is `!important`, so
those controls are too; several of core's rules are two classes deep, so the
matching controls name the parent and win outright instead of tying; and the
grid gap is hardcoded inside the `grid-template-columns` calc, so the gap
control restates that property with the chosen value.

Auditing every styled class against real rendered markup found one dead
control — the all-in-one always draws core's default list view, so a grid gap
control there could never apply. `register()` takes a flag and omits it: 71
controls on the List, 70 on the all-in-one.

Rough size: R1–R4 are a day each, R5 is the bulk at two to three days, R7 needs
live verification on a real template.

---

## 8. Conventions to follow

From `.claude/commands/scaffold-widget.md` and the existing widgets:

- Product-context widgets live in `Widgets/ThemeBuilder/`, `use ProductWidgetTrait`,
  category `fluent-cart-product`. Widgets that must also work on ordinary pages
  (standalone reviews, a homepage rating) take category `fluent-cart`, as
  `ProductInfoWidget` does. All 6 blocks resolve a custom product via
  `query_type: custom`, so all 6 widgets should offer the source control and sit
  in `fluent-cart`.
- Register with one `use` import and one `$widgets_manager->register()` line in
  `ElementorIntegration::registerWidgets()`.
- Assets come from core's `AssetLoader` in both `get_style_depends()` and
  `render()`. Check `ReviewsProInit.php` for how Pro enqueues review CSS/JS.
- Style selectors target core's `.fct-review-*` classes.
- Add new slugs to `$syncWidgets` in `maybeEnqueueSingleProductSync()` only if
  they must react to variation changes — the item-level review filter may need it.

Known Elementor traps already paid for, in `dev-docs/template-library/handoff.md`
and the receipt-widget notes: controls carrying selectors land in
`style_controls`, not `get_controls()`; editor iframe CSS goes stale unless the
handle is in `get_style_depends()`; `mce_*` filters need priority 11.

---

## 9. Open questions

1. ~~**Does the Review List need the slider?**~~ **Answered: yes, shipped.**
   Slider is a view mode on both list-bearing widgets and a Pro one, and the
   Carousel, Photo Strip and Testimonials layouts are built on it. Its eight
   settings live in their own panel section, shown only for a slider.
2. **Item-level reviews.** Core supports reviewing a specific variation
   (`item_id`). Whether the widgets expose that, or always show product-level
   reviews, needs a product decision. Still open.
3. **Divi parity.** `fluent-cart-divi-modules` has no review modules either.
   Whatever approach lands here ports directly, since the rendering is
   server-side. Still open, and cheaper than it was: a layout is now a map of
   settings any builder can write (§10), not a block tree.

## 10. Layouts: applied, not consulted

Added 2026-09-28. This replaced the model the sections above describe.

### What it was, and why it had to change

Choosing a layout stored its name, and the name was read again on every render,
where `ReviewLayoutPresets::rendererOptions()` was merged over the widget's own
settings and `explicitReviewOptions()` tried to work out which of the merchant's
settings should win. Who won depended on how that argument was scored, and the
scoring could not tell a setting left alone from one deliberately set to the
same value. Ask for two columns under a layout that wants six and nothing
happened; the picker went on naming a layout after the view mode had been
changed out from under it.

### What it is

The block editor has never worked that way. `applyPreset()` builds the row from
the layout's blocks, lays the merchant's tuning back on, replaces the row — and
then nothing reads the layout's name again. `presetForBlocks()` works out what a
section *is* by reading the blocks back.

So Elementor does the same with settings in place of blocks:

- **Choosing** a layout writes `ReviewLayoutPresets::controlSettings($id)` onto
  the widget, through `$e.run('document/elements/settings', …)`, and is done
  with. The renderer is handed the settings and nothing else.
- **Naming** it is derived. `ReviewLayoutPresets::detect($settings)` in PHP and
  `layoutInForce()` in the picker compare the settings against each layout's
  and answer with the one that matches, or Custom. The wrapper CSS class comes
  from `detect()` too, so a layout's stylesheet cannot outlive the layout.
- **Custom** is that answer, not a choice. It is a status line above the grid,
  the way `presetForBlocks()` reports `custom`. A merchant leaves a layout by
  changing a setting.

### Tuning

Twelve settings are excluded from both halves, mirroring `TUNING_ATTRIBUTES`:
`grid_columns`, `per_page`, `pagination_type`, `default_sort` and the eight
`slider_*` settings. They belong to the merchant, travel from one layout to the
next, and changing one leaves the layout's name alone. Choosing a layout applies
its tuning only where the merchant had left the outgoing layout's tuning alone —
`tunedAttributes()` draws the same line.

### What that forced

Twelve settings had no control and so could only ever arrive from a layout: the
avatar, title, text, variation, footer and meta line, the three orderings within
a card, the summary's placement, the review count, and the card's own class
(hidden — it is a class name, not a choice). A layout that writes settings can
only write settings that exist, so they are controls now.

`explicitReviewOptions()` and its two tables of defaults went with all this —
109 lines whose whole job was refereeing an argument that no longer happens.

### The picker

`ReviewLayoutPresetControl`, a custom control registered on
`elementor/controls/register` like the two that already existed. The cards are
`<label>`s over real radio inputs, so the browser supplies arrow-key movement,
the group's announcement to a screen reader, the focus ring and a `disabled`
that genuinely disables. `review-layout-picker.js` does only what a radio group
cannot: check the card matching the settings, filter by category, and write a
layout when one is chosen.

Drawings come from `ReviewLayoutThumbnails`, a port of core's `PresetThumb.jsx`.
That is a second copy of eleven pictures, kept deliberately rather than moving
them into core; if the two ever disagree, core's is right.

### Pro

Nothing here changes what is gated. Core has three review gates —
`resolveViewMode()`, `isPhotoStylingAllowed()`, `isMultipleRepliesAllowed()` —
and the first two have panel controls, which `pro-feature-guard.js` shows and
disables rather than hides. `exists()` remains the single authority on which
layouts are real, so the picker and the renderer cannot disagree.
