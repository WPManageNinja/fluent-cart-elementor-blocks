# Product Gallery: Scrollable Thumbnails & Max Limit — Core Implementation Plan

## Context

The product gallery thumbnail strip (`renderGalleryThumbControls`) currently renders ALL thumbnails in a single row/column. When a product has many images, this looks bad — thumbnails overflow and break the layout.

This plan adds two features to `ProductRenderer::renderGallery()` in FluentCart core:

1. **Scrollable thumbnails** — constrain the thumbnail strip to the main image dimensions and scroll overflow
2. **Max thumbnails limit** — cap visible thumbnails with a "See More" button that opens the lightbox

### Consumers

These changes affect all gallery consumers:
- **Elementor** — `ProductGalleryWidget` + `ProductInfoWidget` (passes args to `renderGallery()`)
- **Bricks** — `ProductGallery` element (passes args to `renderGallery()`)
- **Block Editor** — `ProductGalleryBlockEditor` (passes args to `renderGallery()`)

Each consumer will pass the new settings; core handles all rendering, CSS, and JS.

---

## New Parameters for `renderGallery($args)`

Add to the `$defaults` array in `renderGallery()`:

```php
$defaults = [
    'thumbnail_mode'      => 'all',      // existing: horizontal, vertical
    'thumb_position'      => 'bottom',   // existing: bottom, left, right, top
    'scrollable_thumbs'   => 'no',       // NEW: yes / no
    'max_thumbnails'      => null,       // NEW: null = no limit, integer = max visible
];
```

---

## Feature 1: Scrollable Thumbnails

### When `scrollable_thumbs` = `'yes'`

#### Behavior by Position

| `thumb_position` | Scroll direction | Container constraint |
|---|---|---|
| `bottom` | Horizontal (`overflow-x: auto`) | Width matches main image width |
| `top` | Horizontal (`overflow-x: auto`) | Width matches main image width |
| `left` | Vertical (`overflow-y: auto`) | Height matches main image height |
| `right` | Vertical (`overflow-y: auto`) | Height matches main image height |

#### Implementation

1. **Add a data attribute** to the wrapper div (`.fct-product-gallery-wrapper`) so CSS can target it:
   ```html
   <div class="fct-product-gallery-wrapper thumb-pos-bottom thumb-mode-all"
        data-scrollable-thumbs="yes"
        ...>
   ```

2. **CSS** — Add styles for when `data-scrollable-thumbs="yes"` is present:
   ```css
   /* Horizontal scroll for top/bottom positions */
   .fct-product-gallery-wrapper[data-scrollable-thumbs="yes"].thumb-pos-bottom .fct-gallery-thumb-controls,
   .fct-product-gallery-wrapper[data-scrollable-thumbs="yes"].thumb-pos-top .fct-gallery-thumb-controls {
       overflow-x: auto;
       overflow-y: hidden;
       flex-wrap: nowrap;
       /* Width is naturally constrained by the parent/main image */
   }

   /* Vertical scroll for left/right positions */
   .fct-product-gallery-wrapper[data-scrollable-thumbs="yes"].thumb-pos-left .fct-gallery-thumb-controls,
   .fct-product-gallery-wrapper[data-scrollable-thumbs="yes"].thumb-pos-right .fct-gallery-thumb-controls {
       overflow-y: auto;
       overflow-x: hidden;
       flex-wrap: nowrap;
       /* Height should match the main image height — use JS or max-height */
   }
   ```

3. **JS (optional but recommended for left/right)** — For left/right positions, the thumbnail container height should match the main image height dynamically. Either:
   - Use a small JS snippet that sets `max-height` on `.fct-gallery-thumb-controls` to match `.fct-product-gallery-thumb img` height on load/resize
   - Or use CSS `max-height: 100%` if the parent flex layout already constrains it (check existing CSS)

4. **Scrollbar styling** — Consider thin/subtle scrollbar styling:
   ```css
   .fct-gallery-thumb-controls::-webkit-scrollbar {
       width: 4px;
       height: 4px;
   }
   .fct-gallery-thumb-controls::-webkit-scrollbar-thumb {
       background: rgba(0, 0, 0, 0.2);
       border-radius: 2px;
   }
   ```

---

## Feature 2: Max Thumbnails Limit

### When `max_thumbnails` is set (non-null integer)

#### PHP Changes in `renderGalleryThumbControl()`

Currently loops through ALL images:

```php
public function renderGalleryThumbControl()
{
    foreach ($this->images as $imageId => $image) {
        // renders ALL thumbnails
    }
}
```

**Change to:**

```php
public function renderGalleryThumbControl($maxThumbnails = null)
{
    $count = 0;
    $totalImages = 0;

    // First, count total renderable images
    foreach ($this->images as $imageId => $image) {
        if (empty($image['media']) || !is_array($image['media'])) {
            continue;
        }
        foreach ($image['media'] as $item) {
            if (!empty(Arr::get($item, 'url', ''))) {
                $totalImages++;
            }
        }
    }

    // Then render up to max
    foreach ($this->images as $imageId => $image) {
        if (empty($image['media']) || !is_array($image['media'])) {
            continue;
        }

        foreach ($image['media'] as $item) {
            if (empty(Arr::get($item, 'url', ''))) {
                continue;
            }

            if ($maxThumbnails !== null && $count >= $maxThumbnails) {
                // Render "See More" button and stop
                $this->renderGallerySeeMoreButton($totalImages - $maxThumbnails);
                return;
            }

            $this->renderGalleryThumbControlButton($item, $imageId);
            $count++;
        }
    }
}
```

#### New Method: `renderGallerySeeMoreButton()`

```php
public function renderGallerySeeMoreButton($remainingCount)
{
    ?>
    <button
        type="button"
        class="fct-gallery-see-more-button"
        data-fluent-cart-gallery-see-more
        aria-label="<?php echo esc_attr(
            sprintf(__('View all %d images', 'fluent-cart'), $remainingCount + 1)
        ); ?>"
    >
        <span class="fct-see-more-count">+<?php echo esc_html($remainingCount); ?></span>
        <span class="fct-see-more-text"><?php echo esc_html__('More', 'fluent-cart'); ?></span>
    </button>
    <?php
}
```

#### JS: "See More" Button Click Handler

When `[data-fluent-cart-gallery-see-more]` is clicked, trigger the lightbox gallery with ALL product images (not just the visible thumbnails). The lightbox should already exist in core — just need to trigger it.

```js
document.addEventListener('click', function(e) {
    const seeMoreBtn = e.target.closest('[data-fluent-cart-gallery-see-more]');
    if (!seeMoreBtn) return;

    const galleryWrapper = seeMoreBtn.closest('[data-fct-product-gallery]');
    // Trigger lightbox with all images
    // (depends on existing lightbox implementation — adapt accordingly)
});
```

#### CSS: "See More" Button Styling

```css
.fct-gallery-see-more-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    background: rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    min-width: 70px;
    min-height: 70px;
    transition: background 0.2s;
}

.fct-gallery-see-more-button:hover {
    background: rgba(0, 0, 0, 0.1);
}

.fct-see-more-count {
    font-size: 16px;
    font-weight: 600;
}

.fct-see-more-text {
    font-size: 12px;
    opacity: 0.7;
}
```

---

## Wiring It Together in `renderGallery()`

Update `renderGallery()` to pass the new args through:

```php
public function renderGallery($args = [])
{
    $defaults = [
        'thumbnail_mode'    => 'all',
        'thumb_position'    => 'bottom',
        'scrollable_thumbs' => 'no',       // NEW
        'max_thumbnails'    => null,        // NEW
    ];

    $atts = wp_parse_args($args, $defaults);
    $thumbnailMode = $atts['thumbnail_mode'];

    $wrapperAtts = [
        'class'                                    => 'fct-product-gallery-wrapper thumb-pos-' . $atts['thumb_position'] . ' thumb-mode-' . $thumbnailMode,
        'data-fct-product-gallery'                 => '',
        'data-fluent-cart-product-gallery-wrapper'  => '',
        'data-thumbnail-mode'                      => $thumbnailMode,
        'data-product-id'                          => $this->product->ID,
        'data-scrollable-thumbs'                   => $atts['scrollable_thumbs'],  // NEW
    ];

    ?>
    <div <?php RenderHelper::renderAtts($wrapperAtts); ?>>
        <?php
            $this->renderGalleryThumb();
            $this->renderGalleryThumbControls($atts['max_thumbnails']); // PASS max
        ?>
    </div>
    <?php
}
```

Update `renderGalleryThumbControls()` to accept and pass `$maxThumbnails`:

```php
public function renderGalleryThumbControls($maxThumbnails = null)
{
    $totalThumbImages = Arr::pluck($this->images, 'media.*.url');

    if (count($totalThumbImages) == 1 && count($totalThumbImages[0]) == 1) {
        return '';
    }

    ?>
    <div class="fct-gallery-thumb-controls" data-fluent-cart-single-product-page-product-thumbnail-controls>
        <?php $this->renderGalleryThumbControl($maxThumbnails); ?>
    </div>
    <?php
}
```

---

## Summary of Files to Modify

| File | Changes |
|---|---|
| `app/Services/Renderer/ProductRenderer.php` | Add `scrollable_thumbs` + `max_thumbnails` to defaults, pass through to wrapper attrs and thumb control methods. Add `renderGallerySeeMoreButton()`. Modify `renderGalleryThumbControl()` to respect max limit. |
| Gallery CSS file | Add scrollable styles (overflow, flex-wrap, scrollbar), "See More" button styles |
| Gallery JS file | Add height-matching logic for left/right scroll, "See More" click handler to trigger lightbox |

---

## How Both Features Interact

| `scrollable_thumbs` | `max_thumbnails` | Behavior |
|---|---|---|
| `no` | `null` | Current behavior — all thumbs, no scroll, no limit |
| `yes` | `null` | All thumbs shown, scrollable container |
| `no` | `10` | Show 10 thumbs + "See More" button, no scroll |
| `yes` | `10` | Show 10 thumbs + "See More" button, scrollable container |
