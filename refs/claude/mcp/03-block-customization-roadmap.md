# Block customization roadmap — for the developer discussion

**Date:** 9 October 2026
**Scope:** `fluent-cart-elementor-blocks` only. Nothing here requires a change
to `fluent-cart` or `fluent-cart-pro`.

Every number below was **measured** from the live widget registry
(`get_controls()` on each registered widget), not estimated. Reproduce with
`get-builder-widget-schema`, `include: ["style"]`.

---

## How to read this

Each block has three separate kinds of configurability, and they fail
differently:

- **Content controls** — what it shows. Too few means the block can only do
  one thing.
- **Style controls** — how it looks. Too few means the designer must write CSS.
- **Layout repeater** — the order and presence of its internal parts. Absent
  means the arrangement is fixed.

A block can have 400 style controls and still be unstylable where it matters —
see the buy section below.

---

## The measured state

| Block | Content | Style | Layout repeater | Biggest gap |
|---|---:|---:|---|---|
| `shop_app` | 36 | 514 | `shop_layout`, `card_elements` | — healthy |
| `product_carousel` | 19 | 475 | `card_elements` | — healthy |
| `product_card` | 11 | 468 | `card_elements` | no swatch / colour-name element |
| `checkout` | 10 | — | `form_elements`, `summary_elements` | out of scope by decision |
| `receipt` | 15 | — | none | — adequate |
| `product_review_list` | 36 | — | `row_fields` | — healthy |
| `product_info` | 16 | — | `summary_sections` | — healthy |
| `product_buy_section` | 2 | 372 | none | **all 372 style the buttons. Zero for swatches or sizes.** |
| `product_gallery` | 5 | 271 | none | **all 271 are Elementor's generic tab. Zero gallery-specific.** |
| `mini_cart` | 5 | — | none | fine as-is (one button) |
| `product_categories_list` | 4 | — | none | fine as-is (one list) |
| `product_title` / `price` / `sku` / `stock` / `excerpt` | 2–4 | ~285 | none | fine — single-purpose |
| `cart` | **0** | — | none | **no configurability at all** |
| `customer_dashboard` | **0** | — | none | **no configurability at all** |

---

## Priority 1 — style controls that do not exist

These force CSS on anyone building a designed storefront. They are the reason
a "no custom CSS" build is currently impossible.

### 1a. `product_buy_section` — swatch and size styling

372 controls, and **every one of them styles the Add-to-Cart / Buy-Now
buttons**. The colour swatches and size chips — the most visually distinctive
part of any fashion product page — have none.

Needed, as a new Style section:

- Swatch size, gap, shape (circle / rounded / square)
- Swatch border width + colour, and the **selected** state border/ring
- Swatch hover state
- Size-chip padding, min-width, radius, border, typography
- Size-chip selected and **disabled** states (the JS already adds `.disabled`;
  nothing styles it, so unavailable combinations look identical to available
  ones)
- Attribute label typography and spacing

**Evidence:** rebuilding one product page required ~2.5 KB of CSS, of which
roughly 90% was swatch and size styling that no control could express.

### 1b. `product_gallery` — any gallery control at all

271 controls, all inherited from Elementor's generic Advanced tab (`_margin`,
`_padding`, `_background`…). Nothing for the gallery itself.

Needed: thumbnail size, thumbnail gap, thumbnail border/active state, main
image aspect ratio and radius, rail width.

---

## Priority 2 — new card elements

`card_elements` currently offers: `image`, `title`, `excerpt`, `price`,
`button` (and `rating` on `shop_app` only).

Two additions would close the most visible gap against a real fashion store:

- **`colour_name`** — the selected/primary variant's colour name under the
  title. Every Allbirds card has it.
- **`swatches`** — the colourway dots on the card. **This is the big one:** it
  also needs JS to swap the card image and price inline. It touches
  `ProductCardRender`, which every builder shares, so it is the largest item
  here and probably belongs in core rather than this add-on.

Also worth adding for parity: `rating` on `product_card` and
`product_carousel` (currently `shop_app` only, for no obvious reason).

---

## Priority 3 — decomposition ("breaking" blocks into parts)

Two blocks expose **zero** content controls because they render as one lump:

### `cart` — 0 controls

`CartRenderer::render()` is three ordered public calls:
`renderItems()`, `renderTotal()`, `renderCheckoutButton()`. Core's own
Gutenberg cart block already calls them independently, so **no core change is
required** to add a `cart_layout` repeater here.

*Gotcha if this is built:* `data-fluent-cart-cart-checkout-button-wrap` is
emitted by `render()`, **not** by `renderCheckoutButton()`. A caller invoking
the button alone must re-emit that wrapper or the cart JS cannot swap button
state. The Gutenberg block does exactly this.

### `customer_dashboard` — 0 controls

Same shape, but **excluded by decision** along with `checkout`: third parties
extend both through hooks, and checkout is the payment surface. Not reopening.

---

## New blocks worth adding

Ranked by how often their absence forced a workaround:

| Block | Why | Effort |
|---|---|---|
| **Breadcrumb** | Every product page has one; Elementor's atomic set has no breadcrumb, so it has to be faked with a paragraph | small |
| **Announcement bar** | Standard storefront furniture; currently a styled flexbox with no rotation | small |
| **Product badge / label** | "New", "Limited Edition" — currently only sale/sold-out badges exist | small |
| **Size guide / fit note** | A disclosure linked from the buy section | small |
| **Recently viewed** | Standard commerce block, no equivalent today | medium |

---

## What NOT to do

- **Do not touch `checkout` or `customer_dashboard` markup.** Extensibility
  (third-party hook fields) and security (payment surface). This was decided
  deliberately and is not an effort judgement.
- **Do not re-emit core's markup** in any new layout repeater. Call core's
  existing render methods in a chosen order. This keeps customer CSS, the
  FluentCart JS bindings, and the Gutenberg/Divi/Bricks versions all working.
- **Do not hand-maintain lists** that describe widgets. Two attempts drifted
  within an hour. Read from the widget.

---

## Suggested order

1. **1a swatch/size style controls** — unblocks designed storefronts, contained
   to one widget, no core change.
2. **1b gallery controls** — same reasoning, smaller.
3. **`colour_name` card element** — small, visible.
4. **`cart_layout` repeater** — no core change needed, closes a 0-control block.
5. **Breadcrumb + announcement blocks** — small, frequently needed.
6. **`swatches` card element** — largest, shared renderer, discuss core vs add-on.

---

## Noted for later: Gutenberg and Divi support

**Decision: we will support Gutenberg and Divi. Not now — noted so it is not
forgotten, and so today's choices do not block it.**

What this means for the work above:

- **Anything that renders** — a new `cart_layout` repeater, the `colour_name`
  and `swatches` card elements, swatch style controls — ends up duplicated
  three times if it is built Elementor-first. These call core's renderers, and
  core's Gutenberg blocks already call the same methods. Build the *capability*
  so a second builder can reuse it.
- **The catalogue abilities** (`CatalogTools`) are builder-agnostic by nature.
  They live in this add-on today only because core must stay untouched. When
  Gutenberg or Divi support starts, they should move to core so all three
  builders get them, rather than being copied.
- **`validate-storefront` and `StorefrontRecipes`** are likewise not
  Elementor-specific — "the shop page needs the shop widget and the setting
  must point at it" is true for every builder. Same move, same reason.
- **`WidgetSchemaReader` and `StyleHooks` are Elementor-specific** and stay
  here. Each builder needs its own equivalent.

The practical rule: when something is about *FluentCart data or storefront
correctness*, it belongs in core eventually. When it is about *Elementor
widgets*, it stays in this add-on.
