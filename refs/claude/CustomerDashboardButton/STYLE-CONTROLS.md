# CustomerDashboardButtonWidget — Style Controls Reference

**Widget slug:** `fluent_cart_customer_dashboard_button`
**File:** `app/Modules/Integrations/Elementor/Widgets/CustomerDashboardButtonWidget.php`
**Wraps:** `FluentCart\App\Services\Renderer\CustomerDashboardButtonRenderer`
**Category:** `fluent-cart`

---

## Renderer HTML Output

The renderer outputs one of two CSS classes depending on `display_type`:

| `display_type` | CSS class on `<a>` |
|---|---|
| `button` (default) | `wp-block-button__link wp-element-button fct-customer-dashboard-btn` |
| `link` | `fct-customer-dashboard-link` |

The icon is always wrapped in `<span class="fct-customer-dashboard-icon">` containing an inline SVG.

The `is_shortcode` att must be `true` so the renderer uses its own `renderAttributes()` method instead of `get_block_wrapper_attributes()` (which would conflict with the Elementor wrapper).

---

## Content Controls

| Control ID | Type | Default | Description |
|---|---|---|---|
| `display_type` | SELECT | `button` | `button` or `link` — determines the CSS class on the anchor |
| `button_text` | TEXT | `My Account` | Label text; sanitized with `sanitize_text_field()` |
| `show_icon` | SWITCHER | `yes` | Toggles the inline SVG user icon |
| `link_target` | SELECT | `_self` | `_self` or `_blank`; adding `_blank` also adds `rel="noopener noreferrer"` |

---

## Style Controls

### Section: Button / Link (`button_style_section`)

Both display types are targeted together via a compound selector so controls apply regardless of which type is active.

**Selectors:**

| Variable | Value |
|---|---|
| `$btnSelector` | `{{WRAPPER}} .fct-customer-dashboard-btn, {{WRAPPER}} .fct-customer-dashboard-link` |
| `$btnHoverSelector` | `{{WRAPPER}} .fct-customer-dashboard-btn:hover, {{WRAPPER}} .fct-customer-dashboard-link:hover` |

| Control ID | Type | CSS property | Selector |
|---|---|---|---|
| `button_typography` | Group: Typography | font properties | `$btnSelector` |
| `button_padding` | DIMENSIONS (responsive) | `padding` | `$btnSelector` |
| — | `start_controls_tabs('tabs_button_style')` | — | — |
| **Normal tab** | | | |
| `button_text_color` | COLOR | `color` | `$btnSelector` |
| `button_background` | Group: Background (classic+gradient) | background | `$btnSelector` |
| `button_border` | Group: Border | border | `$btnSelector` |
| `button_border_radius` | DIMENSIONS | `border-radius` | `$btnSelector` |
| `button_box_shadow` | Group: Box Shadow | box-shadow | `$btnSelector` |
| **Hover tab** | | | |
| `button_hover_text_color` | COLOR | `color` | `$btnHoverSelector` |
| `button_hover_background` | Group: Background (classic+gradient) | background | `$btnHoverSelector` |
| `button_hover_border` | Group: Border | border | `$btnHoverSelector` |
| `button_hover_box_shadow` | Group: Box Shadow | box-shadow | `$btnHoverSelector` |

---

### Section: Icon (`icon_style_section`)

Shown only when `show_icon` switcher is `yes` (via `condition`).

**Selectors:**

| Variable | Value |
|---|---|
| `$iconSelector` | `{{WRAPPER}} .fct-customer-dashboard-icon svg` |

| Control ID | Type | CSS property | Selector |
|---|---|---|---|
| `icon_size` | SLIDER (responsive) | `width` + `height` | `$iconSelector` |
| `icon_color` | COLOR | `fill` + `color` | `$iconSelector` |
| `icon_gap` | SLIDER (responsive) | `margin-inline-end` | `{{WRAPPER}} .fct-customer-dashboard-icon` |

---

## Asset Loading

The renderer outputs only HTML — no JS is needed. The widget calls `AssetLoader::markFrontendAssetsRequired()` in `render()` to ensure FluentCart's global frontend CSS bundle is enqueued. The renderer's SCSS (`public/customer-dashboard-button/customer-dashboard-button.scss`) is loaded by core's Vite pipeline when the shortcode or block is rendered; when using this Elementor widget the same stylesheet handle should be enqueued via the core `AssetLoader` if a dedicated method is added in future.

---

## Security Notes

- `display_type` validated with `in_array()` against `['button', 'link']`
- `link_target` validated with `in_array()` against `['_self', '_blank']`
- `show_icon` validated with `in_array()` against `['yes', 'no']`
- `button_text` sanitized with `sanitize_text_field()`
- Renderer output is not double-escaped — the renderer uses `wp_kses_post()` and `esc_attr()` internally
