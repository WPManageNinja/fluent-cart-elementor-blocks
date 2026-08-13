# Template Library — Research

What was studied before/while building, and the verdicts that shaped the
implementation. (Retro-written alongside the build; every claim here was
verified against local source, not recalled from memory.)

## 1. Prior art inside our own product family

### fluent-cart-divi-modules (primary reference)

The Divi addon shipped the same feature first: 8 bundled templates under
`app/Services/TemplateLibrary/templates/<slug>/` with an index manifest,
per-template manifest + JSON payload + preview. **Verdict: mirror it
slug-for-slug** — same storage shape, same 8 pages — so the two addons stay
maintainable as a pair and template designs can be ported between builders.

Ported with translation rather than copied:

- Divi layouts carry inline email-style HTML; Elementor merchants expect
  **native Style/Advanced controls** — layouts were rebuilt control-first
  (see developer-guide authoring rules).
- Divi's Receipt module previews via a bundled static sample
  (`receipt-sample.php` equivalent) — we adopted the same sample as a
  *fallback* but upgraded the primary preview (see §3).
- Divi's Customer Dashboard VB preview is a 622-line hand-built JS mock with
  its own class names (`customer-dashboard-editor.js`) — necessity for a
  client-rendered builder, not a choice. We did NOT port it (see §4).

### fluent-cart core

- Receipt/thank-you (`ThankYouRender`), cart (`CartRenderer`), shop
  (`ShopAppRenderer`) are **server-rendered** — canvas previews work for free.
- Customer profile is a **Vue SPA** (skeleton shell + client boot) — canvas
  previews are structurally impossible without core changes.
- Short codes: core owns a canonical, filterable registry
  (`EditorShortCodeHelper`, `fluent_cart/editor_shortcodes`) and parser
  (`ShortcodeTemplateBuilder`) used by email templates — reused wholesale for
  receipt custom texts instead of inventing a second vocabulary.

## 2. Template distribution models compared

| Model | Example | Verdict |
|---|---|---|
| **Bundled in plugin, seeded into native library** | (chosen) | Zero infra, zero new UI, updates ride releases via version gate. |
| CDN remote manifest + auto-seed | early phase-2 design | Needs hosted catalog + publishing pipeline + trust boundary for remote JSON into the DB. Dropped 2026-08-12 by product decision; extension filters kept as escape hatch. |
| In-editor browsing modal + on-demand import | Elementor Kit Library, Envato Elements | Full "template store" UX — a build the size of the library itself. Only worth it with a large/rotating catalog, which bundled-only makes moot. |

## 3. Editor-preview strategies for order-dependent widgets

Studied **Elementor Pro's WooCommerce Purchase Summary** widget (the direct
analog of our receipt) as the native convention:

| Strategy | Used by | Notes |
|---|---|---|
| **Preview with a real order (latest / by ID), read-only** | Elementor Pro Purchase Summary → adopted for our Receipt | Real data, zero markup drift, per-store currency. Must bypass side effects (our shortcode path flips `sales_recorded`) — render via `ThankYouRender` directly. |
| Static sample mirroring core markup | Divi Receipt → our fallback for empty stores | Faithful only while hand-copied markup matches core; acceptable for the receipt's 80 lines, kept class-identical so toggles/style controls behave. |
| Hand-built mock with own classes | Divi Customer Dashboard | Doesn't track core design; 622 lines of drift surface. Forced by Divi 5's client-rendered VB. |
| Frozen skeleton + explanatory badge | our Customer Dashboard | Honest floor when the core UI is a client-rendered SPA. |
| Editor-only iframe of the real page | assessed, not shipped | Best fidelity/effort for SPA cores (real app boots in a normal page load); candidate upgrade. |

Also studied Pro's control conventions for the widgets we extended: section
show/hide switchers, "Preview order with" select, grouped typography sections,
CSS-variable selectors — mirrored where they fit.

## 4. Elementor platform findings (bisection-verified, 4.2.2)

These shaped multiple implementation decisions; full detail in
developer-guide.md:

1. `get_wp_editor_config()` strips ALL priority-10 `mce_buttons` /
   `mce_external_plugins` callbacks → any TinyMCE extension must register at
   priority 11+.
2. Optimized Control Loading buckets selector-carrying controls into
   `style_controls` (invisible to `get_controls()`) — several "missing
   control" investigations were this.
3. SELECT2 + `render_type: 'ui'` is silently dropped from the stack.
4. Panel WYSIWYGs are clones of one base editor config — one registration
   point for toolbar extensions, guarded per-widget in JS.
5. Editor AJAX re-renders never receive mid-render enqueues or refreshed
   stylesheets → `get_style_depends()` handles + inline critical CSS.
6. Container spacing renders as CSS variables (`--padding-top`), not
   `padding:` — affects how generated CSS must be verified.

## 5. Renderer-markup audits

Every widget style selector was verified against live rendered output before
shipping (grep/render, never guessed), which surfaced real discrepancies:

- Stock widget's existing controls targeted `.fct-stock-status` — an element
  core never outputs (dead since shipping). Real: `.fct-stock-badge` +
  `fct_status_badge_*` state classes.
- Product-card buttons split across two classes: `fct-product-view-button`
  (View Options / Buy Now) vs `fluent-cart-add-to-cart-button` (Add To Cart).
- `ThankYouRender` inconsistency: Ship To heading is classed, Bill To's is a
  bare `<h5>` (core ticket candidate).
- Cart and dashboard text colors are CSS-variable themed — controls must set
  the variables, not rely on inheritance.
