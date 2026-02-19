# Product Gallery: Scrollable Thumbnails & Max Limit — Elementor Widget Plan

## Context

After core implements the scrollable thumbnails and max limit features (see `CORE-SCROLLABLE-THUMBS-PLAN.md`), the Elementor widget needs to expose the two new controls and pass them to `ProductRenderer::renderGallery()`.

Core adds these new parameters to `renderGallery($args)`:
- `scrollable_thumbs` — `'yes'` / `'no'` (default: `'no'`)
- `max_thumbnails` — `null` (no limit) or integer

---

## Changes Required

### 1. Add Controls to `registerGalleryContentControls()` static method

File: `app/Modules/Integrations/Elementor/Widgets/ThemeBuilder/ProductGalleryWidget.php`

Add two new controls after the existing `thumbnail_mode` control:

```php
// After thumbnail_mode control...

$widget->add_control(
    'scrollable_thumbs',
    [
        'label'        => esc_html__('Scrollable Thumbnails', 'fluent-cart'),
        'type'         => Controls_Manager::SWITCHER,
        'label_on'     => esc_html__('Yes', 'fluent-cart'),
        'label_off'    => esc_html__('No', 'fluent-cart'),
        'return_value' => 'yes',
        'default'      => '',
        'description'  => esc_html__('Enable scrolling when thumbnails exceed the main image dimensions.', 'fluent-cart'),
    ]
);

$widget->add_control(
    'max_thumbnails',
    [
        'label'       => esc_html__('Max Thumbnails', 'fluent-cart'),
        'type'        => Controls_Manager::NUMBER,
        'min'         => 1,
        'step'        => 1,
        'default'     => '',
        'description' => esc_html__('Leave empty for no limit. Excess images accessible via "See More" button.', 'fluent-cart'),
    ]
);
```

### 2. Pass New Settings in `render()`

Update the `render()` method to pass the new settings:

```php
$renderer->renderGallery([
    'thumb_position'    => $settings['thumb_position'] ?: 'bottom',
    'thumbnail_mode'    => $settings['thumbnail_mode'] ?: 'all',
    'scrollable_thumbs' => !empty($settings['scrollable_thumbs']) ? 'yes' : 'no',
    'max_thumbnails'    => !empty($settings['max_thumbnails']) ? (int) $settings['max_thumbnails'] : null,
]);
```

### 3. Update STYLE-CONTROLS.md

Update `refs/claude/ProductGallery/STYLE-CONTROLS.md` to document the new controls:

| Section ID | Control ID | Type | Description |
|---|---|---|---|
| `content_section` | `scrollable_thumbs` | Switcher | Enable/disable thumbnail scrolling |
| `content_section` | `max_thumbnails` | Number | Max visible thumbnails (empty = no limit) |

---

## Files to Modify

| File | Changes |
|---|---|
| `ProductGalleryWidget.php` | Add `scrollable_thumbs` switcher + `max_thumbnails` number control in `registerGalleryContentControls()`. Update `render()` to pass them. |
| `refs/claude/ProductGallery/STYLE-CONTROLS.md` | Document the 2 new controls |

---

## Notes

- Since controls are in the static `registerGalleryContentControls()` method, **ProductInfoWidget** automatically gets these controls too (it calls the same static method).
- No CSS changes needed on the Elementor side — all styling is handled by core.
- No JS changes needed on the Elementor side — all behavior is handled by core.
