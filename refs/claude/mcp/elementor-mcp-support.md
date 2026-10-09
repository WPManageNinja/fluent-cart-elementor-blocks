# FluentCart — Elementor MCP Support

**Developer handover.** What we built, why it works this way, how to run it, and what is still open.

| | |
|---|---|
| Status | Working and verified end to end on a live site |
| Built | 7 Oct 2026 |
| Test site | https://elementor.junior.ninja (WP 7.1.3, PHP 8.3, no page cache) |
| Versions tested | FluentCart 1.7.0 + Pro · FluentCart Elementor Blocks 1.1.0 · Elementor 4.3.4 + Pro 4.3.1 · Angie 1.1.19 |
| Related | [`page-builder-mcp-research.md`](page-builder-mcp-research.md) (why) · [`builder-abilities-spec.md`](builder-abilities-spec.md) (design + test transcripts) |

---

## 1. What this is

Elementor shipped an official MCP in **4.3** (22 Sep 2026). Connect Claude, Cursor or Codex to a site and the agent builds Elementor pages.

It cannot build a FluentCart store, because it cannot touch our widgets. This work closes that gap: **8 FluentCart abilities that discover, configure and place FluentCart widgets**, appearing automatically inside Elementor's own tool catalog.

An agent connected to Elementor's MCP can now build a working FluentCart storefront — shop, cart, checkout, receipt, account, single-product template and category archives.

## 2. Why it is built this way

Three facts from live testing drove every decision. Full transcripts in the research doc.

**Our abilities already reach Elementor.** FluentCart's existing 33 commerce abilities appear in Elementor's catalog with descriptions intact and execute correctly. Anything registered as a public WordPress ability is available to an agent connected to Elementor. No transport work was needed.

**Elementor will not configure our widgets, by design.** `manage-elements` refuses with a dedicated error code `elementor_v3_not_supported`; `get-widget-schema` does not know them; components are atomic-only. Our widgets extend `\Elementor\Widget_Base` (classic/V3) and Elementor's MCP operates on V4 atomic elements. **There is no public API for third-party V4 atomic widgets** — Elementor says explicitly they will not release one soon and advise against integrating ([#32950](https://github.com/orgs/elementor/discussions/32950)).

**But we can configure them ourselves.** Writing settings into `_elementor_data` renders our widgets perfectly. This is the same approach Premium Addons (40 abilities) and The Plus Addons (163) ship today.

**Division of labour:** Elementor owns layout, containers, styling, document creation, publishing. FluentCart owns FluentCart widgets. Neither reaches into the other — `update-builder-widget` refuses any element that is not ours.

### Dead ends, so nobody re-walks them

| Approach | Why not |
|---|---|
| Ship widgets as Elementor components | *"Components require atomic elements only."* Components are also per-site user content, not something a plugin ships. |
| Port widgets to V4 atomic | No public API; Elementor advise against it. Revisit when announced. |
| Shortcode via `shortcode` dynamic tag | Calls succeed, page renders as **plain text**. Atomic text props strip everything but a few inline tags, and no atomic widget accepts raw HTML. |
| Ship guidance as MCP resources | A server's resource list comes from its own `create_server()` call; an ability cannot nominate itself. Tested with `meta.mcp.uri` — still arrived as a tool. Angie's resources exist because Angie is first-party. |

## 3. The abilities

All in `app/Modules/MCP/Tools/BuilderTools.php`, registered through `AbilitiesRegistrar` like the other 33.

| Ability | Type | Permission | Purpose |
|---|---|---|---|
| `fluent-cart/get-builder-context` | read | `dashboard_stats/view` | START HERE. Active builders, widget counts, product/archive template status, and the workflow strings. |
| `fluent-cart/list-builder-widgets` | read | `dashboard_stats/view` | All 31 widgets with availability, gates and product-context flag. |
| `fluent-cart/get-builder-widget-schema` | read | `dashboard_stats/view` | One widget's settings contract, read live from the widget. |
| `fluent-cart/get-storefront-guide` | read | `dashboard_stats/view` | Markdown how-to for the whole flow. |
| `fluent-cart/place-builder-widget` | **write** | `store/settings` + `edit_post` | Insert a configured FluentCart widget. |
| `fluent-cart/update-builder-widget` | **write** | `store/settings` + `edit_post` | Change settings on a placed widget. |
| `fluent-cart/build-storefront-page` | **write** | `store/settings` | Create/fill a storefront page **and** assign the FluentCart setting. |
| `fluent-cart/validate-storefront` | read | `dashboard_stats/view` | Audit: pages assigned, published, carrying content; templates live. |

Tool names are sanitised by the adapter: `fluent-cart/get-order` → `fluent-cart-get-order`. Ability names keep the slash, tool names do not.

## 4. Files

```
app/Modules/MCP/
├── Tools/BuilderTools.php          the 8 ability definitions + handlers
├── Support/
│   ├── WidgetSchemaReader.php      Elementor get_controls() → JSON Schema
│   ├── BuilderRegistry.php         31-widget catalog, availability, gates
│   ├── ElementorDocument.php       read/mutate/write _elementor_data, backup, CSS flush
│   ├── StorefrontRecipes.php       page roles → widget/block/shortcode + setting key
│   └── ProductTemplate.php         Theme Builder product + archive template status
└── AbilitiesRegistrar.php          +BuilderTools, +mcp_meta pass-through
```

Nothing outside `app/Modules/MCP/` changed. The feature is inert unless FluentCart MCP is enabled.

## 5. Key design decisions

### 5.1 Schemas are introspected, never hand-written

```php
$widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($name);
$controls = $widget->get_controls();
```

All 31 widgets, every future widget, zero maintenance, and it cannot drift from the code.

This paid off immediately: the Elementor `fluent_cart_product_card` takes `product_id` as a **select control**, while the Gutenberg block uses a `query_type` + `product_id` pair. A hand-written table would have been wrong on the first widget.

Mapping notes: switchers become `enum ["yes",""]` (not booleans — sending `true` silently does nothing); `select` controls get their `enum` from control options; conditional controls carry *"applies when x=y"* in the description; style controls are excluded unless `include:["style"]`.

### 5.2 Writes are guarded

- Unknown setting keys are **rejected**, not ignored — a silently-dropped key renders the default and is the hardest failure for an agent to notice.
- `update-builder-widget` refuses non-FluentCart elements by `widgetType`.
- Previous `_elementor_data` is kept in `_fluent_cart_elementor_backup` before every write.
- `dry_run` on every write.
- Elementor CSS cache is flushed after each write, else the frontend serves stale styles.
- `_elementor_template_type` is set when absent (`page`→`wp-page`, `fluent-products`→`fluentcart-product-post`), matching what Elementor's own create-page writes.

### 5.3 Three ways to fill a page role

FluentCart's installer uses **blocks** for Shop and Account, **shortcodes** for Cart/Checkout/Receipt. `StorefrontRecipes` therefore records all three forms per role, and `validate-storefront` accepts any. An earlier version checked only Elementor widgets and reported a perfectly good store as broken.

### 5.4 Page assignment is half the job

Layout alone does not make a working store — cart, checkout and receipt only function when FluentCart's `*_page_id` settings point at them. `build-storefront-page` does both in one call.

## 6. Document types

| Document | Type | Use | Condition |
|---|---|---|---|
| Product template | `fluentcart-product` | one reusable layout for all products | `include/fluentcart_product` |
| Single product | `fluentcart-product-post` | a one-off layout for one product | n/a (that post only) |
| Category / brand archive | `archive` (Elementor's own) | taxonomy listings | `include/archive/fluentcart_product_archive` |

Created with **Elementor's** `manage-site-parts`, which already offers `fluentcart-product` in its enum. We do not duplicate document creation.

⚠️ **Elementor does not validate condition strings on create** — all three candidates we tried were accepted. Only the one above actually applies (verified against a live `product-categories` archive). A typo fails silently; check the URL afterwards.

Taxonomies are `product-categories` and `product-brands`.

## 7. Running it

### Setup

1. WordPress 6.9+ (Abilities API in core). Tested on 7.1.3.
2. Elementor 4.3+ and Elementor Pro (Theme Builder widgets need Pro).
3. FluentCart + the FluentCart Elementor Blocks addon.
4. **Angie** (free, wordpress.org) — Elementor's MCP needs it to expose third-party abilities, **and its external-scripts consent must be granted** at `wp-admin/admin.php?page=angie-app`. Until then every aggregated call returns a consent error.
5. FluentCart MCP on: Settings → Features & addon → MCP.
6. Elementor MCP on: Elementor → Elementor MCP → Enable, then generate the config.

Fluent Toolkit is **not** required on an Elementor site — Elementor/Angie supplies the MCP adapter.

### Connecting

The endpoint is `/wp-json/elementor/mcp/` and is **not advertised in the REST index**. Get it from the Elementor MCP admin page, which mints a per-agent application password.

### Client requirements

- `Mcp-Session-Id` header on every call after `initialize` (the id comes back as a response header). Omitting it returns `-32600`, which reads like "zero tools" if the error is swallowed.
- `Accept: application/json, text/event-stream`.

### Typical flow

```
fluent-cart-get-builder-context
fluent-cart-get-storefront-guide          (optional, full how-to)
fluent-cart-list-builder-widgets
fluent-cart-get-builder-widget-schema     before placing anything
elementor-create-page / build-composition containers and layout
fluent-cart-place-builder-widget          the FluentCart widgets
elementor-publish-document
fluent-cart-validate-storefront
```

## 8. Deploying to the test site

Skill at `.claude/skills/fluentcart-testsite-deploy/`. SSH host alias `elementor.junior.ninja` (user `elementor`, key `~/.ssh/id_rsa`).

```bash
scripts/deploy.sh fluent-cart --path app/Modules/MCP        # dry run
scripts/deploy.sh fluent-cart --path app/Modules/MCP --go
```

⚠️ **After adding any new PHP file**, regenerate the autoloader or the class will not exist:

```bash
ssh elementor.junior.ninja 'cd /var/www/elementor.junior.ninja/wp-content/plugins/fluent-cart \
  && /usr/local/bin/composer dump-autoload -o --no-interaction'
```

The site's autoloader is **classmap-authoritative**, so PSR-4 fallback is off. `AbilitiesRegistrar` guards with `class_exists()`, so a missing class means the abilities silently never appear — no error anywhere. This cost a full debugging cycle. Composer is not on `PATH`; use the full path. Editing an existing file needs no regen.

## 9. Verified

| Check | Result |
|---|---|
| 8 abilities in Elementor's catalog | ✅ 85 → 93 tools |
| Schema introspection incl. traits + repeater | ✅ 11 settings for product card |
| Place configured widget → renders | ✅ product, price, image, button |
| Unknown setting key | ✅ rejected with valid keys listed |
| Update a non-FluentCart element | ✅ refused |
| `build-storefront-page recipe:"shop"` | ✅ 10-product grid in one call |
| Theme Builder product template | ✅ live product page: gallery, variations, quantity, Buy Now |
| Archive template | ✅ applies on a live `product-categories` archive |
| `validate-storefront` false positives | ✅ none; warns correctly when a template is unpublished |
| Elementor editor reopens our documents | ✅ preview, Structure panel and widget panel intact |
| `_elementor_template_type` | ✅ set to `wp-page` on pages we create |

Test artifacts kept on the site: pages 111, 119, 121, 123; component 113; product template 125; archive template 132.

## 10. Still open

- **Gutenberg.** The `builder` parameter is already in the contract and `StorefrontRecipes` already carries each role's block name, so this is a `GutenbergDocument` sibling to `ElementorDocument` rather than a redesign.
- **Divi and Bricks**, same shape, once Gutenberg proves the second builder.
- **V4 atomic port.** Track [elementor#32950](https://github.com/orgs/elementor/discussions/32950). When the API lands, these abilities keep working — only the widget layer beneath changes. Keep widget business logic out of the Elementor adapter layer so that stays true.
- **Security review** before recommending this publicly. Two things: our transport gate `PermissionGate::transport()` only guards `/wp-json/fluent-cart/mcp`, so on the aggregated path each ability's own `permission_callback` is the **only** boundary; and recommending Angie puts `angie-execute-php`, `angie-read-file` and `angie-list-directory` on a customer's store (super-admin gated, behind consent, but it should be said out loud in docs).

## 11. Closed non-issues

- **Multi-currency** — FluentCart has a single store `currency` setting; no multi-currency exists in free or Pro. No recipe or widget takes a currency parameter. (Raised speculatively in an earlier draft; checked and withdrawn.)
- **`WP_MCP_VERSION` collision with Fluent Toolkit** — does not arise. Elementor/Angie supplies the adapter; Toolkit is not needed on an Elementor site.

## Sources

- [Introducing the Elementor MCP](https://elementor.com/blog/elementor-mcp-launch/) · [Angie](https://wordpress.org/plugins/angie/) · [Elementor V4 developer API discussion](https://github.com/orgs/elementor/discussions/32950)
- [Premium Addons MCP](https://premiumaddons.com/elementor-mcp-and-ai-abilities/) · [The Plus Addons MCP](https://theplusaddons.com/mcp-abilities/) — prior art for the abilities approach
