# FluentCart + Page Builder MCP — Research

> **Status:** Research + **live verification complete**. No decisions locked, no code.
> **Date:** 7 October 2026
> **Question:** Can a store owner connect their AI tool and have it build a FluentCart storefront with Elementor (and later Gutenberg/Divi/Bricks), with FluentCart's MCP knowing our widgets and cooperating with Elementor's own MCP?
> **Short answer:** **The plumbing already exists and is verified working.** The missing piece is not a transport — it is *knowledge*. See §0 for what was proven on a live site.

---

## 0. Verified on a live site — 7 Oct 2026

Test rig: **elementor.junior.ninja** (xCloud, WP 6.9+, PHP 8.3, no cache). Installed: FluentCart 1.7.0 + Pro (unlicensed), FluentCart Elementor Blocks 1.1.0, Elementor 4.3.4, Elementor Pro 4.3.1, Angie 1.1.19. FluentCart MCP enabled; Angie external-scripts consent granted. Auth: WP application password.

### Result: World A. Aggregation works end to end.

| Check | Result |
|---|---|
| FluentCart abilities in core's registry | **33/33 registered.** Site total 57 (fluent-cart 33, angie 21, core 3). Category `fluent-cart` registered alongside `elementor`, `code-snippets`, `super-admin`. No ordering bug, no collision. |
| FluentCart's own server `/wp-json/fluent-cart/mcp` | **Works.** `FluentCart MCP Server 1.7.0`, 33 tools listed. |
| Aggregator `/wp-json/mcp/mcp-adapter-default-server` | **Works.** Exposes **3** tools, not 57. |
| `discover-abilities` through the aggregator | **36 entries** — core 3 + **fluent-cart 33**. Angie's own 21 are **not** exposed. |
| `get-ability-info` on a FluentCart ability | **Works.** Returns our full description + schema. |
| `execute-ability` → `fluent-cart/get-store-context` | **Works.** Returned real store data and the caller's full FluentCart permission list. |

**No FluentCart code is required for the commerce half.** It already works today on a correctly configured site.

### CORRECTION — there are two different servers, and the one that matters is not the adapter's

An earlier pass of this document concluded that an AI tool sees only 3 meta-tools. **That is true of the adapter default server, but that is not the server Elementor connects clients to.**

Elementor's real endpoint is **`https://<site>/wp-json/elementor/mcp/`**. It is **not advertised in the REST index** (`/wp-json/` does not list it) — the only way to obtain it is the *Elementor → Elementor MCP* admin page, which mints a per-agent application password and emits a ready `mcpServers` block. Treat the admin UI as the source of truth for the endpoint; probing REST will not find it.

**That server exposes 85 tools as a flat catalog:**

| Group | Count | Notes |
|---|---|---|
| `elementor-*` | 22 | page building, site parts, classes/variables, components, publish, preview |
| **`fluent-cart-*`** | **33** | **ours, natively, with our full descriptions intact** |
| `angie-*` | 25 | kit/snippets/dev-mode, plus `execute-php`, `read-file`, `list-directory` |
| `core-*` | 3 | site/user/environment info |
| `mcp-adapter-*` | 3 | the discover/info/execute meta-tools |

So FluentCart's tools **are** first-class in Elementor's catalog. `fluent-cart-get-store-context` appears with its "START HERE — call once per session" description verbatim. The progressive-disclosure finding below still describes the adapter default server accurately, but it is the *secondary* path; it is not what customers will connect to.

### The shape that matters on the adapter default server: progressive disclosure

An AI tool connected to the WordPress/Elementor side does **not** see `fluent-cart-list-orders` in its tool list. It sees three generic meta-tools:

```
mcp-adapter-discover-abilities    {}                              → 36 entries, name+label+description only
mcp-adapter-get-ability-info      {ability_name}                  → full schema for one
mcp-adapter-execute-ability       {ability_name, parameters}      → runs it
```

Measured cost of the discovery step: **16,528 chars ≈ 4,100 tokens** for all 36, carrying only `name`, `label`, `description`. Full schemas come on demand.

This is the same summary-first / detail-on-demand principle as §3.1 of `plan.md`, applied one level up — and it resolves the token-budget worry in §8: two servers do **not** mean two full catalogs.

### Consequences we have to design around

1. **Our ability `description` IS the discovery surface.** At selection time the agent sees only name + label + description. Input schemas, annotations and our careful `include[]` design are invisible until it has already committed to calling `get-ability-info`. Descriptions must be self-selling in one or two sentences.
2. **FluentCart is 33 of 36 discoverable abilities — 92% of the surface.** On a FluentCart site, we effectively *are* the aggregated catalog. That is leverage and responsibility both.
3. **Angie withholds its own 21 abilities from discovery.** Its raw-PHP / file-access abilities stay internal to its own agent. Worth noting before assuming "everything registered is exposed" — Elementor evidently applies its own filter.
4. **Our transport gate is bypassed on the aggregator path.** `PermissionGate::transport()` guards `/fluent-cart/mcp` only. Through the aggregator, enforcement falls entirely to each ability's `permission_callback`. That still holds (they all use `PermissionManager`), and the kill switch still works because abilities are not registered at all when MCP is off — but the two paths are **not** equivalently gated, and any future ability that leans on the transport gate for safety would be exposed. Treat per-ability `permission_callback` as the only real boundary.
5. **Write abilities are aggregated too.** `fluent-cart/refund-order` is discoverable and executable through Elementor's connection, with our `dry_run` → `confirm_token` protocol intact. Correct, but it means enabling FluentCart MCP grants refund capability to any AI tool connected on the Elementor side, subject to the caller's caps.
6. **Angie's consent gate is a required setup step.** Until external-scripts consent is granted at `wp-admin/admin.php?page=angie-app`, every aggregated call returns a consent error. "Install Angie" is not the whole instruction for customers.

### Client-correctness details (for any snippet we ship)

- Servers require an **`Mcp-Session-Id` header** on every call after `initialize`; the id comes back as a response header on `initialize`. Omitting it returns `-32600 Invalid Request`.
- Tool names are **sanitized**: `fluent-cart/get-order` → `fluent-cart-get-order`. Ability names keep the slash; tool names do not. Docs must not mix the two forms.
- `Accept: application/json, text/event-stream` is required.

### The gap, measured precisely

`elementor-list-widget-schemas` returns the full set of widget types Elementor MCP can configure. **53 types, and every one is Elementor's own V4 atomic widget:**

```
e-accordion(+6 parts)  e-background-video(+4)  e-button  e-collection-loop(+3)
e-div-block  e-divider  e-flexbox  e-form(+12 field types)  e-grid  e-heading
e-image  e-pagination(+2)  e-paragraph  e-self-hosted-video  e-svg
e-tab(+4)  e-youtube  nav-menu
theme-archive-title  theme-post-content  theme-post-excerpt
theme-post-featured-image  theme-post-title
```

**Zero FluentCart widgets. Not one.** FluentCart appears in that entire 121KB payload exactly once — as the string `fluent-products`, an enum value in the `source` list of the collection-loop widget.

So the current state is precisely:

| Elementor MCP knows FluentCart as… | …but cannot |
|---|---|
| a **data source** — `fluent-products` is a valid loop source | place `fluent_cart_product_card`, `fluent_cart_add_to_cart`, `fluentcart_product_title`, or any of our widgets |
| a **document type** — `manage-site-parts` lists `fluentcart-product` among its Theme Builder types | know what those widgets need, which require product context, or which are Pro |
| 33 **commerce tools** — read orders, customers, products, reports | compose a storefront page set (shop / product / cart / checkout / dashboard) |

An agent can therefore build a page that *lists* FluentCart products through Elementor's own loop widget — but it cannot build a real storefront with our buy buttons, variation selectors, cart or checkout.

### The decisive test — placement works, configuration is architecturally blocked

Run live against a real draft page (post 111) on the test site, with an Elementor-atomic widget as the control:

| Step | Call | Result |
|---|---|---|
| Create page | `elementor-create-page` | ✅ draft created |
| Place `e-heading` (control) | `elementor-build-composition` | ✅ persisted |
| **Place `fluent_cart_product_card`** | `elementor-build-composition` | ✅ **persisted** |
| Read back | `elementor-get-page-structure` | ✅ `{"elType":"widget","widgetType":"fluent_cart_product_card","title":"Product Card"}` |
| **Get its schema** | `elementor-get-widget-schema` | ❌ **`Unknown widget type: fluent_cart_product_card`** |
| **Configure it** | `elementor-manage-elements` update | ❌ **`elementor_v3_not_supported` — "Legacy V3 element cannot be modified through this MCP. Edit V3 elements directly in the Elementor editor."** |

Elementor resolved our widget to its real registration — the `title: "Product Card"` came from our own widget class, not from the XML. So **`build-composition` will place any registered widget type, classic or atomic.**

But that is where it stops:

- **No schema.** `get-widget-schema` does not know our widget; `list-widget-schemas` returns only the 53 atomic types. The agent has no way to learn what settings `fluent_cart_product_card` accepts.
- **No configuration, by explicit design.** `manage-elements` refuses with a dedicated error code, `elementor_v3_not_supported`. This is not an oversight or a missing name — Elementor deliberately declines to mutate classic (V3) elements through MCP.

**Net effect: an AI can drop a FluentCart widget onto a page, but it lands with default/empty settings and cannot be told which product to show or what to display.** A placed-but-unconfigurable widget is close to useless for a real storefront.

### What this means for the plan — the manifest alone will not fix Elementor

This invalidates the optimistic reading of §5 Option 1 *for Elementor specifically*. Publishing a widget manifest would let an agent discover and place our widgets, and it would still be unable to configure a single one, because the block is on Elementor's side and is architectural, not informational.

Four options were considered. **Two are eliminated by testing, one is unavailable, one works today.**

#### ❌ Elementor components — eliminated empirically

I suggested this first; it is wrong. Tested on the live site:

```
create component containing fluent_cart_product_card
  → "Components require atomic elements only. Remove widgets to create this component."

expose overridable_prop from fluent_cart_product_card
  → "Invalid element: Element type widget with widget type
     fluent_cart_product_card is not an atomic element/widget."

control: same calls with e-heading
  → ✅ component_id 113 created
```

Two further points settle it regardless: components are **per-site user content** (WordPress documents created through the editor or MCP), not a plugin distribution channel — there is nothing to "ship" in a plugin and nothing for Elementor to approve. And `create`/`rename`/`archive` require an **active Elementor Pro licence**.

#### ❌ Port our widgets to the V4 atomic API — not currently possible

Not a question of effort. **Elementor has not released a third-party API for atomic widgets, and explicitly advises against integrating with them.** Their position, maintained across the V4 beta and still current through 2026: they will not release an API soon, cannot offer documentation while the infrastructure is changing, and will announce it extensively when ready ([discussion #32950](https://github.com/orgs/elementor/discussions/32950), [#33421](https://github.com/orgs/elementor/discussions/33421)).

Anything built now would be reverse-engineered against an unstable, undocumented internal API — on the critical path of a commerce plugin. **This is blocked on Elementor, not on us.** We cannot resolve it with our own engineering.

#### ❌ Write `_elementor_data` ourselves — rejected

Fights Elementor's versioning and duplicates what their MCP already does well, against an internal format that is actively changing.

#### ❌ Shortcode bridge — API calls succeed, rendering fails

An earlier revision of this document called this "verified working". **That was wrong, and the error is worth naming: I had verified that the tool calls returned `status: ok`, not that the page rendered.** Rendered and screenshotted, it fails.

`shortcode` *is* a first-class Elementor dynamic tag, and every call succeeds:

```
place   e-paragraph bound to {name:"shortcode", settings:{shortcode:"[fluent_cart_products per_page=6]"}}  ✅
update  swap the shortcode via manage-elements                                                             ✅ status ok
style   padding / background via manage-elements                                                           ✅ status ok
publish                                                                                                    ✅
```

**But the published page renders the product grid as a wall of unstyled plain text** — titles, prices and "View Options" run together in a paragraph. No grid, no cards, no images, no buttons, no FluentCart CSS.

The cause is in the atomic schema itself. Text props explicitly state: *"May contain inline HTML … limited to these tags: `<b>, <strong>, <sup>, <sub>, <s>, <em>, <i>, <u>, <a>, <del>, <span>, <br>`. **Any other tag is stripped on save**."* A product grid is `<div>`/`<ul>`/`<img>` markup, so it is stripped down to its text nodes.

And there is no escape hatch: of the 53 atomic widget types, **none** accepts raw HTML, a shortcode or an embed (`/html|shortcode|code|embed|raw/` matches nothing). The dynamic tag is built for *text values* — a price, a count, a post field — not for component markup.

The bridge is therefore usable only for **single text values** pulled from FluentCart, never for UI.

#### What actually renders: the V3 widget, placed but empty

The one thing that does render correctly is the widget MCP cannot configure. On post 111, `fluent_cart_product_card` was placed by MCP, published, and renders as a **properly styled FluentCart container** — our CSS loads, the component is real. It displays **"Product not found"**, because `product_id` was never set and `manage-elements` refuses to set it (`elementor_v3_not_supported`).

That single screenshot is the whole problem in one frame: **the widget works, the styling works, placement works — and the agent cannot tell it which product to show.**

### ✅ THE ANSWER: register our own abilities that write `_elementor_data`

**A previous revision of this document concluded "there is no way". That was wrong.** The error was scoping the question to *"what can Elementor's tools do to our widgets"* instead of *"what can our own code do"*. There is a well-established path, already shipped by at least two major Elementor addon vendors.

#### How the addon vendors actually do it

- **Premium Addons for Elementor** — ships **40 AI abilities**. Their own docs state: *"since every ability is registered as public through the WordPress Abilities API, any MCP server on your site … can call them directly. No Premium Addons MCP setup required."* Their ability list includes `get-widget-schema`, `get-element-settings`, `get-page-structure`, `list-available-elements`, `check-elementor-element`, `detect-atomic-support` — and they advertise **"Works with Third-Party Plugins, Not Only Premium Addons & Elementor Core Widgets"**.
- **The Plus Addons for Elementor** — ships **163 abilities**, *"cover every widget, from headings and buttons to WooCommerce, Lottie, and dynamic content"*, with `get-theplus-widget-schema` giving the agent the full Elementor control schema before it builds. Output is *"real Elementor elements … fully editable in the panel afterwards"*.

Neither waits for Elementor's V4 API. Neither uses components. **They register their own WordPress abilities that read and write Elementor's document data directly.** Because the abilities are public on the Abilities API, they surface inside Elementor's own MCP catalog automatically — exactly as FluentCart's 33 already do (§0).

#### Proven on our own site

Verified end to end on post 111, the same page that previously showed "Product not found":

```
GET  /wp/v2/pages/111?context=edit
      → meta._elementor_data is exposed and readable           ✅
      → fluent_cart_product_card node present, settings: []

POST /wp/v2/pages/111
      meta._elementor_data with settings:
        { query_type:"custom", product_id:105,
          price_format:"starts_from", card_width:320 }          ✅ 200

GET  /wp/v2/pages/111?context=edit  → settings persisted        ✅
DELETE /elementor/v1/cache                                      ✅ 200
```

**The published page now renders the complete product card** — product image, title "Air Max 1 running shoe", price "From ৳12.00", full FluentCart styling. Screenshot in the research thread.

So the V3 restriction is only on *Elementor's* `manage-elements` tool. It is **not** a restriction on the data format, the renderer, or WordPress. Our widgets work perfectly when something writes their settings — and we are allowed to be that something.

#### What FluentCart should build

A set of FluentCart-owned **builder abilities**, registered the same way our 33 commerce abilities already are, appearing automatically in Elementor's MCP catalog:

| Ability | Purpose |
|---|---|
| `fluent-cart/list-builder-widgets` | Every FluentCart widget for the active builder: name, label, what it renders, product-context requirement, free/Pro gate |
| `fluent-cart/get-builder-widget-schema` | One widget's settings contract — keys, types, defaults, enums (e.g. `query_type`, `product_id`, `price_format`, `card_width`) |
| `fluent-cart/place-builder-widget` | Insert a configured FluentCart widget into an Elementor document, writing `_elementor_data` |
| `fluent-cart/update-builder-widget` | Change settings on an already-placed FluentCart widget |
| `fluent-cart/build-storefront-page` | Compose a whole page from a recipe — shop, product template, cart, checkout, dashboard |
| `fluent-cart/validate-storefront` | Post-build audit: pages assigned in settings, Theme Builder conditions set, no widgets pointing at deleted products |

The agent then uses **Elementor's** tools for layout, styling, classes and variables, and **FluentCart's** tools for FluentCart widgets. Each side owns what it knows. That is the cooperation model the original brief asked for, and it is available today.

#### Constraints to respect

- Settings keys must match each widget's real control names (verified: `query_type`, `product_id`, `price_format`, `card_width` for the product card — mirrored from `ProductCardShortCode` / `ProductCardBlockEditor`). The schema ability must be generated from the widget classes, not hand-maintained.
- Flush Elementor's CSS cache after writing (`DELETE /elementor/v1/cache`), or the frontend may serve stale styles.
- Writes must go through FluentCart's permission model, and should respect Elementor's draft-first convention rather than publishing silently.
- When Elementor's V4 third-party API eventually lands ([#32950](https://github.com/orgs/elementor/discussions/32950)), these abilities keep working; only the widget layer beneath them changes.

### Revised recommendation

1. **Build the FluentCart builder abilities.** This is the real §5 manifest — with write abilities, not just read. Proven feasible, entirely within our control, no Elementor approval, no V4 API, no components.
2. **Still do Gutenberg too** — the same ability set should be builder-parameterised, and Gutenberg is simpler. But Elementor is no longer blocked and should not be deprioritised.
3. **Generate the widget schemas from the widget classes** (the builder-parity skill's references are the map), so the manifest cannot drift from the code.

**Gutenberg has none of this problem** and is now clearly the right first target: core blocks serialize to markup an agent can write directly, with no V3/V4 divide and no dependency on Elementor, Angie or Elementor Pro.

### Still true and still valuable

The commerce half needs nothing. All 33 FluentCart tools are first-class in Elementor's catalog with our descriptions intact, and `execute` works. An agent connected to Elementor's MCP can already research the store, read orders and products, and run operator tasks — today, with zero FluentCart code. Only the *building* half is blocked.

### A pattern worth copying: MCP resources

Elementor exposes **resources** alongside tools — markdown guides fetched on demand via `elementor-list-resources` / `elementor-read-resource`:

- `elementor://style/best-practices` — typography, color, spacing, hierarchy
- `elementor://wordpress/best-practices` — single template vs N pages, condition scoping, Post Content placement, dynamic tags
- `elementor://variables/tools/manage-global-variable-guide` — per-tool deep guides

This is exactly the delivery mechanism §5's `get-storefront-recipe` wants, and the MCP adapter supports resources already (`RegisterAbilityAsMcpResource`). **FluentCart should ship storefront recipes as MCP resources, not only as tools** — the agent pulls them when relevant instead of carrying them in every catalog.

### Security surface worth a deliberate decision

The same catalog that carries our 33 tools also carries `angie-execute-php` ("Execute PHP code on the server (super admin only)"), `angie-read-file` and `angie-list-directory`. Recommending Angie to customers means recommending that surface onto their store. It is gated to super admin and behind the consent screen, but Support and docs must say so plainly rather than discover it later.

### Not external MCP servers (ruled out)

- `elementor/v1/mcp-proxy` — internal nonce-protected RPC (`tool` + `input`) for the editor, not an MCP endpoint.
- `elementor-mcp-composer/v1.0.19/*` — rejects application passwords with `invalid_nonce`; browser/cookie only.

The adapter default server is the single external entry point. Fluent Toolkit is **not** needed on an Elementor site — Elementor/Angie supplies the adapter, and the feared `WP_MCP_VERSION` collision does not arise.

---

## 1. The landscape (verified, with dates)

### Elementor MCP — official, and very new

| Fact | Detail |
|---|---|
| Shipped | Elementor **4.3.0**, 22 Sep 2026. Launch post 29 Sep 2026. Beta preceded it (~16 Sep 2026). |
| Admin surface | **Elementor → Elementor MCP** page. Admin-only, per-site. |
| Connection | Pick a client → generate prompt → paste. Elementor **auto-creates the Application Password** and embeds it in the prompt. Manual config also offered. |
| Clients listed | Claude Code, Claude Desktop, Codex, Cursor, "Other". |
| Output | **Always a draft**, never published. Returns real, editable Elementor structure. |
| Concurrency | If the Editor and an AI tool touch the same page, Elementor detects the conflict and offers refresh/discard. |
| Cost | Connecting is free. Basic generation free; **Pro features require a Pro plan**. Client-side AI cost is the user's. |

**What it can do:** build atomic pages/layouts; edit or redesign existing ones; create/apply global classes + variables, states, responsive breakpoints; create and manage Theme Builder parts (headers, footers, archives, popups) with display conditions; place and fill components; connect dynamic content (post fields, custom fields, taxonomies).

### Angie — the piece that matters most to us

Free on wordpress.org ("Angie – Agentic AI"), Elementor's in-WordPress agent. Registers **~31 abilities** spanning raw server file/PHP access (super admin), full CRUD over its code-snippet / custom-Elementor-widget / Gutenberg-block system, and Elementor Site Settings kit management.

Three Elementor MCP capabilities are explicitly marked **"Requires Angie to be installed"**:

1. Create custom widgets and Admin experiences
2. Content management, debugging and maintenance (bulk updates across hundreds of records)
3. **"Act as a single WordPress entry point — connect the Elementor MCP, and your AI tool gets access to every ability registered to the WordPress Abilities API, including WooCommerce, Yoast, WPForms, Jetpack, ACF, and more."**

Angie also ships an SDK (`@elementor/angie-sdk`, [GitHub](https://github.com/elementor/angie-sdk/)) for registering your own MCP capabilities into it, with a demo plugin.

---

## 2. The central finding — we may already be plugged in

FluentCart registers every MCP tool through **core's Abilities API** (`wp_register_ability`), under category `fluent-cart`, with `meta.show_in_rest => true` and `meta.mcp.public => true` (`AbilitiesRegistrar::registerAbility()`).

That is *exactly* the registry Elementor describes aggregating. So on a site running **FluentCart + Elementor 4.3 + Angie**, our 33 commerce tools may already appear inside the Elementor MCP session — no FluentCart code required.

**This single question splits the project into two very different shapes:**

- **World A — aggregation works.** We write no transport code. The work becomes *teaching the agent to build with our widgets* (§5), plus making sure our abilities are well-described enough to be picked correctly out of a very crowded catalog.
- **World B — it doesn't work, or is gated.** (Angie not installed; Pro-gated; Elementor filters to its own namespace; or our transport gate / `mcp_enabled: off` default blocks it.) Then we need our own bridge, and §5 Option 3 gains weight.

**Verify empirically before designing anything.** Test plan in §7.

---

## 3. What FluentCart already has

### MCP: 33 tools, all *operator*, zero *builder*

```
get-store-context  list-reference-data  get-search-schema  query-sources
list-orders  get-order  query-orders  change-order-status  refund-order
  add-order-note  get-order-activity  list-transactions  get-upcoming-payments
list-customers  get-customer  query-customers  upsert-customer
list-products  get-product  query-products  get-inventory  get-product-financials
list-subscriptions  get-subscription  query-subscriptions  change-subscription-status
list-coupons  manage-coupon  apply-labels
get-sales-report  get-sales-trend  get-top-products  get-refund-report
```

Not one of these creates a page, places a widget, or knows a builder exists. The existing MCP runs a store; it cannot build a storefront. **That is the gap.**

### Page-builder surface (the asset we'd be exposing)

| Builder | Where | Notes |
|---|---|---|
| **Gutenberg** (source of truth) | `fluent-cart/app/Hooks/Handlers/BlockEditors/` (~35 block editors) + `resources/admin/BlockEditor/` | InnerBlocks families: Cart, Checkout, ShopApp, ProductCarousel, ProductReviewList, MediaCarousel, RelatedProduct. Renderers in `app/Services/Renderer/`. |
| **Elementor** | separate addon `fluent-cart-elementor-blocks` → `app/Modules/Integrations/Elementor/` (`Widgets/`, `Widgets/ThemeBuilder/`, `Controls/`, `Support/`) | **Not in this checkout.** Naming: general `fluent_cart_<name>`, Theme Builder/product-context `fluentcart_<name>`. Categories: `fluent-cart`, `fluent-cart-product` (Theme Builder docs only). Theme Builder integration needs **Elementor Pro**. |
| **Divi** | `fluent-cart-divi-blocks` (present) | `app/Modules/{AddToCart,BuyNow,ProductCarousel,MediaCarousel,CustomerDashboardButton}` + `DynamicContent/DynamicContentRegistry.php`. |
| **Bricks** | addon `fluent-cart-bricks-blocks` + core `app/Modules/Templating/Bricks/` | |
| **Shortcodes** | `fluent_cart_order_review`, `fluent_cart_receipt`, `fluent_cart_product_header`, `fluent_cart_related_products`, `fluent_cart_show_coupon`, plus ShopApp/Cart/PricingTable/ProductCard/CustomerLogin/Registration/ProductReviews classes | The universal fallback for any builder. |

### Already-written knowledge we can mine

`fluent-cart/.claude/skills/fluentcart-builder-parity/` is, in effect, a half-built machine-readable widget spec: per-builder anatomy (`references/builders/{gutenberg,elementor,divi,bricks}.md`), naming/slug rules, registration checklists, render flow, Pro gating, control-type equivalence tables, and a parity-matrix vocabulary. It was written to port blocks between builders — but it is the same knowledge an agent needs to *compose* them. Strong candidate to generate the manifest from (or at minimum to validate it against).

---

## 4. Why Elementor's MCP alone can't do this

Elementor MCP knows how to place and style *Elementor* elements. It has no idea that:

- `fluent_cart_product_card` exists, or that its Theme Builder sibling is `fluentcart_product_title`
- certain widgets only function inside a **product context** (Theme Builder document) and render a placeholder anywhere else
- Theme Builder widgets need **Elementor Pro**, and some controls need **FluentCart Pro** (`App::isProActive()`, enforced per-control/per-render, not at registration)
- a working store needs a *set* of pages wired together — shop, product template, cart, checkout, customer dashboard, receipt — not one pretty landing page
- which products/variations actually exist to point a widget at

Conversely, FluentCart MCP knows all of that and cannot place a single element. **The two servers are complements, not competitors.** The product idea is sound; the design question is only *where the knowledge lives*.

---

## 5. Design options

### Option 1 — Capability manifest (recommended)

FluentCart exposes a small set of **read-only, builder-aware** tools. Elementor MCP does all the writing.

| Proposed tool | Purpose |
|---|---|
| `get-builder-context` | Which builders are installed/active (Elementor + version + Pro, Gutenberg, Divi, Bricks), which FluentCart builder addons are active, whether Angie/Elementor MCP is present, FluentCart Pro state, and which storefront pages already exist. The builder sibling of `get-store-context`. |
| `list-builder-widgets` | Every FluentCart element for a given builder: widget name, label, category, what it renders, product-context requirement, free/Pro gate, Gutenberg/Divi/Bricks equivalent. |
| `get-builder-widget-schema` | One widget's settings contract — keys, types, defaults, required, enum values, responsive storage — in the shape that builder's MCP expects to receive. |
| `get-storefront-recipe` | Opinionated page compositions ("product template", "shop archive", "checkout page", "customer dashboard"): which widgets, in what order, with what settings, and what to do after (assign the Theme Builder condition, set the FluentCart page setting). |
| `validate-storefront` | Post-build audit: are cart/checkout/receipt pages set in FluentCart settings? Does the product template have a Theme Builder condition? Any widget pointing at a deleted product? |

**Why this wins:** FluentCart stays the authority on FluentCart and owns nothing of Elementor's internals. Elementor's structure format can churn without breaking us. It is a handful of read-only tools — cheap, safe, no new write surface, no new auth story. And the same manifest serves **every** builder, so Gutenberg/Divi/Bricks come nearly free.

**Risk:** success depends on the agent actually chaining two servers well. Mitigate with a very directive `get-builder-context` (an explicit "START HERE → then call Elementor's X" workflow string), mirroring how `get-store-context` already works.

### Option 2 — FluentCart writes Elementor JSON itself

Generate `_elementor_data` post meta directly.

Full control and works without Angie, but: we would be reimplementing what Elementor MCP already does well, against an undocumented internal structure, two weeks after it shipped, with Elementor's atomic-widgets/global-classes format actively changing. We'd also inherit draft/publish semantics, conflict detection, and CSS regeneration. **Not recommended as the primary path** — though a narrow version (scaffold *only* FluentCart widgets into an existing page) is a reasonable World-B fallback.

### Option 3 — Angie SDK integration

Register FluentCart capabilities into Angie via `@elementor/angie-sdk`. Puts us inside Elementor's own agent loop and is the most "native" answer. Costs: hard dependency on a third-party free plugin, a JS/TS surface unlike anything else we ship, and Angie is young. Worth a spike, not a v1 commitment.

### Option 4 — Builder-agnostic from day one

Not an alternative so much as a constraint on Option 1: make the manifest builder-parameterised (`builder: elementor|gutenberg|divi|bricks`) rather than Elementor-shaped. Gutenberg is the easiest win — core blocks are already serialisable as markup, so an agent could write a complete Gutenberg storefront with *no* second MCP server at all. **Gutenberg may be the better first target than Elementor**, precisely because it has no dependency on Angie, Elementor Pro, or anyone else's MCP.

---

## 6. Recommendation

1. **Run the §7 verification first.** The aggregation answer changes the design.
2. Build **Option 1** as a new `Tools/BuilderTools.php` in the existing MCP module — read-only, gated on `dashboard_stats/view` (or a new `store/settings` read), generated/validated against the builder-parity references.
3. **Target Gutenberg first**, Elementor second. Gutenberg proves the manifest with zero third-party dependencies; Elementor then exercises the two-server handoff.
4. Keep every write in Elementor's hands. We stay the knowledge layer.
5. Treat "FluentCart + Elementor, built by AI" as the *marketing* story and the manifest as the *engineering* story — the manifest is what makes Divi and Bricks nearly free later.

---

## 7. Verification plan (do this before designing)

On a scratch site with FluentCart + Elementor 4.3 + Angie + `fluent-cart-elementor-blocks`:

1. Enable FluentCart MCP (Settings → Features & addon → MCP). Enable Elementor MCP.
2. Connect Claude Code to **Elementor's** endpoint only. Ask it to list its tools. **Do FluentCart's 33 abilities appear?**
3. If yes: does calling one work, and whose permission model applies — ours (`PermissionGate`) or Elementor's app password/admin check?
4. Uninstall Angie, repeat step 2. Confirms whether Angie is genuinely the aggregation gate.
5. Connect **both** servers at once. Measure the combined tool-catalog token cost and watch for name collisions and mis-selection.
6. Ask it to build a product page with FluentCart widgets, giving it nothing but the two servers. **Record exactly where it fails** — that failure list is the real spec for §5's tools.
7. Check whether Elementor MCP can place a *third-party* widget at all, or only Elementor's own.

---

## 8. Risks and open questions

- **Immaturity.** Elementor MCP is ~2 weeks old. Expect API churn; don't couple tightly.
- **Angie's permissions.** Angie takes raw server file/PHP access for super admins. If our recommendation ends up "install Angie", Support and docs need a clear-eyed security note. Worth a security read before we recommend it to customers.
- **Double Pro gating.** Elementor Pro (Theme Builder) × Elementor plan (MCP Pro features) × FluentCart Pro. A matrix we must state plainly or Support will carry it.
- **Draft-only.** Nothing Elementor MCP generates is live. Good for safety; set expectations in any marketing.
- **Token budget.** Two full catalogs in one context window. Our 33 tools are already substantial; a builder manifest must be summary-first, consistent with the existing §7 token-efficiency rules in `plan.md`.
- **Addon not in this checkout.** `fluent-cart-elementor-blocks` is a sibling repo and absent here. Any widget inventory must be generated from the live addon, not from the parity references (the skill itself warns: "do not assume a `refs/` snapshot, old plan, or documented widget count reflects current code").
- **Unverified:** Elementor MCP's actual tool names and schemas. Not published in the material reviewed — needs reading off a live install (step 2 above).

---

## Sources

- [Introducing the Elementor MCP](https://elementor.com/blog/elementor-mcp-launch/) — Elementor, 29 Sep 2026
- [Elementor MCP: Connect AI Tools to WordPress (Beta)](https://elementor.com/blog/elementor-mcp-beta/)
- [How to Connect Elementor to an AI Tool Using MCP](https://elementor.com/help/how-to-connect-elementor-to-an-ai-tool-using-mcp/)
- [MCP for WordPress: what each option is and what to install](https://elementor.com/blog/mcp-for-wordpress-what-each-option-is-and-what-to-install/)
- [Angie – Agentic AI](https://wordpress.org/plugins/angie/) — wordpress.org
- [Angie abilities directory](https://easymcpai.com/abilities-directory/angie) — third-party ability inventory
- [Angie SDK](https://github.com/elementor/angie-sdk/)
- [What Is the Elementor MCP? Official 4.3 Setup Guide](https://theplusaddons.com/blog/what-is-the-elementor-mcp/)
- [Elementor Build With Prompts vs MCP](https://theplusaddons.com/blog/elementor-build-with-prompts-vs-mcp/)
- Internal: `fluent-cart/app/Modules/MCP/`, `fluent-cart/.claude/skills/fluentcart-builder-parity/`, `fluent-cart-divi-blocks/`, `fluent-toolkit/Classes/McpManager.php`
