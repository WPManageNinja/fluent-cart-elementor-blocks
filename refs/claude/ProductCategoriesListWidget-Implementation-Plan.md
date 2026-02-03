# Product Categories List Widget - Elementor Implementation Plan

## Overview

This document outlines the plan to replicate the WordPress Block Editor `ProductCategoriesListBlockEditor` as an Elementor widget in the `fluentcart-elementor-blocks` plugin.

## Source Files Reference (fluent-cart plugin)

| File | Purpose |
|------|---------|
| `ProductCategoriesListBlockEditor.php` | PHP class for block registration, rendering, asset loading |
| `ProductCategoriesListBlockEditor.jsx` | Main React component for block editor UI |
| `ProductCategoriesListInspectorSettings.jsx` | Settings panel (sidebar controls) |
| `ProductCategoriesListRenderer.php` | PHP renderer service with list/dropdown rendering |
| `product-categories-list.js` | Frontend JS for dropdown "Go" button navigation |
| `product-categories-list.scss` | Styles for the categories list |

---

## Features to Implement

### 1. Display Style Options
| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `display_style` | Select | list | Display as list or dropdown |

### 2. List Settings
| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `show_product_count` | Toggle | true | Show number of products per category |
| `show_hierarchy` | Toggle | true | Display nested parent/child structure |
| `show_empty` | Toggle | false | Include categories with 0 products |

### 3. Rendered Output

**List Style:**
```html
<div class="fct-product-categories-list">
    <ul class="fct-categories-list">
        <li class="fct-category-item fct-category-item--depth-0">
            <span class="fct-category-link-wrap">
                <a href="..." class="fct-category-link">Category Name</a>
                <span class="fct-category-count">(5)</span>
            </span>
            <ul class="fct-categories-children">
                <li class="fct-category-item fct-category-item--depth-1">...</li>
            </ul>
        </li>
    </ul>
</div>
```

**Dropdown Style:**
```html
<div class="fct-product-categories-list fct-product-categories-list--dropdown">
    <div class="fct-categories-dropdown-wrap" data-fct-categories-dropdown-wrap>
        <select class="fct-categories-dropdown" data-fct-categories-dropdown>
            <option value="">Select a category</option>
            <option value="https://...">Category Name (5)</option>
            <option value="https://...">— Child Category (3)</option>
        </select>
        <button type="button" class="fct-categories-go-btn" data-fct-categories-go-btn>
            <svg>...</svg>
        </button>
    </div>
</div>
```

---

## Implementation Tasks

### Phase 1: Widget PHP Class

#### 1.1 Create `ProductCategoriesListWidget.php`
```
Location: app/Modules/Integrations/Elementor/Widgets/ProductCategoriesListWidget.php
```

**Methods to implement:**

| Method | Purpose |
|--------|---------|
| `get_name()` | Return `fluent_cart_product_categories_list` |
| `get_title()` | Return "Product Categories List" |
| `get_icon()` | Return `eicon-bullet-list` |
| `get_categories()` | Return `['fluent-cart']` |
| `get_keywords()` | Return categories-related keywords |
| `get_style_depends()` | Return required CSS dependencies |
| `get_script_depends()` | Return frontend JS dependency |
| `register_controls()` | Define all Elementor controls |
| `render()` | Output categories list/dropdown HTML |

---

### Phase 2: Control Sections

#### 2.1 Content Tab > Settings Section

| Control | Type | ID | Default | Description |
|---------|------|-----|---------|-------------|
| Display Style | Select | `display_style` | `list` | Options: List, Dropdown |
| Show Product Count | Switcher | `show_product_count` | `yes` | Show (n) after category |
| Show Hierarchy | Switcher | `show_hierarchy` | `yes` | Nest children under parents |
| Show Empty Categories | Switcher | `show_empty` | `` (no) | Include 0-product categories |

---

### Phase 3: Style Controls

#### 3.1 Style Tab > List Items Section
| Control | Type | Selector |
|---------|------|----------|
| Typography | Group_Control_Typography | `.fct-category-link` |
| Text Color | COLOR | `.fct-category-link` |
| Hover Color | COLOR | `.fct-category-link:hover` |
| Item Spacing | SLIDER | `.fct-category-item` margin-bottom |
| Child Indent | SLIDER | `.fct-categories-children` padding-left |

#### 3.2 Style Tab > Count Section
| Control | Type | Selector |
|---------|------|----------|
| Typography | Group_Control_Typography | `.fct-category-count` |
| Color | COLOR | `.fct-category-count` |

#### 3.3 Style Tab > Dropdown Section (condition: display_style=dropdown)
| Control | Type | Selector |
|---------|------|----------|
| Select Typography | Group_Control_Typography | `.fct-categories-dropdown` |
| Select Background | COLOR | `.fct-categories-dropdown` |
| Select Text Color | COLOR | `.fct-categories-dropdown` |
| Select Border | Group_Control_Border | `.fct-categories-dropdown` |
| Select Border Radius | DIMENSIONS | `.fct-categories-dropdown` |
| Select Padding | DIMENSIONS | `.fct-categories-dropdown` |
| Button Background | COLOR | `.fct-categories-go-btn` |
| Button Color | COLOR | `.fct-categories-go-btn` |
| Button Hover Background | COLOR | `.fct-categories-go-btn:hover` |
| Button Hover Color | COLOR | `.fct-categories-go-btn:hover` |

---

### Phase 4: Rendering Logic

#### 4.1 Render Method Implementation

```php
protected function render()
{
    $settings = $this->get_settings_for_display();
    $isEditor = \Elementor\Plugin::$instance->editor->is_edit_mode();

    // Load assets
    $this->registerAssets();

    // Prepare attributes for renderer
    $atts = [
        'display_style'      => $settings['display_style'] ?? 'list',
        'show_product_count' => ($settings['show_product_count'] ?? 'yes') === 'yes',
        'show_hierarchy'     => ($settings['show_hierarchy'] ?? 'yes') === 'yes',
        'show_empty'         => ($settings['show_empty'] ?? '') === 'yes',
    ];

    // Add editor class to disable interactions
    $editorClass = $isEditor ? ' fct-elementor-preview' : '';

    // Use ProductCategoriesListRenderer from fluent-cart
    $renderer = new ProductCategoriesListRenderer();

    echo '<div class="fluent-cart-elementor-categories-list' . esc_attr($editorClass) . '">';
    $renderer->render($atts);
    echo '</div>';
}
```

#### 4.2 Reuse Existing Renderer
The `ProductCategoriesListRenderer` class from fluent-cart handles:
- Fetching categories from `product-categories` taxonomy
- Building hierarchical structure
- Rendering list or dropdown HTML
- Handling empty states

---

### Phase 5: Asset Management

#### 5.1 Required Assets
```php
private function registerAssets()
{
    $app = \FluentCart\App\App::getInstance();
    $slug = $app->config->get('app.slug');

    // Styles
    Vite::enqueueStyle(
        $slug . '-product-categories-list',
        'public/product-categories-list/product-categories-list.scss'
    );

    // Frontend JS (for dropdown "Go" button)
    Vite::enqueueScript(
        $slug . '-product-categories-list-js',
        'public/product-categories-list/product-categories-list.js'
    );
}
```

---

### Phase 6: Registration

#### 6.1 Update ElementorIntegration.php

```php
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ProductCategoriesListWidget;

public function registerWidgets($widgets_manager)
{
    // Existing widgets...
    $widgets_manager->register(new ProductCategoriesListWidget());
}
```

---

## File Structure (New Files)

```
fluentcart-elementor-blocks/
├── app/
│   └── Modules/
│       └── Integrations/
│           └── Elementor/
│               └── Widgets/
│                   └── ProductCategoriesListWidget.php (NEW)
```

---

## Dependencies

- FluentCart plugin (core) - provides:
  - `ProductCategoriesListRenderer` class
  - `product-categories` taxonomy
  - Frontend CSS/JS assets
- Elementor plugin

---

## API / Data Source

Categories are fetched directly via WordPress `get_terms()`:
```php
$terms = get_terms([
    'taxonomy'   => 'product-categories',
    'hide_empty' => !$showEmpty,
    'orderby'    => 'name',
    'order'      => 'ASC',
    'parent'     => $showHierarchy ? 0 : null, // Root only if hierarchical
]);
```

No REST API calls needed - uses WordPress taxonomy system directly.

---

## Editor Preview Considerations

1. **Disable Link Clicks**: Add `fct-elementor-preview` class with `pointer-events: none` on links
2. **Dropdown Interaction**: Dropdown can be visible but "Go" button should be disabled in editor
3. **Live Preview**: Categories will render live since data comes from taxonomy (no AJAX needed)

---

## Complexity Assessment

| Component | Complexity | Notes |
|-----------|------------|-------|
| Widget PHP Class | Low | Simple controls, reuses core renderer |
| Style Controls | Medium | Multiple sections for list/dropdown styles |
| Asset Loading | Low | Reuse existing FluentCart assets |
| Editor Integration | Low | No custom controls needed |

---

## Differences from Block Editor Version

| Aspect | Block Editor | Elementor |
|--------|--------------|-----------|
| Settings UI | InspectorControls sidebar | Elementor panel controls |
| State Management | React useState/useMemo | PHP render with settings |
| Preview | React component with buildTree() | Direct PHP renderer |
| Styling | Block supports (typography, color) | Elementor style controls |

---

## Notes

- This is a simpler widget compared to ProductCarousel - no product selection, no carousel logic
- Categories come from WordPress taxonomy, automatically updated
- Can reuse `ProductCategoriesListRenderer` directly from fluent-cart core
- Frontend JS only needed for dropdown "Go" button functionality
- Hierarchy rendering is handled recursively by the renderer
