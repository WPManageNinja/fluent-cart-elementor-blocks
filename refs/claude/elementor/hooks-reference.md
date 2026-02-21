# Elementor Hooks Reference

> Complete hooks reference for Elementor PHP actions/filters, JS APIs, and control injection.
> Our project currently has no hooks documentation — this fills that gap.
>
> Source: `peixotorms/odinlayer-skills` (elementor-hooks skill)

---

## PHP Action Hooks

### Lifecycle

| Hook | Parameters | When to Use |
|---|---|---|
| `elementor/loaded` | none | Check Elementor availability before using its classes |
| `elementor/init` | none | Register custom functionality after Elementor fully loads |

### Registration

| Hook | Parameters | Method |
|---|---|---|
| `elementor/widgets/register` | `Widgets_Manager` | `register()` / `unregister()` |
| `elementor/controls/register` | `Controls_Manager` | `register()` / `unregister()` |
| `elementor/dynamic_tags/register` | `Dynamic_Tags_Manager` | `register()` / `unregister()` |
| `elementor/finder/register` | `Categories_Manager` | `register()` / `unregister()` |
| `elementor/elements/categories_registered` | `Elements_Manager` | `add_category()` |
| `elementor/documents/register` | `Documents_Manager` | `register_document_type()` |

### Frontend Scripts & Styles

| Hook | When |
|---|---|
| `elementor/frontend/before_register_scripts` | Before frontend scripts registered |
| `elementor/frontend/after_register_scripts` | After frontend scripts registered |
| `elementor/frontend/before_enqueue_scripts` | Before frontend scripts enqueued |
| `elementor/frontend/after_enqueue_scripts` | After frontend scripts enqueued |
| `elementor/frontend/before_register_styles` | Before frontend styles registered |
| `elementor/frontend/after_register_styles` | After frontend styles registered |
| `elementor/frontend/before_enqueue_styles` | Before frontend styles enqueued |
| `elementor/frontend/after_enqueue_styles` | After frontend styles enqueued |

### Editor Scripts & Styles

| Hook | When |
|---|---|
| `elementor/editor/before_enqueue_scripts` | Before editor scripts enqueued |
| `elementor/editor/after_enqueue_scripts` | After editor scripts enqueued |
| `elementor/editor/before_enqueue_styles` | Before editor styles enqueued |
| `elementor/editor/after_enqueue_styles` | After editor styles enqueued |

### Preview

| Hook | When |
|---|---|
| `elementor/preview/enqueue_scripts` | Enqueue scripts in preview iframe |
| `elementor/preview/enqueue_styles` | Enqueue styles in preview iframe |

### Widget Rendering

| Hook | Parameters | When |
|---|---|---|
| `elementor/widget/{$name}/skins_init` | `Widget_Base` | Register custom skins |
| `elementor/widget/before_render_content` | `Widget_Base` | Before widget content renders |
| `elementor/frontend/before_render` | `Element_Base` | Before any element renders |
| `elementor/frontend/after_render` | `Element_Base` | After any element renders |
| `elementor/frontend/{$type}/before_render` | `Element_Base` | Before specific type renders |
| `elementor/frontend/{$type}/after_render` | `Element_Base` | After specific type renders |

Element types: `section`, `column`, `container`, `widget`

### Document & Save

| Hook | Parameters |
|---|---|
| `elementor/documents/register_controls` | `Document` |
| `elementor/editor/after_save` | `int $post_id, array $editor_data` |
| `elementor/document/before_save` | `Document, array $data` |
| `elementor/document/after_save` | `Document, array $data` |

### CSS File Hooks

| Hook | Parameters | Use |
|---|---|---|
| `elementor/element/parse_css` | `Post $post_css, Element_Base` | Add custom CSS rules |
| `elementor/element/before_parse_css` | `Post $post_css, Element_Base` | Before CSS parsed |
| `elementor/css-file/{$name}/enqueue` | `CSS_File` | When CSS file enqueued |
| `elementor/core/files/clear_cache` | none | When CSS cache cleared |

### Forms (Pro)

| Hook | Parameters |
|---|---|
| `elementor_pro/forms/validation` | `Form_Record, Ajax_Handler` |
| `elementor_pro/forms/validation/{$field_type}` | `array $field, Form_Record, Ajax_Handler` |
| `elementor_pro/forms/process` | `Form_Record, Ajax_Handler` |
| `elementor_pro/forms/new_record` | `Form_Record, Ajax_Handler` |
| `elementor_pro/forms/mail_sent` | `array $settings, Form_Record` |

### Query (Pro)

```php
// Filter Posts/Portfolio widget query. Set Query ID in widget settings.
add_action('elementor/query/{$query_id}', function($query, $widget) {
    $query->set('post_type', 'custom_post_type');
}, 10, 2);
```

---

## PHP Filter Hooks

### Widget Output

| Filter | Parameters | Returns |
|---|---|---|
| `elementor/widget/render_content` | `string $content, Widget_Base` | Modified HTML |
| `elementor/{$type}/print_template` | `string $template, Widget_Base` | Modified JS template |
| `elementor/frontend/the_content` | `string $content` | Modified page output |
| `elementor/frontend/{$type}/should_render` | `bool, Element_Base` | Whether element renders |

### Visual Elements

| Filter | Purpose |
|---|---|
| `elementor/frontend/print_google_fonts` | Return `false` to disable Google Fonts |
| `elementor/shapes/additional_shapes` | Add custom shape dividers |
| `elementor/mask_shapes/additional_shapes` | Add custom mask shapes |
| `elementor/utils/get_placeholder_image_src` | Change default placeholder image |
| `elementor/icons_manager/additional_tabs` | Add custom icon libraries |
| `elementor/fonts/additional_fonts` | Add custom fonts |
| `elementor/controls/animations/additional_animations` | Add custom animations |

---

## Injecting Controls into Existing Widgets

### Hook Patterns

| Hook | Use |
|---|---|
| `elementor/element/before_section_start` | Add new section before existing |
| `elementor/element/after_section_start` | Add control inside start of existing section |
| `elementor/element/before_section_end` | Add control inside end of existing section |
| `elementor/element/after_section_end` | Add new section after existing |

All receive: `$element, $section_id, $args` (3 params)

### Targeting a Specific Widget + Section

```php
// Add control to the heading widget's title section
add_action('elementor/element/heading/section_title/before_section_end', function($element, $args) {
    $element->add_control('custom_control', [
        'type' => \Elementor\Controls_Manager::NUMBER,
        'label' => esc_html__('Custom Control', 'textdomain'),
    ]);
}, 10, 2);
```

Pattern: `elementor/element/{$widget_name}/{$section_id}/{position}`

### Page Settings

```php
add_action('elementor/documents/register_controls', function($document) {
    $document->start_controls_section('my_section', [
        'tab' => \Elementor\Controls_Manager::TAB_SETTINGS,
        'label' => esc_html__('My Settings', 'textdomain'),
    ]);
    $document->add_control('my_setting', [
        'type' => \Elementor\Controls_Manager::TEXT,
        'label' => esc_html__('My Setting', 'textdomain'),
    ]);
    $document->end_controls_section();
});
```

---

## JS Frontend Hooks

Available on frontend pages via `elementorFrontend.hooks`:

### Actions

```js
// Any element ready
elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope) { ... });

// Specific widget ready (format: widgetType.skinName)
elementorFrontend.hooks.addAction('frontend/element_ready/image.default', function($scope) {
    $scope.find('a').lightbox();
});

// Frontend init
elementorFrontend.hooks.addAction('elementor/frontend/init', function() { ... });
```

### Filters

```js
// Adjust menu anchor scroll offset
elementorFrontend.hooks.addFilter(
    'frontend/handlers/menu_anchor/scroll_top_distance',
    function(scrollTop) { return scrollTop - 80; }
);
```

---

## JS Editor Hooks

Available in editor via `elementor.hooks`:

```js
// Any widget panel opens
elementor.hooks.addAction('panel/open_editor/widget', function(panel, model, view) {
    console.log('Editing:', model.get('widgetType'));
});

// Specific widget panel opens
elementor.hooks.addAction('panel/open_editor/widget/slider', function(panel, model, view) { ... });
```

---

## JS Commands API (`$e.commands`)

### Key Methods

| Method | Returns | Purpose |
|---|---|---|
| `$e.run(command, args)` | `*` | Execute a command |
| `$e.commands.register(component, command, callback)` | `Commands` | Register command |
| `$e.commands.getAll()` | `Object` | All registered commands |
| `$e.commands.is(command)` | `Boolean` | Check if running |

### Custom Component + Command

```js
class ExampleCommand extends $e.modules.CommandBase {
    apply(args) { return { result: 'done' }; }
}

class CustomComponent extends $e.modules.ComponentBase {
    getNamespace() { return 'custom-component'; }
    defaultCommands() {
        return { example: (args) => (new ExampleCommand(args)).run() };
    }
}

$e.components.register(new CustomComponent());
$e.run('custom-component/example', { property: 'value' });
```

---

## JS Hooks API (`$e.hooks`)

Hooks fire before/after commands run via `$e.run()`:

| Method | When |
|---|---|
| `$e.hooks.registerUIBefore(instance)` | Before command (UI) |
| `$e.hooks.registerUIAfter(instance)` | After command (UI) |
| `$e.hooks.registerUICatch(instance)` | Command fails (UI) |
| `$e.hooks.registerDataDependency(instance)` | Before command (data) |
| `$e.hooks.registerDataAfter(instance)` | After command (data) |
| `$e.hooks.registerDataCatch(instance)` | Command fails (data) |

### Hook Class Convention

```js
import HookUIAfter from 'elementor-api/modules/hooks/ui/after';

export class MyHook extends HookUIAfter {
    getCommand() { return 'document/elements/settings'; }
    getId() { return 'my-hook--document/elements/settings'; }
    getContainerType() { return 'document'; } // optional, improves perf
    getConditions(args) { return args.settings && typeof args.settings.post_status !== 'undefined'; }
    apply(args) { /* do something */ }
}
```

---

## Quick Lookup: "I want to..."

| Goal | Hook |
|---|---|
| Register a custom widget | `elementor/widgets/register` (action) |
| Register a custom control | `elementor/controls/register` (action) |
| Add a widget category | `elementor/elements/categories_registered` (action) |
| Register a dynamic tag | `elementor/dynamic_tags/register` (action) |
| Enqueue frontend script | `elementor/frontend/after_register_scripts` (action) |
| Enqueue editor script | `elementor/editor/after_enqueue_scripts` (action) |
| Enqueue preview script | `elementor/preview/enqueue_scripts` (action) |
| Add control to existing widget | `elementor/element/{widget}/{section}/before_section_end` (action) |
| Add page settings control | `elementor/documents/register_controls` (action) |
| Modify widget HTML output | `elementor/widget/render_content` (filter) |
| Modify widget JS template | `elementor/widget/print_template` (filter) |
| Filter entire page output | `elementor/frontend/the_content` (filter) |
| Disable Google Fonts | `elementor/frontend/print_google_fonts` return false (filter) |
| Run code when widget ready (JS) | `frontend/element_ready/{widget.skin}` via `elementorFrontend.hooks` |
| Hook into editor panel open (JS) | `panel/open_editor/{type}/{name}` via `elementor.hooks` |
| Register editor command (JS) | `$e.commands.register()` |
| Hook before/after command (JS) | `$e.hooks.registerUIBefore()` / `$e.hooks.registerUIAfter()` |
| Add custom CSS rules | `elementor/element/parse_css` (action) |
| Filter posts widget query (Pro) | `elementor/query/{$query_id}` (action) |
| Validate form data (Pro) | `elementor_pro/forms/validation` (action) |
