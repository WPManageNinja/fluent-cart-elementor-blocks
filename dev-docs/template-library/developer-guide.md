# Template Library — Developer Guide

How to add a new bundled template, update an existing one, and avoid the
Elementor traps we hit building the first eight. Storage-format reference:
`app/Services/TemplateLibrary/README.md`.

## Add a new template

1. **Design it in Elementor** on a scratch page, using **native widget
   controls for every style** (see Authoring rules below). When done, use the
   element context menu → Copy / or Export Template to get the element tree.

2. **Create the folder** `app/Services/TemplateLibrary/templates/<slug>/` with:

   - `manifest.json`
     ```json
     {
         "slug": "fc-my-template",
         "title": "FluentCart — My Template",
         "type": "page",
         "category": "FluentCart",
         "template_version": "1.0.0",
         "preview": "preview.webp"
     }
     ```
     `slug` must equal the folder name — it is the ownership marker.

   - `template.json` — the Elementor export payload:
     ```json
     {
         "version": "0.4",
         "title": "FluentCart — My Template",
         "type": "page",
         "content": [ ...element tree... ],
         "page_settings": { "template": "elementor_header_footer" }
     }
     ```
     `page_settings.template` controls the page layout (Full Width above);
     the seeder re-applies and verifies it after save because Elementor's
     `save_item()` silently drops it.

   - `preview.webp` — the library-card thumbnail (lives here, NOT in
     `resources/` — only `app/` ships in the release ZIP, and `assets/` is
     wiped on every build).

3. **Register the slug** in `templates/manifest.json` (the flat index array).

4. **Bump `TemplateLibrary::TEMPLATES_VERSION`** — without this, installs that
   already seeded never look again. Keep it coordinated across stacked PRs so
   the merged result is strictly newer than anything previously shipped.

5. **Test the seed** (wp-cli):
   ```php
   wp eval --user=1 '
   use FluentCartElementorBlocks\App\Services\TemplateLibrary\TemplateManifest;
   use FluentCartElementorBlocks\App\Services\TemplateLibrary\TemplateSeeder;
   $r = TemplateSeeder::seed(TemplateManifest::load("fc-my-template"));
   echo $r; // created | updated | skipped | failed
   '
   ```
   For a full re-seed test: delete the option
   `fluent_cart_elementor_templates_version` and load any wp-admin page.
   `TemplateManifest::loadAll()` returns `['templates' => [...], 'complete' => bool]`
   — don't iterate the wrapper by mistake.

## Update an existing template

1. Edit `<slug>/template.json` (and/or `preview.webp` — previews self-heal by
   file hash without a version bump).
2. Bump `template_version` in the template's `manifest.json`.
3. Bump `TEMPLATES_VERSION`.
4. Re-seed and verify. Updates replace the seeded item **in place** (same post
   ID); user-created templates are never touched (matching is by private meta,
   never title).

## Authoring rules (learned the hard way)

- **Native controls, not inline HTML styles.** Anything a merchant might
  restyle must live in widget Style/Advanced settings. Pills/badges = Heading
  widget + Advanced background/padding/999px radius + **Inline (auto) width**,
  centered by the container (`flex_align_items`). Text blocks = align/color/
  typography controls + Advanced custom width instead of inline `max-width`.
  The ONLY acceptable inline styles are partial-text effects inside a single
  heading (accent-colored or highlighter `<span>`s) — Elementor has no control
  for those.
- **Font Awesome 5 names only.** Elementor ships FA5; an FA6 name
  (`fa-rotate-left`) renders as silent nothing. FA5 equivalent: `fa-undo`.
- **Every `selected_icon` needs `"__fa4_migrated": {"selected_icon": true}`**
  or Elementor's import migration blanks/remaps icons during `save_item()`.
- **Responsive values** use the `_mobile` / `_tablet` key suffixes
  (`padding_mobile`, `_margin_mobile`, `typography_font_size_mobile`).
  Containers emit them as CSS variables (`--padding-top`) — grep for the
  variable, not `padding:`, when verifying generated CSS.
- **ShopApp default filters must be a PHP array** via `handelShortcodeCall($atts)`
  — a JSON-string shortcode attr never passes the `enabled` check and breaks
  AJAX pagination scoping.

## Widget-development gotchas (Elementor 4.2, verified by bisection)

- **`style_controls` bucket**: with Optimized Control Loading, every
  selector-carrying control lands in `get_stacks()[name]['style_controls']`,
  NOT in `get_controls()`. A control that "silently disappeared" is usually
  just in the other bucket — check before debugging.
- **SELECT2 + `render_type => 'ui'` never registers** — dropped without any
  `doing_it_wrong`. Omit the render_type.
- **`mce_buttons` / `mce_external_plugins` must be priority 11+**: Elementor's
  `get_wp_editor_config()` runs `remove_all_filters(<hook>, 10)` before
  printing the base `elementorwpeditor` that every panel WYSIWYG clones.
- **Editor iframe styles**: declare every stylesheet handle in
  `get_style_depends()` (side-effect enqueues mid-render never reach the
  editor's AJAX re-renders). CSS that MUST work in the canvas (e.g. section
  show/hide rules) is safest inlined with the widget output.
- **Verify selectors against real rendered markup, never guess.** Render the
  core shortcode/renderer and read the classes (the stock widget shipped with
  selectors for an element that never existed; Add To Cart vs View Options use
  different classes; Bill To's heading is a classless `<h5>`).
- **Core scss colors elements directly** — container-inheritance color
  controls do nothing; target explicit text elements via `:where(...)` (low
  specificity so specific controls still win) with `!important`. Where core
  themes via CSS variables (cart, dashboard), set the variables instead.
- **Text overrides on core-rendered HTML**: anchor `preg_replace` on core's
  stable classes, replace only the first match, pass through when unmatched,
  and `addcslashes($value, '\\$')` the replacement — user text containing
  `$1` would otherwise be eaten as a backreference.
- **Editor previews of order-dependent widgets**: preview with a real order
  (Elementor Pro's Purchase Summary pattern) rendered read-only — never via
  the shortcode path if it has side effects (`sales_recorded`). Static sample
  fallbacks must mirror core markup class-for-class or toggles/overrides
  silently miss them.
- **Client-rendered cores can't preview**: the Customer Dashboard is a Vue
  SPA that boots at page load; AJAX-injected canvas markup can never mount
  it. Present the skeleton as a wireframe, or embed the real page in an
  editor-only iframe — don't fight the boot.
