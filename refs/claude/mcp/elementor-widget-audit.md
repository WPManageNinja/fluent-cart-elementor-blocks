# FluentCart Elementor Widgets — Customization Audit

> **Date:** 8 October 2026
> **Method:** Audited the AI-built site on elementor.junior.ninja element by element, then introspected all 31 FluentCart Elementor widgets' control surfaces live via `get-builder-widget-schema`.
> **Question:** can an AI agent customize FluentCart widgets as deeply as Elementor allows for its own?

---

## 1. Headline

**Styling depth is not the problem.** Every FluentCart widget exposes 270–600 style controls once Elementor's group controls (typography, border, box-shadow, background) are expanded. That is comparable to Elementor's own widgets.

**Three real problems, in order of impact:**

1. **Repeater contents were invisible to the agent** — fixed, see §3. This caused the broken product cards on the homepage.
2. **`card_elements` ordering does not actually drive the visual layout** — a FluentCart addon bug, see §4. Not fixable from the MCP side.
3. **The functional blocks have almost no content controls** — cart, dashboard and checkout are monolithic, see §5.

---

## 2. Control surface, all 31 widgets

Content vs style control counts, read live from `get_controls()`:

| Widget | Ctx | Content | Style |
|---|---|---|---|
| `fluent_cart_customer_dashboard` | page | **0** | 271 |
| `fluent_cart_cart` | page | **0** | 301 |
| `fluentcart_product_stock` | TB | 2 | 287 |
| `fluentcart_product_review_summary` | TB | 2 | 310 |
| `fluent_cart_add_to_cart` | page | 2 | 367 |
| `fluentcart_product_buy_section` | TB | 2 | 370 |
| `fluentcart_product_price` / `_excerpt` / `_content` | TB | 3 | 282 |
| `fluentcart_product_title` | TB | 4 | 282 |
| `fluent_cart_search_bar` | page | 4 | 507 |
| `fluent_cart_checkout` | page | 10 | 601 |
| `fluent_cart_product_card` | page | 11 | 468 |
| `fluent_cart_receipt` | page | 15 | 332 |
| `fluentcart_product_info` | TB | 16 | 400 |
| `fluent_cart_product_carousel` | page | 19 | 475 |
| `fluentcart_product_review_list` | TB | 36 | 434 |
| `fluent_cart_shop_app` | page | 36 | 514 |
| `fluentcart_product_reviews` | TB | 51 | 493 |

Median style controls: **319**. Widgets with fewer than 5 style controls: **0**.

---

## 3. FIXED — repeater contents were being thrown away

`WidgetSchemaReader` flattened every Elementor repeater to `{type: "array", items: {type: "object"}}`, discarding the inner `fields`. The agent therefore saw:

```
card_elements   array   "Card Elements"
```

No row shape, no valid values, no default. So it guessed, and wrote:

```json
card_elements: [{"title"},{"price"},{"image"},{"button"}]
```

Title and price before the image — which is exactly the broken card on the homepage.

**Fix:** `repeaterItems()` now expands a repeater's `fields` into a real object schema, and the description says the order matters. The agent now sees:

```json
"card_elements": {
  "type": "array",
  "description": "Card Elements Row order is the render order.",
  "default": [{"element_type":"image"},{"element_type":"title"},
              {"element_type":"price"},{"element_type":"button"}],
  "items": { "type": "object", "properties": {
    "element_type": { "type":"string", "default":"image",
      "enum":["image","title","excerpt","price","rating","button"] } } }
}
```

This also unlocked `shop_layout` on `fluent_cart_shop_app`, whose sections (`view_switcher`, `sort_by`, `filter`, `product_grid`, `paginator`) were equally invisible. **These are the deepest customization controls FluentCart has, and the agent could not see any of them.**

Note `rating` is a valid card element on `shop_app` but not on `product_card` — a difference the agent can now observe rather than guess.

---

## 4. NOT A BUG — withdrawn after a third pass

**This section originally claimed a FluentCart addon bug. It was wrong, and so were two attempted explanations. The record is kept because the failure mode it uncovered is real and more interesting than the bug would have been.**

`card_elements` works correctly. The renderer loops the configured order (`ElementorShopAppRenderer::renderCardElements`) and emits the DOM in that order — verified.

The visual order was overridden by **custom CSS the AI agent wrote into the Elementor Default Kit** while building the reference design:

```css
/* Shop cards */
.fct-product-card { display: flex; flex-direction: column; … }
.fct-product-card .fct-product-card-title       { order: 1; }
.fct-product-card .fct-product-card-prices      { order: 2; }
.fct-product-card .fct-product-card-image-wrap  { order: 3; }
.fct-product-card > :last-child                 { order: 4; }
```

Found in `elementor_library` post 7 (Default Kit), `_elementor_page_settings.custom_css`, served as `elementor-post-7-css`. Fourteen rules in that kit carry an `order` declaration; these four pin the card.

**Two wrong diagnoses, recorded so nobody repeats them:**

1. *"Stale CSS/transient cache."* Disproved by deleting all 17 transients and flushing Elementor's CSS twice — no change.
2. *"CSS Grid named areas pin it."* The `grid-area: image|title|price|button` declarations in `shop-app.scss` are real, but scoped to `.mode-list` inside `@media (min-width: 600px)`. The page was in grid mode, where the card has `display: grid` with a single column and **no named areas**. An exhaustive search of core, Pro, every addon and every installed theme found **zero** `order` declarations on card children — a genuine absence, which should have redirected the search outward much sooner.

### The real finding: an agent's CSS can silently defeat our layout controls

This is worth more than the bug would have been. The agent set `card_elements` *and* wrote custom CSS that contradicted it. Both "succeeded". The page was wrong, and nothing anywhere reported a conflict.

Any layout repeater we ship — `cart_layout`, `checkout_layout`, a future `product_layout` — carries the same exposure: an agent can honour the control and then override it with `order`, `grid-area` or `flex-direction` in kit CSS.

Worth considering:

- `get-storefront-guide` should tell agents to reorder via `card_elements`, never via custom CSS.
- `validate-storefront` could flag kit `custom_css` that sets `order`/`grid-area` on `fct-*` selectors — a conflict detector, the same shape as the composite-widget warning in §3.

### Historical: the original (incorrect) report follows

After setting the correct order on the homepage shop widget, the stored setting and the rendered DOM both became `image → title → price → button`. The page still displayed title and price above the image.

The card is `display: grid`, and its children carry CSS `order` values that contradict the DOM:

```
.fct-product-card-image-wrap   order: 3
.fct-product-card-title        order: 1
.fct-product-card-prices       order: 2
.fct-product-view-button       order: 4
```

Ruled out, in this order:

- Stale setting — no, the stored `card_elements` is correct.
- Stale DOM — no, the rendered DOM order is correct.
- Inline styles — no, `el.style.order` is empty on all four children.
- `fc_el_collection_*` transient — deleted all 17 transients; no change. That transient only feeds AJAX pagination (`ElementorIntegration.php:222`), not the first render.
- Elementor CSS cache — `wp elementor flush-css` run twice; no change.
- FluentCart static SCSS — the only `order:` in `shop-app.scss` is on the loader.

So the per-type `order` is emitted by the addon's own generated CSS and does **not** follow `card_elements`. Net effect: **the card layout control is inert for any order other than the one the CSS hardcodes** — in the editor as well as over MCP.

**For the dev team:** find where the addon emits `order` for `.fct-product-card` children and key it off the `card_elements` index. Until then, documenting `card_elements` as a reordering control is inaccurate.

---

## 5. The monolithic widgets

| Widget | Content controls | What that means |
|---|---|---|
| `fluent_cart_cart` | **0** | No control over columns, quantity stepper, coupon field, totals rows, empty state. |
| `fluent_cart_customer_dashboard` | **0** | No control over which tabs appear or their order. |
| `fluent_cart_checkout` | 10 | 601 style controls over a layout whose structure cannot be changed. |
| `fluentcart_product_buy_section` | 2 | Variations, quantity and buttons are one unit — cannot be separated or reordered. |

This is the deeper version of the user's point. Breaking these into addressable parts — the way `shop_app` already does with `shop_layout` — is what would give real in-depth customization power. `shop_layout` is the model to copy: a repeater of named sections whose order and presence are controllable.

**Suggested split, highest value first:**

1. `fluent_cart_cart` → a `cart_layout` repeater (items, coupon, totals, checkout button, empty state).
2. `fluentcart_product_buy_section` → separate variation selector, quantity, add-to-cart, buy-now; or a `buy_layout` repeater.
3. `fluent_cart_customer_dashboard` → a `dashboard_tabs` repeater.
4. `fluent_cart_checkout` → a `checkout_layout` repeater (contact, shipping, payment, summary).

---

## 6. What the site actually looks like

Built by the agent from a reference design, as of this audit:

| Part | Post | FluentCart widgets |
|---|---|---|
| Homepage | 148 | `shop_app`, `product_carousel` |
| Header | 151 | `store_logo`, `search_bar`, `mini_cart` |
| Footer | 152 | — |
| Product template | 146 | gallery, title, price, excerpt, buy_section, sku, content, related_products |

Working: header with live cart total, hero, featured grid, product template with gallery/variations/quantity/buttons, category archives.

Weak: the card layout bug in §4; several abandoned draft templates (135, 138, 140, 142, 144) left by earlier rebuild attempts.

---

## 7. Changed in this pass

- `WidgetSchemaReader::repeaterItems()` — expands repeater fields (§3).
- Earlier the same day: composite-widget conflict warnings, `remove-builder-widget`, duplicate-template detection, `build-storefront`, `build-product-template`.

**Not done:** no automated test covers the repeater expansion. `npm test` currently fails on this workstation for unrelated reasons (PHP memory exhaustion in WordPress core, reproducible with all changes stashed).
