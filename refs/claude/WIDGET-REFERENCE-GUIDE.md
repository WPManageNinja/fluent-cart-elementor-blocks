# Widget Reference Guide

> **Purpose:** Before building any new Elementor widget, read the two reference widgets below.
> They collectively cover nearly every pattern used across the codebase.

---

## Reference Widgets

### 1. ProductCardWidget (Provider Pattern)

**File:** `app/Modules/Integrations/Elementor/Widgets/ProductCardWidget.php`
**Docs:** `refs/claude/ProductCard/STYLE-CONTROLS.md`

Read this to understand:

- **Creating public static style methods** (`registerCard*StyleControls($widget, $selector)`) — the shared controls pattern
- **All Elementor control types:** COLOR, DIMENSIONS, SLIDER, SELECT, SWITCHER
- **All group controls:** Typography, Border, Box Shadow, Background (classic + gradient)
- **Repeater** with `title_field` JS template expression
- **Custom control** instantiation: `(new ProductSelectControl())->get_type()`
- **Normal/Hover tabs** (`start_controls_tabs` / `start_controls_tab`)
- **Responsive controls** (`add_responsive_control`)
- **Selector derivation** inside static methods (e.g., `str_replace('-image', '-image-wrap', $selector)`)
- **Render pattern:** `ProductCardRender` from core, iterating repeater elements via `foreach`/`switch`
- **Editor placeholder** with `is_edit_mode()` check
- **`get_style_depends()`** with `AssetLoader` side-effect registration

### 2. ProductCarouselWidget (Consumer Pattern)

**File:** `app/Modules/Integrations/Elementor/Widgets/ProductCarouselWidget.php`
**Docs:** `refs/claude/ProductCarousel/STYLE-CONTROLS.md`

Read this to understand:

- **Consuming static methods** from another widget (`ProductCardWidget::registerCardStyleControls($this, ...)`)
- **Local style sections** alongside shared ones (navigation arrows, Swiper pagination)
- **`condition` on controls** (`'condition' => ['autoplay' => 'yes']`)
- **`condition` on sections** (`'condition' => ['show_arrows' => 'yes']`)
- **Responsive NUMBER** with device defaults (`tablet_default`, `mobile_default`)
- **`get_script_depends()`** for JS dependencies
- **Asset registration** with static `$registered` guard (once-only pattern)
- **Two asset systems:** `FluentCart\App\Vite` (core) vs `Enqueue` (plugin-local)
- **JSON data attribute** for JS config (`data-carousel-settings`)
- **Editor-only `<style>` block** disabling pointer-events
- **Multi-product ProductSelectControl** (`'multiple' => true`)
- **Inline SVG** rendering for arrow icons

---

## Additional Patterns (Not in Reference Widgets)

These patterns appear in other widgets. When building a widget that needs them, check the listed source file.

| Pattern | Where to Find | Example |
|---|---|---|
| `Controls_Manager::TEXT` (text input) | `CheckoutWidget.php` | Custom button text, headings |
| `Controls_Manager::CHOOSE` (icon alignment) | `CheckoutWidget.php` | `submit_button_alignment` |
| `Controls_Manager::HEADING` with `separator` | `ShopAppWidget.php`, `CheckoutWidget.php` | Visual dividers in panels |
| `Controls_Manager::NUMBER` with CSS `selectors` | `CheckoutWidget.php` | `transition: all {{VALUE}}ms` |
| `selectors_dictionary` | `CheckoutWidget.php` | Maps select values to CSS |
| Negated condition (`'key!' => 'value'`) | `CheckoutWidget.php` | `'summary_separator_style!' => 'none'` |
| Normal/Focus tabs (vs Normal/Hover) | `CheckoutWidget.php` | Input focus states |
| `default` with size+unit array | `CheckoutWidget.php` | `['size' => 65, 'unit' => '%']` |
| Separate editor/frontend renderers | `CheckoutWidget.php` | `DummyCheckoutRenderer` vs `ElementorCheckoutRenderer` |
| Multiple Repeaters in one widget | `CheckoutWidget.php` | `form_elements` + `summary_elements` |
| Repeater with nested conditions | `CheckoutWidget.php` | Show control when sibling field = value |
| `ProductWidgetTrait` (Theme Builder) | `ThemeBuilder/ProductTitleWidget.php` | Source selector + `getProduct()` |
| Composite static reuse (5+ widgets) | `ThemeBuilder/ProductInfoWidget.php` | Consumes Title, Price, Stock, Excerpt, BuySection |
| Conditional style sections via toggles | `ThemeBuilder/ProductInfoWidget.php` | `show_gallery` hides gallery style section |
| Multiple widget categories | `ThemeBuilder/ProductInfoWidget.php` | `['fluentcart-elements-single', 'fluent-cart']` |
| Shortcode delegation | `ThemeBuilder/RelatedProductsWidget.php` | `do_shortcode('[fluent_cart_related_products]')` |

---

## When to Update This Guide

After implementing a new widget, evaluate whether it introduces patterns not covered by the current two reference widgets. Update this guide if **any** of these apply:

1. **New control type or group control** not listed above (e.g., `MEDIA`, `GALLERY`, `CODE`, `WYSIWYG`)
2. **New registration pattern** (e.g., dynamic controls, conditional repeater fields, popover controls)
3. **New render pattern** (e.g., REST API fetch in render, AJAX-loaded content, iframe embedding)
4. **New asset pattern** (e.g., conditional asset loading, external CDN, inline script registration)
5. **New static method pattern** that's materially different from ProductCardWidget's approach

### How to update:

1. **If the new widget just uses existing patterns** — no changes needed
2. **If the new widget adds 1-2 new patterns** — add rows to the "Additional Patterns" table above
3. **If the new widget has broader coverage than a reference widget** — promote it to replace the less-comprehensive reference widget:
   - Update the reference widget section above
   - Move the demoted widget's unique patterns to the "Additional Patterns" table
   - Update `MEMORY.md` to point to the new reference

### Checklist after each new widget:

```
[ ] Does it introduce a new Elementor control type?
[ ] Does it introduce a new registration pattern?
[ ] Does it introduce a new render or asset pattern?
[ ] Does it cover MORE patterns than ProductCardWidget or ProductCarouselWidget?
→ If yes to any: update this guide
→ If no to all: no changes needed
```

---

## Quick Reference: Widget File Locations

| Widget | File |
|---|---|
| ProductCardWidget | `Widgets/ProductCardWidget.php` |
| ProductCarouselWidget | `Widgets/ProductCarouselWidget.php` |
| ShopAppWidget | `Widgets/ShopAppWidget.php` |
| CheckoutWidget | `Widgets/CheckoutWidget.php` |
| AddToCartWidget | `Widgets/AddToCartWidget.php` |
| BuyNowWidget | `Widgets/BuyNowWidget.php` |
| MiniCartWidget | `Widgets/MiniCartWidget.php` |
| ProductCategoriesListWidget | `Widgets/ProductCategoriesListWidget.php` |
| ProductTitleWidget | `Widgets/ThemeBuilder/ProductTitleWidget.php` |
| ProductGalleryWidget | `Widgets/ThemeBuilder/ProductGalleryWidget.php` |
| ProductPriceWidget | `Widgets/ThemeBuilder/ProductPriceWidget.php` |
| ProductStockWidget | `Widgets/ThemeBuilder/ProductStockWidget.php` |
| ProductExcerptWidget | `Widgets/ThemeBuilder/ProductExcerptWidget.php` |
| ProductBuySectionWidget | `Widgets/ThemeBuilder/ProductBuySectionWidget.php` |
| ProductContentWidget | `Widgets/ThemeBuilder/ProductContentWidget.php` |
| ProductInfoWidget | `Widgets/ThemeBuilder/ProductInfoWidget.php` |
| RelatedProductsWidget | `Widgets/ThemeBuilder/RelatedProductsWidget.php` |

All paths relative to `app/Modules/Integrations/Elementor/`.
