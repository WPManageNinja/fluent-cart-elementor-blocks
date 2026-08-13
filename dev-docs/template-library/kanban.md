# Template Library — Kanban (as built)

Stacked PR train, merges bottom-up. Base of the stack: `master`.

## Done (awaiting merge)

| PR | Ticket | State |
|---|---|---|
| #32 | Storage scaffold (`TemplateLibrary/templates/` structure + README) | approved |
| #33 | `TemplateManifest` loader (validation, normalize, `complete` flag) | approved |
| #34 | `TemplateSeeder` (ownership markers, version-aware update, verified writes) | approved |
| #35 | Version-gated orchestrator + boot wiring (lock, cooldown, self-heal) | approved |
| #36 | Preview thumbnails (hash-deduped featured images, best-effort) | approved |
| #37 | Shop layout | approved |
| #38 | Single Product layout | approved |
| #39 | Product Category layout + shop archive auto-scoping | approved |
| #40 | Cart layout + full Cart widget (+ Cart Style tab: Item Row, Checkout Button) | approved |
| #41 | Checkout layout | **CHANGES_REQUESTED — stale bot review**; the requested `TEMPLATES_VERSION` bump has been on the branch since `9c6f110`. Needs a review dismissal, nothing else. |
| #42 | Thank You layout + Order Receipt widget | approved |
| #43 | Customer Dashboard layout + widget (+ canvas wireframe presentation) | approved |
| #44 | Campaign Landing layout (+ native-controls rework, FA5 icon fix, seeder page-settings verification) | approved |
| #45 | Order Receipt widget: full feature set (preview settings, sections, short codes + `{{:}}` toolbar picker, Style tab) | approved |
| #46 | Related Products widget: Style tab (Heading/Grid/Card/Title/Price/Button) | approved |
| #47 | Product Info style coverage (Package Description section, stock badge + dead-selector repair, per-button Buy Now / Add To Cart) | approved |

## Remaining to ship

1. Dismiss the stale review on **#41** (fix already on branch).
2. Merge the train bottom-up **#32 → #47**.
3. Post-merge sanity on a clean load: delete option
   `fluent_cart_elementor_templates_version`, load wp-admin, confirm all 8
   templates seed with previews.
4. Release: version bump + `npm run build:zip`.

## Parked (recorded, non-blocking)

- **Single-product mobile seam**: oversized gap between product info and
  related products on phones. Two template-level fixes were tried and
  reverted (verified the CSS applied; the space is core's). Needs one
  DevTools element identification; likely a fluent-cart core scss fix.
- **Customer Dashboard canvas preview upgrade**: current = frozen-skeleton
  wireframe + badge. Options assessed: editor-only iframe of the real page
  (best fidelity/effort, no core change), port of Divi's 622-line static VB
  mock, or a core PR exposing `window.fluentCartBootCustomerProfile(container)`.
- **Core tickets worth filing**:
  - `ThankYouRender::renderSample()` so builder addons stop hand-copying the
    receipt sample (Divi + Elementor each carry one today).
  - A re-callable customer-profile boot function (see above).
  - Bill To heading in `ThankYouRender` is a bare `<h5>` while Ship To's is
    classed — give it a class.
- **Divi parity**: FluentCart short codes in the Divi Receipt module
  (feasible, ~1 day; select-append attribute instead of a TinyMCE button).
