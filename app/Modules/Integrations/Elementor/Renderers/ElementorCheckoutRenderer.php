<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers;

use FluentCart\App\App;
use FluentCart\App\Helpers\CartHelper;
use FluentCart\App\Modules\Tax\TaxModule;
use FluentCart\App\Services\Renderer\CartRenderer;
use FluentCart\App\Services\Renderer\CartSummaryRender;
use FluentCart\App\Services\Renderer\CheckoutRenderer;
use FluentCart\App\Services\Renderer\RenderHelper;
use FluentCart\Framework\Support\Arr;

class ElementorCheckoutRenderer
{
    protected $settings;
    protected $cart;
    protected $checkoutRenderer;
    protected $summaryRenderer;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->cart = CartHelper::getCart();

        if ($this->cart && !empty(Arr::get($this->cart, 'cart_data', []))) {
            $this->checkoutRenderer = new CheckoutRenderer($this->cart);
            $this->summaryRenderer = new CartSummaryRender($this->cart);
        }
    }

    /**
     * Main render method
     */
    public function render(): string
    {
        // Check for empty cart
        if (!$this->cart || empty(Arr::get($this->cart, 'cart_data', []))) {
            return $this->renderEmptyCart();
        }

        $layoutType = $this->settings['layout_type'] ?? 'two-column';
        $useDefaultStyle = ($this->settings['use_default_style'] ?? 'yes') === 'yes';
        $stickySummary = ($this->settings['sticky_summary'] ?? '') === 'yes';

        $wrapperClasses = [
            'fce-checkout-wrapper',
            'fluent-cart-checkout-page',
            'fct-checkout',
            'fct-checkout-type-' . $this->cart->cart_group,
        ];

        if (!$useDefaultStyle) {
            $wrapperClasses[] = 'fce-custom-styles';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapperClasses)); ?>" data-fluent-cart-checkout-page>
            <?php $this->renderNotices(); ?>
            <?php $this->renderCheckoutForm($layoutType, $stickySummary); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render empty cart message
     */
    protected function renderEmptyCart(): string
    {
        $message = $this->settings['empty_cart_message'] ?? __('Your cart is empty.', 'fluent-cart');

        ob_start();
        ?>
        <div class="fce-checkout-empty-cart">
            <?php (new CartRenderer())->renderEmpty(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render cart notices
     */
    protected function renderNotices(): void
    {
        $notices = Arr::get($this->cart->checkout_data, '__cart_notices', []);
        $hookedNotices = apply_filters('fluent_cart/checkout_page_notices', [], [
            'cart' => $this->cart
        ]);

        if (!$notices && !$hookedNotices) {
            return;
        }
        ?>
        <div class="fct-cart-notices" role="status" aria-live="polite">
            <?php foreach ($notices as $notice):
                if (empty($notice['content'])) {
                    continue;
                } ?>
                <div class="fct-alert">
                    <?php echo wp_kses_post($notice['content']); ?>
                </div>
            <?php endforeach; ?>
            <?php foreach ($hookedNotices as $notice):
                if (empty($notice['content'])) {
                    continue;
                } ?>
                <div class="fct-alert">
                    <?php echo wp_kses_post($notice['content']); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render the checkout form
     */
    protected function renderCheckoutForm(string $layoutType, bool $stickySummary): void
    {
        global $wp;
        $current_url = home_url(add_query_arg([], $wp->request));
        $current_url = add_query_arg($_GET, $current_url);

        $formAttributes = [
            'method'                                       => 'POST',
            'data-fluent-cart-checkout-page-checkout-form' => '',
            'class'                                        => 'fct_checkout fluent-cart-checkout-page-checkout-form',
            'action'                                       => $current_url,
            'enctype'                                      => 'multipart/form-data',
        ];

        do_action('fluent_cart/before_checkout_form', ['cart' => $this->cart]);
        ?>
        <form <?php RenderHelper::renderAtts($formAttributes); ?> aria-label="<?php esc_attr_e('Checkout Form', 'fluent-cart'); ?>">
            <?php do_action('fluent_cart/checkout_form_opening', ['cart' => $this->cart]); ?>

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
                <div class="fce-checkout-single-column">
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
        do_action('fluent_cart/after_checkout_form', ['cart' => $this->cart]);
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
                $this->checkoutRenderer->renderNameFields();
                break;

            case 'create_account':
                $this->checkoutRenderer->renderCreateAccountField([
                    'title' => $customHeading ?: null,
                ]);
                break;

            case 'address_fields':
                $this->renderAddressFields($element);
                break;

            case 'shipping_methods':
                $this->renderShippingMethods();
                break;

            case 'payment_methods':
                $this->renderPaymentMethods();
                break;

            case 'agree_terms':
                $this->checkoutRenderer->agreeTerms([
                    'title' => $customHeading ?: null,
                ]);
                break;

            case 'order_notes':
                $this->checkoutRenderer->renderOrderNoteField($customHeading ?: '');
                break;

            case 'submit_button':
                $this->checkoutRenderer->renderCheckoutButton();
                break;
        }
    }

    /**
     * Render address fields based on settings
     */
    protected function renderAddressFields(array $element): void
    {
        $addressType = $element['address_type'] ?? 'both';
        $showShipToDifferent = ($element['show_ship_to_different'] ?? 'yes') === 'yes';
        $customHeading = $element['custom_heading'] ?? '';

        echo '<div class="fct_checkout_billing_and_shipping">';

        if ($addressType === 'both' || $addressType === 'billing') {
            do_action('fluent_cart/before_billing_fields', ['cart' => $this->cart]);
            $this->checkoutRenderer->renderBillingAddressFields($customHeading ?: '');
        }

        if ($addressType === 'both' && $this->cart->requireShipping()) {
            if ($showShipToDifferent) {
                $this->checkoutRenderer->renderShipToDifferentField();
            }
            do_action('fluent_cart/after_billing_fields_section', ['cart' => $this->cart]);
            $this->checkoutRenderer->renderShippingAddressFields();
        }

        if ($addressType === 'shipping' && $this->cart->requireShipping()) {
            $this->checkoutRenderer->renderShippingAddressFields($customHeading ?: '');
        }

        echo '</div>';
    }

    /**
     * Render shipping methods
     */
    protected function renderShippingMethods(): void
    {
        $requireShipping = $this->cart->requireShipping();
        $class = $requireShipping ? '' : 'is-hidden';
        ?>
        <div class="fct_checkout_shipping_methods <?php echo esc_attr($class); ?>">
            <?php $this->checkoutRenderer->renderShippingOptions(); ?>
        </div>
        <?php
    }

    /**
     * Render payment methods
     */
    protected function renderPaymentMethods(): void
    {
        do_action('fluent_cart/before_payment_methods', ['cart' => $this->cart]);
        ?>
        <div class="fct_checkout_payment_methods" data-fluent-cart-checkout-payment-methods>
            <?php $this->checkoutRenderer->renderPaymentMethods(); ?>
        </div>
        <?php
        do_action('fluent_cart/after_payment_methods', ['cart' => $this->cart]);
    }

    /**
     * Render summary elements based on settings
     */
    protected function renderSummaryElements(): void
    {
        $summaryElements = $this->settings['summary_elements'] ?? [];
        $summaryHeading = $this->settings['summary_heading'] ?? __('Order Summary', 'fluent-cart');

        ?>
        <div class="fct_summary active" data-fluent-cart-checkout-page-checkout-form-order-summary aria-labelledby="order-summary-heading">
            <div class="fct_summary_box" data-fluent-cart-checkout-page-cart-items-wrapper>
                <div class="fct_checkout_form_section">
                    <?php $this->renderSummaryHeader($summaryHeading, $summaryElements); ?>
                    <div id="order_summary_panel" class="fct_form_section_body">
                        <div class="fct_form_section_body_inner">
                            <?php $this->renderSummaryContent($summaryElements); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php

        // Render order notes and order bump if they are in summary elements
        $this->renderSummaryExtras($summaryElements);
    }

    /**
     * Render summary header
     */
    protected function renderSummaryHeader(string $heading, array $summaryElements): void
    {
        // Check if order summary is visible
        $hasOrderSummary = false;
        foreach ($summaryElements as $element) {
            if ($element['element_type'] === 'order_summary' && ($element['element_visibility'] ?? 'yes') === 'yes') {
                $hasOrderSummary = true;
                break;
            }
        }

        if ($hasOrderSummary) {
            $this->summaryRenderer->renderOrderSummarySectionHeading();
        }
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

            // These go in the footer list
            if (in_array($type, ['subtotal', 'shipping', 'coupon', 'manual_discount', 'tax', 'shipping_tax', 'total'])) {
                $footerElements[] = $element;
                continue;
            }

            // Order summary (cart items)
            if ($type === 'order_summary') {
                ?>
                <div class="fct_items_wrapper" data-fluent-cart-checkout-item-wrapper>
                    <?php $this->summaryRenderer->renderItemsLists(); ?>
                </div>
                <?php
            }
        }

        // Render footer elements
        if (!empty($footerElements)) {
            $this->renderSummaryFooter($footerElements);
        }
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
                            $this->summaryRenderer->renderSubtotal();
                            break;

                        case 'shipping':
                            $atts = 'class="' . ($this->cart->getShippingTotal() === 0 ? 'shipping-charge-hidden' : '') . '" data-fluent-cart-checkout-shipping-amount-wrapper';
                            $this->summaryRenderer->renderShipping($atts);
                            break;

                        case 'coupon':
                            ?>
                            <li data-fluent-cart-checkout-page-applied-coupon>
                                <?php $this->summaryRenderer->showCouponField(); ?>
                            </li>
                            <?php
                            break;

                        case 'manual_discount':
                            $this->summaryRenderer->showManualDiscount();
                            break;

                        case 'tax':
                            (new TaxModule())->renderTaxRow($this->cart);
                            break;

                        case 'shipping_tax':
                            (new TaxModule())->renderShippingTaxRow($this->cart);
                            break;

                        case 'total':
                            $atts = 'class="fct_summary_items_total" data-fluent-cart-checkout-page-current-total';
                            $this->summaryRenderer->renderTotal($atts);
                            break;
                    }
                }
                ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Render extra elements that appear after summary
     */
    protected function renderSummaryExtras(array $summaryElements): void
    {
        // Render order notes before order bumps (matching core FluentCart behavior)
        $this->checkoutRenderer->renderOrderNoteField();

        foreach ($summaryElements as $element) {
            $type = $element['element_type'] ?? '';
            $visible = ($element['element_visibility'] ?? 'yes') === 'yes';

            if (!$visible) {
                continue;
            }

            if ($type === 'order_bump') {
                $this->renderOrderBump();
            }
        }
    }

    /**
     * Render order bump (Pro feature)
     */
    protected function renderOrderBump(): void
    {
        if (!App::isProActive()) {
            return;
        }

        if (!class_exists('\FluentCartPro\App\Modules\Promotional\OrderBump\OrderBumpBoot')) {
            return;
        }

        $moduleSettings = new \FluentCart\Api\ModuleSettings();
        if (!$moduleSettings->isActive('order_bump')) {
            return;
        }

        ?>
        <div class="fce-order-bump-wrapper">
            <?php
            (new \FluentCartPro\App\Modules\Promotional\OrderBump\OrderBumpBoot())->maybeShowBumps([
                'cart' => $this->cart,
            ]);
            ?>
        </div>
        <?php
    }
}
