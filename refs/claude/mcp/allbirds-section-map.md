# Allbirds clone — block-by-block render map

**Date:** 9 October 2026 · Captured from allbirds.com at 1440px

## The rule this plan follows

**Blocks render, Elementor styles.** No hand-written markup, and no kit
`custom_css` — because custom CSS is invisible in the editor and the user can
never touch it. Every FluentCart widget exposes 468–514 Elementor style
controls (`get-builder-widget-schema` with `include:["style"]`), and the atomic
widgets take per-element `style` strings. That is the whole styling surface.

Build vocabulary: **53 Elementor atomic widgets + 31 FluentCart widgets.**
Anything outside that is a gap, not a styling problem.

---

## Home

| Captured block | Rendered by | Styled with |
|---|---|---|
| Announcement bar (dark strip, centred text, chevrons) | `e-flexbox` + `e-paragraph` | element style: bg, padding, letter-spacing |
| Sticky header (white pill, centred nav, icons) | header template: `e-flexbox` + `nav-menu` + `fluent_cart_store_logo` + `fluent_cart_search_bar` + `fluent_cart_mini_cart` | element style + each widget's own controls |
| Full-bleed hero image + overlay copy | `e-flexbox` (bg image) + `e-heading` + `e-button` | element style |
| "BEST SELLERS" header + arrows | `e-heading` (arrows come from the carousel) | element style: uppercase, tracking, underline |
| Best-sellers carousel | **`fluent_cart_product_carousel`** | `show_arrows`, `arrow_size`, `pagination_type` + ~475 style controls |
| Product card inside it | the carousel's own card | `card_*` style controls: background, border, radius, padding, title/price typography |
| NEW badge | card badge controls | `sale_badge_style`/`position` + badge style controls |
| Editorial 3-up photos | `e-grid` + `e-image` | element style: gap, radius |
| 3-up text cards | `e-grid` + `e-div-block` + `e-heading` + `e-paragraph` | element style |
| Footer (dark, 4 columns, email capture, socials) | footer template: `e-grid`, `e-paragraph`, `e-form` + `e-form-input` + `e-form-submit-button`, `e-svg` for socials | element style |

## Collection

| Captured block | Rendered by | Styled with |
|---|---|---|
| 4-column product grid | **`fluent_cart_shop_app`** (`product_box_grid_size: 4`) | ~514 style controls |
| Filters / sort / pagination | the same widget's `shop_layout` repeater | its own controls |
| Card: name, colour name, price | `card_elements` repeater | card style controls |
| **Colour swatch dot on the card** | — | ❌ **gap 1** |

## Product page — the one that matters

| Captured block | Rendered by | Styled with |
|---|---|---|
| Vertical image stack (not thumbnails) | **`fluentcart_product_gallery`** | `thumb_position`, `scrollable_thumbs` + style controls |
| Sticky right panel (white rounded card) | `e-flexbox` container | element style: `position:sticky`, bg, radius, padding |
| Title (serif, regular weight) | **`fluentcart_product_title`** | 282 style controls — font family, size, weight |
| "ALSO AVAILABLE IN: …" | `e-paragraph` with link | element style |
| Price + "+ FREE SHIPPING" pill | **`fluentcart_product_price`** + `e-paragraph` | price style controls; pill = element style |
| "COLOR: Anthracite (Limited Edition)" | part of **`fluentcart_product_buy_section`** | buy-section style controls |
| **Colour swatch row** | same widget, driven by the Colourway image group | swatch size/gap/border/active-ring controls |
| MEN'S / WOMEN'S size tabs | `e-tabs` | element style (cosmetic — we have one size set) |
| **Size grid** | same buy-section, Shoe Size group | swatch label controls |
| Unavailable size: diagonal strike + grey | — | ❌ **gap 3** |
| CTA disabled until a size is chosen | — | ❌ **gap 4** (newly found) |
| Fit note + "Fit Guide" link | `e-paragraph` | element style |
| Editorial: copy + chip row + circle image + bullets | `e-grid` + `e-paragraph` + `e-flexbox` chips + `e-image` | element style |
| "MATERIALS & SUSTAINABILITY" accordions | `e-accordion` | element style |
| Reviews | `fluentcart_product_review_*` | their own style controls |
| Breadcrumb | — | ❌ **gap 2** — fake with `e-paragraph` |

---

## Gaps, now precisely defined

1. **Card colour swatch + colour name** — `card_elements` has no swatch option.
   Deferred by decision; cards will read plainer than Allbirds.
2. **Breadcrumb** — no atomic widget. Fake with `e-paragraph` + links.
3. **Unavailable sizes** — Allbirds strikes them through diagonally and greys
   them. Ours renders stock-0 identically to in-stock, so a customer can select
   a size that cannot ship. This is a correctness bug, not decoration.
4. **CTA state** — Allbirds' button reads "SELECT A SIZE" and is disabled until
   a variant is chosen. Needs checking against our buy section.

Gaps 3 and 4 are the only ones that affect whether the store *works* rather
than how it looks.
