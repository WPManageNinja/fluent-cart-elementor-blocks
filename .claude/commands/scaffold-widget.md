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

### 4. Register the Widget

Add the widget to `app/Modules/Integrations/Elementor/ElementorIntegration.php`:

1. Add the `use` import at the top of the file
2. Add `$widgets_manager->register(new {$ARGUMENTS}Widget());` in the `registerWidgets()` method

### 5. Create Style Controls Documentation

Create `refs/claude/{$ARGUMENTS}/STYLE-CONTROLS.md` documenting:
- All content controls (section, control ID, type, default)
- All style controls (section, control ID, type, selector)
- Any static methods and their signatures
- CSS selector map

### 6. Evaluate Reference Guide

After creating the widget, run through the checklist from `refs/claude/WIDGET-REFERENCE-GUIDE.md`:
- Does it introduce a new Elementor control type?
- Does it introduce a new registration pattern?
- Does it introduce a new render or asset pattern?
- Does it cover MORE patterns than the current reference widgets?

If yes to any, update the reference guide accordingly.

### 7. Summary

After completing all steps, output:
- Files created/modified (with paths)
- Widget slug for testing: `fluent_cart_{snake_case}`
- Suggested next step: `/test-widget fluent_cart_{snake_case}` to verify in browser
