# Review Widget: $ARGUMENTS

You are reviewing an Elementor widget for the FluentCart Elementor Blocks plugin against project patterns and conventions.

**Target:** `$ARGUMENTS` (widget name like `ProductCard`, or file path like `app/Modules/.../ProductCardWidget.php`)

## Steps

### 1. Locate the Widget

- If `$ARGUMENTS` is a file path, read that file directly
- If `$ARGUMENTS` is a widget name (e.g., `ProductCard`), find the file at:
  - `app/Modules/Integrations/Elementor/Widgets/{$ARGUMENTS}Widget.php` (general widgets)
  - `app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/{$ARGUMENTS}Widget.php` (theme builder widgets)

Read the widget file completely.

### 2. Read Reference Materials

Read these for comparison:
1. `refs/claude/WIDGET-REFERENCE-GUIDE.md`
2. Check if `refs/claude/{$ARGUMENTS}/STYLE-CONTROLS.md` exists — read it if so

### 3. Run the Review Checklist

Evaluate each item and mark as Pass/Fail/N-A:

#### Structure & Naming
- [ ] **Extends Widget_Base** — Class extends `Elementor\Widget_Base`
- [ ] **Correct namespace** — `FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets` (or `...\Widgets\ThemeBuilder` for theme builder widgets)
- [ ] **get_name()** — Returns `fluent_cart_{snake_case}` format
- [ ] **get_title()** — Returns a human-readable title string
- [ ] **get_icon()** — Returns a valid Elementor icon class
- [ ] **get_categories()** — Returns array containing `'fluent-cart'` (general) or `'fluentcart-elements-single'` (theme builder)
- [ ] **get_keywords()** — Returns array of search keywords

#### Controls
- [ ] **register_controls() exists** — Method is defined
- [ ] **Content tab sections** — At least one content section with controls
- [ ] **Style tab sections** — Style controls use `Controls_Manager::TAB_STYLE`
- [ ] **Selector format** — All selectors use `{{WRAPPER}} .fct-*` pattern
- [ ] **Responsive controls** — Spacing/sizing controls use `add_responsive_control` where appropriate
- [ ] **Group controls** — Uses `Group_Control_Typography` for text, `Group_Control_Border` for borders, `Group_Control_Box_Shadow` for shadows where appropriate
- [ ] **Normal/Hover tabs** — Interactive elements have hover state controls via `start_controls_tabs`
- [ ] **Control conditions** — Dependent controls use `'condition'` arrays

#### Static Methods (if applicable)
- [ ] **Naming convention** — Static style methods follow `register{X}StyleControls($widget, $selector)` pattern
- [ ] **Parameter usage** — Methods accept `$widget` and `$selector` params, use them for `add_control` calls
- [ ] **Selector derivation** — If deriving sub-selectors, uses `str_replace` pattern from ProductCardWidget

#### Render
- [ ] **render() method** — Method is defined and outputs HTML
- [ ] **FluentCart data check** — Validates FluentCart data is available before rendering
- [ ] **Editor placeholder** — Uses `is_edit_mode()` for placeholder content when no data (if applicable)
- [ ] **Proper escaping** — Output is properly escaped (`esc_html`, `esc_attr`, `wp_kses_post`, etc.)

#### Assets (if applicable)
- [ ] **get_style_depends()** — CSS dependencies declared
- [ ] **get_script_depends()** — JS dependencies declared (if widget needs JS)
- [ ] **Once-only registration** — Uses static `$registered` guard for asset registration

#### Registration
- [ ] **Registered in ElementorIntegration** — Widget is instantiated in `registerWidgets()` method of `ElementorIntegration.php`
- [ ] **Use import exists** — The `use` statement for the widget class is in `ElementorIntegration.php`

#### Documentation
- [ ] **Style controls doc exists** — `refs/claude/{Name}/STYLE-CONTROLS.md` exists
- [ ] **Doc matches implementation** — Controls in the doc match what's in the code

### 4. Report

Output the completed checklist with notes on each failed item:

```
## Widget Review: $ARGUMENTS

### Summary
- Total checks: XX
- Passed: XX
- Failed: XX
- N/A: XX

### Checklist
[completed checklist with pass/fail marks and notes]

### Issues Found
[numbered list of issues with severity (Critical/Warning/Info) and suggested fixes]

### Recommendations
[any improvements or patterns that could be adopted]
```

Focus on **Critical** issues first (things that would cause runtime errors or break Elementor), then **Warnings** (convention violations), then **Info** (suggestions).