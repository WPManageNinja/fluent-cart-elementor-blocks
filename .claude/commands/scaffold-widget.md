# Scaffold Widget: $ARGUMENTS

You are scaffolding a new Elementor widget for the FluentCart Elementor Blocks plugin.

**Widget Name:** `$ARGUMENTS` (e.g., `ProductTabs`, `ProductReviews`)

## Steps

### 1. Read Reference Materials

Before writing any code, read these files in order:

1. `refs/claude/WIDGET-REFERENCE-GUIDE.md` — understand all patterns
2. `app/Modules/Integrations/Elementor/Widgets/ProductCardWidget.php` — reference widget #1 (provider pattern)
3. `app/Modules/Integrations/Elementor/Widgets/ProductCarouselWidget.php` — reference widget #2 (consumer pattern)
4. `app/Modules/Integrations/Elementor/ElementorIntegration.php` — registration pattern

### 2. Clarify Requirements

If the widget purpose isn't obvious from the name `$ARGUMENTS`, ask the user:
- What data should this widget display?
- What content controls does it need? (e.g., product selector, text inputs, toggles)
- What style controls does it need? (e.g., typography, colors, spacing, borders)
- Should it consume static style methods from ProductCardWidget or define its own?
- Does it need JavaScript? (e.g., Swiper, AJAX, interactivity)

### 3. Generate the Widget File

Create `app/Modules/Integrations/Elementor/Widgets/{$ARGUMENTS}Widget.php` following these conventions:

**Namespace:** `FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets`

**Class structure:**
```php
namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
// ... other imports as needed

class {$ARGUMENTS}Widget extends Widget_Base
{
    // Required methods:
    public function get_name()        // 'fluent_cart_{snake_case_name}'
    public function get_title()       // Human-readable title
    public function get_icon()        // Elementor icon class (e.g., 'eicon-products')
    public function get_categories()  // ['fluent-cart']
    public function get_keywords()    // Search keywords array

    // Optional (if widget needs assets):
    public function get_style_depends()   // CSS dependencies
    public function get_script_depends()  // JS dependencies

    // Controls:
    protected function register_controls()
    // — Content tab: add_control / add_responsive_control
    // — Style tab: style sections with selectors using {{WRAPPER}} .fct-*

    // Render:
    protected function render()
    // — Check for FluentCart data availability
    // — Use is_edit_mode() for editor placeholders if needed
}
```

**Naming conventions:**
- Widget slug: `fluent_cart_{snake_case}` (e.g., `fluent_cart_product_tabs`)
- CSS selectors: `{{WRAPPER}} .fct-{kebab-case}` (e.g., `{{WRAPPER}} .fct-product-tabs`)
- Static style methods (if providing shared controls): `public static function register{Name}StyleControls($widget, $selector)`

**Follow these patterns from reference widgets:**
- Normal/Hover tabs for interactive elements (`start_controls_tabs` / `start_controls_tab`)
- Responsive controls for spacing/sizing (`add_responsive_control`)
- Group controls for typography (`Group_Control_Typography`), borders (`Group_Control_Border`), shadows (`Group_Control_Box_Shadow`)
- `condition` arrays to show/hide dependent controls
- Asset registration with static `$registered` guard for once-only loading
- Editor placeholder with `\Elementor\Plugin::$instance->editor->is_edit_mode()` check

### 4. Create Frontend Assets (if needed)

If the widget needs custom JS or CSS (interactivity, Swiper, AJAX, etc.):

**a) Create the JS/CSS entry file:**
- JS: `resources/elementor/{kebab-case-name}.js` (e.g., `resources/elementor/product-tabs.js`)
- CSS (if standalone): `resources/elementor/{kebab-case-name}.css`
- CSS imported from JS: just `import './your-styles.css'` inside the JS file — Vite will extract it automatically

**b) Add the entry to `vite.config.mjs`:**
Add the new file path to the `inputs` array:
```js
const inputs = [
    'resources/elementor/product-variation-select-control.js',
    'resources/elementor/product-carousel-elementor.js',
    'resources/elementor/product-select-control.js',
    'resources/elementor/{new-entry}.js',  // ← add here
];
```

**c) Reference the asset in the widget class:**
- Use `Vite::enqueueScript($handle, 'elementor/{filename}.js', [...], null, true)` in the widget's `render()` method or a static asset loader method
- Use `get_script_depends()` / `get_style_depends()` if the asset should load when the widget is present on a page
- Use the static `$registered` guard pattern to avoid double-loading:
```php
private static $registered = false;
private function registerAssets() {
    if (self::$registered) return;
    self::$registered = true;
    Vite::enqueueScript('fceb-{name}', 'elementor/{name}.js', [], null, true);
}
```

**d) Verify build:**
Run `npm run build` to confirm the new entry compiles without errors and appears in `assets/manifest.json`.

### 5. Input Validation & Output Escaping

Elementor widget settings (`$this->get_settings_for_display()`) are user-controlled — treat them as untrusted input. Every `render()` method MUST validate all settings before use.

#### Input validation rules (REQUIRED)

| Setting type | Validation | Example |
|---|---|---|
| ID (product_id, variant_id, etc.) | `absint()` | `$productId = absint($settings['product_id'] ?? 0);` |
| Enum/option (layout, price_format, query_type) | `in_array($val, [...allowed], true)` with fallback | See pattern below |
| Numeric with bounds (limit, columns, gap) | `absint()` + `min()`/`max()` | `$limit = max(1, min(50, absint($settings['limit'] ?? 10)));` |
| Free-text string (custom_label, heading_text) | `sanitize_text_field()` | `$label = sanitize_text_field($settings['button_text'] ?? '');` |
| Boolean-like (yes/no switchers) | `in_array()` with explicit allowed values | Treat as enum, NOT as truthy/falsy |
| Repeater items | Validate each item's values individually | Loop through repeater array, validate each field |

**Standard validation pattern for enums:**

```php
$settings = $this->get_settings_for_display();

$priceFormat = $settings['price_format'] ?? 'starts_from';
if (!in_array($priceFormat, ['starts_from', 'range', 'lowest'], true)) {
    $priceFormat = 'starts_from';
}

$showExcerpt = $settings['show_excerpt'] ?? 'yes';
if (!in_array($showExcerpt, ['yes', 'no'], true)) {
    $showExcerpt = 'yes';
}
```

**Never do this:**
```php
// BAD — raw setting interpolated into shortcode or HTML attribute
$format = $settings['price_format'];
echo do_shortcode("[fluent_cart_product price_format=$format]");

// GOOD — validated first
$allowedFormats = ['starts_from', 'range', 'lowest'];
$format = $settings['price_format'] ?? 'starts_from';
if (!in_array($format, $allowedFormats, true)) {
    $format = 'starts_from';
}
echo do_shortcode("[fluent_cart_product price_format=$format]");
```

#### Output escaping rules (contextual — don't blindly escape everything)

Apply escaping based on **what** you're outputting and **where**:

| Context | Approach | Notes |
|---|---|---|
| Trusted WP content (post_content, post_excerpt) | `wpautop()` or `apply_filters('the_content', ...)` | Already trusted — extra escaping breaks HTML |
| FluentCart renderer output | Output directly | Renderers handle their own escaping |
| User-provided text in HTML attributes | `esc_attr()` | e.g., custom CSS classes, data attributes |
| User-provided text in HTML body | `esc_html()` | e.g., custom labels, headings |
| URLs | `esc_url()` | e.g., custom links, image sources |
| Dynamic HTML from untrusted sources | `wp_kses_post()` | Only when HTML isn't already trusted WP content |

**Key principle:** Understand the data source before choosing an escaping function. Trusted WP post content and FluentCart renderer output are already safe — re-escaping will break formatting. But raw setting values from user input fields need escaping before touching HTML.

### 6. Register the Widget

Add the widget to `app/Modules/Integrations/Elementor/ElementorIntegration.php`:

1. Add the `use` import at the top of the file
2. Add `$widgets_manager->register(new {$ARGUMENTS}Widget());` in the `registerWidgets()` method

### 7. Create Style Controls Documentation

Create `refs/claude/{$ARGUMENTS}/STYLE-CONTROLS.md` documenting:
- All content controls (section, control ID, type, default)
- All style controls (section, control ID, type, selector)
- Any static methods and their signatures
- CSS selector map

### 9. Evaluate Reference Guide

After creating the widget, run through the checklist from `refs/claude/WIDGET-REFERENCE-GUIDE.md`:
- Does it introduce a new Elementor control type?
- Does it introduce a new registration pattern?
- Does it introduce a new render or asset pattern?
- Does it cover MORE patterns than the current reference widgets?

If yes to any, update the reference guide accordingly.

### 10. Summary

After completing all steps, output:

```
## Widget Created: fluent_cart_{snake_case}

### Files Created
- Widget: `app/Modules/Integrations/Elementor/Widgets/{$ARGUMENTS}Widget.php`
- Docs: `refs/claude/{$ARGUMENTS}/STYLE-CONTROLS.md`
- Assets: `resources/elementor/{kebab-case}.js` (if applicable)

### Files Modified
- `app/Modules/Integrations/Elementor/ElementorIntegration.php` — Widget registration added
- `vite.config.mjs` — Entry points added (if applicable)

### Security Checklist
- [ ] All ID settings use `absint()` in `render()`
- [ ] All enum/option settings use `in_array()` with strict whitelist in `render()`
- [ ] All numeric settings have min/max bounds in `render()`
- [ ] All free-text settings use `sanitize_text_field()` in `render()`
- [ ] Switcher values treated as enums (checked against `['yes', 'no']`)
- [ ] Repeater item values validated individually
- [ ] No raw setting values interpolated into shortcode strings or HTML
- [ ] Output escaping applied contextually (not blindly — verify it won't break renderer output)
- [ ] FluentCart renderer output not double-escaped

### Next Steps
1. Run `/test-widget fluent_cart_{snake_case}` to verify in browser
2. Run `/review-widget {$ARGUMENTS}` to check against patterns
```
