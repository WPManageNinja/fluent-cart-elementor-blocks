# StoreLogoWidget — Style Controls Reference

**Widget file:** `app/Modules/Integrations/Elementor/Widgets/StoreLogoWidget.php`
**Widget slug:** `fluent_cart_store_logo`
**Renderer:** `FluentCart\App\Services\Renderer\StoreLogoRenderer`
**Category:** `fluent-cart`
**Icon:** `eicon-site-logo`

---

## Renderer HTML Structure

`StoreLogoRenderer::render()` outputs:

```html
<!-- With link (is_link = true) -->
<div>
  <a href="/" target="_self" rel="" class="fct-store-logo-link">
    <!-- If logo URL is set: -->
    <img src="..." alt="Store Name" class="fct-store-logo-img" style="--max-width:150px; --max-height:70px;">
    <!-- If no logo URL (fallback): -->
    <span class="fct-store-logo-text">Store Name</span>
  </a>
</div>

<!-- Without link (is_link = false) -->
<div>
  <div class="fct-store-logo-without-link">
    <img ...> OR <span class="fct-store-logo-text">...</span>
  </div>
</div>
```

When called with `is_shortcode = true` (which this widget always passes), the outer `<div>` has **no** `get_block_wrapper_attributes()` attributes — it renders as a plain `<div>`.

> Note: The renderer controls `max-width` and `max-height` via inline CSS variables on the `<img>` tag. The widget's Elementor style controls override these using Elementor's CSS output (`max-width: {{SIZE}}{{UNIT}}`) targeting `.fct-store-logo-img` directly, which takes precedence as Elementor injects a `<style>` block with higher specificity.

---

## Content Controls

| Control ID   | Type     | Default | Description                                         |
|--------------|----------|---------|-----------------------------------------------------|
| `link_to`    | SELECT   | `home`  | `home` = wrap in `<a href="/">`, `none` = no link   |
| `link_target`| SWITCHER | `''`    | `yes` = `_blank`, `''` = `_self`. Only when `link_to = home` |

---

## Style Sections & Controls

### Section: Logo Image (`logo_style_section`)

**Method:** `StoreLogoWidget::registerLogoStyleControls($widget, $selector)`

Default `$selector`: `{{WRAPPER}} .fct-store-logo-img`

| Control ID       | Type   | Units          | Range (px)  | Default      | CSS Property  |
|------------------|--------|----------------|-------------|--------------|---------------|
| `logo_max_width` | SLIDER (responsive) | `px`, `%`, `vw` | 20–500 px | `150px` | `max-width` |
| `logo_max_height`| SLIDER (responsive) | `px`, `em`, `vh` | 10–300 px | — (empty) | `max-height` |

---

### Section: Alignment (`alignment_style_section`)

**Method:** `StoreLogoWidget::registerAlignmentStyleControls($widget, $selector)`

Default `$selector`: `{{WRAPPER}} .fct-store-logo-wrapper`

| Control ID       | Type   | Options               | CSS Property |
|------------------|--------|-----------------------|--------------|
| `logo_alignment` | CHOOSE (responsive) | `left`, `center`, `right` | `text-align` |

---

### Section: Store Name Fallback (`store_name_style_section`)

**Method:** `StoreLogoWidget::registerStoreNameStyleControls($widget, $selector)`

Default `$selector`: `{{WRAPPER}} .fct-store-logo-text`

Applies only when no logo URL is configured — the renderer falls back to a `<span class="fct-store-logo-text">` text node.

| Control ID              | Type       | Description                  |
|-------------------------|------------|------------------------------|
| `store_name_typography` | GROUP (Typography) | Font size, family, weight, etc. |
| `store_name_color`      | COLOR      | Text color of store name fallback |

---

## Render Method: Attribute Mapping

| Elementor Setting | Renderer Att   | Validation                              |
|-------------------|----------------|-----------------------------------------|
| `link_to`         | `is_link`      | `in_array(['home','none'])`, `home` = `true` |
| `link_target`     | `link_target`  | `yes` → `_blank`, else `_self`          |
| —                 | `is_shortcode` | Always `true` (suppresses block wrapper attributes) |

---

## Asset Loading

- No JS assets required (`get_script_depends()` not declared).
- `AssetLoader::markFrontendAssetsRequired()` is called in both `get_style_depends()` and `render()` to ensure FluentCart's global frontend CSS loads.
- No `get_style_depends()` handle list is returned (returns `[]`) — FluentCart's global CSS is loaded via the mark mechanism.

---

## Consuming Static Methods in Other Widgets

All three style methods are `public static` and can be reused:

```php
// Logo image sizing
StoreLogoWidget::registerLogoStyleControls($this, '{{WRAPPER}} .fct-store-logo-img');

// Wrapper alignment
StoreLogoWidget::registerAlignmentStyleControls($this, '{{WRAPPER}} .fct-store-logo-wrapper');

// Fallback text name styling
StoreLogoWidget::registerStoreNameStyleControls($this, '{{WRAPPER}} .fct-store-logo-text');
```
