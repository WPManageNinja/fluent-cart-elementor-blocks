# VOLT redesign — coverage matrix and what it caught

**Date:** 8 October 2026 · **Site:** elementor.junior.ninja

The redesign doubled as an end-to-end exercise of the builder abilities: every
widget placed, every layout repeater configured, every ability called. That is
what turned it into a bug hunt — four real defects only appear when you drive
the whole surface and then *look at the rendered page*.

## Coverage

| | Result |
|---|---|
| Widgets placed | **31 / 31** |
| Layout repeaters configured | **8 / 8** (10 instances) |
| Builder abilities exercised | **11 / 11** |
| `validate-storefront` | healthy, 0 findings |
| Addon suite | 5 / 5 green |

Repeaters, and the non-default orders they were given:

| Widget | Repeater | Where |
|---|---|---|
| `fluent_cart_shop_app` | `shop_layout`, `card_elements` | Home #148, Shop #39 |
| `fluent_cart_product_card` | `card_elements` | Home #148 |
| `fluent_cart_product_carousel` | `card_elements` | Home #148 |
| `fluent_cart_checkout` | `form_elements`, `summary_elements` | Checkout #35 |
| `fluentcart_product_review_list` | `row_fields` | Product template #272 |
| `fluentcart_product_info` | `summary_sections` | Composite draft #274 |

Two product templates exist on purpose: #272 (`individual`, live) carries the
fifteen separate widgets, #274 (`composite`, draft) carries the two that would
double-render beside them — `fluentcart_product_info` and
`fluentcart_product_reviews`. A draft cannot fight the live one.

## The four defects this caught

### 1. `flushConditions()` disabled the whole Theme Builder — ours, serious

`ProductTemplate::flushConditions()` called `delete_option()` on
`elementor_pro_theme_builder_conditions`. That is not invalidation:
`Conditions_Cache::refresh()` does `get_option($key, [])` and **nothing rebuilds
it lazily**, so a missing option means "no template claims any location".

Header, footer and every product template went dark site-wide — including
templates that have nothing to do with FluentCart. The site silently fell back
to the theme's own header. Fixed to call Elementor Pro's own
`get_conditions_manager()->get_cache()->regenerate()`, which is what
`Documents\Section::save()` does. Verified by deleting the option, calling
`flushConditions()`, and watching `single`, `header` and `footer` come back.

### 2. `multiple` controls were typed as strings — ours

`WidgetSchemaReader` mapped `ProductSelectControl` to `string`, but the control
is `multiple => true` and `ProductCarouselWidget::render()` bails on
`!is_array($productIds)`. Following our own schema produced a carousel that
saved fine, reported `ok`, and rendered **nothing**. The reader now types any
`multiple` control as an array — which immediately also corrected
`shop_app`'s two `default_filter_taxonomy_*` props.

### 3. The seeder left the rating aggregate at zero — ours

The review widgets do not count rows; the summary, star rating and "N Reviews"
heading all read `fct_product_details.other_info`, which only the approval path
updates. Three seeded reviews listed happily above a summary reading "Based on
0 reviews". `seed-reviews.php` now recomputes the aggregate, on both the seed
and the already-seeded path.

### 4. `build-composition` writes a revision, not the document — Elementor's

It returns `success: true` and a list of root ids while the live page still
serves the old tree. `elementor-publish-document` is required afterwards.
Nothing in the response hints at this; the first run looked like a no-op.

## Two styling traps worth remembering

- **`manage-elements` patches by default.** Restyling an inherited template
  keeps every variant you did not explicitly overwrite — Maia's `position:fixed`
  vertical rail, its near-white footer panel and its green footer bar all
  survived and had to be cleared with `style_apply_mode: 'replace'`.
- **Element `style` strings take a single font family.** `font-family: Archivo,
  sans-serif` is quoted whole into `"Archivo, sans-serif"`, an invalid family,
  and the page silently falls back to serif. The kit's raw `custom_css` is not
  affected — only per-element styles.
- **`mix-blend-mode` is not safe inside the shop app.** Multiply knocked the
  white photo background out everywhere except the Vue grid, where every image
  blended to nothing. A white image tile needs no blending at all.

## Closed since: widgets now publish their CSS hooks

Four of the round-trips above shared one cause — **the MCP described how to
configure a widget and nothing about how to style it**, so the only way to find
a class name was to render the page and inspect the DOM.

`get-builder-widget-schema` now returns `style_hooks`, read the same way the
settings schema is: by asking the widget. It renders the widget once (filling
`product_id`/`variant_id` from the catalogue, because the three most
style-sensitive widgets print nothing without them) and reports:

- `selectors` — every FluentCart class the markup actually contains, outermost
  first. This is where `.fct-product-card-image-wrap` and the shop grid's
  `.fct-product-image-wrap` stop being a discovery exercise.
- `theme_styled_buttons` — the widgets that emit WordPress button markup and so
  take the **active theme's** colours rather than the kit's. Each comes with a
  selector scoped to the add-on's widget wrapper, because a bare
  `.wp-block-button__link` would repaint every button on the site.

Three widgets are flagged, and they are exactly the three that rendered
unreadable during this redesign:

```
fluent_cart_add_to_cart                .fluent-cart-elementor-add-to-cart .wp-block-button__link
fluent_cart_buy_now                    .fluent-cart-elementor-buy-now .wp-block-button__link
fluent_cart_customer_dashboard_button  .fct-customer-dashboard-btn .wp-block-button__link
```

Those are character-for-character the selectors that were hand-derived in a
browser, so the feature reproduces the answer rather than approximating it.

Guarded by `tests/smoke/style-hooks.php` (13 checks): every reported selector
belongs to FluentCart, the three buttons stay flagged, and each keeps a *scoped*
selector. 28 of 31 widgets render and report hooks; the three that do not
(`stock`, `sku`, `package_description`) genuinely have no data on this store and
say so rather than reporting an empty list silently.

Cost: roughly 850 ms per widget, once per request, memoised. `style_hooks:false`
skips the render when only settings are wanted.

## Still open

Not built, and worth revisiting if they bite again:

- **`validate-storefront` only checks positioning.** CSS that makes our output
  *invisible* — `mix-blend-mode`, `display:none`, `opacity:0` — is not checked,
  which is why the blank shop grid took a browser dig.
- **`build-composition` writes an autosave revision** and returns success; a
  stale revision published later would overwrite placed widgets. Our
  `place-builder-widget` could warn.
- **`build-product-template` emits bare containers**, so the first render is
  always cramped until they are styled by hand.
- **Two Elementor-side traps we cannot fix**, only document: element `style`
  strings take a single font family, and `manage-elements` patches unless you
  pass `style_apply_mode: "replace"`.

`display_errors` is fixed: `wp-config.php` now sets `display_errors=0` and
`log_errors=1`, so warnings are recorded but never printed into the page.
