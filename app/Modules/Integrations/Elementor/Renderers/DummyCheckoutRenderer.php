<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers;

use FluentCart\App\Services\Renderer\CheckoutRenderer;
use FluentCart\App\Models\Cart;
use FluentCart\App\Vite;


/**
 * Dummy Checkout Renderer for Elementor Editor Preview
 * Renders a realistic checkout preview with mock data
 */
class DummyCheckoutRenderer
{
    protected $settings;

    protected $cart;

    protected $requireShipping = false;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->cart = new Cart();
        $this->requireShipping = $this->cart->requireShipping();
    }

    /**
     * Main render method
     */
    public function render(): string
    {
        $layoutType = $this->settings['layout_type'] ?? 'two-column';
        $useDefaultStyle = ($this->settings['use_default_style'] ?? 'yes') === 'yes';
        $stickySummary = ($this->settings['sticky_summary'] ?? '') === 'yes';

        $wrapperClasses = [
            'fce-checkout-wrapper',
            'fluent-cart-checkout-page',
            'fct-checkout',
            'fce-checkout-preview',
        ];

        if (!$useDefaultStyle) {
            $wrapperClasses[] = 'fce-custom-styles';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapperClasses)); ?>">
            <?php $this->renderCheckoutForm($layoutType, $stickySummary); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the checkout form
     */
    protected function renderCheckoutForm(string $layoutType, bool $stickySummary): void
    {
        ?>
        <form class="fct_checkout fluent-cart-checkout-page-checkout-form">
            <?php if ($layoutType === 'two-column'): ?>
                <div class="fce-checkout-columns fct_checkout_inner">
                    <div class="fce-checkout-form-column fct_checkout_form">
                        <div class="fct_checkout_form_items">
                            <?php $this->renderFormElements(); ?>
                        </div>
                    </div>
                    <div class="fce-checkout-summary-column fct_checkout_summary <?php echo $stickySummary ? 'is-sticky' : ''; ?>">
                        <?php $this->renderSummaryElements(); ?>

                        <?php
                            $summaryElements = $this->settings['summary_elements'] ?? [];

                            foreach ($summaryElements as $element) {
                                $type = $element['element_type'] ?? '';

                                if ($type === 'order_bump') {
                                    $this->renderOrderBump();
                                    break;
                                }
                            }

                        ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="fce-checkout-single-column fct_checkout_inner">
                    <div class="fct_checkout_form">
                        <div class="fct_checkout_form_items">
                            <?php $this->renderFormElements(); ?>
                        </div>
                    </div>
                    <div class="fct_checkout_summary">
                        <?php $this->renderSummaryElements(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </form>
        <?php
    }

    /**
     * Render form elements based on settings
     */
    protected function renderFormElements(): void
    {
        $formElements = $this->settings['form_elements'] ?? [];

        foreach ($formElements as $element) {
            $type = $element['element_type'] ?? '';
            $visible = ($element['element_visibility'] ?? 'yes') === 'yes';

            if (!$visible) {
                continue;
            }

            $this->renderFormElement($element);
        }
    }

    /**
     * Render a single form element
     */
    protected function renderFormElement(array $element): void
    {
        $type = $element['element_type'] ?? '';
        $customHeading = $element['custom_heading'] ?? '';

        switch ($type) {
            case 'name_fields':
                $this->renderNameFields($customHeading);
                break;

            case 'create_account':
                $this->renderCreateAccount($customHeading);
                break;

            case 'address_fields':
                $this->renderAddressFields($element);
                break;

            case 'shipping_methods':
                $this->renderShippingMethods($customHeading);
                break;

            case 'payment_methods':
                $this->renderPaymentMethods($customHeading);
                break;

            case 'agree_terms':
                $this->renderAgreeTerms($customHeading);
                break;

            case 'order_notes':
                $this->renderOrderNotes($customHeading);
                break;

            case 'submit_button':
                $this->renderSubmitButton();
                break;
        }
    }

    /**
     * Render name fields
     */
    protected function renderNameFields(string $customHeading = ''): void
    {
        (new CheckoutRenderer($this->cart))->renderNameFields();
    }

    /**
     * Render create account field
     */
    protected function renderCreateAccount(string $customHeading = ''): void
    {
        $heading = $customHeading ?: __('Create an account?', 'fluent-cart');
        ?>
        <div class="fct_allow_create_account_wrapper">
            <div class="fct_input_wrapper fct_input_wrapper_textarea" id="fct_wrapper_allow_create_account">
                <label for="allow_create_account" class="fct_input_label fct_input_label_textarea">
                    <input
                        type="checkbox"
                        class="fct-input fct-input-checkbox"
                        id="allow_create_account"
                        name="allow_create_account"
                        value="yes"
                        disabled
                    >
                    <?php echo esc_html($heading); ?>
                </label>
            </div>
        </div>
        <?php
    }

    /**
     * Render address fields
     */
    protected function renderAddressFields(array $element): void
    {
        $addressType = $element['address_type'] ?? 'both';
        $showShipToDifferent = ($element['show_ship_to_different'] ?? 'yes') === 'yes';
        $customHeading = $element['custom_heading'] ?? '';

        $renderer = new CheckoutRenderer($this->cart);

        ?>
        <div class="fct_checkout_billing_and_shipping">
            <?php if ($addressType === 'both' || $addressType === 'billing'): ?>
                <?php $renderer->renderBillingAddressFields(); ?>
            <?php endif; ?>

            <?php if ($addressType === 'both' && $showShipToDifferent): ?>
                <?php $renderer->renderShipToDifferentField(); ?>
            <?php endif; ?>

            <?php if ($addressType === 'shipping'): ?>
                <?php $renderer->renderShippingAddressFields(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render address fields group
     */
    protected function renderAddressFieldsGroup(): void
    {
        ?>
        <div class="fct_form_group">
            <label class="fct_form_label"><?php esc_html_e('Street Address', 'fluent-cart'); ?> <span class="required">*</span></label>
            <input type="text" class="fct_form_control" placeholder="<?php esc_attr_e('123 Main Street', 'fluent-cart'); ?>" disabled>
        </div>
        <div class="fct_form_group">
            <input type="text" class="fct_form_control" placeholder="<?php esc_attr_e('Apartment, suite, etc. (optional)', 'fluent-cart'); ?>" disabled>
        </div>
        <div class="fct_form_group_row">
            <div class="fct_form_group fct_form_group_half">
                <label class="fct_form_label"><?php esc_html_e('City', 'fluent-cart'); ?> <span class="required">*</span></label>
                <input type="text" class="fct_form_control" placeholder="<?php esc_attr_e('New York', 'fluent-cart'); ?>" disabled>
            </div>
            <div class="fct_form_group fct_form_group_half">
                <label class="fct_form_label"><?php esc_html_e('State/Province', 'fluent-cart'); ?></label>
                <select class="fct_form_control" disabled>
                    <option><?php esc_html_e('Select State', 'fluent-cart'); ?></option>
                </select>
            </div>
        </div>
        <div class="fct_form_group_row">
            <div class="fct_form_group fct_form_group_half">
                <label class="fct_form_label"><?php esc_html_e('Postal Code', 'fluent-cart'); ?> <span class="required">*</span></label>
                <input type="text" class="fct_form_control" placeholder="<?php esc_attr_e('10001', 'fluent-cart'); ?>" disabled>
            </div>
            <div class="fct_form_group fct_form_group_half">
                <label class="fct_form_label"><?php esc_html_e('Country', 'fluent-cart'); ?> <span class="required">*</span></label>
                <select class="fct_form_control" disabled>
                    <option><?php esc_html_e('United States', 'fluent-cart'); ?></option>
                </select>
            </div>
        </div>
        <div class="fct_form_group">
            <label class="fct_form_label"><?php esc_html_e('Phone', 'fluent-cart'); ?></label>
            <input type="tel" class="fct_form_control" placeholder="<?php esc_attr_e('+1 (555) 123-4567', 'fluent-cart'); ?>" disabled>
        </div>
        <?php
    }

    /**
     * Render shipping methods
     */
    protected function renderShippingMethods(string $customHeading = ''): void
    {
        $heading = $customHeading ?: __('Shipping Method', 'fluent-cart');
        ?>
        <div class="fct_checkout_shipping_methods <?php echo $this->requireShipping ? '' : 'is-hidden' ?>">
            <?php (new CheckoutRenderer($this->cart))->renderShippingOptions(); ?>
        </div>
        <?php
    }

    /**
     * Render payment methods
     */
    protected function renderPaymentMethods(string $customHeading = ''): void
    {

        $heading = $customHeading ?: __('Payment Method', 'fluent-cart');
        $card = Vite::getAssetUrl('images/payment-methods/card.svg');
        $paypal = Vite::getAssetUrl('images/payment-methods/paypal-icon.svg');
        $offlinePayment = Vite::getAssetUrl('images/payment-methods/offline-payment.svg');
        ?>
        <div class="fct_checkout_payment_methods">
            <div class="fct_checkout_form_section">
                <div class="fct_form_section_header">
                    <h4 id="payment_methods_label" class="fct_form_section_header_label">
                        <?php echo esc_html($heading); ?>
                    </h4>
                </div>

                <div class="fct_form_section_body">
                    <div class="fct_payment_methods_list fct_payment_method_mode_logo">
                        <div class="fct_payment_method_logo fct_payment_method_wrapper fct_payment_method_stripe active">
                            <input class="form-radio-input" type="radio" name="_fct_pay_method" id="fluent_cart_payment_method_stripe" value="stripe" required="1" checked="true" role="radio" aria-checked="true">
                            <label for="fluent_cart_payment_method_stripe">
                                <img decoding="async" src="<?php echo esc_url($card); ?>" alt="Card">
                                <?php esc_html_e('Card', 'fluent-cart'); ?>
                            </label>
                        </div>


                        <div class="fct_payment_method_logo fct_payment_method_wrapper fct_payment_method_offline_payment" tabindex="0" role="presentation">
                            <input class="form-radio-input" type="radio" name="_fct_pay_method" id="fluent_cart_payment_method_offline_payment" value="offline_payment" required="1" role="radio" aria-checked="false">
                            <label for="fluent_cart_payment_method_offline_payment">
                                <img decoding="async" src="<?php echo esc_url($offlinePayment); ?>" alt="Cash">
                                Cash
                            </label>
                        </div>

                        <div class="fct_payment_method_logo fct_payment_method_wrapper fct_payment_method_paypal">
                            <input class="form-radio-input" type="radio" name="_fct_pay_method" id="fluent_cart_payment_method_paypal" value="paypal" required="1" role="radio" aria-checked="false">

                            <label for="fluent_cart_payment_method_paypal">
                                <img decoding="async" src="<?php echo esc_url($paypal); ?>" alt="PayPal">
                                <?php esc_html_e('PayPal', 'fluent-cart'); ?>
                            </label>
                        </div>


                    </div>
                </div>
            </div>


        </div>
        <?php
    }

    /**
     * Render agree to terms
     */
    protected function renderAgreeTerms(string $customHeading = ''): void
    {
        (new CheckoutRenderer($this->cart))->agreeTerms();
    }

    /**
     * Render order notes
     */
    protected function renderOrderNotes(string $customHeading = ''): void
    {
        $heading = $customHeading ?: __('Order Notes', 'fluent-cart');
        ?>
        <div class="fct_checkout_form_section fct_order_notes_section">
            <h3 class="fct_form_section_heading"><?php echo esc_html($heading); ?></h3>
            <div class="fct_form_group">
                <textarea class="fct_form_control" rows="3" placeholder="<?php esc_attr_e('Notes about your order, e.g. special notes for delivery.', 'fluent-cart'); ?>" disabled></textarea>
            </div>
        </div>
        <?php
    }

    /**
     * Render submit button
     */
    protected function renderSubmitButton(): void
    {
        (new CheckoutRenderer($this->cart))->renderCheckoutButton();
    }

    /**
     * Render summary elements
     */
    protected function renderSummaryElements(): void
    {
        $summaryElements = $this->settings['summary_elements'] ?? [];
        $summaryHeading = $this->settings['summary_heading'] ?? __('Order Summary', 'fluent-cart');

        ?>
        <div class="fct_summary active">
            <div class="fct_summary_box">
                <div class="fct_checkout_form_section">
                    <div class="fct_form_section_header">
                        <h4 id="order_summary_label"><?php echo esc_html($summaryHeading); ?></h4>
                    </div>

                    <div class="fct_form_section_body">
                        <div class="fct_form_section_body_inner">
                            <?php $this->renderSummaryContent($summaryElements); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render summary content
     */
    protected function renderSummaryContent(array $summaryElements): void
    {
        $footerElements = [];

        foreach ($summaryElements as $element) {
            $type = $element['element_type'] ?? '';
            $visible = ($element['element_visibility'] ?? 'yes') === 'yes';

            if (!$visible) {
                continue;
            }

            if (in_array($type, ['subtotal', 'shipping', 'coupon', 'manual_discount', 'tax', 'shipping_tax', 'total'])) {
                $footerElements[] = $element;
                continue;
            }

            if ($type === 'order_summary') {
                $this->renderOrderSummaryItems();
            }

//            if ($type === 'order_bump') {
//                $this->renderOrderBump();
//            }
        }

        if (!empty($footerElements)) {
            $this->renderSummaryFooter($footerElements);
        }
    }

    /**
     * Render order summary items (dummy products)
     */
    protected function renderOrderSummaryItems(): void
    {
        ?>
        <div class="fct_items_wrapper">
            <div class="fct_line_items">
                <div class="fct_line_item">
                    <div class="fct_line_item_info">
                        <div class="fct_item_image">
                            <a href="#">
                                <img decoding="async" src="https://placehold.co/600x400" alt="">
                            </a>
                        </div>
                        <div class="fct_item_content">
                            <div class="fct_item_title">
                                <a href="#">
                                    Social Ninja - The Ultimate Social Media Plugin for WordPress
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="fct_line_item_price">
                        <span class="fct_line_item_total">
                            $200.00
                        </span>
                    </div>
                </div>

                <div class="fct_line_item">
                    <div class="fct_line_item_info">
                        <div class="fct_item_image">
                            <a href="#">
                                <img decoding="async" src="https://placehold.co/600x400" alt="">
                            </a>
                        </div>
                        <div class="fct_item_content">
                            <div class="fct_item_title">
                                <a href="#">
                                    Fluent Support – Helpdesk & Customer Support Ticket System
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="fct_line_item_price">
                        <span class="fct_line_item_total">
                            $300.00
                        </span>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Render summary footer
     */
    protected function renderSummaryFooter(array $footerElements): void
    {
        ?>
        <div class="fct_summary_items">
            <ul class="fct_summary_items_list">
                <?php
                foreach ($footerElements as $element) {
                    $type = $element['element_type'] ?? '';

                    switch ($type) {
                        case 'subtotal':
                            ?>
                            <li class="fct_summary_item">
                                <span class="fct_summary_label"><?php esc_html_e('Subtotal', 'fluent-cart'); ?></span>
                                <span class="fct_summary_value">$109.97</span>
                            </li>
                            <?php
                            break;

                        case 'shipping':
                            ?>
                            <li class="fct_summary_item">
                                <span class="fct_summary_label"><?php esc_html_e('Shipping', 'fluent-cart'); ?></span>
                                <span class="fct_summary_value">$5.99</span>
                            </li>
                            <?php
                            break;

                        case 'coupon':
                            $couponLabel = $element['coupon_label'] ?? __('Have a Coupon?', 'fluent-cart');
                            ?>
                            <li class="fct_summary_item fct_coupon_row">
                                <div class="fct_coupon_toggle_wrap">
                                    <a href="#" class="fct_coupon_toggle" onclick="return false;"><?php echo esc_html($couponLabel); ?></a>
                                </div>
                            </li>
                            <?php
                            break;

                        case 'manual_discount':
                            ?>
                            <li class="fct_summary_item fct_discount_row">
                                <span class="fct_summary_label"><?php esc_html_e('Discount', 'fluent-cart'); ?></span>
                                <span class="fct_summary_value">-$10.00</span>
                            </li>
                            <?php
                            break;

                        case 'tax':
                            ?>
                            <li class="fct_summary_item fct_tax_row">
                                <span class="fct_summary_label"><?php esc_html_e('Tax', 'fluent-cart'); ?></span>
                                <span class="fct_summary_value">$8.50</span>
                            </li>
                            <?php
                            break;

                        case 'shipping_tax':
                            ?>
                            <li class="fct_summary_item fct_shipping_tax_row">
                                <span class="fct_summary_label"><?php esc_html_e('Shipping Tax', 'fluent-cart'); ?></span>
                                <span class="fct_summary_value">$0.50</span>
                            </li>
                            <?php
                            break;

                        case 'total':
                            ?>
                            <li class="fct_summary_item fct_summary_items_total">
                                <span class="fct_summary_label"><?php esc_html_e('Total', 'fluent-cart'); ?></span>
                                <span class="fct_summary_value">$114.96</span>
                            </li>
                            <?php
                            break;
                    }
                }
                ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Render order bump placeholder
     */
    protected function renderOrderBump(): void
    {
        ?>
        <div class="fce-order-bump-wrapper fct_order_bump_section">
            <div class="fct_order_bump_item" style="border: 1px solid #ddd; padding: 16px; border-radius: 8px; margin-top: 15px; background: #ffffff;">
                <label class="fct_checkbox_label">
                    <input type="checkbox" disabled>
                    <span class="fct_order_bump_title"><?php esc_html_e('Add Extended Warranty - $9.99', 'fluent-cart'); ?></span>
                </label>
                <p class="fct_order_bump_description" style="margin: 8px 0 0 24px; color: #666; font-size: 13px;">
                    <?php esc_html_e('Protect your purchase with our extended warranty program.', 'fluent-cart'); ?>
                </p>
            </div>
        </div>
        <?php
    }
}
