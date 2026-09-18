# Template Library — PRD (as built)

**Status:** Built · PRs #32–#47 · This PRD documents what shipped, including
decisions made during implementation.

## User stories

1. As a merchant, when I open Elementor's Insert Template modal, I see 8
   "FluentCart — …" page templates with previews, and inserting one gives me a
   working store page.
2. As a merchant, my own saved templates are never modified by the plugin.
3. As a merchant, when the plugin updates with improved layouts, my library
   copies update automatically — but only the plugin-seeded ones.
4. As a merchant, I can customize the seeded pages through normal Elementor
   controls (the layouts use native widget settings, not baked-in HTML styling).

## Architecture

```
app/Services/TemplateLibrary/
├── TemplateLibrary.php    ← orchestrator: version-gated seeding on admin_init
├── TemplateManifest.php   ← loader: reads/validates the bundled files
├── TemplateSeeder.php     ← writer: creates/updates library items via Elementor's API
├── README.md              ← storage format reference
└── templates/
    ├── manifest.json      ← index: array of slugs to seed
    └── <slug>/
        ├── manifest.json  ← title, type, category, template_version, preview
        ├── template.json  ← Elementor export payload (element tree + page_settings)
        └── preview.webp   ← library-card thumbnail
```

### Seeding pipeline

- **Trigger**: `admin_init`, gated by option `fluent_cart_elementor_templates_version`
  vs `TemplateLibrary::TEMPLATES_VERSION` — steady-state cost is one
  `get_option()`. Runs on every admin load (not activation hooks), so drifted /
  directly-updated installs self-heal.
- **Concurrency**: atomic seeding lock (unique option row) with TTL reclaim;
  failed/incomplete passes retry after a cooldown, and the version gate only
  advances when the pass completed — a transient failure is never recorded as
  finished.
- **Writes go through Elementor's own document layer** (`save_item()` /
  `$document->save()`), never hand-written post meta — that is what correctly
  slashes `_elementor_data`, assigns the library-type term, and sets edit mode.
- **Ownership + versioning**: each seeded item carries private meta
  (`_fluent_cart_elementor_template_slug`, `…_version`). Items are matched by
  that marker only. An item is replaced only when the bundled
  `template_version` is strictly newer. Marker writes are verified by
  read-back (`update_post_meta` can't distinguish failure from already-equal),
  and a failed write deletes the half-created post so the gate stays behind.
- **Page settings** (e.g. Full Width layout): Elementor's `save_item()` drops
  page settings that aren't registered controls on the library document, so
  the seeder re-applies the manifest's `page_settings` after save and verifies
  them — an item without its layout is a failed seed, not a success.
- **Previews**: best-effort featured images imported from each template's
  bundled `preview.webp`, deduped by slug + file hash, self-healing without a
  version bump. A missing preview never fails a seed.

### Widgets shipped for the templates

- **Cart** (`fluent_cart_cart`) — core `[fluent_cart_cart]` wrapper + Style tab
  (Item Row, Checkout Button — Divi parity).
- **Order Receipt** (`fluent_cart_receipt`) — full widget: real-order editor
  preview (latest / by ID, read-only — never mutates `sales_recorded`),
  section toggles, WYSIWYG confirmation message with FluentCart short codes +
  a `{{:}}` toolbar picker fed from core's `EditorShortCodeHelper` registry,
  custom action texts, scoped Style tab. Static sample fallback for empty
  stores mirrors core markup class-for-class.
- **Customer Dashboard** (`fluent_cart_customer_dashboard`) — core SPA
  wrapper; editor canvas shows the skeleton as a frozen wireframe with a badge
  (the Vue app cannot boot against AJAX-injected markup; see kanban parked
  items for upgrade paths).
- Style/content upgrades that fell out of template QA: Related Products Style
  tab, Product Info style coverage (package description, stock badge — incl. a
  repair of pre-existing dead selectors), shop archive auto-scoping.

## Acceptance criteria (verified)

- Fresh store: one admin load seeds 8 items, all with previews; second load
  seeds nothing (gate satisfied).
- Version bump: only templates whose `template_version` increased are updated
  in place (same post ID); user edits to *our* items are replaced by design,
  user-created items untouched.
- Blocked meta writes (simulated via filters) → seed reports `failed`, no
  orphan posts, next load retries.
- Campaign Landing inserts as Full Width; hero CTA scrolls to the product
  section; all icons render (FA5 names only).
- Category template on a term archive: heading binds to the term, product
  grid auto-scopes to the term incl. AJAX pagination.

## Explicitly out of scope

- CDN / remote template catalog and in-editor browsing modal (dropped by
  decision — see idea.md).
- Elementor-Pro-dependent archive document types / conditions as shipped
  code (archives can be wired site-side via Pro's Theme Builder with an
  archive template + `include/product-categories` condition).
