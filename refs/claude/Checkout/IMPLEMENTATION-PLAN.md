# FluentCart Elementor Checkout Blocks - Implementation Plan

## Overview

This plan outlines the implementation of Elementor widgets that mirror the FluentCart Gutenberg checkout block system. The goal is to provide Elementor users with the same flexible checkout customization capabilities available to Gutenberg users.

---

## Reference: FluentCart Gutenberg Checkout Structure

### Source Files
- **Main Block:** `/fluent-cart/app/Hooks/Handlers/BlockEditors/Checkout/CheckoutBlockEditor.php`
- **Inner Blocks:** `/fluent-cart/app/Hooks/Handlers/BlockEditors/Checkout/InnerBlocks/InnerBlocks.php`
- **Renderer:** `/fluent-cart/app/Services/Renderer/CheckoutRenderer.php`
- **Summary Renderer:** `/fluent-cart/app/Services/Renderer/CartSummaryRender.php`

### Gutenberg Inner Blocks (22 blocks)

| Block Slug | Title | Parent Context |
|------------|-------|----------------|
| `checkout-name-fields` | Checkout Name Fields | Top-level |
| `checkout-create-account-field` | Create Account Field | Top-level |
| `checkout-address-fields` | Address Fields (Container) | Top-level |
| `checkout-billing-address-field` | Billing Address Field | address-fields |
| `checkout-shipping-address-field` | Shipping Address Field | address-fields |
| `checkout-ship-to-different-field` | Ship to Different Field | address-fields |
| `checkout-shipping-methods` | Shipping Methods | Top-level |
| `checkout-payment-methods` | Payment Methods | Top-level |
| `checkout-agree-terms-field` | Agree Terms Field | Top-level |
| `checkout-submit-button` | Submit Button | Top-level |
| `checkout-order-notes-field` | Order Notes Field | Top-level |
| `checkout-summary` | Summary (Container) | Top-level |
| `checkout-order-summary` | Order Summary | summary |
| `checkout-summary-footer` | Summary Footer (Container) | summary |
| `checkout-subtotal` | Subtotal | summary-footer |
| `checkout-shipping` | Shipping | summary-footer |
| `checkout-coupon` | Coupon | summary-footer |
| `checkout-manual-discount` | Manual Discount | summary-footer |
| `checkout-tax` | Tax | summary-footer |
| `checkout-shipping-tax` | Shipping Tax | summary-footer |
| `checkout-total` | Total | summary-footer |
| `checkout-order-bump` | Order Bump (Pro) | Top-level |

---

## Implementation Strategy

### Approach: Single Widget with Nested Sections

Rather than creating 22+ separate Elementor widgets (which would clutter the widget panel), we'll create:

1. **CheckoutWidget** - A single comprehensive checkout widget with section-based controls
2. Uses Elementor repeaters and section controls for layout customization
3. Leverages existing FluentCart renderers directly

### Why This Approach?

1. **User Experience:** Easier to configure one widget than drag/drop 22 widgets
2. **Consistency:** Matches existing `ShopAppWidget` pattern in this plugin
3. **Maintainability:** Single widget is easier to maintain than 22 separate ones
4. **FluentCart Compatibility:** Can directly use existing renderer classes

---

## File Structure

```
app/Modules/Integrations/Elementor/
├── Widgets/
│   └── CheckoutWidget.php                    # Main checkout widget (10 style sections, 80+ controls)
├── Renderers/
│   ├── ElementorCheckoutRenderer.php         # Frontend renderer (uses real cart data)
│   └── DummyCheckoutRenderer.php             # Editor preview renderer (fake data)
```

---

## Implementation Details

### 1. CheckoutWidget.php

**Location:** `app/Modules/Integrations/Elementor/Widgets/CheckoutWidget.php`

#### Widget Registration

```php
public function get_name() { return 'fluent_cart_checkout'; }
public function get_title() { return 'Checkout'; }
public function get_icon() { return 'eicon-checkout'; }
public function get_categories() { return ['fluent-cart']; }
```

#### Control Sections

**A. General Settings**
- Layout style (one-column, two-column)
- Column widths (for two-column layout)
- Use default styles toggle

**B. Form Fields Layout (Repeater)**
Order and visibility of checkout form sections:
- Name Fields
- Create Account Field
- Address Fields (with sub-options for billing/shipping)
- Ship to Different Address Toggle
- Shipping Methods
- Payment Methods
- Terms Agreement
- Order Notes
- Submit Button

**C. Summary Layout (Repeater)**
Order and visibility of summary sections:
- Order Summary (cart items)
- Subtotal
- Shipping Cost
- Coupon Field
- Manual Discount
- Tax
- Shipping Tax
- Total
- Order Bump (Pro)

**D. Style Controls**
- Form Field Styles (typography, colors, spacing)
- Button Styles (submit button, apply coupon button)
- Summary Styles (background, borders, typography)
- Section Heading Styles

### 2. ElementorCheckoutRenderer.php

**Location:** `app/Modules/Integrations/Elementor/Renderers/ElementorCheckoutRenderer.php`

This class will:
1. Accept widget settings as constructor parameter
2. Build checkout HTML using FluentCart's existing renderers
3. Respect the order/visibility settings from widget controls
4. Apply Elementor-specific wrapper classes for styling

#### Key Methods

```php
class ElementorCheckoutRenderer
{
    protected $settings;
    protected $cart;
    protected $checkoutRenderer;
    protected $summaryRenderer;

    public function __construct(array $settings) { }
    public function render(): string { }

    // Form field rendering
    protected function renderNameFields(): string { }
    protected function renderCreateAccountField(): string { }
    protected function renderAddressFields(): string { }
    protected function renderBillingAddress(): string { }
    protected function renderShippingAddress(): string { }
    protected function renderShipToDifferent(): string { }
    protected function renderShippingMethods(): string { }
    protected function renderPaymentMethods(): string { }
    protected function renderAgreeTerms(): string { }
    protected function renderSubmitButton(): string { }
    protected function renderOrderNotes(): string { }

    // Summary rendering
    protected function renderSummary(): string { }
    protected function renderOrderSummary(): string { }
    protected function renderSubtotal(): string { }
    protected function renderShippingCost(): string { }
    protected function renderCouponField(): string { }
    protected function renderManualDiscount(): string { }
    protected function renderTax(): string { }
    protected function renderShippingTax(): string { }
    protected function renderTotal(): string { }
    protected function renderOrderBump(): string { }
}
```

---

## Control Definitions

### Form Fields Repeater

```php
$repeater = new Repeater();

$repeater->add_control('element_type', [
    'label'   => 'Section',
    'type'    => Controls_Manager::SELECT,
    'options' => [
        'name_fields'          => 'Name Fields',
        'create_account'       => 'Create Account',
        'address_fields'       => 'Address Fields',
        'shipping_methods'     => 'Shipping Methods',
        'payment_methods'      => 'Payment Methods',
        'agree_terms'          => 'Agree to Terms',
        'order_notes'          => 'Order Notes',
        'submit_button'        => 'Submit Button',
    ],
]);

// Conditional controls per element type
$repeater->add_control('address_type', [
    'label'     => 'Address Display',
    'type'      => Controls_Manager::SELECT,
    'options'   => [
        'both'     => 'Billing + Shipping',
        'billing'  => 'Billing Only',
        'shipping' => 'Shipping Only',
    ],
    'condition' => ['element_type' => 'address_fields'],
]);
```

### Summary Repeater

```php
$repeater = new Repeater();

$repeater->add_control('element_type', [
    'label'   => 'Section',
    'type'    => Controls_Manager::SELECT,
    'options' => [
        'order_summary'    => 'Order Summary (Items)',
        'subtotal'         => 'Subtotal',
        'shipping'         => 'Shipping',
        'coupon'           => 'Coupon Field',
        'manual_discount'  => 'Manual Discount',
        'tax'              => 'Tax',
        'shipping_tax'     => 'Shipping Tax',
        'total'            => 'Total',
        'order_bump'       => 'Order Bump (Pro)',
    ],
]);
```

---

## Default Layout Configuration

### Two-Column Layout (Default)

**Left Column (65%):**
1. Name Fields
2. Create Account Field
3. Address Fields
4. Agree Terms
5. Shipping Methods
6. Payment Methods
7. Submit Button

**Right Column (35%):**
1. Order Summary
2. Subtotal
3. Shipping
4. Coupon
5. Manual Discount
6. Tax
7. Shipping Tax
8. Total
9. Order Notes
10. Order Bump

---

## Style Control Sections (10 sections implemented)

See `refs/claude/Checkout/STYLE-CONTROLS.md` for the full control-by-control reference with CSS selectors.

1. **Form Fields** - Input/label typography, colors, border, focus states, spacing, height, transition
2. **Section Headings** - Typography, color, background, padding, margin, border
3. **Submit Button** - Typography, width, alignment, normal/hover colors, background, border, shadow, loading opacity, transition
4. **Summary Box** - Background, border, border radius, box shadow, padding
5. **Summary Items** - Label/value typography+color, separator style/width/color, row padding, total row styling
6. **Line Items** - Title/price typography+color, image border radius, item spacing/border/padding
7. **Coupon Field** - Toggle color, button text/bg normal+hover, success/error message colors
8. **Payment Methods** - Background, selected background, border, selected border, radius, padding, spacing, title/description typography+color
9. **Address Fields** - Group border, border radius, padding, title typography+color
10. **Error/Validation** - Error message color+typography, error border color

---

## Asset Dependencies

The widget will depend on FluentCart checkout assets:
- `checkout.scss` (from FluentCart)
- Checkout JavaScript for form functionality

```php
public function get_style_depends()
{
    AssetLoader::loadCheckoutAssets();
    return ['fluentcart-checkout-css'];
}

public function get_script_depends()
{
    return ['fluentcart-checkout-js'];
}
```

---

## Implementation Phases

### Phase 1: Basic Widget Structure - DONE
- [x] Create `CheckoutWidget.php` with basic registration
- [x] Add general settings controls (layout type, column widths)
- [x] Create `DummyCheckoutRenderer.php` for editor preview
- [x] Register widget in `ElementorIntegration.php`
- [x] Implement editor preview with realistic dummy checkout HTML

### Phase 2: Form Fields Implementation - DONE
- [x] Implement form fields repeater with defaults
- [x] Add conditional controls per field type
- [x] Implement rendering for each form field section using FluentCart renderers
- [x] Test form field ordering/visibility/deletion

### Phase 3: Summary Implementation - DONE
- [x] Implement summary repeater with defaults
- [x] Add summary section rendering
- [x] Implement summary footer components
- [x] Test summary ordering/visibility

### Phase 4: Two-Column Layout - DONE
- [x] Implement column layout controls
- [x] Add responsive column width controls
- [x] Create responsive layout rendering
- [x] Test on different screen sizes

### Phase 5: Extended Style Controls - DONE
- [x] Form field styles (normal/focus states, transitions)
- [x] Submit button styles (normal/hover, animations, loading state)
- [x] Summary box styles (background, border, shadow)
- [x] Section heading styles
- [x] Payment/Shipping method styles (bg, border, selected state, title/desc typography+color)
- [x] Coupon field styles (toggle, button normal/hover, success/error messages)
- [x] Error/validation styles
- [x] Line Items styles (title/price typography+color, image radius, spacing, border, padding)
- [x] Summary Items styles (label/value typography+color, separator, total row)
- [x] Address Fields styles (group border, title typography+color)
- [x] All CSS selectors verified against DummyCheckoutRenderer HTML
- [x] Test all style applications in Elementor editor

### Phase 6: Testing & Polish - DONE
- [x] Test with editor preview (DummyCheckoutRenderer)
- [x] Test all 10 style sections visible in Style tab
- [x] Test all style controls apply correctly via DOM verification
- [x] Elementor preview/editor testing
- [ ] Test with real cart data (frontend)
- [ ] Test checkout submission flow (frontend)
- [ ] Browser compatibility testing
- [ ] Mobile responsiveness testing

---

## Notes

### Cart Data Handling
The widget will use `CartHelper::getCart()` to retrieve cart data, same as Gutenberg blocks. Empty cart state should display appropriate message.

### Form Submission
Form action and method will match the Gutenberg block implementation to ensure checkout processing works identically.

### Pro Features
Order Bump rendering will check `App::isProActive()` before rendering, same as Gutenberg implementation.

### Editor Preview (DummyCheckoutRenderer)
Instead of simple placeholder boxes, the editor uses `DummyCheckoutRenderer` which renders a fully realistic checkout form with fake data. This allows style controls to work visually in the Elementor editor.

**File:** `app/Modules/Integrations/Elementor/Renderers/DummyCheckoutRenderer.php`

- Renders complete checkout HTML with fake form fields, payment methods, order summary
- Uses the same CSS classes as the real `CheckoutRenderer` so Elementor style controls work in preview
- Includes sample line items, address fields, payment methods (Card, Cash, PayPal), coupon field, summary footer
- No actual cart data required - all data is hardcoded dummy content

---

## Finalized Decisions

### 1. Widget Approach: Single Widget with Repeaters
- **Decision:** Single comprehensive `CheckoutWidget` with Repeater controls
- **Rationale:** Repeaters provide "default template" behavior similar to Gutenberg InnerBlocks
- Users can delete, reorder, and add back elements as needed
- Matches existing `ShopAppWidget` pattern

### 2. Style Customizations: Extended Elementor Styling
- **Decision:** Go beyond Gutenberg capabilities
- Include hover effects, animations, advanced typography
- Full Elementor styling power for each section

### 3. Editor Preview: Placeholder Content
- **Decision:** Show styled placeholder boxes in editor
- Each section displays its name and approximate size
- No mock cart data needed

### 4. Responsive Breakpoints: Elementor Defaults
- **Decision:** Use standard Elementor breakpoints
- Desktop, Tablet, Mobile as defined by user's Elementor settings
