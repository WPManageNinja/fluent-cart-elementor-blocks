<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use FluentCart\Api\Orders;
use FluentCart\App\Helpers\Status;
use FluentCart\App\Models\Order;
use FluentCart\App\Models\OrderOperation;
use FluentCart\App\Models\OrderTransaction;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\FrontendView;
use FluentCart\App\Services\Renderer\Receipt\ThankYouRender;
use FluentCart\App\Services\ShortCodeParser\ShortcodeTemplateBuilder;
use FluentCart\Framework\Support\Arr;

/**
 * Order Receipt / thank-you widget — renders FluentCart core's
 * [fluent_cart_receipt]. The handler resolves the order from the request's
 * order_hash / trx_hash (the URL the checkout redirects to), renders the
 * thank-you view (order summary, totals, customer + shipping details), and
 * falls back to a "not found" message when no order is in context.
 *
 * Editor preview: no order ever exists in the editor context, so the canvas
 * previews with a REAL store order (latest, or a chosen order ID — the same
 * pattern as Elementor Pro's Purchase Summary widget), read-only. A static
 * sample view is the fallback for stores with no orders yet.
 *
 * Section visibility is CSS class toggles on this widget's wrapper (rules
 * inlined with the output). Custom confirmation / button texts are applied by
 * replacing the inner text of class-anchored elements in the rendered HTML —
 * core markup stays the single source of truth. Style-tab selectors carry
 * !important where core prints inline email-style colors.
 */
class ReceiptWidget extends Widget_Base
{
    /**
     * The order the current render is for — the resolved preview order in the
     * editor, or the order from the checkout redirect URL on the frontend.
     * Context for parsing short codes ({{order.*}}) in the custom texts; null
     * when no order resolved (codes are then left as-is).
     *
     * @var \FluentCart\App\Models\Order|null
     */
    private $contextOrder = null;

    public function get_name()
    {
        return 'fluent_cart_receipt';
    }

    public function get_title()
    {
        return esc_html__('Order Receipt', 'fluent-cart-elementor-blocks');
    }

    public function get_icon()
    {
        return 'eicon-purchase-summary fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['receipt', 'thank you', 'order', 'confirmation', 'commerce', 'fluent'];
    }

    public function get_style_depends()
    {
        // Register every stylesheet the receipt needs and return the handles,
        // so Elementor guarantees them in the editor preview iframe AND on the
        // frontend head. Core only enqueues them mid-shortcode-render, which is
        // too late for the editor's AJAX render and unreliable on the frontend.
        // (The section-toggle rules are NOT a stylesheet — they ship inline
        // with the widget output; see toggleCss().)
        $this->registerAssets();

        return [
            'fluentcart-thank-you-css',
            'fluent-cart-not-found-style',
            $this->confirmationStyleHandle(),
        ];
    }

    /**
     * The handle core's ReceiptHandler uses for the confirmation stylesheet —
     * derived from core's configured slug, exactly as the handler builds it,
     * so the two never drift apart.
     *
     * @return string
     */
    private function confirmationStyleHandle()
    {
        $slug = \FluentCart\App\App::getInstance()->config->get('app.slug');

        return $slug . '_checkout_confirmation';
    }

    private function registerAssets()
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        AssetLoader::enqueueThankYouPageAssets();
        FrontendView::enqueueNotFoundPageAssets();

        // The confirmation stylesheet the receipt shortcode loads on the
        // frontend — the editor canvas needs it for an identical preview.
        \FluentCart\App\Vite::enqueueStyle(
            $this->confirmationStyleHandle(),
            'public/checkout/style/confirmation.scss'
        );
    }

    protected function register_controls()
    {
        $this->registerPreviewControls();
        $this->registerSectionControls();
        $this->registerConfirmationControls();
        $this->registerActionControls();

        $this->registerConfirmationStyleControls();
        $this->registerHeadingsStyleControls();
        $this->registerParagraphStyleControls();
        $this->registerLinksStyleControls();
        $this->registerItemTableStyleControls();
        $this->registerViewOrderButtonStyleControls();
    }

    /* ------------------------------------------------------------------ */
    /* Style tab                                                           */
    /*                                                                     */
    /* Selectors target core's stable fct-thank-you-page-* classes. Where  */
    /* core prints inline email-style colors (header band, header title,   */
    /* link colors), the override carries !important — inline styles win   */
    /* over any selector otherwise.                                        */
    /* ------------------------------------------------------------------ */

    private function registerConfirmationStyleControls()
    {
        $this->start_controls_section(
            'style_confirmation',
            [
                'label'     => esc_html__('Confirmation', 'fluent-cart-elementor-blocks'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_confirmation' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'confirmation_background',
            [
                'label'     => esc_html__('Background Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-thank-you-page-header' => 'background: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'confirmation_icon_background',
            [
                'label'     => esc_html__('Icon Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-thank-you-page-header-icon' => 'background: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'confirmation_title_color',
            [
                'label'     => esc_html__('Title Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-thank-you-page-header-title' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'confirmation_title_typography',
                'label'    => esc_html__('Title Typography', 'fluent-cart-elementor-blocks'),
                'selector' => '{{WRAPPER}} .fct-thank-you-page-header-title',
            ]
        );

        $this->end_controls_section();
    }

    private function registerHeadingsStyleControls()
    {
        $this->start_controls_section(
            'style_headings',
            [
                'label' => esc_html__('Headings', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        // Bill To's heading is a BARE h5 in core markup (Ship To's carries a
        // class) — target both shapes.
        $headingSelector = '{{WRAPPER}} .no-print-title,'
            . ' {{WRAPPER}} .fct-thank-you-page-order-items-addresses-bill-to > h5,'
            . ' {{WRAPPER}} .fct-thank-you-page-order-items-addresses-ship-to > h5,'
            . ' {{WRAPPER}} .fct-thank-you-page-order-items-addresses-ship-to-title,'
            . ' {{WRAPPER}} .fct-thank-you-page-order-items-downloads-heading,'
            . ' {{WRAPPER}} .fct-thank-you-page-order-items-subscriptions-heading';

        $this->add_control(
            'headings_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $headingSelector => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'headings_typography',
                'selector' => $headingSelector,
            ]
        );

        $this->end_controls_section();
    }

    private function registerParagraphStyleControls()
    {
        $this->start_controls_section(
            'style_paragraph',
            [
                'label' => esc_html__('Paragraph', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        // Core scss colors many inner elements directly, so inheritance from
        // the container never reaches them — target the text elements
        // explicitly. :where() keeps specificity low so the Headings / Links /
        // Item Table controls always win over this catch-all.
        $textSelector = '{{WRAPPER}} .fct-thank-you-page-body-content-inner'
            . ' :where(p, span, td, th, li, b,'
            . ' .fct-meta-line-label, .fct-meta-line-value,'
            . ' .fct-thank-you-page-order-items-list-price-inner,'
            . ' .fct-thank-you-page-order-items-addresses-bill-to-address,'
            . ' .fct-thank-you-page-order-items-addresses-bill-to-name,'
            . ' .fct-thank-you-page-order-items-addresses-bill-to-email,'
            . ' .fct-thank-you-page-order-items-addresses-ship-to-address),'
            . ' {{WRAPPER}} .fce-receipt-message';

        $this->add_control(
            'paragraph_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $textSelector => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'paragraph_typography',
                'selector' => $textSelector,
            ]
        );

        $this->end_controls_section();
    }

    private function registerLinksStyleControls()
    {
        $this->start_controls_section(
            'style_links',
            [
                'label' => esc_html__('Links', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        // Covers every link including the footer's Download Receipt (it reads
        // as a text link); only the View Order button is excluded — it has its
        // own style section.
        $linkSelector = '{{WRAPPER}} .fce-order-receipt a:not(.fct-thank-you-page-view-order-button),'
            . ' {{WRAPPER}} .fct-thank-you-page .no-print strong';

        $this->add_control(
            'links_color',
            [
                'label'     => esc_html__('Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $linkSelector => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'links_hover_color',
            [
                'label'     => esc_html__('Hover Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fce-order-receipt a:not(.fct-thank-you-page-view-order-button):hover' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerItemTableStyleControls()
    {
        $this->start_controls_section(
            'style_item_table',
            [
                'label'     => esc_html__('Item Table', 'fluent-cart-elementor-blocks'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_order_items' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'table_header_background',
            [
                'label'     => esc_html__('Header Background', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-thank-you-page-order-items-header' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'table_header_text_color',
            [
                'label'     => esc_html__('Header Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-thank-you-page-order-items-header-row' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'table_body_text_color',
            [
                'label'     => esc_html__('Body Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-thank-you-page-order-items-list, {{WRAPPER}} .fct-thank-you-page-order-items-list :where(p, div, span, b)' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'table_divider_color',
            [
                'label'     => esc_html__('Divider Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .fct-thank-you-page-order-items-header, {{WRAPPER}} .fct-thank-you-page-order-items-list, {{WRAPPER}} .fct-thank-you-page-order-items-total .fct-meta-line' => 'border-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerViewOrderButtonStyleControls()
    {
        $this->start_controls_section(
            'style_view_order_button',
            [
                'label'     => esc_html__('View Order Button', 'fluent-cart-elementor-blocks'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_actions'    => 'yes',
                    'show_view_order' => 'yes',
                ],
            ]
        );

        $buttonSelector = '{{WRAPPER}} .fct-thank-you-page-view-order-button';
        $buttonHoverSelector = '{{WRAPPER}} .fct-thank-you-page-view-order-button:hover';

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'view_order_typography',
                'selector' => $buttonSelector,
            ]
        );

        $this->start_controls_tabs('view_order_tabs');

        $this->start_controls_tab(
            'view_order_tab_normal',
            [
                'label' => esc_html__('Normal', 'fluent-cart-elementor-blocks'),
            ]
        );

        $this->add_control(
            'view_order_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $buttonSelector => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'view_order_background',
            [
                'label'     => esc_html__('Background Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $buttonSelector => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'view_order_tab_hover',
            [
                'label' => esc_html__('Hover', 'fluent-cart-elementor-blocks'),
            ]
        );

        $this->add_control(
            'view_order_hover_text_color',
            [
                'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $buttonHoverSelector => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'view_order_hover_background',
            [
                'label'     => esc_html__('Background Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    $buttonHoverSelector => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'view_order_border',
                'selector'  => $buttonSelector,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'view_order_border_radius',
            [
                'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    $buttonSelector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'view_order_padding',
            [
                'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    $buttonSelector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /* ------------------------------------------------------------------ */
    /* Content tab                                                         */
    /* ------------------------------------------------------------------ */

    private function registerPreviewControls()
    {
        $this->start_controls_section(
            'section_preview',
            [
                'label' => esc_html__('Preview Settings', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'preview_order_type',
            [
                'label'       => esc_html__('Preview order with', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SELECT,
                'default'     => '',
                'options'     => [
                    ''       => esc_html__('Latest Order', 'fluent-cart-elementor-blocks'),
                    'custom' => esc_html__('Order ID', 'fluent-cart-elementor-blocks'),
                ],
                'description' => esc_html__('Editor preview only. On the live page the order always comes from the checkout redirect URL.', 'fluent-cart-elementor-blocks'),
                'render_type' => 'template',
            ]
        );

        $this->add_control(
            'preview_order_id',
            [
                'label'       => esc_html__('Order ID', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 1,
                'condition'   => [
                    'preview_order_type' => 'custom',
                ],
                'description' => esc_html__('Find order IDs under FluentCart → Orders. A sample receipt is shown when no order is found.', 'fluent-cart-elementor-blocks'),
                'render_type' => 'template',
            ]
        );

        $this->end_controls_section();
    }

    private function registerSectionControls()
    {
        $this->start_controls_section(
            'section_visibility',
            [
                'label' => esc_html__('Sections', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $sections = [];
        foreach ($this->sectionMap() as $key => $section) {
            if (!empty($section['label'])) {
                $sections[$key] = $section['label'];
            }
        }

        foreach ($sections as $key => $label) {
            $this->add_control(
                $key,
                [
                    'label'        => $label,
                    'type'         => Controls_Manager::SWITCHER,
                    'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                    'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                    'return_value' => 'yes',
                    'default'      => 'yes',
                ]
            );
        }

        $this->end_controls_section();
    }

    private function registerConfirmationControls()
    {
        $this->start_controls_section(
            'section_confirmation',
            [
                'label'     => esc_html__('Confirmation', 'fluent-cart-elementor-blocks'),
                'tab'       => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'show_confirmation' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'confirmation_title',
            [
                'render_type' => 'template',
                'label'       => esc_html__('Title', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Purchase Successful!', 'fluent-cart-elementor-blocks'),
                'label_block' => true,
                'description' => esc_html__('Short codes supported, e.g. {{order.customer.first_name}}. Leave empty to keep the default title.', 'fluent-cart-elementor-blocks'),
            ]
        );

        $this->add_control(
            'confirmation_message',
            [
                'render_type' => 'template',
                'label'       => esc_html__('Message', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::WYSIWYG,
                'description' => esc_html__('Replaces the order confirmation line — links and formatting are supported, and the Short Codes button in the editor toolbar inserts order data (e.g. {{order.customer.full_name}}). Leave empty to keep the default (which links to the order).', 'fluent-cart-elementor-blocks'),
                'condition'   => [
                    'show_order_info' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function registerActionControls()
    {
        $this->start_controls_section(
            'section_actions',
            [
                'label'     => esc_html__('Actions', 'fluent-cart-elementor-blocks'),
                'tab'       => Controls_Manager::TAB_CONTENT,
                'condition' => [
                    'show_actions' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_view_order',
            [
                'label'        => esc_html__('View Order Button', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'view_order_text',
            [
                'render_type' => 'template',
                'label'       => esc_html__('View Order Text', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => esc_html__('View Order', 'fluent-cart-elementor-blocks'),
                'condition'   => [
                    'show_view_order' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_download_receipt',
            [
                'label'        => esc_html__('Download Receipt Button', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Show', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('Hide', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'download_receipt_text',
            [
                'render_type' => 'template',
                'label'       => esc_html__('Download Receipt Text', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => esc_html__('Download Receipt', 'fluent-cart-elementor-blocks'),
                'condition'   => [
                    'show_download_receipt' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }


    /* ------------------------------------------------------------------ */
    /* Render                                                              */
    /* ------------------------------------------------------------------ */

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $isEditor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        if ($isEditor) {
            // No order exists in the editor context — preview with a real
            // store order (read-only), or the static sample when none exists.
            $html = $this->renderPreviewReceipt($settings);
        } else {
            // Same delegation as the [fluent_cart_receipt] shortcode / the
            // Divi Receipt module — core resolves the order from the request
            // and renders the thank-you view (or the not-found state).
            $html = do_shortcode('[fluent_cart_receipt]');

            // Short-code context only. Leaking is impossible even when the
            // handler denied access: the overrides anchor on receipt-only
            // classes, which the not-found markup does not contain.
            $this->contextOrder = $this->resolveFrontendOrder();
        }

        if (!is_string($html) || $html === '') {
            return;
        }

        $html = $this->applyTextOverrides($html, $settings);

        printf(
            '%s<div class="fce-order-receipt %s">%s</div>',
            $this->toggleCss(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static CSS below
            esc_attr(implode(' ', $this->hiddenSectionClasses($settings))),
            $html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-rendered receipt markup
        );
    }

    /**
     * Single source of truth for every visibility toggle: the switcher key,
     * its Sections-panel label (null for the Actions sub-toggles, which are
     * registered with their own labels/conditions in registerActionControls),
     * the wrapper modifier class, and the core selectors that class hides.
     * Drives the Sections controls, hiddenSectionClasses() and toggleCss() —
     * adding a toggle means adding ONE entry here.
     *
     * @return array<string, array{label: ?string, class: string, hides: array<int, string>}>
     */
    private function sectionMap()
    {
        return [
            'show_confirmation' => [
                'label' => esc_html__('Confirmation', 'fluent-cart-elementor-blocks'),
                'class' => 'fce-receipt--hide-confirmation',
                'hides' => ['.fct-thank-you-page-header'],
            ],
            'show_order_info' => [
                'label' => esc_html__('Order Information', 'fluent-cart-elementor-blocks'),
                'class' => 'fce-receipt--hide-order-info',
                'hides' => ['.fct-thank-you-page .no-print'],
            ],
            'show_order_items' => [
                'label' => esc_html__('Order Items', 'fluent-cart-elementor-blocks'),
                'class' => 'fce-receipt--hide-order-items',
                'hides' => [
                    '.fct-thank-you-page-order-items-header',
                    '.fct-thank-you-page-order-items-list',
                ],
            ],
            'show_order_summary' => [
                'label' => esc_html__('Order Summary', 'fluent-cart-elementor-blocks'),
                'class' => 'fce-receipt--hide-order-summary',
                'hides' => ['.fct-thank-you-page-order-items-total'],
            ],
            'show_billing_address' => [
                'label' => esc_html__('Billing Address', 'fluent-cart-elementor-blocks'),
                'class' => 'fce-receipt--hide-billing',
                'hides' => ['.fct-thank-you-page-order-items-addresses-bill-to'],
            ],
            'show_shipping_address' => [
                'label' => esc_html__('Shipping Address', 'fluent-cart-elementor-blocks'),
                'class' => 'fce-receipt--hide-shipping',
                'hides' => ['.fct-thank-you-page-order-items-addresses-ship-to'],
            ],
            'show_actions' => [
                'label' => esc_html__('Actions', 'fluent-cart-elementor-blocks'),
                'class' => 'fce-receipt--hide-actions',
                'hides' => ['.fct-thank-you-page-footer'],
            ],
            'show_view_order' => [
                'label' => null,
                'class' => 'fce-receipt--hide-view-order',
                'hides' => ['.fct-thank-you-page-view-order-button'],
            ],
            'show_download_receipt' => [
                'label' => null,
                'class' => 'fce-receipt--hide-download-receipt',
                'hides' => ['.fct-thank-you-page-download-receipt-button'],
            ],
        ];
    }

    /**
     * The section-toggle rules, generated from sectionMap() and inlined with
     * the widget output so they travel with EVERY render — including the
     * editor's AJAX widget re-renders, where an enqueued stylesheet may be
     * stale-cached or absent from the preview iframe. Static rules only, no
     * user input.
     *
     * @return string
     */
    private function toggleCss()
    {
        $rules = [];

        foreach ($this->sectionMap() as $section) {
            foreach ($section['hides'] as $selector) {
                $rules[] = '.' . $section['class'] . ' ' . $selector;
            }
        }

        return '<style>' . implode(',', $rules) . '{display:none !important;}</style>';
    }

    /**
     * Wrapper modifier classes for the visibility toggles (from sectionMap()).
     * Hiding is CSS-only — the markup is core's blob, so sections are hidden,
     * never removed, and the frontend receipt logic is untouched.
     *
     * @param array $settings
     * @return array
     */
    private function hiddenSectionClasses(array $settings)
    {
        $classes = [];

        foreach ($this->sectionMap() as $key => $section) {
            if (($settings[$key] ?? 'yes') !== 'yes') {
                $classes[] = $section['class'];
            }
        }

        return $classes;
    }

    /**
     * Replace the inner text of class-anchored elements in core's rendered
     * receipt. Anchoring on core's stable classes keeps core the single
     * source of truth for markup; a pattern that finds no target (changed
     * markup, not-found state) leaves the HTML untouched.
     *
     * @param string $html
     * @param array  $settings
     * @return string
     */
    private function applyTextOverrides($html, array $settings)
    {
        $title = $this->resolvedCustomText($settings, 'confirmation_title');
        if ($title !== '') {
            $html = $this->replaceFirstMatch(
                $html,
                '/(<h1\s+class="fct-thank-you-page-header-title"[^>]*>).*?(<\/h1>)/s',
                '$1%s$2',
                esc_html($title)
            );
        }

        // WYSIWYG content: block-level HTML of its own, so it replaces the
        // whole confirmation <p> after the greeting title instead of being
        // injected inside it. wp_kses_post allows links/formatting only.
        // The renderable check keeps an image-only message from being treated
        // as empty (wp_strip_all_tags would reduce it to '').
        $message = $this->resolvedCustomText($settings, 'confirmation_message');
        if ($message !== '' && (trim(wp_strip_all_tags($message)) !== '' || strpos($message, '<img') !== false)) {
            $html = $this->replaceFirstMatch(
                $html,
                '/(<div class="no-print"[^>]*>\s*<div class="no-print-title"[^>]*>.*?<\/div>\s*)<p[^>]*>.*?<\/p>/s',
                '$1<div class="fce-receipt-message">%s</div>',
                wp_kses_post($message)
            );
        }

        $viewOrderText = $this->resolvedCustomText($settings, 'view_order_text');
        if ($viewOrderText !== '') {
            $html = $this->replaceFirstMatch(
                $html,
                '/(<a\s+class="fct-thank-you-page-view-order-button"[^>]*>).*?(<\/a>)/s',
                '$1%s$2',
                esc_html($viewOrderText)
            );
        }

        $downloadText = $this->resolvedCustomText($settings, 'download_receipt_text');
        if ($downloadText !== '') {
            $html = $this->replaceFirstMatch(
                $html,
                '/(<a\s+class="fct-thank-you-page-download-receipt-button"[^>]*>).*?(<\/a>)/s',
                '$1%s$2',
                esc_html($downloadText)
            );
        }

        return $html;
    }

    /**
     * A custom-text setting, trimmed and with short codes resolved.
     *
     * @param array  $settings
     * @param string $key
     * @return string
     */
    private function resolvedCustomText(array $settings, $key)
    {
        return $this->parseShortCodes(trim((string) ($settings[$key] ?? '')));
    }

    /**
     * Replace the first match of a class-anchored pattern with a template
     * carrying the (already sanitized) value. The value is escaped for
     * preg_replace's replacement position — user text containing "$1" or
     * backslashes must never be interpreted as backreferences.
     *
     * @param string $html
     * @param string $pattern
     * @param string $template sprintf template using preg groups ($1/$2) + %s
     * @param string $valueHtml
     * @return string
     */
    private function replaceFirstMatch($html, $pattern, $template, $valueHtml)
    {
        return (string) preg_replace(
            $pattern,
            sprintf($template, addcslashes($valueHtml, '\\$')),
            $html,
            1
        );
    }

    /**
     * Editor-only: render the receipt for a real order — latest, or the one
     * chosen in Preview Settings — mirroring the read-only parts of core's
     * ReceiptHandler::renderRedirectPage(). Deliberately skips the handler's
     * side effects (sales_recorded flag, first-time actions): a canvas
     * preview must never mutate store data.
     *
     * @param array $settings
     * @return string
     */
    private function renderPreviewReceipt(array $settings)
    {
        try {
            $order = $this->resolvePreviewOrder($settings);

            if (!$order) {
                return $this->sampleReceiptHtml();
            }

            $this->contextOrder = $order;

            $order->loadMissing([
                'customer',
                'order_items',
                'billing_address',
                'shipping_address',
                'transactions',
            ]);

            $order->last_transaction = OrderTransaction::query()
                ->where('order_id', $order->id)
                ->where('transaction_type', '=', Status::TRANSACTION_TYPE_CHARGE)
                ->orderBy('id', 'DESC')
                ->first();

            $config = [
                'order'                => $order,
                'vat_tax_id'           => $order->getMeta('vat_tax_id', ''),
                'order_operation'      => OrderOperation::query()->where('order_id', $order->id)->first(),
                'is_first_time'        => false,
                'default_banner_image' => \FluentCart\App\Vite::getAssetUrl('images/email-template/email-banner.png'),
                'user_tz'              => Arr::get($order->config, 'user_tz', wp_timezone_string()),
            ];

            ob_start();
            (new ThankYouRender($config))->render();
            $html = (string) ob_get_clean();

            if ($html === '') {
                return $this->sampleReceiptHtml();
            }

            return (string) ShortcodeTemplateBuilder::make($html, [
                'order' => $order,
            ]);
        } catch (\Throwable $e) {
            // Editor robustness: any data quirk falls back to the sample
            // instead of a broken canvas.
            return $this->sampleReceiptHtml();
        }
    }

    /**
     * The order to preview with: a specific ID when chosen, else the latest.
     *
     * @param array $settings
     * @return \FluentCart\App\Models\Order|null
     */
    private function resolvePreviewOrder(array $settings)
    {
        if (($settings['preview_order_type'] ?? '') === 'custom') {
            $orderId = absint($settings['preview_order_id'] ?? 0);

            return $orderId ? (new Orders())->getById($orderId) : null;
        }

        $latest = Order::query()->orderBy('id', 'DESC')->first();

        return $latest ? (new Orders())->getById($latest->id) : null;
    }

    /**
     * Parse FluentCart short codes ({{order.*}} — e.g.
     * {{order.customer.full_name}}, {{order.invoice_no}}) in a custom text,
     * using core's own template engine with the current render's order as
     * context. Without a resolved order the codes are left as-is. Runs BEFORE
     * sanitization, so code output is escaped like any other user text.
     *
     * @param string $text
     * @return string
     */
    private function parseShortCodes($text)
    {
        if ($text === '' || strpos($text, '{{') === false || !$this->contextOrder) {
            return $text;
        }

        try {
            return (string) ShortcodeTemplateBuilder::make($text, [
                'order' => $this->contextOrder,
            ]);
        } catch (\Throwable $e) {
            return $text;
        }
    }

    /**
     * Frontend short-code context: the same order the receipt shortcode just
     * resolved from the checkout redirect URL. Read-only lookup; when neither
     * hash is present or nothing matches, custom texts keep their codes —
     * and since the overrides only anchor on receipt-only markup, nothing is
     * ever injected into the not-found state anyway.
     *
     * @return \FluentCart\App\Models\Order|null
     */
    private function resolveFrontendOrder()
    {
        try {
            $request = \FluentCart\App\App::request();

            $orderHash = sanitize_text_field((string) $request->get('order_hash'));
            if ($orderHash !== '') {
                return (new Orders())->getBy('uuid', $orderHash);
            }

            $trxHash = sanitize_text_field((string) $request->get('trx_hash'));
            if ($trxHash !== '') {
                $transaction = OrderTransaction::query()->where('uuid', $trxHash)->first();

                return $transaction ? (new Orders())->getById($transaction->order_id) : null;
            }
        } catch (\Throwable $e) {
            // Missing context must never break the receipt page.
        }

        return null;
    }

    /**
     * Sample thank-you page for stores with no orders yet, filled with dummy
     * data — same markup and classes ThankYouRender emits.
     *
     * @return string
     */
    private function sampleReceiptHtml()
    {
        ob_start();
        include dirname(__DIR__, 4) . '/Views/receipt-sample.php';

        return ob_get_clean();
    }
}
