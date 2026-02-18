# ProductCarouselWidget Style Controls Reference

## Overview

The ProductCarouselWidget (`app/Modules/Integrations/Elementor/Widgets/ProductCarouselWidget.php`) renders a Swiper-based product carousel. Card-related style controls are delegated to `ProductCardWidget` static methods; carousel-specific controls (navigation arrows, pagination dots) are local.

**Widget Slug:** `fluent_cart_product_carousel`

---

## Content Controls

| Section | Control ID | Type | Description |
|---|---|---|---|
| Products | `product_ids` | ProductSelectControl (multiple) | Multi-product selector |
| Carousel Settings | `slides_to_show` | Responsive Number | 1–6, default 3/2/1 |
| Carousel Settings | `space_between` | Number | Gap in px, default 16 |
| Carousel Settings | `autoplay` | Switcher | Default yes |
| Carousel Settings | `autoplay_speed` | Number | 500–10000ms, default 3000 |
| Carousel Settings | `infinite_loop` | Switcher | Default off |
| Carousel Settings | `show_arrows` | Switcher | Default yes |
| Carousel Settings | `arrow_size` | Select | sm / md / lg |
| Carousel Settings | `show_pagination` | Switcher | Default yes |
| Carousel Settings | `pagination_type` | Select | dots / fraction / progress |
| Card Layout | `card_elements` | Repeater | image, title, excerpt, price, button |
| Card Layout | `price_format` | Select | starts_from / range / lowest |

---

## Style Section Registration Order

```php
private function registerStyleControls()
{
    // 1. Product Card       — ProductCardWidget::registerCardStyleControls()
    // 2. Product Image      — ProductCardWidget::registerCardImageStyleControls()
    // 3. Product Title      — ProductCardWidget::registerCardTitleStyleControls()
    // 4. Product Excerpt    — ProductCardWidget::registerCardExcerptStyleControls()
    // 5. Product Price      — ProductCardWidget::registerCardPriceStyleControls()
    // 6. Product Button     — ProductCardWidget::registerCardButtonStyleControls()
    // 7. Navigation Arrows  — $this->registerNavigationStyleControls()
    // 8. Pagination         — $this->registerPaginationStyleControls()
}
```

---

## 1–6. Shared Card Styles (via ProductCardWidget)

See [`refs/claude/ProductCard/STYLE-CONTROLS.md`](../ProductCard/STYLE-CONTROLS.md) for full details on sections 1–6.

Selectors used by this widget:
- Card: `{{WRAPPER}} .fct-product-card`
- Image: `{{WRAPPER}} .fct-product-card-image`
- Title: `{{WRAPPER}} .fct-product-card-title`
- Excerpt: `{{WRAPPER}} .fct-product-card-excerpt`
- Price: `{{WRAPPER}} .fct-product-card-prices`
- Button: `{{WRAPPER}} .fct-product-card .fct-product-view-button, {{WRAPPER}} .fct-product-card .fluent-cart-add-to-cart-button`

## 7. Navigation Arrows

**Section ID:** `navigation_style_section`
**Condition:** `show_arrows = yes`

| Control ID | Type | CSS Selector |
|---|---|---|
| `arrow_color` | Color | `{{WRAPPER}} .swiper-button-prev, {{WRAPPER}} .swiper-button-next` + `svg` stroke |
| `arrow_background` | Color | `{{WRAPPER}} .swiper-button-prev, {{WRAPPER}} .swiper-button-next` |
| `arrow_hover_color` | Color | `{{WRAPPER}} .swiper-button-prev:hover, {{WRAPPER}} .swiper-button-next:hover` + `svg` stroke |
| `arrow_hover_background` | Color | `{{WRAPPER}} .swiper-button-prev:hover, {{WRAPPER}} .swiper-button-next:hover` |

## 8. Pagination

**Section ID:** `pagination_style_section`
**Condition:** `show_pagination = yes`

| Control ID | Type | CSS Selector |
|---|---|---|
| `pagination_color` | Color | `{{WRAPPER}} .swiper-pagination-bullet`, `{{WRAPPER}} .swiper-pagination-progressbar` |
| `pagination_active_color` | Color | `{{WRAPPER}} .swiper-pagination-bullet-active`, `{{WRAPPER}} .swiper-pagination-progressbar-fill` |
| `pagination_size` | Responsive Slider | `{{WRAPPER}} .swiper-pagination-bullet` (width + height, dots only) |

---

## Key CSS Selector Patterns

| Element | CSS Class |
|---------|-----------|
| Carousel outer wrapper | `.fluent-cart-elementor-product-carousel` |
| Carousel wrapper | `.fct-product-carousel-wrapper` |
| Swiper container | `.fct-product-carousel` |
| Swiper slide | `.swiper-slide` |
| Product card | `.fct-product-card` |
| Arrow prev | `.swiper-button-prev` |
| Arrow next | `.swiper-button-next` |
| Arrow controls wrapper | `.fct-carousel-controls` |
| Arrow size classes | `.fct-arrows-sm`, `.fct-arrows-md`, `.fct-arrows-lg` |
| Pagination wrapper | `.fct-carousel-pagination` |
| Pagination type classes | `.fct-pagination-dots`, `.fct-pagination-fraction`, `.fct-pagination-progress` |
| Pagination bullet | `.swiper-pagination-bullet` |
| Pagination active bullet | `.swiper-pagination-bullet-active` |
| Pagination progress bar | `.swiper-pagination-progressbar` |
| Pagination progress fill | `.swiper-pagination-progressbar-fill` |

---

## Revision History

- **2026-02-18**: Refactored to use ProductCardWidget static methods for card styles (sections 1–6). Added excerpt style section. Navigation and pagination remain local.
