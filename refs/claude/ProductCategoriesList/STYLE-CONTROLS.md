# ProductCategoriesListWidget Style Controls Reference

## Overview

The ProductCategoriesListWidget (`app/Modules/Integrations/Elementor/Widgets/ProductCategoriesListWidget.php`) displays product categories as a list or dropdown. Renders via FluentCart core's `ProductCategoriesListRenderer`.

**Widget Slug:** `fluent_cart_product_categories_list`

---

## Content Controls

| Section | Control ID | Type | Description |
|---|---|---|---|
| Settings | `display_style` | Select | list / dropdown |
| Settings | `show_product_count` | Switcher | Default yes |
| Settings | `show_hierarchy` | Switcher | Default yes |
| Settings | `show_empty` | Switcher | Default off |

---

## Style Section Registration Order

```php
protected function register_controls()
{
    $this->registerSettingsControls();
    $this->registerListStyleControls();      // 1. List Items
    $this->registerCountStyleControls();     // 2. Product Count
    $this->registerDropdownStyleControls();  // 3. Dropdown
}
```

---

## 1. List Items

**Section ID:** `list_style_section`
**Condition:** `display_style = list`

| Control ID | Type | CSS Selector |
|---|---|---|
| `list_typography` | Typography | `{{WRAPPER}} .fct-category-link` |
| `list_text_color` | Color | `{{WRAPPER}} .fct-category-link` |
| `list_hover_color` | Color | `{{WRAPPER}} .fct-category-link:hover` |
| `list_item_spacing` | Responsive Slider | `{{WRAPPER}} .fct-category-item` (margin-bottom) |
| `list_child_indent` | Responsive Slider | `{{WRAPPER}} .fct-categories-children` (padding-left) |

## 2. Product Count

**Section ID:** `count_style_section`
**Condition:** `show_product_count = yes`

| Control ID | Type | CSS Selector |
|---|---|---|
| `count_typography` | Typography | `{{WRAPPER}} .fct-category-count` |
| `count_color` | Color | `{{WRAPPER}} .fct-category-count` |

## 3. Dropdown

**Section ID:** `dropdown_style_section`
**Condition:** `display_style = dropdown`
**Subsections:** Select Field, Go Button (Normal/Hover tabs)

### Select Field

| Control ID | Type | CSS Selector |
|---|---|---|
| `dropdown_select_heading` | Heading | N/A (separator) |
| `dropdown_typography` | Typography | `{{WRAPPER}} .fct-categories-dropdown` |
| `dropdown_text_color` | Color | `{{WRAPPER}} .fct-categories-dropdown` |
| `dropdown_background` | Color | `{{WRAPPER}} .fct-categories-dropdown` |
| `dropdown_border` | Border | `{{WRAPPER}} .fct-categories-dropdown` |
| `dropdown_border_radius` | Dimensions | `{{WRAPPER}} .fct-categories-dropdown` |
| `dropdown_padding` | Responsive Dimensions | `{{WRAPPER}} .fct-categories-dropdown` |

### Go Button

| Control ID | Type | CSS Selector |
|---|---|---|
| `dropdown_button_heading` | Heading | N/A (separator) |
| `dropdown_button_color` | Color | `{{WRAPPER}} .fct-categories-go-btn`, `{{WRAPPER}} .fct-categories-go-btn svg` |
| `dropdown_button_background` | Color | `{{WRAPPER}} .fct-categories-go-btn` |
| `dropdown_button_hover_color` | Color | `{{WRAPPER}} .fct-categories-go-btn:hover`, `{{WRAPPER}} .fct-categories-go-btn:hover svg` |
| `dropdown_button_hover_background` | Color | `{{WRAPPER}} .fct-categories-go-btn:hover` |

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Elementor wrapper | `.fluent-cart-elementor-categories-list` |
| Category item | `.fct-category-item` |
| Category link | `.fct-category-link` |
| Category count | `.fct-category-count` |
| Children container | `.fct-categories-children` |
| Dropdown select | `.fct-categories-dropdown` |
| Go button | `.fct-categories-go-btn` |

---

## Revision History

- **2026-02-18**: Initial documentation.
