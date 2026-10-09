# MCP support — scope, and exactly which files change

**Date:** 9 October 2026
**Constraint:** change nothing in `fluent-cart` or `fluent-cart-pro`. Change no
existing block in this add-on. Only add.

This is step one. Block customization is a separate document
([`03-block-customization-roadmap.md`](03-block-customization-roadmap.md)) and
a separate conversation with the developer.

---

## What "MCP support" means here

An AI agent connects to the site and can build a FluentCart storefront in
Elementor: create the pages, place the widgets, configure them, style them, and
verify the result. Today it can do all of that **except** configure the
catalogue data the widgets display.

## Files this touches

Everything is **new files in one directory**. No existing file in this plugin
is modified except one line of bootstrap.

```
app/Modules/MCP/                       ← the entire feature, self-contained
  MCPInit.php                          bootstrap; bails if Elementor is absent
  AbilitiesRegistrar.php               registers abilities, validates input
  Tools/BuilderTools.php               the 11 builder abilities
  Tools/CatalogTools.php               ← TO MOVE HERE from core (see audit)
  Support/BuilderRegistry.php          which widgets exist and are usable
  Support/WidgetSchemaReader.php       reads each widget's controls live
  Support/StyleHooks.php               reads each widget's rendered CSS hooks
  Support/ElementorDocument.php        read/write _elementor_data safely
  Support/ProductTemplate.php          Theme Builder document handling
  Support/StorefrontRecipes.php        which page needs which widget
  Support/StyleConflicts.php           warns when CSS fights a layout control

app/Hooks/actions.php                  ← ONE line: boot MCPInit
tests/smoke/*.php                      new test files only
```

**Why this is safe:** the module only *reads* widgets (via Elementor's own
registry) and *writes* `_elementor_data`. It never edits a widget class, a
renderer, or a control definition. Deactivating the module changes nothing
about how the released blocks behave.

## The abilities, and what each is for

### Already built and tested — 11 builder abilities

| Ability | Purpose |
|---|---|
| `get-builder-context` | What is active: Elementor, Pro, the add-on, how many widgets are usable |
| `list-builder-widgets` | The catalog — which widgets exist, which need product context, which conflict |
| `get-builder-widget-schema` | A widget's real settings **and** the CSS hooks it renders |
| `place-builder-widget` | Insert a configured widget (Elementor refuses to do this for classic widgets) |
| `update-builder-widget` | Change settings on a placed widget |
| `remove-builder-widget` | Remove one, or every instance of a type |
| `build-storefront-page` | One call builds a page **and** points FluentCart's setting at it |
| `build-storefront` | All five storefront pages at once |
| `build-product-template` | The Theme Builder document, conditioned and published |
| `get-storefront-guide` | The order of work, and the traps |
| `validate-storefront` | Confirms the store is actually wired up |

### Needs a decision — 6 catalogue abilities

Currently in core. See the audit for the move/keep/drop decision.

| Ability | Purpose |
|---|---|
| `list-attribute-groups` | The swatch library: groups, terms, colour/image type |
| `manage-attribute-group` | Create/update/delete a group |
| `manage-attribute-terms` | Create/update/delete terms, with hex/URL validation |
| `get-product-variations` | A product's variation setup, with findings for what is misconfigured |
| `manage-product-variations` | Set variation type, map variants to terms, set variant images |
| `manage-store-settings` | **Not yet written.** Read/update store settings behind a deny-list |

**Why these matter:** without them an agent can build a beautiful product page
and has no way to give the product the swatches that page exists for. Proven on
the test site: three products, 45 variants, working colour swatches — built
entirely through these abilities.

## What is NOT in scope

- Any change to `fluent-cart` or `fluent-cart-pro`
- Any change to an existing block's markup, controls or renderer
- Any new block
- Gutenberg, Divi or Bricks support

## Known gotchas the abilities must keep guarding

Each of these cost real debugging time and is now encoded in the code or its
docblocks:

1. **`build-composition` writes an autosave revision, not the live document.**
   It returns `success: true` either way. Publishing *after* a direct settings
   write silently discards that write. Order: structural change → publish →
   settings.
2. **Elementor Pro's conditions cache must be regenerated, never deleted.**
   Deleting disables the whole Theme Builder site-wide.
3. **`multiple` controls store arrays**, not strings. A string saves fine and
   renders nothing.
4. **Element `style` strings take a single font family.** `font-family: X,
   sans-serif` is quoted whole and silently falls back to serif.
5. **`manage-elements` patches by default** — inherited template styles survive
   unless `style_apply_mode: "replace"`.

## Open question for the developer

`CatalogTools` is commerce data, not Elementor. Long term it belongs in core so
every builder benefits. Short term it can live here. **Which?**
