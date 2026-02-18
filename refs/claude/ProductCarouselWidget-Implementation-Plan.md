# Product Carousel Widget - Elementor Implementation Plan

## Overview

This document outlines the plan to replicate the WordPress Block Editor `ProductCarouselBlockEditor` as an Elementor widget in the `fluentcart-elementor-blocks` plugin.

## Source Files Reference (fluent-cart plugin)

| File | Purpose |
|------|---------|
| `ProductCarouselBlockEditor.php` | PHP class for block registration, rendering, asset loading |
| `ProductCarouselBlockEditor.jsx` | Main React component for block editor UI |
| `InspectorSettings.jsx` | Settings panel (sidebar controls) |
| `ProductCarouselLoopBlock.jsx` | Inner block for rendering product loop |
| `InnerBlocks.php` | PHP class for inner blocks, render callbacks |
| `product-carousel.js` | Frontend Swiper initialization |
| `SelectProductModal.jsx` / `ProductSelector.jsx` | Product selection modal in block editor |

---

## Features to Implement

### 1. Product Selection
- **Block Editor**: Uses `SelectProductModal` with `ProductSelector` - a modal with search + checkbox list
- **Elementor Approach**: Create a new custom control `ProductSelectControl` (similar to existing `ProductVariationSelectControl`)
  - Multi-select Select2 control
  - AJAX search for products via `/products` endpoint
  - Displays selected product chips/tags

### 2. Carousel Layout Settings
| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `slidesToShow` | Range (1-6) | 3 | Number of visible slides |
| `autoplay` | Toggle | yes | Auto-advance slides |
| `arrows` | Toggle | yes | Show prev/next navigation |
| `arrowsSize` | Select | md | Arrow size (sm/md/lg) |
| `dots` | Toggle | yes | Show pagination |
| `paginationType` | Select | dots | Pagination type (dots/fraction/progress) |
| `infinite` | Toggle | no | Infinite loop |

### 3. Card Elements (Product Display)
Based on ShopApp inner blocks, each product card shows:
- Product Image
- Product Title
- Product Excerpt (optional)
- Product Price
- Buy Buttons (Add to Cart / Buy Now)

### 4. Styling Controls
Following existing ShopAppWidget pattern:
- **Product Card**: Background, Border, Border Radius, Box Shadow, Padding
- **Product Image**: Height, Object Fit, Border, Border Radius
- **Product Title**: Typography, Color, Hover Color
- **Product Price**: Typography, Color
- **Product Button**: Typography, Colors (Normal/Hover), Background, Border, Padding
- **Carousel Arrows**: Color, Background, Size
- **Pagination Dots**: Color (active/inactive), Size

---

## Implementation Tasks

### Phase 1: Custom Control for Product Selection

#### 1.1 Create `ProductSelectControl.php`
```
Location: app/Modules/Integrations/Elementor/Controls/ProductSelectControl.php
```
- Extend `Control_Select2`
- Return type: `fluent_product_select`
- Support multiple selection

#### 1.2 Create `product-select-control.js`
```
Location: resources/elementor/product-select-control.js
```
- Extend Elementor's `BaseData` control
- Initialize Select2 with AJAX
- Endpoint: `products` (existing API)
- Support multi-select mode
- Handle initial value population

#### 1.3 Register Control
Update `ElementorIntegration.php`:
- Register `ProductSelectControl` in `registerControls()`
- Add script enqueue for the new control JS

---

### Phase 2: Widget PHP Class

#### 2.1 Create `ProductCarouselWidget.php`
```
Location: app/Modules/Integrations/Elementor/Widgets/ProductCarouselWidget.php
```

**Methods to implement:**

| Method | Purpose |
|--------|---------|
| `get_name()` | Return `fluent_cart_product_carousel` |
| `get_title()` | Return "Product Carousel" |
| `get_icon()` | Return `eicon-carousel` |
| `get_categories()` | Return `['fluent-cart']` |
| `get_keywords()` | Return carousel-related keywords |
| `get_style_depends()` | Return required CSS dependencies |
| `get_script_depends()` | Return Swiper JS dependency |
| `register_controls()` | Define all Elementor controls |
| `render()` | Output carousel HTML |

**Control Sections:**

1. **Content > Product Selection**
   - `product_ids` - Multi-select product control (custom control)

2. **Content > Carousel Settings**
   - `slides_to_show` - Number control (1-6)
   - `autoplay` - Switcher
   - `autoplay_speed` - Number (ms)
   - `infinite_loop` - Switcher
   - `show_arrows` - Switcher
   - `arrow_size` - Select (sm/md/lg)
   - `show_pagination` - Switcher
   - `pagination_type` - Select (dots/fraction/progress)

3. **Content > Card Layout** (Repeater)
   - `element_type` - Select (image/title/excerpt/price/button)

4. **Style > Product Card**
   - Background, Border, Border Radius, Box Shadow, Padding

5. **Style > Product Image**
   - Height, Object Fit, Border, Border Radius, Padding

6. **Style > Product Title**
   - Typography, Color, Hover Color, Margin

7. **Style > Product Price**
   - Typography, Color, Margin

8. **Style > Product Button**
   - Typography, Colors, Background, Border, Border Radius, Padding

9. **Style > Carousel Navigation**
   - Arrow Color, Arrow Background, Arrow Size
   - Dot Color (active/inactive), Dot Size

---

### Phase 3: Frontend Rendering

#### 3.1 Render Method Implementation
The `render()` method should:

1. Get settings from `$this->get_settings_for_display()`
2. Validate `product_ids` - show placeholder if empty
3. Fetch products from database using `Product::query()->whereIn('id', $product_ids)`
4. Load required assets:
   - `AssetLoader::loadProductArchiveAssets()`
   - Swiper CSS/JS
   - Product carousel CSS
5. Build carousel settings JSON for frontend
6. Output HTML structure:

```html
<div class="fluent-cart-elementor-product-carousel">
    <div class="fct-product-carousel-wrapper">
        <div class="swiper fct-product-carousel"
             data-fluent-cart-product-carousel
             data-carousel-settings="...JSON...">
            <div class="swiper-wrapper">
                <!-- For each product -->
                <div class="swiper-slide">
                    <div class="fct-product-card">
                        <!-- Card elements based on repeater order -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation arrows (if enabled) -->
        <div class="fct-carousel-controls">
            <div class="swiper-button-prev">...</div>
            <div class="swiper-button-next">...</div>
        </div>

        <!-- Pagination (if enabled) -->
        <div class="fct-carousel-pagination">
            <div class="swiper-pagination"></div>
        </div>
    </div>
</div>
```

#### 3.2 Create Carousel Renderer
```
Location: app/Modules/Integrations/Elementor/Renderers/ElementorCarouselRenderer.php
```
- Helper class to render product cards within carousel
- Reuse `ProductCardRender` from fluent-cart core

---

### Phase 4: Asset Management

#### 4.1 Swiper Assets
Need to enqueue Swiper library:
- `public/lib/swiper/swiper-bundle.min.js`
- `public/lib/swiper/swiper-bundle.min.css`

**Option A**: Use FluentCart's bundled Swiper
```php
Vite::enqueueStaticScript('fluentcart-swiper-js', 'public/lib/swiper/swiper-bundle.min.js');
Vite::enqueueStaticStyle('fluentcart-swiper-css', 'public/lib/swiper/swiper-bundle.min.css');
```

**Option B**: Bundle Swiper in this plugin (if independent of main plugin)

#### 4.2 Product Carousel Styles
Create SCSS file:
```
Location: resources/scss/widgets/product-carousel.scss
```
- Carousel container styles
- Arrow/navigation styles (sm/md/lg sizes)
- Pagination styles (dots/fraction/progress)
- Responsive breakpoints

#### 4.3 Frontend JavaScript
Either:
- Reuse `product-carousel.js` from fluent-cart (enqueue it)
- Or copy/adapt the initialization code

---

### Phase 5: Registration & Integration

#### 5.1 Update ElementorIntegration.php

```php
public function registerWidgets($widgets_manager)
{
    // Existing widgets...
    $widgets_manager->register(new ProductCarouselWidget());
}

public function registerControls($controls_manager)
{
    // Existing controls...
    $controls_manager->register(new ProductSelectControl());
}
```

#### 5.2 Update Vite Config (if needed)
Add new entry points for:
- Product select control JS
- Product carousel SCSS

---

## File Structure (New Files)

```
fluentcart-elementor-blocks/
├── app/
│   └── Modules/
│       └── Integrations/
│           └── Elementor/
│               ├── Controls/
│               │   └── ProductSelectControl.php (NEW)
│               ├── Renderers/
│               │   └── ElementorCarouselRenderer.php (NEW)
│               └── Widgets/
│                   └── ProductCarouselWidget.php (NEW)
├── resources/
│   ├── elementor/
│   │   └── product-select-control.js (NEW)
│   └── scss/
│       └── widgets/
│           └── product-carousel.scss (NEW)
```

---

## API Endpoints Required

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/products` | GET | Search/list products |
| `/products/fetchProductsByIds` | GET | Fetch products by IDs |

These endpoints already exist in fluent-cart core.

---

## Dependencies

- FluentCart plugin (core)
- Swiper library (bundled with FluentCart)
- Elementor plugin

---

## Questions for Review

1. **Product Selection Control**:
   - Should we create a completely new control `fluent_product_select` (multi-product)?
   - Or extend/modify the existing `fluent_product_variation_select` to support both modes?

2. **Swiper Assets**:
   - Rely on FluentCart's bundled Swiper?
   - Or bundle independently for this plugin?

3. **Card Element Customization**:
   - Use a Repeater control (like ShopAppWidget) for card element ordering?
   - Or simpler toggles for show/hide each element?

4. **Editor Preview**:
   - Show live carousel preview in Elementor editor?
   - Or show static placeholder (simpler implementation)?

5. **Caching**:
   - Should rendered output be cached (like ShopAppWidget uses transients)?

---

## Estimated Components

| Component | Complexity |
|-----------|------------|
| ProductSelectControl (PHP + JS) | Medium |
| ProductCarouselWidget.php | High |
| ElementorCarouselRenderer.php | Medium |
| product-carousel.scss | Low |
| Integration updates | Low |

---

## Post-Implementation Notes

### Button Selector Fix (2026-02-18)

The Product Button style controls originally used `.fct-button` as the CSS selector. This class does **not exist** in the rendered HTML. The actual button classes from `ProductCardRender.php` are:

- `.fct-product-view-button` — View Options / Buy Now buttons
- `.fluent-cart-add-to-cart-button` — Add to Cart buttons

Both `ProductCarouselWidget.php` and `ShopAppWidget.php` were fixed to use the correct selectors:

```php
$btnSelector = '{{WRAPPER}} .fct-product-card .fct-product-view-button, {{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button';
$btnHoverSelector = '{{WRAPPER}} .fct-product-card .fct-product-view-button:hover, {{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button:hover';
```

The inline CSS in the editor preview section was also fixed (`.fct-elementor-preview .fct-button` -> `.fct-elementor-preview .fct-product-view-button, .fct-elementor-preview .fluent-cart-add-to-cart-button`).

---

## Notes

- The Block Editor version uses React + InnerBlocks for complex nested editing
- Elementor version will be simpler - using controls panel for configuration
- Product card rendering can reuse `ProductCardRender` from FluentCart core
- Frontend carousel JS (`product-carousel.js`) can be reused directly