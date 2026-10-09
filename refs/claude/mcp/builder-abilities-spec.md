# FluentCart Builder Abilities — Specification

> **Status:** **Built and verified on elementor.junior.ninja (7 Oct 2026).** 7 abilities live; Elementor's catalog went 85 → 92 tools. Remaining: MCP resources (§4.8), Gutenberg behind the same contract, Theme Builder product-template creation.
> **Date:** 7 October 2026
> **Depends on:** [`page-builder-mcp-research.md`](page-builder-mcp-research.md) — read §0 first; it contains the live verification this spec is built on.
> **Goal:** Let an AI agent build a working FluentCart storefront in Elementor (and later Gutenberg) by giving it FluentCart-owned abilities that discover, place and configure FluentCart's own widgets.

---

## 1. Why this shape

Three facts from the live verification decide the whole design:

1. **Our abilities already reach Elementor's MCP.** FluentCart's 33 commerce abilities appear in Elementor's 85-tool catalog with our descriptions intact, and execute correctly. Anything we register as a public WordPress ability is automatically available to an agent connected to Elementor.
2. **Elementor will not configure our widgets.** `elementor-manage-elements` refuses with `elementor_v3_not_supported`; `elementor-get-widget-schema` does not know them; components are atomic-only. This is deliberate on their side and not removable by us.
3. **We can configure them ourselves.** Writing settings into `_elementor_data` renders our widgets perfectly — verified: a `fluent_cart_product_card` went from "Product not found" to a fully rendered card with image, title and price.

This is the same approach Premium Addons (40 abilities) and The Plus Addons (163 abilities) ship today. Neither waits on Elementor's V4 API.

**Division of labour:** Elementor's tools own layout, containers, classes, variables, styling and publishing. FluentCart's abilities own FluentCart widgets — discovery, schema, placement, configuration. Neither reaches into the other.

---

## 2. Surface to expose

### 2.1 Elementor widgets — 31 total

Source: `fluent-cart-elementor-blocks/app/Modules/Integrations/Elementor/Widgets/`, registration list in `ElementorIntegration::registerWidgets()`.

**General (14)** — category `fluent-cart`, usable on any page:

| Widget name | Class |
|---|---|
| `fluent_cart_add_to_cart` | AddToCartWidget |
| `fluent_cart_buy_now` | BuyNowWidget |
| `fluent_cart_mini_cart` | MiniCartWidget |
| `fluent_cart_cart` | CartWidget |
| `fluent_cart_shop_app` | ShopAppWidget |
| `fluent_cart_product_card` | ProductCardWidget |
| `fluent_cart_product_carousel` | ProductCarouselWidget |
| `fluent_cart_product_categories_list` | ProductCategoriesListWidget |
| `fluent_cart_checkout` | CheckoutWidget |
| `fluent_cart_receipt` | ReceiptWidget |
| `fluent_cart_customer_dashboard_button` | CustomerDashboardButtonWidget |
| `fluent_cart_customer_dashboard` | CustomerDashboardWidget |
| `fluent_cart_search_bar` | SearchBarWidget |
| `fluent_cart_store_logo` | StoreLogoWidget |

**Theme Builder (17)** — category `fluent-cart-product`, **require product context** (a FluentCart Theme Builder document); render a placeholder elsewhere. Note the naming difference: `fluentcart_` with no underscore.

| Widget name | Gate |
|---|---|
| `fluentcart_product_title` | — |
| `fluentcart_product_gallery` | — |
| `fluentcart_product_price` | — |
| `fluentcart_product_stock` | — |
| `fluentcart_product_sku` | — |
| `fluentcart_product_package_description` | — |
| `fluentcart_product_excerpt` | — |
| `fluentcart_product_buy_section` | — |
| `fluentcart_product_content` | — |
| `fluentcart_product_info` | — |
| `fluentcart_related_products` | — |
| `fluentcart_product_rating` | reviews |
| `fluentcart_product_review_summary` | reviews |
| `fluentcart_write_a_review_button` | reviews |
| `fluentcart_product_review_form` | reviews |
| `fluentcart_product_review_list` | reviews |
| `fluentcart_product_reviews` | reviews |

The six review widgets register only when `ReviewSupport::coreHasReviews()`. Theme Builder integration additionally needs **Elementor Pro**. FluentCart Pro gating is **per control / per render** via `App::isProActive()`, never at registration — so a widget can exist while some of its controls do not apply.

### 2.2 Gutenberg blocks

`fluent-cart/app/Hooks/Handlers/BlockEditors/` — ~35 block editors plus InnerBlocks families (Cart, Checkout, ShopApp, ProductCarousel, ProductReviewList, MediaCarousel, RelatedProduct). Same abilities, `builder: "gutenberg"`.

### 2.3 Shortcodes

The universal fallback and the backing renderer for most widgets: `fluent_cart_products`, `fluent_cart_product_card`, `fluent_cart_cart`, `fluent_cart_checkout`, `fluent_cart_pricing_table`, `fluent_cart_customer_profile`, `fluent_cart_login_form`, `fluent_cart_registration_form`, `fluent_cart_receipt`, `fluent_cart_order_review`, `fluent_cart_show_coupon`, `fluent_cart_product_header`, `fluent_cart_related_products`.

---

## 3. Schema generation — introspect, never hand-maintain

Every widget uses standard `add_control()` / `add_responsive_control()` with `Controls_Manager`. Elementor therefore already holds the full control stack, and we read it back:

```php
$widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($widgetName);
$controls = $widget->get_controls();   // id, type, label, default, options, responsive, condition…
```

Map Elementor control types to JSON Schema (`TEXT`→string, `NUMBER`→number, `SELECT`→string+enum, `SWITCHER`→boolean(`yes`/``), `REPEATER`→array, `MEDIA`→object, …), carry `condition` through as a human-readable note, and drop pure-style controls from the default output behind `include: ["style"]`.

**This is the single most important design decision in the spec.** It means all 31 Elementor widgets, every Gutenberg block, and anything added later are covered automatically, and the manifest can never drift from the code. A hand-written widget table would be stale within one release.

---

## 4. The abilities

Namespace `fluent-cart/`, registered through `AbilitiesRegistrar` exactly as the existing 33 are, so they inherit `wrapExecuteCallback`, the structured error envelope, and the `meta.mcp.public` flag that puts them in Elementor's catalog.

### 4.1 `fluent-cart/get-builder-context` — START HERE

**Read.** Perm: `dashboard_stats/view`.

Returns: which builders are active (Elementor + version + Pro, Gutenberg, Divi, Bricks); which FluentCart builder addons are active; whether reviews are available; FluentCart Pro state; the storefront pages already assigned in FluentCart settings (shop, cart, checkout, receipt, dashboard) and whether a FluentCart Theme Builder product template exists; and an explicit **workflow string** telling the agent to use Elementor's tools for layout and FluentCart's for FluentCart widgets.

That workflow string matters: in Elementor's flat 85-tool catalog nothing otherwise tells the agent these two families belong together.

### 4.2 `fluent-cart/list-builder-widgets`

**Read.** Perm: `dashboard_stats/view`.

Input: `builder` (`elementor`|`gutenberg`, default: first active), optional `context` (`page`|`product_template`), optional `search`.

Output per widget: `name`, `label`, `category`, one-line `purpose`, `requires_product_context` (bool), `requires_elementor_pro`, `requires_fluentcart_pro`, `available` (bool + reason when false), and `gutenberg_equivalent` / `shortcode_equivalent`.

Summary-first: no schemas here. ~31 compact rows.

### 4.3 `fluent-cart/get-builder-widget-schema`

**Read.** Perm: `dashboard_stats/view`.

Input: `widget` (name), `builder`, `include[]` (`style`, `advanced`, `conditions`; default content-only).

Output: JSON Schema of settings with types, defaults, enums and required-ness, plus `placement_notes` (e.g. *"needs a FluentCart product Theme Builder document"*), derived live from `get_controls()`.

### 4.4 `fluent-cart/place-builder-widget`

**Write.** Perm: `store/settings` (editing site structure), plus `edit_post` on the target.

Input: `post_id`, `widget`, `settings{}`, `parent_id` (default document root), `index`, `mode` (`append`|`replace_children`), `dry_run`.

Behaviour: validate `widget` is registered and available; validate `settings` against the introspected schema, **rejecting unknown keys** and echoing valid options on a bad enum; read `_elementor_data`, insert a node `{id, elType:"widget", widgetType, settings, elements:[]}` with a fresh 8-char hex id; write back; flush Elementor CSS cache.

Returns the new `element_id`, the resolved tree fragment, and the document's `edit_url`.

### 4.5 `fluent-cart/update-builder-widget`

**Write.** Same perms.

Input: `post_id`, `element_id`, `settings{}` (partial merge; `null` removes a key), `dry_run`. Errors clearly if `element_id` is not a FluentCart widget — **we never touch Elementor's own elements**, which keeps the boundary clean and avoids fighting their tooling.

### 4.6 `fluent-cart/build-storefront-page`

**Write.** Perm: `store/settings`.

Input: `recipe` (`shop`|`product_template`|`cart`|`checkout`|`customer_dashboard`|`receipt`), `post_id` (or create), `builder`, `dry_run`.

Composes a whole page from an opinionated recipe, creating the Theme Builder document and display condition where the recipe needs one. This is the "build me a store" entry point — one call instead of twenty.

### 4.7 `fluent-cart/validate-storefront`

**Read.** Perm: `store/settings`.

Audits the finished site: are shop/cart/checkout/receipt/dashboard pages assigned in FluentCart settings; does a product template exist with a condition; any FluentCart widget pointing at a deleted product; any Theme Builder widget placed outside product context. Returns findings with a suggested fix per item.

### 4.8 Resources (not tools)

Elementor ships markdown guides via `list-resources` / `read-resource`, and the MCP adapter supports the same through `RegisterAbilityAsMcpResource`. Ship storefront recipes and composition guidance as **resources** so they are pulled when relevant rather than carried in every catalog:

- `fluent-cart://storefront/recipes`
- `fluent-cart://widgets/placement-rules`
- `fluent-cart://theme-builder/product-template`

---

## 5. Safety

- **Draft-first.** Never publish. Match Elementor's convention and return `edit_url`.
- **Permissions.** Reads gated on `dashboard_stats/view`; writes on `store/settings` plus the WordPress capability for the target post. Remember (research §0) that the aggregator path bypasses `PermissionGate::transport()` — the per-ability `permission_callback` is the only real boundary, so every write ability must check independently.
- **Never touch non-FluentCart elements.** Refuse by `widgetType` prefix.
- **Back up before write.** Keep the prior `_elementor_data` in a revision so a bad write is recoverable.
- **`dry_run` on every write**, returning the resolved tree without persisting.
- **Flush CSS cache** after each write or the frontend serves stale styles.
- **Respect gates.** Refuse a review widget when reviews are off, a Theme Builder widget outside product context, with a clear reason rather than a silent empty render.

---

## 6. File layout

```
fluent-cart/app/Modules/MCP/
├── Tools/BuilderTools.php            # the 7 ability definitions
└── Support/
    ├── BuilderRegistry.php           # active builders, addon detection, availability+gates
    ├── WidgetSchemaReader.php        # get_controls() → JSON Schema (§3)
    ├── ElementorDocument.php         # _elementor_data read / mutate / write / cache flush
    └── StorefrontRecipes.php         # recipe definitions (§4.6) + resource payloads
```

Registered by adding `BuilderTools::class` to `AbilitiesRegistrar::toolClasses()`. Gutenberg support later adds a `GutenbergDocument.php` sibling behind the same abilities — the `builder` parameter is in the contract from day one so adding it is not a breaking change.

---

## 7. Build order

1. `WidgetSchemaReader` + `get-builder-widget-schema` — proves introspection against the real 31 widgets.
2. `BuilderRegistry` + `get-builder-context` + `list-builder-widgets` — the read surface.
3. `ElementorDocument` + `place-builder-widget` (dry-run first) — **the end-to-end prototype**: agent places a configured product card and it renders.
4. `update-builder-widget`, then `validate-storefront`.
5. `StorefrontRecipes` + `build-storefront-page` + resources.
6. Gutenberg behind the same contract.

**Exit gate for the prototype:** an agent connected only to Elementor's MCP, given no FluentCart knowledge beyond these abilities, builds a page with a correctly configured FluentCart product card that renders on the frontend.

---

## 7a. What was built — and what the build taught us

All 7 abilities ship in `app/Modules/MCP/Tools/BuilderTools.php` with four support classes. Verified end to end: Elementor's tools made a page and heading, `place-builder-widget` added a configured product card, and it rendered with the right product, price, image and button. `build-storefront-page recipe:"shop"` produced a 10-product grid in one call.

Three things the build corrected:

**1. Introspection was the right call, immediately.** The Elementor `fluent_cart_product_card` takes `product_id` as a select control — *not* the `query_type` + `product_id` pair the Gutenberg block uses. A hand-written schema would have been wrong on the very first widget.

**2. The site's autoloader is classmap-authoritative.** `setClassMapAuthoritative(true)` means PSR-4 fallback is off, so a newly added class does not exist — silently. `AbilitiesRegistrar`'s `class_exists()` guard then skips the tool class and no abilities appear, with no error anywhere. **Any new PHP file needs `composer dump-autoload -o` on the target site.** This cost a full debugging cycle; it is now in the deploy skill.

**3. A role can be filled three ways, and the validator must know all of them.** The first `validate-storefront` reported the Shop and Account pages as broken. They were fine — FluentCart's own installer builds Shop with the `fluent-cart/products` **block** and Account with `fluent-cart/customer-profile`, while Cart/Checkout/Receipt use **shortcodes**. Only Elementor widgets were being checked. Each recipe now carries `widget` + `block` + `shortcode`, and `StorefrontRecipes::pageCarriesContent()` accepts any of them. A validator with false positives is worse than none — it teaches the agent to ignore it.

## 7b. Theme Builder product templates — researched and working

Single product pages are not pages. They are rendered by an **Elementor Pro Theme Builder document** that our Elementor addon registers:

| | |
|---|---|
| Document type | `fluentcart-product` (`Documents/FluentCartProduct.php`, extends `Single_Base`) |
| Location | `single`, `condition_type` `fluentcart_product` |
| Condition | `FluentCartCondition` → `is_singular('fluent-products')`, registered under the `general` group |
| Condition string | `include/fluentcart_product` |
| Stored as | `elementor_library` post, `_elementor_template_type` = `fluentcart-product`, `_elementor_conditions` meta |
| Also registered | `fluentcart-product-post`, and `FluentCartArchiveCondition` under `archive` for taxonomy pages |

**Elementor's own `manage-site-parts` already creates this type** — `fluentcart-product` is in its enum, and it handles display conditions. So we do not duplicate document creation; Elementor owns documents, we own widgets. Verified:

```
elementor-manage-site-parts create type:"fluentcart-product"
  conditions:["include/fluentcart_product"]                    → template 125, draft ✅
elementor-build-composition  two-column layout                 → ✅
fluent-cart-place-builder-widget × 5
  gallery | title | price | excerpt | buy_section              → all ✅
elementor-publish-document                                     → ✅
live product page                                              → ✅ renders the template
```

The resulting product page shows the gallery with thumbnails, title, price range, excerpt, the four variation swatches, quantity stepper and Buy Now / Add To Cart — fully functional commerce, composed by an agent.

### What we added instead of duplicating

`Support/ProductTemplate.php` plus two wiring changes:

- `get-builder-context` now returns a `product_template` block (does one exist, is it live, what conditions, which FluentCart widgets it holds) and a **`product_page_workflow`** string giving the exact `manage-site-parts` call. Without it, nearly half the catalog — the 17 `fluentcart_*` widgets — is undiscoverable: they do nothing on an ordinary page and an agent has no way to learn that a Theme Builder document is the answer.
- `validate-storefront` now reports the two silent failure modes. Both verified by deliberately breaking the site:
  - template exists but is a **draft** → warning (confirmed: unpublishing 125 produced *"is draft, so it does not render"*, republishing cleared it)
  - template is published but has **no display condition** → warning

A draft template alongside a live one is deliberately *not* flagged — that is work in progress, not a fault, and the Shop/Account false-positive episode (§7a) is the reason for the caution.

### One more consistency fix

`ElementorDocument::outline()` keyed its child list `children` while Elementor's `get-page-structure` uses `elements`. I tripped on exactly that mismatch while scripting the template build; an agent alternating between the two tools in one session would too. Now both use `elements`.

## 7c. MCP resources — tested, and not possible for the Elementor path

§4.8 proposed shipping storefront guidance as MCP **resources**, mirroring Elementor's `elementor://style/best-practices`. Elementor's server exposes 14 resources, 7 of them Angie's, which suggested third parties could add their own.

**They cannot.** Tested directly: an ability was registered carrying `meta.mcp.uri` + `mimeType` (the adapter's documented resource markers, via a new `mcp_meta` pass-through in `AbilitiesRegistrar`).

```
elementor server  → resources: 14, fluent-cart ones: 0 ; arrives as a TOOL ✅
fluent-cart server → resources:  0                     ; arrives as a TOOL ✅
```

A server's resource list comes from how **it** calls `create_server()` — FluentCart's own passes `[]` for resources, and Elementor's builds its own list. An ability cannot nominate itself. Angie's resources are there because Angie is Elementor's own plugin registering directly with their server; there is no public hook for anyone else.

**So guidance for the Elementor path has to be callable.** `fluent-cart/get-storefront-guide` is therefore a tool, returning markdown: the two-toolset split, the order of work, the page→widget→setting table generated from `StorefrontRecipes`, and the Theme Builder flow. The `mcp_meta` pass-through stays in `AbilitiesRegistrar` (harmless, and ready if we ever register resources on our own server for the Gutenberg path), but the guide no longer sets a URI that does nothing.

## 7d. Editor round-trip — verified

The last shipping risk from §8: does Elementor's editor cleanly reopen a document whose FluentCart widget settings we wrote into `_elementor_data` from outside?

**Yes.** Template 125 — created by `manage-site-parts`, filled by `place-builder-widget`, styled by `manage-elements` — opens in the Elementor editor with the product preview rendering live, the Structure panel intact, and the FluentCart Product widget category in the panel. A human can take over and edit normally. No corruption, no migration notice.

## 8. Open questions

**Resolved during the build:**

- ~~Does `get_controls()` return a complete stack for trait/helper/repeater widgets?~~ **Yes.** `ProductCardWidget` uses all three and returned 11 content settings including the `card_elements` repeater and badge controls from `BadgeControls`.
- ~~Element id format / collisions?~~ **8-char hex, uniqueness checked within the document** — sufficient.
- ~~Does Elementor's editor reopen externally-written documents?~~ **Yes** (§7d).
- ~~Theme Builder: `manage-site-parts` or build our own?~~ **Elementor's** (§7b).
- ~~Ship guidance as MCP resources?~~ **Not possible for this path** — shipped as a tool (§7c).

**Still open:**

- **Gutenberg**, behind the same `builder` parameter. The contract already carries it, so this is additive: a `GutenbergDocument` sibling to `ElementorDocument`, and block names in `BuilderRegistry` (already half-present — `StorefrontRecipes` carries each role's block name).
- **Archive templates.** The addon also registers `FluentCartArchiveCondition` for product taxonomy pages. Not covered by any recipe yet.
- **`fluentcart-product-post`**, the second document type, is unexplored — unclear when it applies versus `fluentcart-product`.
- **Multi-currency / multi-store** interactions with recipe assignment are untested.
