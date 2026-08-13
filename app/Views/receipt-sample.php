<?php
/**
 * Sample thank-you page for the Order Receipt widget's Elementor editor
 * canvas — shown only when no real order can be previewed (empty store,
 * invalid custom order ID).
 *
 * Mirrors ThankYouRender's markup class-for-class (header, order-items
 * header/list/total, meta lines, bill-to/ship-to, footer buttons) so the
 * widget's section toggles, text overrides and Style-tab selectors all
 * behave on the fallback exactly as they do on a real receipt, and core's
 * thank-you stylesheet styles it natively. Filled with dummy data.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="fct-thank-you-page">
    <div class="fct-thank-you-page-inner">
        <div class="fct-thank-you-page-content email-template-content">
            <div class="fct-thank-you-page-header" style="background: #dcfce7;">
                <div class="fct-thank-you-page-header-icon" style="background: #16a34a;">✓</div>
                <h1 class="fct-thank-you-page-header-title" style="color:#166534;"><?php esc_html_e('Purchase Successful!', 'fluent-cart'); ?></h1>
            </div>
            <div class="fct-thank-you-page-body">
                <div class="fct-thank-you-page-body-inner">
                    <div class="fct-thank-you-page-body-content">
                        <div class="fct-thank-you-page-body-content-inner">
                            <div class="no-print">
                                <div class="no-print-title"><?php esc_html_e('Hello Jane Smith!', 'fluent-cart'); ?></div>
                                <p><?php esc_html_e('Your order', 'fluent-cart'); ?> <strong style="color: #007bff;"><a href="#">#INV-001</a></strong><?php esc_html_e(' has been placed successfully.', 'fluent-cart'); ?></p>
                            </div>

                            <div class="fct-thank-you-page-order-items">
                                <div class="fct-thank-you-page-order-items-header">
                                    <div class="fct-thank-you-page-order-items-header-row"><?php esc_html_e('Item', 'fluent-cart'); ?></div>
                                    <div class="fct-thank-you-page-order-items-header-row"><?php esc_html_e('Total', 'fluent-cart'); ?></div>
                                </div>
                                <div class="fct-thank-you-page-order-items-body">
                                    <div class="fct-thank-you-page-order-items-list">
                                        <div class="fct-thank-you-page-order-items-list-title">
                                            <p class="fct-thank-you-page-order-items-list-quantity">
                                                <?php esc_html_e('Stylish white and blue sneakers', 'fluent-cart'); ?>
                                                <span>x 2</span>
                                            </p>
                                        </div>
                                        <div class="fct-thank-you-page-order-items-list-price">
                                            <div class="fct-thank-you-page-order-items-list-price-inner">$24.00</div>
                                        </div>
                                    </div>
                                    <div class="fct-thank-you-page-order-items-list">
                                        <div class="fct-thank-you-page-order-items-list-title">
                                            <p class="fct-thank-you-page-order-items-list-quantity">
                                                <?php esc_html_e('Elegant running shoe', 'fluent-cart'); ?>
                                            </p>
                                        </div>
                                        <div class="fct-thank-you-page-order-items-list-price">
                                            <div class="fct-thank-you-page-order-items-list-price-inner">$15.00</div>
                                        </div>
                                    </div>

                                    <div class="fct-thank-you-page-order-items-total">
                                        <div class="fct-meta-line fct-thank-you-page-order-items-total-subtotal">
                                            <div class="fct-meta-line-label fct-thank-you-page-order-items-total-label"><?php esc_html_e('Subtotal', 'fluent-cart'); ?></div>
                                            <div class="fct-meta-line-value fct-thank-you-page-order-items-total-value">$39.00</div>
                                        </div>
                                        <div class="fct-meta-line fct-thank-you-page-order-items-total-shipping">
                                            <div class="fct-meta-line-label fct-thank-you-page-order-items-total-label"><?php esc_html_e('Shipping', 'fluent-cart'); ?></div>
                                            <div class="fct-meta-line-value fct-thank-you-page-order-items-total-value">$10.00</div>
                                        </div>
                                        <div class="fct-meta-line fct-thank-you-page-order-items-total-total">
                                            <div class="fct-meta-line-label fct-thank-you-page-order-items-total-label"><?php esc_html_e('Total', 'fluent-cart'); ?></div>
                                            <div class="fct-meta-line-value fct-thank-you-page-order-items-total-value">$49.00</div>
                                        </div>
                                        <div class="fct-meta-line fct-thank-you-page-order-items-total-payment-method">
                                            <div class="fct-meta-line-label fct-thank-you-page-order-items-total-label"><?php esc_html_e('Payment Method', 'fluent-cart'); ?></div>
                                            <div class="fct-meta-line-value fct-thank-you-page-order-items-total-value"><?php esc_html_e('Cash', 'fluent-cart'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="fct-thank-you-page-order-items-addresses">
                                <div class="fct-thank-you-page-order-items-addresses-bill-to">
                                    <h5><?php esc_html_e('Bill To', 'fluent-cart'); ?></h5>
                                    <div class="fct-thank-you-page-order-items-addresses-bill-to-address">Jane Smith, 123 Main Street, Springfield, 1207</div>
                                    <div class="fct-thank-you-page-order-items-addresses-bill-to-email"><a style="color: #007bff;" href="#">jane.smith@example.com</a></div>
                                </div>
                                <div class="fct-thank-you-page-order-items-addresses-ship-to">
                                    <h5 class="fct-thank-you-page-order-items-addresses-ship-to-title"><?php esc_html_e('Ship To', 'fluent-cart'); ?></h5>
                                    <div class="fct-thank-you-page-order-items-addresses-ship-to-address">Jane Smith, 123 Main Street, Springfield, 1207</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="fct-thank-you-page-footer">
    <a class="fct-thank-you-page-view-order-button" href="#"><?php esc_html_e('View Order', 'fluent-cart'); ?></a>
    <a class="fct-thank-you-page-download-receipt-button" href="#"><?php esc_html_e('Download Receipt', 'fluent-cart'); ?></a>
</div>
