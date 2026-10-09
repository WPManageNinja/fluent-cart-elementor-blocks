# Change audit — everything touched, and what to do with it

**Date:** 9 October 2026

One honest list of every file changed across the three plugins, so nothing has
to be reconstructed from conversation history before deciding what to keep.

> **Verify the git side yourself.** The sandbox lost read access to `.git`
> part-way through the session, so branch and push state is **not** verified
> below. Run:
>
> ```
> cd wp-content/plugins/fluent-cart        && git status --short && git log --oneline -5
> cd ../fluent-cart-elementor-blocks       && git status --short && git log --oneline -5
> ```

---

## Summary

| Plugin | Files changed | Verdict |
|---|---|---|
| **fluent-cart-pro** | **0** | Untouched — verified, nothing modified since 8 Oct. |
| **fluent-cart** (core) | 3 code + 5 docs moved out | **Breaks the new rule.** One decision needed. |
| **fluent-cart-elementor-blocks** | ~10 new code + 6 tests/docs | Where everything should live. |

---

## 1. fluent-cart-pro — nothing

No file modified. No decision needed.

## 2. fluent-cart (core) — the only rule-breaking part

| File | Change | Why it exists |
|---|---|---|
| `app/Modules/MCP/Tools/CatalogTools.php` | **NEW**, ~900 lines | Six catalogue abilities: attribute groups, terms, product variations. Put here because attributes are commerce data, not Elementor. |
| `app/Modules/MCP/AbilitiesRegistrar.php` | **MODIFIED, 2 lines** | One `use`, one array entry, to register the above. |
| `tests/smoke/mcp-catalog.php` | **NEW**, ~190 lines | 25 checks. Standalone, self-cleaning, creates only a throwaway group. |
| `dev-docs/mcp-server/*.md` (5 files) | **MOVED OUT** to the add-on | Done on your instruction. |

**Nothing else in core was touched.** No renderer, model, route, block or
existing behaviour. The only functional change is the two-line registration.

### The decision this forces

`CatalogTools` is the sole reason an agent can configure swatches at all.
It has to live somewhere:

- **(A) Move it into the add-on.** Works today — the add-on already imports
  core models. Impure: Gutenberg/Divi users get no catalogue abilities.
  Fully respects "don't touch core".
- **(B) Leave it in core**, hand it to the core developer as a reviewed
  contribution. Correct home, but it is a core change.
- **(C) Drop it.** Swatch configuration goes back to manual admin work and the
  MCP cannot build a working storefront.

**Recommendation: (A) now, (B) later.** Moving it is a namespace change plus
`composer dump-autoload -o`; it unblocks you with no core PR, and the code can
be promoted to core after review.

## 3. fluent-cart-elementor-blocks (the add-on)

### MCP module — new capability, no existing block touched

```
app/Modules/MCP/MCPInit.php                      new
app/Modules/MCP/AbilitiesRegistrar.php           new
app/Modules/MCP/Tools/BuilderTools.php           new   11 builder abilities
app/Modules/MCP/Support/BuilderRegistry.php      new
app/Modules/MCP/Support/ElementorDocument.php    new
app/Modules/MCP/Support/ProductTemplate.php      new
app/Modules/MCP/Support/StorefrontRecipes.php    new
app/Modules/MCP/Support/StyleConflicts.php       new
app/Modules/MCP/Support/WidgetSchemaReader.php   new
app/Modules/MCP/Support/StyleHooks.php           new   reports each widget's CSS hooks
```

### Tests

```
tests/smoke/mcp-builder.php                      new   47 checks
tests/smoke/render-widgets.php                   new   71 checks
tests/smoke/style-hooks.php                      new   13 checks
tests/integration/template-library-self-heal.php new
tests/bin/seed-reviews.php                       modified  recomputes rating aggregate
tests/lib/assert.php                             modified  added skip()
```

### Not changed

**No existing widget file was modified.** Nothing under
`app/Modules/Integrations/Elementor/Widgets/` was touched — no markup, no
renderer, no control definition. Every released block behaves exactly as before.

---

## Site data changed (not revertible by git checkout)

- Ten sneakers consolidated into three (**VOLT Runner / Dasher / Court**),
  45 variants of Colourway × Shoe Size. The other seven are **drafted**, not
  deleted.
- Two attribute groups created: **Colourway** (image), **Shoe Size**.
- Elementor kit globals rewritten twice (VOLT palette, then oat).
- Product template **#272** rebuilt and styled; **#274** is a composite draft.
- Home, shop, cart, checkout, receipt, account pages rebuilt.
- Server `wp-config.php`: `display_errors=0`, `log_errors=1` (backup kept).
- **Test residue:** the core suite created `phase8-*` products; three are
  published and will show in the shop grid. They should be deleted.

---

## Bugs found and fixed along the way

In **our own MCP code**, all caught by testing rather than shipped:

1. `flushConditions()` called `delete_option()` on Elementor Pro's conditions
   cache. That is not invalidation — it disabled the **entire Theme Builder
   site-wide**, header and footer included. Now calls `regenerate()`.
2. `WidgetSchemaReader` typed `multiple` controls as strings, so
   `product_ids` was reported as a string while the renderer requires an
   array — the carousel saved fine and rendered nothing.
3. `manage-product-variations` deleted variants *after* inserting, colliding on
   `sku_unique` when replacing a set.
4. `products/manage` was an invented capability; core uses
   `products/create` / `edit` / `delete`.

**Three false alarms** I nearly "fixed" and did not, because a test disproved
them first: out-of-stock handling (already correct), the swatch `.disabled`
logic (already implemented), and `flushCss()` (already works).
