# CheckoutWidget Style Controls Reference

## Overview

The CheckoutWidget (`app/Modules/Integrations/Elementor/Widgets/CheckoutWidget.php`) provides **10 style sections** with **80+ individual controls** for comprehensive checkout styling.

**Widget Slug:** `fluent_cart_checkout`

---

## Style Section Registration Order

```php
// In register_controls()
$this->registerFormFieldStyleControls();      // 1. Form Fields
$this->registerSectionHeadingStyleControls(); // 2. Section Headings
$this->registerSubmitButtonStyleControls();   // 3. Submit Button
$this->registerSummaryBoxStyleControls();     // 4. Summary Box
$this->registerSummaryItemsStyleControls();   // 5. Summary Items
$this->registerLineItemsStyleControls();      // 6. Line Items
$this->registerCouponFieldStyleControls();    // 7. Coupon Field
$this->registerPaymentMethodsStyleControls(); // 8. Payment Methods
$this->registerAddressFieldsStyleControls();  // 9. Address Fields
$this->registerErrorValidationStyleControls();// 10. Error/Validation
```

---

## 1. Form Fields

**Section ID:** `form_field_style_section`
**Tabs:** Normal / Focus

| Control ID | Type | CSS Selector |
|---|---|---|
| `input_typography` | Typography | `.fct_checkout input, .fct_checkout select, .fct_checkout textarea` |
| `label_typography` | Typography | `.fct_checkout label, .fct_checkout .fct_input_label` |
| `label_color` | Color | `.fct_checkout label, .fct_checkout .fct_input_label` |
| `input_bg_color` | Color | `.fct_checkout input, .fct_checkout select, .fct_checkout textarea` |
| `input_text_color` | Color | `.fct_checkout input, .fct_checkout select, .fct_checkout textarea` |
| `input_placeholder_color` | Color | `.fct_checkout input::placeholder, .fct_checkout textarea::placeholder` |
| `input_border` | Border | `.fct_checkout input, .fct_checkout select, .fct_checkout textarea` |
| `input_focus_bg_color` | Color | `.fct_checkout input:focus, .fct_checkout select:focus, .fct_checkout textarea:focus` |
| `input_focus_border_color` | Color | `.fct_checkout input:focus, .fct_checkout select:focus, .fct_checkout textarea:focus` |
| `input_focus_shadow` | Box Shadow | `.fct_checkout input:focus, .fct_checkout select:focus, .fct_checkout textarea:focus` |
| `input_border_radius` | Dimensions | `.fct_checkout input, .fct_checkout select, .fct_checkout textarea` |
| `input_padding` | Dimensions | `.fct_checkout input, .fct_checkout select, .fct_checkout textarea` |
| `input_height` | Slider | `.fct_checkout input:not([type="checkbox"]):not([type="radio"]), .fct_checkout select` |
| `field_spacing` | Slider | `.fct_checkout .fct_input_wrapper` |
| `transition_duration` | Number | `.fct_checkout input, .fct_checkout select, .fct_checkout textarea` |

## 2. Section Headings

**Section ID:** `section_heading_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `section_heading_typography` | Typography | `.fct_checkout .fct_form_section_header_label, .fct_checkout .fct_form_section_header h3, .fct_checkout .fct_form_section_header h4` |
| `section_heading_color` | Color | `.fct_checkout .fct_form_section_header_label, .fct_checkout .fct_form_section_header h3, .fct_checkout .fct_form_section_header h4` |
| `section_heading_bg_color` | Color | `.fct_checkout .fct_form_section_header` |
| `section_heading_padding` | Dimensions | `.fct_checkout .fct_form_section_header` |
| `section_heading_margin` | Dimensions | `.fct_checkout .fct_form_section_header` |
| `section_heading_border` | Border | `.fct_checkout .fct_form_section_header` |

## 3. Submit Button

**Section ID:** `submit_button_style_section`
**Tabs:** Normal / Hover
**Subsections:** Loading State

| Control ID | Type | CSS Selector |
|---|---|---|
| `submit_button_typography` | Typography | `.fct_place_order_btn, .fct_place_order_btn_wrap button[type="submit"]` |
| `submit_button_width` | Select | `.fct_place_order_btn` |
| `submit_button_alignment` | Choose | `.fct_checkout .fct_place_order_btn_wrap` |
| `submit_button_text_color` | Color | `.fct_place_order_btn, .fct_place_order_btn_wrap button[type="submit"]` |
| `submit_button_background` | Background | `.fct_place_order_btn, .fct_place_order_btn_wrap button[type="submit"]` |
| `submit_button_border` | Border | `.fct_place_order_btn, .fct_place_order_btn_wrap button[type="submit"]` |
| `submit_button_shadow` | Box Shadow | `.fct_place_order_btn, .fct_place_order_btn_wrap button[type="submit"]` |
| `submit_button_hover_text_color` | Color | `...btn:hover, ...button[type="submit"]:hover` |
| `submit_button_hover_background` | Background | `...btn:hover, ...button[type="submit"]:hover` |
| `submit_button_hover_border` | Border | `...btn:hover, ...button[type="submit"]:hover` |
| `submit_button_hover_shadow` | Box Shadow | `...btn:hover, ...button[type="submit"]:hover` |
| `submit_button_border_radius` | Dimensions | `.fct_place_order_btn, .fct_place_order_btn_wrap button[type="submit"]` |
| `submit_button_padding` | Dimensions | `.fct_place_order_btn, .fct_place_order_btn_wrap button[type="submit"]` |
| `submit_button_transition` | Number | `.fct_place_order_btn, .fct_place_order_btn_wrap button[type="submit"]` |
| `loading_opacity` | Slider | `.fct_place_order_btn:disabled, .fct_place_order_btn_wrap button[type="submit"]:disabled` |

## 4. Summary Box

**Section ID:** `summary_box_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `summary_box_background` | Background | `.fct_checkout_summary, .fct_summary_box` |
| `summary_box_border` | Border | `.fct_checkout_summary, .fct_summary_box` |
| `summary_box_border_radius` | Dimensions | `.fct_checkout_summary, .fct_summary_box` |
| `summary_box_shadow` | Box Shadow | `.fct_checkout_summary, .fct_summary_box` |
| `summary_box_padding` | Dimensions | `.fct_checkout_summary, .fct_summary_box` |

## 5. Summary Items

**Section ID:** `summary_items_style_section`
**Subsection:** Total Row

| Control ID | Type | CSS Selector |
|---|---|---|
| `summary_label_typography` | Typography | `.fct_summary_items_list li .fct_summary_label` |
| `summary_value_typography` | Typography | `.fct_summary_items_list li .fct_summary_value` |
| `summary_label_color` | Color | `.fct_summary_items_list li .fct_summary_label` |
| `summary_value_color` | Color | `.fct_summary_items_list li .fct_summary_value` |
| `summary_separator_style` | Select | `.fct_summary_items_list li` |
| `summary_separator_width` | Slider | `.fct_summary_items_list li` |
| `summary_separator_color` | Color | `.fct_summary_items_list li` |
| `summary_row_padding` | Dimensions | `.fct_summary_items_list li` |
| `total_typography` | Typography | `.fct_summary_items_total` |
| `total_color` | Color | `.fct_summary_items_list li.fct_summary_items_total .fct_summary_label, ...fct_summary_value` |
| `total_bg_color` | Color | `.fct_summary_items_total` |

## 6. Line Items

**Section ID:** `line_items_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `line_item_title_typography` | Typography | `.fct_item_title, .fct_item_title a` |
| `line_item_title_color` | Color | `.fct_item_title, .fct_item_title a` |
| `line_item_price_typography` | Typography | `.fct_line_item_price, .fct_line_item_total` |
| `line_item_price_color` | Color | `.fct_line_item_price, .fct_line_item_total` |
| `line_item_image_border_radius` | Dimensions | `.fct_item_image img` |
| `line_item_spacing` | Slider | `.fct_line_item` |
| `line_item_border` | Border | `.fct_line_item` |
| `line_item_padding` | Dimensions | `.fct_line_item` |

## 7. Coupon Field

**Section ID:** `coupon_field_style_section`
**Tabs:** Normal / Hover (for coupon button)
**Subsection:** Messages

| Control ID | Type | CSS Selector |
|---|---|---|
| `coupon_toggle_color` | Color | `.fct_coupon_toggle, .fct_coupon_toggle a` |
| `coupon_button_text_color` | Color | `.fct_coupon_field button[type="submit"]` |
| `coupon_button_bg_color` | Color | `.fct_coupon_field button[type="submit"]` |
| `coupon_button_hover_text_color` | Color | `.fct_coupon_field button[type="submit"]:hover` |
| `coupon_button_hover_bg_color` | Color | `.fct_coupon_field button[type="submit"]:hover` |
| `coupon_success_color` | Color | `.fct_coupon_success` |
| `coupon_error_color` | Color | `.fct_coupon_error` |

## 8. Payment Methods

**Section ID:** `payment_methods_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `payment_method_bg_color` | Color | `.fct_payment_method_wrapper` |
| `payment_method_selected_bg_color` | Color | `.fct_payment_method_wrapper.active` |
| `payment_method_border` | Border | `.fct_payment_method_wrapper` |
| `payment_method_selected_border_color` | Color | `.fct_payment_method_wrapper.active` |
| `payment_method_border_radius` | Dimensions | `.fct_payment_method_wrapper` |
| `payment_method_padding` | Dimensions | `.fct_payment_method_wrapper` |
| `payment_method_spacing` | Slider | `.fct_payment_method_wrapper` |
| `payment_method_title_typography` | Typography | `.fct_payment_method_wrapper label` |
| `payment_method_title_color` | Color | `.fct_payment_method_wrapper label` |
| `payment_method_desc_typography` | Typography | `.fct_payment_method_instructions` |
| `payment_method_desc_color` | Color | `.fct_payment_method_instructions` |

## 9. Address Fields

**Section ID:** `address_fields_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `address_group_border` | Border | `.fct_checkout_billing_and_shipping .fct_checkout_form_section` |
| `address_group_border_radius` | Dimensions | `.fct_checkout_billing_and_shipping .fct_checkout_form_section` |
| `address_group_padding` | Dimensions | `.fct_checkout_billing_and_shipping .fct_checkout_form_section` |
| `address_title_typography` | Typography | `.fct_checkout_billing_and_shipping .fct_form_section_header_label` |
| `address_title_color` | Color | `.fct_checkout_billing_and_shipping .fct_form_section_header_label` |

## 10. Error/Validation

**Section ID:** `error_validation_style_section`

| Control ID | Type | CSS Selector |
|---|---|---|
| `error_message_color` | Color | `.fct_form_error` |
| `error_border_color` | Color | `.fct_checkout .has-error input, .fct_checkout .has-error select, .fct_checkout .has-error textarea` |
| `error_message_typography` | Typography | `.fct_form_error` |

---

## Key CSS Selector Patterns

All checkout selectors are scoped under `.fct_checkout` (note: underscore, not hyphen).

### Core HTML Classes (from DummyCheckoutRenderer / CheckoutRenderer)

| Element | CSS Class |
|---------|-----------|
| Checkout wrapper | `.fct_checkout` |
| Input wrapper | `.fct_input_wrapper` |
| Input label | `.fct_input_label` |
| Section header | `.fct_form_section_header` |
| Section header label | `.fct_form_section_header_label` |
| Place order button | `.fct_place_order_btn` |
| Place order wrapper | `.fct_place_order_btn_wrap` |
| Summary container | `.fct_checkout_summary`, `.fct_summary_box` |
| Summary items list | `.fct_summary_items_list` |
| Summary label | `.fct_summary_label` |
| Summary value | `.fct_summary_value` |
| Summary total row | `.fct_summary_items_total` |
| Line item | `.fct_line_item` |
| Line item title | `.fct_item_title` |
| Line item price | `.fct_line_item_price` |
| Line item total | `.fct_line_item_total` |
| Line item image | `.fct_item_image` |
| Coupon toggle | `.fct_coupon_toggle` |
| Coupon field | `.fct_coupon_field` |
| Payment method wrapper | `.fct_payment_method_wrapper` |
| Payment instructions | `.fct_payment_method_instructions` |
| Billing/Shipping section | `.fct_checkout_billing_and_shipping` |
| Form section | `.fct_checkout_form_section` |
| Form error | `.fct_form_error` |
| Has error state | `.has-error` |
| Coupon success | `.fct_coupon_success` |
| Coupon error | `.fct_coupon_error` |
| Buy section | `.fct_buy_section` |

---

## Revision History

- **2026-02-17**: Initial implementation with 4 style sections (Form Fields, Section Headings, Submit Button, Summary Box)
- **2026-02-18**: Expanded to 10 style sections. Added Line Items, Coupon Field, Payment Methods, Address Fields, Error/Validation, Summary Items. Fixed CSS selectors across all sections to match actual HTML classes from DummyCheckoutRenderer. Added Label Color, Payment Method Title/Description Typography & Color controls.
