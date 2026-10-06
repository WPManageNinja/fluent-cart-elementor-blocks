# WriteAReviewButtonWidget Style Controls Reference

## Overview

The trigger that lets a shopper write a review, plus the drawer or modal it opens. Mirrors the `fluent-cart/write-a-review-button` block.

**Widget Slug:** `fluentcart_write_a_review_button`
**Category:** `fluent-cart`
**Icon:** `eicon-button fluent-cart-widget-icon`
**Requires:** a FluentCart with the review feature; Product Reviews module on

---

## Core Renderer

Two calls on one renderer, in order:

```php
$renderer = new ProductReviewRenderer($product->ID, [
    'container'  => 'drawer|modal',
    'layout'     => 'inline|steps',
    'ctaAddText' => '', 'ctaEditText' => '', 'ctaLoginText' => '',
]);
$renderer->renderWriteReviewCta();
$renderer->renderForm();
```

The form always travels with the button. To send reviewers to a page of their own, put the Review Form widget on that page and link to it with an ordinary button.

**Assets:** `AssetLoader::loadReviewSubmissionFormAssets()` — the form's own bundle, not the full single-product one. This widget is a trigger plus a form and needs none of the gallery, product card or review list. On a product page the same handle is already enqueued.

---

## The three button texts

The renderer emits **all applicable CTA variants in one pass** and the storefront script shows the right one. So a single render contains both the new-review and the already-reviewed text; that is not a bug and tests should not assert on only one being present.

| Control ID | Shown to | Placeholder |
|---|---|---|
| `add_review_button_text` | a shopper with no review yet | Write a Review |
| `edit_review_button_text` | a shopper who already reviewed | Edit Your Review |
| `login_review_button_text` | a logged-out visitor | Log in to Review |

Blank means the store default. All three go through `sanitize_text_field()`.

---

## Content Controls

| Control ID | Type | Default | Notes |
|---|---|---|---|
| `source` / `product_id` | from `ProductWidgetTrait` | `default` | |
| `container` | SELECT | `drawer` | `drawer` or `modal`. Validated against the whitelist at render |
| `layout` | SELECT | `inline` | `inline` or `steps` |
| `add_review_button_text` | TEXT | `''` | |
| `edit_review_button_text` | TEXT | `''` | |
| `login_review_button_text` | TEXT | `''` | |

---

## Style Controls — `registerCtaStyleControls($widget, $selector = '{{WRAPPER}} .fct-review-cta-btn')`

| Control ID | Type | Selector |
|---|---|---|
| `cta_typography` | Typography | button |
| `cta_color` / `cta_background` | COLOR (Normal tab) | button |
| `cta_color_hover` / `cta_background_hover` / `cta_border_color_hover` | COLOR (Hover tab) | `button:hover` |
| `cta_border` | Group_Control_Border | button |
| `cta_radius` | DIMENSIONS px/% | button → `border-radius` |
| `cta_padding` | DIMENSIONS, responsive | button → `padding` |
| `cta_align` | CHOOSE, responsive | `.fluentcart-write-a-review-button` → flex `align-items` |

Alignment targets the addon wrapper, not the button: the button is inline-level, so aligning it means aligning the column that holds it. `stretch` gives a full-width button.

---

## CSS Selector Map

| Class | Element |
|---|---|
| `.fluentcart-write-a-review-button` | Widget wrapper (addon) |
| `.fct-review-cta-btn` | The trigger, in every state |
| `.fct-reviews-summary-write-btn` | Second class on the trigger, shared with the summary's own CTA |

---

## Editor states

| Condition | Canvas |
|---|---|
| Module off / core too old | reason from `ReviewSupport::unavailableReason()` |
| No product | "select a product" |
| Reviews off for this product | "reviews are turned off for this product" |
| Visitor cannot review (store permission mode) | "this visitor cannot review this product" |
