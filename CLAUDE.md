# Claude Code Instructions — FluentCart Elementor Blocks

## Before Starting Any Task

Always check `refs/claude/` first — it contains implementation plans, style control references, and architectural notes for existing widgets.

## Building a New Widget

1. **Start by reading** `refs/claude/WIDGET-REFERENCE-GUIDE.md` — it picks 2 reference widgets (ProductCardWidget + ProductCarouselWidget) that collectively cover nearly every pattern in the codebase.
2. Read the actual widget source files referenced in the guide before writing any code.
3. After shipping a new widget, evaluate if it should replace one of the reference picks (checklist is in the guide).

## Key Reference Files

| Topic | File |
|---|---|
| Widget patterns index | `refs/claude/WIDGET-REFERENCE-GUIDE.md` |
| Checkout architecture | `refs/claude/Checkout/IMPLEMENTATION-PLAN.md` |
| Checkout style controls | `refs/claude/Checkout/STYLE-CONTROLS.md` |
| Shop/Products style controls | `refs/claude/shopapp/STYLE-CONTROLS.md` |
| Shop layout repeater + AJAX | `refs/claude/shopapp/reorderable-shop-layout.md` |
| Shared card style methods | `refs/claude/ProductCard/STYLE-CONTROLS.md` |
| Carousel style controls | `refs/claude/ProductCarousel/STYLE-CONTROLS.md` |
| Categories list controls | `refs/claude/ProductCategoriesList/STYLE-CONTROLS.md` |
| Add to Cart controls | `refs/claude/AddToCart/STYLE-CONTROLS.md` |
| Buy Now controls | `refs/claude/BuyNow/STYLE-CONTROLS.md` |
| Mini Cart controls | `refs/claude/MiniCart/STYLE-CONTROLS.md` |
| Theme Builder overview | `refs/claude/ThemeBuilder/STYLE-CONTROLS.md` |
| Theme Builder integration plan | `refs/claude/theme-builder-integration-plan.md` |

## Divi 5 Integration Notes

- Legacy module approach (not native D5) — modules in `app/Modules/Integrations/Divi/Modules/Legacy/`
- `et_builder_ready` does NOT fire on normal frontend pages in Divi 5 — must register slugs via `et_builder_3rd_party_module_slugs` filter for lazy loading
- Check `ET_BUILDER_5_DIR` constant for D5 detection (not `ET_BUILDER_5_VERSION`)