# Elementor Controls Reference (Supplementary)

> Covers control types and features NOT already documented in our widget reference guide.
> For basic patterns (COLOR, SLIDER, DIMENSIONS, SELECT, SWITCHER, Typography, Border, Box Shadow, Background, Responsive, Normal/Hover tabs), see our existing widgets.
>
> Source: `peixotorms/odinlayer-skills` (elementor-controls + elementor-development skills)

---

## Control Types We Haven't Used Yet

### Text/Input Controls

```php
// WYSIWYG — rich text editor
$this->add_control('content', [
    'label' => esc_html__('Content', 'textdomain'),
    'type' => \Elementor\Controls_Manager::WYSIWYG,
    'default' => '<p>Default content</p>',
]);

// CODE — syntax-highlighted editor
$this->add_control('custom_css', [
    'label' => esc_html__('Custom CSS', 'textdomain'),
    'type' => \Elementor\Controls_Manager::CODE,
    'language' => 'css', // 'html' | 'css' | 'javascript'
    'rows' => 20,
]);

// DATE_TIME — date picker (Flatpickr)
$this->add_control('due_date', [
    'label' => esc_html__('Due Date', 'textdomain'),
    'type' => \Elementor\Controls_Manager::DATE_TIME,
    'default' => gmdate('Y-m-d H:i'),
]);
```

### Selection Controls

```php
// SELECT2 — searchable multi-select
$this->add_control('categories', [
    'label' => esc_html__('Categories', 'textdomain'),
    'type' => \Elementor\Controls_Manager::SELECT2,
    'multiple' => true,
    'options' => ['cat1' => 'Category 1', 'cat2' => 'Category 2'],
    'default' => [],
]);

// SELECT with optgroups
$this->add_control('animation', [
    'type' => \Elementor\Controls_Manager::SELECT,
    'groups' => [
        ['label' => 'Slide', 'options' => ['slide-right' => 'Slide Right', 'slide-left' => 'Slide Left']],
        ['label' => 'Zoom', 'options' => ['zoom-in' => 'Zoom In', 'zoom-out' => 'Zoom Out']],
    ],
]);

// VISUAL_CHOICE — image-based selection
$this->add_control('layout', [
    'type' => \Elementor\Controls_Manager::VISUAL_CHOICE,
    'options' => [
        'grid' => ['title' => 'Grid', 'image' => 'path/to/grid.svg'],
        'list' => ['title' => 'List', 'image' => 'path/to/list.svg'],
    ],
]);
```

### Media/Asset Controls

```php
// MEDIA — image/SVG picker (returns ['id' => int, 'url' => string])
$this->add_control('image', [
    'label' => esc_html__('Choose Image', 'textdomain'),
    'type' => \Elementor\Controls_Manager::MEDIA,
    'media_types' => ['image', 'svg'],
    'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()],
]);

// GALLERY — multiple images (returns array of ['id', 'url'])
$this->add_control('gallery', [
    'label' => esc_html__('Gallery', 'textdomain'),
    'type' => \Elementor\Controls_Manager::GALLERY,
    'default' => [],
]);

// ICONS — icon picker (returns ['value' => string, 'library' => string])
$this->add_control('icon', [
    'label' => esc_html__('Icon', 'textdomain'),
    'type' => \Elementor\Controls_Manager::ICONS,
    'default' => ['value' => 'fas fa-circle', 'library' => 'fa-solid'],
    'recommended' => [
        'fa-solid' => ['circle', 'dot-circle', 'square-full'],
    ],
]);

// URL — link with options (returns ['url', 'is_external', 'nofollow', 'custom_attributes'])
$this->add_control('link', [
    'label' => esc_html__('Link', 'textdomain'),
    'type' => \Elementor\Controls_Manager::URL,
    'placeholder' => 'https://your-link.com',
    'options' => ['url', 'is_external', 'nofollow'],
    'default' => ['url' => '', 'is_external' => true, 'nofollow' => true],
]);

// IMAGE_DIMENSIONS — width/height inputs (returns ['width' => int, 'height' => int])
$this->add_control('image_size', [
    'type' => \Elementor\Controls_Manager::IMAGE_DIMENSIONS,
    'default' => ['width' => '', 'height' => ''],
]);

// FONT — font family picker
$this->add_control('font_family', [
    'type' => \Elementor\Controls_Manager::FONT,
    'default' => "'Open Sans', sans-serif",
]);
```

### UI Controls (Display-Only, No Stored Data)

```php
// ALERT — colored alert box
$this->add_control('notice', [
    'type' => \Elementor\Controls_Manager::ALERT,
    'alert_type' => 'info', // 'info' | 'success' | 'warning' | 'danger'
    'content' => esc_html__('Important notice text', 'textdomain'),
]);

// RAW_HTML — arbitrary HTML in panel
$this->add_control('help_text', [
    'type' => \Elementor\Controls_Manager::RAW_HTML,
    'raw' => '<p>Custom HTML content in the panel</p>',
    'content_classes' => 'elementor-descriptor',
]);

// BUTTON — clickable button in panel
$this->add_control('action_button', [
    'type' => \Elementor\Controls_Manager::BUTTON,
    'text' => esc_html__('Click Me', 'textdomain'),
    'button_type' => 'success', // 'default' | 'success'
    'event' => 'myPlugin:myEvent',
]);

// NOTICE — dismissible notice
$this->add_control('deprecation_warning', [
    'type' => \Elementor\Controls_Manager::NOTICE,
    'notice_type' => 'warning',
    'dismissible' => true,
    'heading' => esc_html__('Notice', 'textdomain'),
    'content' => esc_html__('This feature will be removed.', 'textdomain'),
]);
```

---

## Advanced Features

### Advanced Conditions (Operators)

Beyond basic `'condition' => ['key' => 'value']`, use `conditions` for complex logic:

```php
'conditions' => [
    'relation' => 'or',  // 'and' (default) or 'or'
    'terms' => [
        ['name' => 'type', 'operator' => '===', 'value' => 'video'],
        ['name' => 'type', 'operator' => '===', 'value' => 'slideshow'],
    ],
],
```

**Available operators:** `==`, `!=`, `!==`, `===`, `in`, `!in`, `contains`, `!contains`, `<`, `<=`, `>`, `>=`

Conditions can be nested. Note: repeater inner fields can only depend on other inner fields, NOT outer controls.

### Popover Controls

Group controls in a popup:

```php
$this->add_control('box_toggle', [
    'type' => \Elementor\Controls_Manager::POPOVER_TOGGLE,
    'label' => esc_html__('Options', 'textdomain'),
    'label_off' => esc_html__('Default', 'textdomain'),
    'label_on' => esc_html__('Custom', 'textdomain'),
    'return_value' => 'yes',
]);
$this->start_popover();
// ... controls inside popover ...
$this->end_popover();
```

### Dynamic Content Tags

Enable Elementor Pro dynamic tags on any data control:

```php
$this->add_control('heading', [
    'type' => \Elementor\Controls_Manager::TEXT,
    'dynamic' => ['active' => true],
]);
```

### Global Styles Integration

Inherit from site design system:

```php
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;

// Color with global default
$this->add_control('heading_color', [
    'type' => \Elementor\Controls_Manager::COLOR,
    'global' => ['default' => Global_Colors::COLOR_PRIMARY],
    'selectors' => ['{{WRAPPER}} .heading' => 'color: {{VALUE}};'],
]);

// Typography with global default
$this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
    'name' => 'heading_typo',
    'selector' => '{{WRAPPER}} .heading',
    'global' => ['default' => Global_Typography::TYPOGRAPHY_PRIMARY],
]);
```

**Constants:** `COLOR_PRIMARY`, `COLOR_SECONDARY`, `COLOR_TEXT`, `COLOR_ACCENT`, `TYPOGRAPHY_PRIMARY`, `TYPOGRAPHY_SECONDARY`, `TYPOGRAPHY_TEXT`, `TYPOGRAPHY_ACCENT`

### Frontend Available (JS Access to Settings)

```php
$this->add_control('slides_count', [
    'type' => \Elementor\Controls_Manager::NUMBER,
    'default' => 3,
    'frontend_available' => true,
]);
// Access in JS handler: this.getElementSettings('slides_count')
```

### Cross-Control Value References

Reference another control's value in selectors:

```php
$this->add_control('aspect_width', ['type' => Controls_Manager::NUMBER]);
$this->add_control('aspect_height', [
    'type' => Controls_Manager::NUMBER,
    'selectors' => [
        '{{WRAPPER}} img' => 'aspect-ratio: {{aspect_width.VALUE}} / {{aspect_height.VALUE}};',
    ],
]);
```

### Selectors Dictionary

Transform stored values to CSS values (backward compat, only string-returning controls):

```php
$this->add_control('align', [
    'type' => Controls_Manager::CHOOSE,
    'selectors_dictionary' => [
        'left' => is_rtl() ? 'end' : 'start',
        'right' => is_rtl() ? 'start' : 'end',
    ],
    'selectors' => ['{{WRAPPER}} .el' => 'text-align: {{VALUE}};'],
]);
```

### Customizing Group Control Defaults (fields_options)

```php
$this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
    'name' => 'box_border',
    'fields_options' => [
        'border' => ['default' => 'solid'],
        'width' => ['default' => ['top' => '1', 'right' => '2', 'bottom' => '3', 'left' => '4', 'isLinked' => false]],
        'color' => ['default' => '#D4D4D4'],
    ],
    'selector' => '{{WRAPPER}} .box',
]);
```

### Additional Group Controls

```php
// TEXT SHADOW
$this->add_group_control(\Elementor\Group_Control_Text_Shadow::get_type(), [
    'name' => 'text_shadow',
    'selector' => '{{WRAPPER}} .heading',
]);

// TEXT STROKE
$this->add_group_control(\Elementor\Group_Control_Text_Stroke::get_type(), [
    'name' => 'text_stroke',
    'selector' => '{{WRAPPER}} .heading',
]);

// CSS FILTER (brightness, contrast, saturation, etc.)
$this->add_group_control(\Elementor\Group_Control_Css_Filter::get_type(), [
    'name' => 'css_filters',
    'selector' => '{{WRAPPER}} img',
]);

// IMAGE SIZE (use with MEDIA control)
$this->add_group_control(\Elementor\Group_Control_Image_Size::get_type(), [
    'name' => 'thumbnail',
    'default' => 'large',
    'exclude' => ['custom'],
]);
// Render: Group_Control_Image_Size::get_attachment_image_html($settings, 'thumbnail');
```

---

## Custom Control Creation

```php
class My_Custom_Control extends \Elementor\Base_Data_Control {
    public function get_type(): string { return 'my-custom-control'; }
    protected function get_default_settings(): array { return ['my_setting' => 'default_value']; }
    public function get_default_value(): string { return ''; }

    public function content_template(): void {
        $control_uid = $this->get_control_uid();
        ?>
        <div class="elementor-control-field">
            <# if (data.label) { #>
                <label for="<?php echo $control_uid; ?>" class="elementor-control-title">{{{ data.label }}}</label>
            <# } #>
            <div class="elementor-control-input-wrapper">
                <input id="<?php echo $control_uid; ?>" type="text" data-setting="{{ data.name }}" />
            </div>
        </div>
        <?php
    }

    public function enqueue(): void {
        wp_enqueue_script('my-control-js', plugins_url('assets/js/control.js', __FILE__));
        wp_enqueue_style('my-control-css', plugins_url('assets/css/control.css', __FILE__));
    }
}
```

---

## Widget Rendering Extras

### content_template() — JS Editor Preview

Enables instant preview without server round-trip. Template syntax: `<# ... #>` for logic, `{{ }}` escaped, `{{{ }}}` unescaped.

```php
protected function content_template(): void {
    ?>
    <# if ('' === settings.title) return; #>
    <div {{{ view.getRenderAttributeString('wrapper') }}}>
        <h3 {{{ view.getRenderAttributeString('title') }}}>{{ settings.title }}</h3>
        <# _.each(settings.list, function(item) { #>
            <li>{{{ item.text }}}</li>
        <# }); #>
    </div>
    <?php
}
```

**JS render attributes:** `view.addRenderAttribute()`, `view.addInlineEditingAttributes()`, `view.getRenderAttributeString()`

### Render Attributes (PHP)

```php
$this->add_render_attribute('wrapper', ['id' => 'custom-id', 'class' => ['wrapper', $settings['class']]]);
$this->add_link_attributes('link', $settings['link']); // For URL controls
$this->add_inline_editing_attributes('title', 'none'); // 'none' | 'basic' | 'advanced'
$this->print_render_attribute_string('wrapper');
```

### Widget Optimization Methods

```php
// DOM optimization: single wrapper instead of double
public function has_widget_inner_wrapper(): bool { return false; }

// Output caching for static content
protected function is_dynamic_content(): bool { return false; }
```

### Inline Editing Toolbars

| Mode | Toolbar | Use Case |
|---|---|---|
| `'none'` | No toolbar | Plain text headings |
| `'basic'` | Bold, italic, underline | Short descriptions |
| `'advanced'` | Full (links, headings, lists) | Rich text content |

---

## Selector Value Placeholders Quick Reference

| Control Type | Placeholder |
|---|---|
| String controls (TEXT, SELECT, COLOR, etc.) | `{{VALUE}}` |
| SLIDER | `{{SIZE}}{{UNIT}}` |
| DIMENSIONS | `{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}` |
| URL / MEDIA | `{{URL}}` |
| Cross-control | `{{other_control_id.VALUE}}` |
| Repeater per-item | `{{CURRENT_ITEM}}` with class `elementor-repeater-item-{$item['_id']}` |
| Element ID | `{{ID}}` (discouraged — prefer `{{WRAPPER}}`) |

---

## Common Mistakes

| Mistake | Fix |
|---|---|
| Using `_register_controls()` with underscore | Use `register_controls()` (no underscore) |
| Using `scheme` for colors/typography | Use `global` with `Global_Colors`/`Global_Typography` |
| `add_control()` outside a section | Always wrap in `start_controls_section()` / `end_controls_section()` |
| `selector` (singular) on non-group controls | Non-group = `selectors` (plural, array). Group = `selector` (singular, string). |
| `{{VALUE}}` with SLIDER | Use `{{SIZE}}{{UNIT}}` |
| `{{VALUE}}` with DIMENSIONS | Use `{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}}...` |
| SWITCHER default as `true` or `1` | Default should be `'yes'` or `''` (string) |
| Nesting sections | Sections can't nest. End one before starting another. |
| Tabs outside a section | `start_controls_tabs()` must be inside a section |
| Repeater field depending on outer control | Not supported — inner fields can only depend on inner fields |
| Missing `has_widget_inner_wrapper` returning false | Return `false` to reduce DOM nodes |
| Not implementing `content_template()` | Without it, editor preview requires server round-trip every change |
| Enqueueing scripts globally | Register with `wp_register_script`, declare via `get_script_depends()` |
