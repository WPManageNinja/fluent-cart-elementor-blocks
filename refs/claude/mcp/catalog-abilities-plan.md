# Catalog write abilities + the Allbirds clone — plan

**Date:** 9 October 2026 · **Decided with:** Kamrul

## Why this exists

Asked to rebuild the test site as a clone of allbirds.com, the blocker was not
design. It was that **the MCP can read the catalogue but cannot configure it.**
Of 33 core abilities only seven write anything, and none of them touch products,
variations, attributes or settings. An agent can build a flawless product page
and then has no way to give the product the colour swatches that page is for.

Kamrul put it directly: to switch between variations with colour swatches we
have to change the variation-swatch settings inside FluentCart, so the MCP has
to be able to do that or the store will never look right.

## What was verified first

| Claim | Finding |
|---|---|
| Allbirds is Elementor/WordPress | **No — Shopify.** Nothing is importable; the design is rebuilt in Elementor |
| FluentCart cannot do swatches | **It can.** `AdvancedVariationRenderer` renders colour dots, image swatches and dropdowns |
| We need to build an attribute system | **Already exists** — 13 groups, 72 terms, 174 relations are seeded on the site |
| Our sneakers can use it | **No.** All ten are `simple_variations`, variants are camera angles named "Version 1–4", and every `media_id` is null |
| Allbirds switches variants in its grid | **No.** Each colourway is a separate card. The grid switcher is a different site, and a separate feature |

## Decisions

1. **Consolidate the ten sneakers into three products** — Runner, Dasher, Slide —
   each with Colour × Size, reusing the existing images as colourways. Anything
   less produces a one-swatch colour row, which demonstrates nothing.
2. **Add catalogue write abilities to core, not the Elementor add-on.** These are
   commerce data and every builder needs them — the same split that moved the
   builder abilities out of core in the first place.
3. **Match Allbirds in the grid** (one card per colourway, swatch dot + colour
   name) and spec the real inline switcher separately. It needs a new
   `card_elements` option plus JS in `ProductCardRender`, which every builder
   shares — too big to bolt onto a redesign.

## The abilities

| Ability | Does |
|---|---|
| `list-attribute-groups` | Groups + terms, with swatch type and styling |
| `manage-attribute-group` | Create/update/delete a group; refuses deleting system groups |
| `manage-attribute-terms` | Create/update/delete terms; validates hex for colour groups, URL for image groups |
| `get-product-variations` | A product's variation type, variants, term mapping and variant media |
| `manage-product-variations` | Set variation type, map variants to terms, set variant image/price/SKU |
| `manage-store-settings` | Read and update store settings, behind a deny-list |

**`manage-store-settings` refuses three families outright**, and says why rather
than silently dropping them:

- tax and VAT identity (`seller_vat_id`, `seller_tax_id`, `legal_registration_id`)
- page assignments (`*_page_id`) — owned by `build-storefront-page`; two writers
  on the same key is how a storefront ends up pointing at the wrong page
- anything payment-related

This follows the existing rule that the payment surface is not ours to touch.

## Order of work

1. Catalogue abilities in core, with tests.
2. Restructure the sneakers **using those abilities** — if they cannot do it,
   they are not finished.
3. Allbirds design system in the Elementor kit.
4. Product template: gallery, colour swatches, size grid, CTA, editorial blocks.
5. Home, collection, header, footer.
6. Verify rendered, run the suites, record what was learned.

---

## Built, and what it cost to get swatches actually working

Phases 1–2 are done. The six abilities ship in core
(`app/Modules/MCP/Tools/CatalogTools.php`), and the ten sneakers were
consolidated into **VOLT Runner / Dasher / Court**, 3 colourways × 5 sizes each
(45 variants), **driven entirely through those abilities** — which is the only
honest test of whether they are finished.

Getting a swatch to change the picture turned out to need **four separate
stores of the same fact to agree**. Three of them are invisible from the admin
UI, and each failed silently:

| What was set | What still did nothing | Why |
|---|---|---|
| `variations.media_id` | nothing | The gallery only swaps between slides it already holds |
| …plus the gallery meta | thumbnails appeared, main image frozen | `variant_first_media_map` was `[]` |
| …plus `fct_atts_relations` | map still empty | `AdvancedVariationHandler` builds the term map from `variation_identifier`, splitting it on `_` — **not** from the relation rows |
| …plus `variation_identifier` | map still empty | The gallery reads the variant's first image from a `product_thumbnail` **product-meta row**, not from `media_id` |

All four are now written together by `manage-product-variations`, because any
one of them alone produces a page that looks broken rather than an error. The
swatch now swaps the image, the label and the SKU.

Two defects were found and fixed on the way:

- **Deletes ran after inserts.** Replacing a variant set that reuses SKUs
  collided on `sku_unique` while the old rows were still present. Deletion now
  precedes insertion, matching what the dry run already promised.
- **`products/manage` does not exist.** It was invented here; core's attribute
  routes use `products/create` / `edit` / `delete`. The abilities now check the
  same capability per action rather than one blanket permission.

### Test position, stated honestly

- `tests/smoke/mcp-catalog.php` — **25/25**, standalone, self-cleaning, runs on
  a real install.
- Core lint gates — **8/8**, including `route-coverage` and
  `permission-inventory`, the two that police a new ability's capability.
- Core's `php -l` gate reports *"could not discover changed PHP files"* — it
  diffs against git and the server copy is not a checkout. Both changed files
  were linted directly: clean.
- **Core's four DB-backed tiers did not run anywhere.** Locally they exhaust the
  128 MB limit; on the server the fixtures abort on a missing `fct_licenses`
  table because Pro is unlicensed. A proper integration test is still owed.

### Still to build

3. Allbirds design system in the kit (oat `#ECE9E2`, serif display, geometric sans).
4. Product template styled to match: sticky gallery, swatch row, size grid, editorial blocks.
5. Home, collection, header, footer.
6. `manage-store-settings` — specified above, not yet written.
