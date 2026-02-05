<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers;

/**
 * Dummy Checkout Renderer for Elementor Editor Preview
 * Renders a realistic checkout preview with mock data
 */
class DummyCheckoutRenderer
{
    protected $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
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
        ?>
        <div class="fct_checkout_form_section fct_name_fields_section">
            <div class="fct_form_group_row">
                <div class="fct_form_group fct_form_group_half">
                    <label class="fct_form_label"><?php esc_html_e('First Name', 'fluent-cart'); ?> <span class="required">*</span></label>
                    <input type="text" class="fct_form_control" placeholder="<?php esc_attr_e('John', 'fluent-cart'); ?>" disabled>
                </div>
                <div class="fct_form_group fct_form_group_half">
                    <label class="fct_form_label"><?php esc_html_e('Last Name', 'fluent-cart'); ?> <span class="required">*</span></label>
                    <input type="text" class="fct_form_control" placeholder="<?php esc_attr_e('Doe', 'fluent-cart'); ?>" disabled>
                </div>
            </div>
            <div class="fct_form_group">
                <label class="fct_form_label"><?php esc_html_e('Email Address', 'fluent-cart'); ?> <span class="required">*</span></label>
                <input type="email" class="fct_form_control" placeholder="<?php esc_attr_e('john@example.com', 'fluent-cart'); ?>" disabled>
            </div>
        </div>
        <?php
    }

    /**
     * Render create account field
     */
    protected function renderCreateAccount(string $customHeading = ''): void
    {
        $heading = $customHeading ?: __('Create Account', 'fluent-cart');
        ?>
        <div class="fct_checkout_form_section fct_create_account_section">
            <div class="fct_form_group">
                <label class="fct_checkbox_label">
                    <input type="checkbox" disabled>
                    <span><?php echo esc_html($heading); ?></span>
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

        ?>
        <div class="fct_checkout_billing_and_shipping">
            <?php if ($addressType === 'both' || $addressType === 'billing'): ?>
                <div class="fct_checkout_form_section fct_billing_section">
                    <h3 class="fct_form_section_heading"><?php echo esc_html($customHeading ?: __('Billing Address', 'fluent-cart')); ?></h3>
                    <?php $this->renderAddressFieldsGroup(); ?>
                </div>
            <?php endif; ?>

            <?php if ($addressType === 'both' && $showShipToDifferent): ?>
                <div class="fct_checkout_form_section fct_ship_different_section">
                    <div class="fct_form_group">
                        <label class="fct_checkbox_label">
                            <input type="checkbox" disabled>
                            <span><?php esc_html_e('Ship to a different address?', 'fluent-cart'); ?></span>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($addressType === 'shipping'): ?>
                <div class="fct_checkout_form_section fct_shipping_section">
                    <h3 class="fct_form_section_heading"><?php echo esc_html($customHeading ?: __('Shipping Address', 'fluent-cart')); ?></h3>
                    <?php $this->renderAddressFieldsGroup(); ?>
                </div>
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
        <div class="fct_checkout_form_section fct_checkout_shipping_methods">
            <h3 class="fct_form_section_heading"><?php echo esc_html($heading); ?></h3>
            <div class="fct_shipping_methods_list">
                <div class="fct_shipping_method_item is-selected">
                    <label class="fct_radio_label">
                        <input type="radio" name="dummy_shipping" checked disabled>
                        <span class="fct_shipping_method_title"><?php esc_html_e('Standard Shipping', 'fluent-cart'); ?></span>
                        <span class="fct_shipping_method_price">$5.99</span>
                    </label>
                </div>
                <div class="fct_shipping_method_item">
                    <label class="fct_radio_label">
                        <input type="radio" name="dummy_shipping" disabled>
                        <span class="fct_shipping_method_title"><?php esc_html_e('Express Shipping', 'fluent-cart'); ?></span>
                        <span class="fct_shipping_method_price">$12.99</span>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render payment methods
     */
    protected function renderPaymentMethods(string $customHeading = ''): void
    {
        $heading = $customHeading ?: __('Payment Method', 'fluent-cart');
        ?>
        <div class="fct_checkout_form_section fct_checkout_payment_methods">
            <h3 class="fct_form_section_heading"><?php echo esc_html($heading); ?></h3>
            <div class="fct_payment_methods_list">
                <div class="fct_payment_method_item is-selected">
                    <label class="fct_radio_label">
                        <input type="radio" name="dummy_payment" checked disabled>
                        <span class="fct_payment_method_title"><?php esc_html_e('Credit Card', 'fluent-cart'); ?></span>
                    </label>
                    <div class="fct_payment_method_description">
                        <?php esc_html_e('Pay securely with your credit card.', 'fluent-cart'); ?>
                    </div>
                </div>
                <div class="fct_payment_method_item">
                    <label class="fct_radio_label">
                        <input type="radio" name="dummy_payment" disabled>
                        <span class="fct_payment_method_title"><?php esc_html_e('PayPal', 'fluent-cart'); ?></span>
                    </label>
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
        ?>
        <div class="fct_checkout_form_section fct_agree_terms_section">
            <div class="fct_form_group">
                <label class="fct_checkbox_label">
                    <input type="checkbox" disabled>
                    <span><?php esc_html_e('I agree to the terms and conditions', 'fluent-cart'); ?> <span class="required">*</span></span>
                </label>
            </div>
        </div>
        <?php
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
        ?>
        <div class="fct_checkout_form_section fct_checkout_btn_wrap">
            <button type="button" class="fct_checkout_btn fct_btn fct_btn_primary" disabled>
                <?php esc_html_e('Place Order', 'fluent-cart'); ?>
            </button>
        </div>
        <?php
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
                    <h3 class="fct_form_section_heading" id="order-summary-heading"><?php echo esc_html($summaryHeading); ?></h3>
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

            if ($type === 'order_bump') {
                $this->renderOrderBump();
            }
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
            <div class="fct_cart_items">
                <div class="fct_cart_item">
                    <div class="fct_cart_item_image">
                        <div class="fct_cart_item_image_placeholder" style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                            <span style="color: #999; font-size: 24px;">&#128722;</span>
                        </div>
                    </div>
                    <div class="fct_cart_item_details">
                        <div class="fct_cart_item_title"><?php esc_html_e('Sample Product', 'fluent-cart'); ?></div>
                        <div class="fct_cart_item_meta">
                            <span class="fct_cart_item_qty"><?php esc_html_e('Qty: 1', 'fluent-cart'); ?></span>
                        </div>
                    </div>
                    <div class="fct_cart_item_price">$49.99</div>
                </div>
                <div class="fct_cart_item">
                    <div class="fct_cart_item_image">
                        <div class="fct_cart_item_image_placeholder" style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                            <span style="color: #999; font-size: 24px;">&#128722;</span>
                        </div>
                    </div>
                    <div class="fct_cart_item_details">
                        <div class="fct_cart_item_title"><?php esc_html_e('Another Product', 'fluent-cart'); ?></div>
                        <div class="fct_cart_item_meta">
                            <span class="fct_cart_item_qty"><?php esc_html_e('Qty: 2', 'fluent-cart'); ?></span>
                        </div>
                    </div>
                    <div class="fct_cart_item_price">$29.99</div>
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
            <div class="fct_order_bump_item" style="border: 1px dashed #ddd; padding: 15px; border-radius: 4px; margin-top: 15px;">
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