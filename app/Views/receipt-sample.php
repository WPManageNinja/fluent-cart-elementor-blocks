<?php
/**
 * Sample thank-you page for the Order Receipt widget's Elementor editor
 * canvas — the same front-end markup and classes ThankYouRender emits
 * (styled by the thank_you stylesheet the canvas loads), filled with dummy
 * data, so the builder previews the true receipt format instead of the
 * "no receipt found" fallback (no order exists in the editor context).
 *
 * Inline styles mirror the real slip markup (shared email-style templates),
 * so the preview matches what a customer sees after checkout. Ported from
 * the Divi addon's receipt-sample view.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Repeated cell styles, deduped for readability — view-local presentation
// constants, not caller-provided data.
$item_row_style  = 'font-size:13px;line-height:1.4;color:#333;padding:8px 10px;border:none;';
$total_row_style = 'padding:4px 10px;border:none;text-align:right;';
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
                        <div class="fct-thank-you-page-body-content-inner" style="font-size:13px;line-height:1.4;color:#333;padding:20px;">
                            <div class="no-print">
                                <div class="no-print-title" style="font-size:20px;font-weight:700;color:rgb(17,24,39);margin:0 0 4px;"><?php esc_html_e('Hello Jane Smith!', 'fluent-cart'); ?></div>
                                <p style="margin:0 0 14px;"><?php esc_html_e('Your order', 'fluent-cart'); ?> <strong style="color: #007bff;"><a href="#">#INV-001</a></strong><?php esc_html_e(' has been placed successfully.', 'fluent-cart'); ?></p>
                            </div>
                            <table style="width:100%;border-collapse:collapse;margin-bottom:10px;border:none;">
                                <tr style="background:#f9fafb;">
                                    <th style="<?php echo esc_attr($item_row_style); ?>text-align:left;"><?php esc_html_e('ITEM', 'fluent-cart'); ?></th>
                                    <th style="<?php echo esc_attr($item_row_style); ?>text-align:right;"><?php esc_html_e('TOTAL', 'fluent-cart'); ?></th>
                                </tr>
                                <tr>
                                    <td style="<?php echo esc_attr($item_row_style); ?>"><?php esc_html_e('Stylish white and blue sneakers × 2', 'fluent-cart'); ?></td>
                                    <td style="<?php echo esc_attr($item_row_style); ?>text-align:right;font-weight:700;">$24.00</td>
                                </tr>
                                <tr>
                                    <td style="<?php echo esc_attr($item_row_style); ?>"><?php esc_html_e('Elegant running shoe × 1', 'fluent-cart'); ?></td>
                                    <td style="<?php echo esc_attr($item_row_style); ?>text-align:right;font-weight:700;">$15.00</td>
                                </tr>
                            </table>
                            <table style="width:100%;border-collapse:collapse;border:none;margin-bottom:14px;">
                                <tr><td style="<?php echo esc_attr($total_row_style); ?>"><?php esc_html_e('Subtotal', 'fluent-cart'); ?></td><td style="<?php echo esc_attr($total_row_style); ?>width:90px;">$39.00</td></tr>
                                <tr><td style="<?php echo esc_attr($total_row_style); ?>"><?php esc_html_e('Shipping', 'fluent-cart'); ?></td><td style="<?php echo esc_attr($total_row_style); ?>">$10.00</td></tr>
                                <tr><td style="<?php echo esc_attr($total_row_style); ?>font-weight:700;"><?php esc_html_e('Total', 'fluent-cart'); ?></td><td style="<?php echo esc_attr($total_row_style); ?>font-weight:700;">$49.00</td></tr>
                                <tr><td style="<?php echo esc_attr($total_row_style); ?>"><?php esc_html_e('Payment Method', 'fluent-cart'); ?></td><td style="<?php echo esc_attr($total_row_style); ?>"><?php esc_html_e('Cash', 'fluent-cart'); ?></td></tr>
                            </table>
                            <table style="width:100%;border-collapse:collapse;border:none;">
                                <tr>
                                    <td style="<?php echo esc_attr($item_row_style); ?>vertical-align:top;">
                                        <strong><?php esc_html_e('Bill To', 'fluent-cart'); ?></strong><br>
                                        Jane Smith, 123 Main Street,<br>Springfield, 1207<br>
                                        <a style="color: #007bff;" href="#">jane.smith@example.com</a>
                                    </td>
                                    <td style="<?php echo esc_attr($item_row_style); ?>vertical-align:top;">
                                        <strong><?php esc_html_e('Ship To', 'fluent-cart'); ?></strong><br>
                                        Jane Smith, 123 Main Street,<br>Springfield, 1207
                                    </td>
                                </tr>
                            </table>
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
