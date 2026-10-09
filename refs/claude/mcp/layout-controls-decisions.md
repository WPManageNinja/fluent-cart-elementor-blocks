# Layout controls for the MCP — decision record

**Date:** 8 October 2026 · **Branch:** `mcp-kamrul` · **Decided by:** Kamrul, after a structured grilling pass

Why these decisions were made, and what was rejected. Read this before extending the builder abilities or adding a layout repeater to any widget.

---

## The goal

Give FluentCart widgets the depth of customization Elementor users expect, and make it reachable by an AI agent through the MCP. `shop_app`'s `shop_layout` repeater is the reference pattern: a list of named sections whose order and presence are controllable.

The gap measured in [`elementor-widget-audit.md`](elementor-widget-audit.md): styling is deep everywhere (270–600 controls per widget), but the functional blocks have almost no *content* controls — Cart and Customer Dashboard expose **zero**.

---

## Decisions

### 1. Scope: Theme Builder product widgets, after a conflict detector

Order of work:

1. **CSS-override conflict detector** — `validate-storefront` warns when kit CSS contradicts a layout control.
2. **`product_layout` for the Theme Builder product widgets** (17 of them) — the biggest win; product pages are where customization matters most.

Then, unsequenced: product categories list, mini cart. **Both investigated and closed with no work needed — see below.**

**Why the detector first:** it is the lesson the investigation below produced, it is small, and it makes every later repeater trustworthy. Shipping repeaters that an agent can silently override is shipping half a feature.

### 2. Checkout and Customer Dashboard are out of scope — indefinitely

Kamrul's reasoning, recorded verbatim in substance:

- **Extensibility.** Third parties add payment fields and other UI through hooks on the checkout. Supporting those additions across every page builder is not tractable, and re-emitting checkout markup would break them.
- **Security.** It is the payment surface. Don't touch it.

This is not an effort judgement. Do not reopen it because checkout looks easy — it is the *easiest* widget to change technically (the addon already owns `ElementorCheckoutRenderer`), and it is still excluded.

### 3. Cart is dropped for now

Investigated and viable but thin. `CartRenderer::render()` is three ordered public calls — `renderItems()`, `renderTotal()`, `renderCheckoutButton()` — and core's own Gutenberg cart block already renders them independently (`InnerBlocks.php`), so no core change would be needed.

But three sections is a thin feature for a page inside the purchase funnel, and the same hook-extensibility concern as checkout applies in smaller measure. Revisit once `product_layout` proves the pattern.

Gotcha if it is revisited: `data-fluent-cart-cart-checkout-button-wrap` is emitted by `render()`, **not** by `renderCheckoutButton()`. A caller invoking the button alone must re-emit that wrapper or the cart JS cannot swap button state. The Gutenberg block does exactly this.

### 4. Core is not edited from here

Elementor-specific work lives in `fluent-cart-elementor-blocks`. Where a change genuinely belongs in core, it is handed over as a precise patch rather than made here. This follows the architectural split agreed the same day: core carries no Elementor code, so the MCP builder abilities moved out of it entirely.

### 5. Additive markup only

A layout section calls core's existing render method in a chosen order. It never re-emits core's inner markup. This keeps three things intact: customer CSS overrides, the FluentCart JS that binds to those selectors, and the Gutenberg/Divi/Bricks versions sharing the same renderers.

### 6. Hook preservation is a tested rule, not a convention

Core fires filters *inside* its render methods — `fluent_cart/cart/line_item/line_meta`, `fluent_cart/cart/total_label`, `fluent_cart/cart/checkout_button_text` and others. Calling those methods individually preserves them; re-implementing the markup would not.

The addon smoke suite asserts this: hook a filter, render through the layout repeater, assert it fired. Given extensibility is the stated reason two whole pages are excluded, it earns a test rather than a line in a doc.

### 7. Verification is visual, not structural

Settled because of the investigation below: asserting that a setting saved, or that the DOM order changed, proves nothing. Every layout control ships with:

- a DOM-order assertion (catches renderer faults), **and**
- a rendered-page check that the *visual* order matches, read from computed styles in a browser.

---

## Closed with no work: mini cart and product categories list

Both were on the list on the assumption they had reorderable sections. Measured against the live widget registry, neither does:

| Widget | Content controls | Repeaters |
|---|---|---|
| `fluent_cart_mini_cart` | 5 | none |
| `fluent_cart_product_categories_list` | 4 | none |

`MiniCartRenderer::renderMiniCart()` is one call; its icon, badge and total are inline parts of a single button, governed by booleans. Ordering them is a flex concern, not a layout repeater. `ProductCategoriesListRenderer::render()` emits one list, with `display_style` already switching list/dropdown.

Adding a repeater to either would be ceremony, not capability. Both are appropriately configured for what they are. **The deep-customization gap is real only on Cart, Checkout and Customer Dashboard — all three out of scope by decisions 2 and 3.**

## `layoutControls` is derived, not listed

The first version hand-listed which widgets could reorder themselves. It was wrong within the hour: it missed `fluent_cart_checkout` (`form_elements`, `summary_elements`) and `fluentcart_product_review_list` (`row_fields`) — six layout-capable widgets, not four.

It is now derived from the widget: any content-tab repeater the schema reader can expand *is* a layout control. Same reasoning as the schema reader itself — a parallel table drifts from the code it describes, and this one drifted before it was even committed.

Advertising the checkout's repeaters does **not** violate decision 2. Nothing re-emits checkout markup; the widget renders its own rows exactly as it does when a human reorders them in the editor. Only the *visibility* of an existing control changed.

## Correction: the conditions cache is regenerated, never deleted

An earlier version of `flushConditions()` called `delete_option()` on
`elementor_pro_theme_builder_conditions`, and this record described that as the
fix for "template not applying". It was the wrong fix, and worse than the bug.

`Conditions_Cache::refresh()` reads `get_option($key, [])` and nothing rebuilds
it lazily, so a deleted option does not mean "stale, rebuild me" — it means "no
template claims any location". Deleting it took the **entire Theme Builder**
down site-wide: header, footer and every template, FluentCart's or not. On the
test site the header silently reverted to the theme's own markup, which looks
like a broken theme rather than a cleared cache.

It now calls Elementor Pro's `get_conditions_manager()->get_cache()->regenerate()`,
the same call `Documents\Section::save()` makes. Full write-up in
[`redesign-volt-coverage.md`](redesign-volt-coverage.md).

## The investigation that produced rule 7

The homepage product cards rendered title and price above the image. Diagnosing it took three attempts, two of them wrong. Recorded because the failure modes recur.

| # | Diagnosis | How it was disproved |
|---|---|---|
| 1 | Stale CSS / transient cache | Deleted all 17 transients, flushed Elementor CSS twice. No change. |
| 2 | CSS Grid named areas pin the order | The `grid-area` rules in `shop-app.scss` are scoped to `.mode-list` inside `@media (min-width: 600px)`. The page was in grid mode: `display:grid`, one column, no named areas. |
| 3 | **Correct** | Custom CSS in the Elementor Default Kit, written by the AI agent that built the site. |

```css
/* kit post 7, _elementor_page_settings.custom_css, served as elementor-post-7-css */
.fct-product-card .fct-product-card-title      { order: 1; }
.fct-product-card .fct-product-card-prices     { order: 2; }
.fct-product-card .fct-product-card-image-wrap { order: 3; }
.fct-product-card > :last-child                { order: 4; }
```

`card_elements` was never broken. The renderer emitted the configured order correctly; the agent's own CSS overrode it, deliberately, to match the reference design.

**The signal that was missed:** an exhaustive search found `order` on card children in *no* FluentCart file, no addon, and no installed theme. A genuine absence should have redirected the search outward immediately instead of prompting a second theory about core CSS.

### Why this matters more than the bug would have

The agent set `card_elements` **and** wrote CSS contradicting it. Both operations reported success. The page was wrong and nothing anywhere reported a conflict.

Every layout repeater carries this exposure. A layout control an agent can silently override is only half a control — hence decision 1.
