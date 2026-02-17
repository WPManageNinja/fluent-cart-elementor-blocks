<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Repeater;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers\ElementorCheckoutRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers\DummyCheckoutRenderer;
use FluentCartElementorBlocks\App\Utils\Enqueuer\Vite;

class CheckoutWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_checkout';
    }

    public function get_title()
    {
        return esc_html__('Checkout', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-checkout';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['checkout', 'cart', 'payment', 'order', 'fluent', 'commerce'];
    }

    public function get_style_depends()
    {
        // Load checkout CSS directly to ensure it loads even without a cart (for editor preview)
        $this->loadCheckoutStyles();

        $app = \FluentCart\App\App::getInstance();
        $slug = $app->config->get('app.slug');

        return [
            // Checkout styles
                'fce-checkout-page-css',
                'fce-checkout-select-css',
            // Cart base styles
                $slug . '-fluentcart-drawer',
                $slug . '-global-styles',
                $slug . '-fluentcart-toastify-notify-js',
        ];
    }

    /**
     * Load checkout styles directly using FluentCart's Vite
     * This bypasses the cart check in AssetLoader::loadCheckoutAssets()
     */
    private function loadCheckoutStyles()
    {
        static $isLoaded = false;
        if ($isLoaded) {
            return;
        }
        $isLoaded = true;

        // Load cart assets first (base styles)
        AssetLoader::loadCartAssets();

        // Directly enqueue checkout styles with specific handles
        Vite::enqueueStyle(
                'fce-checkout-page-css',
                'public/checkout/style/checkout.scss'
        );

        Vite::enqueueStyle(
                'fce-checkout-select-css',
                'public/components/select/style/style.scss'
        );
    }

    public function get_script_depends()
    {
        return [
                'fluentcart-checkout-js',
        ];
    }

    protected function register_controls()
    {
        $this->registerGeneralControls();
        $this->registerFormFieldsControls();
        $this->registerSummaryControls();
        $this->registerLayoutControls();
        $this->registerFormFieldStyleControls();
        $this->registerSectionHeadingStyleControls();
        $this->registerSubmitButtonStyleControls();
        $this->registerSummaryBoxStyleControls();
        $this->registerSummaryItemsStyleControls();
        $this->registerCouponFieldStyleControls();
        $this->registerPaymentMethodsStyleControls();
        $this->registerAddressFieldsStyleControls();
        $this->registerErrorValidationStyleControls();
    }

    /**
     * General Settings Controls
     */
    private function registerGeneralControls()
    {
        $this->start_controls_section(
                'general_section',
                [
                        'label' => esc_html__('General Settings', 'fluent-cart'),
                        'tab'   => Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'layout_type',
                [
                        'label'   => esc_html__('Layout', 'fluent-cart'),
                        'type'    => Controls_Manager::SELECT,
                        'default' => 'two-column',
                        'options' => [
                                'one-column' => esc_html__('One Column', 'fluent-cart'),
                                'two-column' => esc_html__('Two Column', 'fluent-cart'),
                        ],
                ]
        );

        $this->add_responsive_control(
                'form_column_width',
                [
                        'label'      => esc_html__('Form Column Width (%)', 'fluent-cart'),
                        'type'       => Controls_Manager::SLIDER,
                        'size_units' => ['%'],
                        'range'      => [
                                '%' => ['min' => 30, 'max' => 80],
                        ],
                        'default'    => ['size' => 65, 'unit' => '%'],
                        'selectors'  => [
                                '{{WRAPPER}} .fce-checkout-form-column' => 'width: {{SIZE}}{{UNIT}};',
                        ],
                        'condition'  => [
                                'layout_type' => 'two-column',
                        ]
                ]
        );

        $this->add_responsive_control(
                'summary_column_width',
                [
                        'label'      => esc_html__('Summary Column Width (%)', 'fluent-cart'),
                        'type'       => Controls_Manager::SLIDER,
                        'size_units' => ['%'],
                        'range'      => [
                                '%' => ['min' => 20, 'max' => 70],
                        ],
                        'default'    => ['size' => 35, 'unit' => '%'],
                        'selectors'  => [
                                '{{WRAPPER}} .fce-checkout-summary-column' => 'width: {{SIZE}}{{UNIT}};',
                        ],
                        'condition'  => [
                                'layout_type' => 'two-column',
                        ],
                ]
        );

        $this->add_responsive_control(
                'column_gap',
                [
                        'label'      => esc_html__('Column Gap', 'fluent-cart'),
                        'type'       => Controls_Manager::SLIDER,
                        'size_units' => ['px', 'em'],
                        'range'      => [
                                'px' => ['min' => 0, 'max' => 100],
                                'em' => ['min' => 0, 'max' => 10],
                        ],
                        'default'    => ['size' => 30, 'unit' => 'px'],
                        'selectors'  => [
                                '{{WRAPPER}} .fce-checkout-columns' => 'gap: {{SIZE}}{{UNIT}};',
                        ],
                        'condition'  => [
                                'layout_type' => 'two-column',
                        ],
                ]
        );

        $this->add_control(
                'use_default_style',
                [
                        'label'        => esc_html__('Use Default FluentCart Styles', 'fluent-cart'),
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => esc_html__('Yes', 'fluent-cart'),
                        'label_off'    => esc_html__('No', 'fluent-cart'),
                        'return_value' => 'yes',
                        'default'      => 'yes',
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Form Fields Repeater Controls
     */
    private function registerFormFieldsControls()
    {
        $this->start_controls_section(
                'form_fields_section',
                [
                        'label' => esc_html__('Form Fields', 'fluent-cart'),
                        'tab'   => Controls_Manager::TAB_CONTENT,
                ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
                'element_type',
                [
                        'label'   => esc_html__('Section', 'fluent-cart'),
                        'type'    => Controls_Manager::SELECT,
                        'default' => 'name_fields',
                        'options' => [
                                'name_fields'      => esc_html__('Name Fields', 'fluent-cart'),
                                'create_account'   => esc_html__('Create Account', 'fluent-cart'),
                                'address_fields'   => esc_html__('Address Fields', 'fluent-cart'),
                                'shipping_methods' => esc_html__('Shipping Methods', 'fluent-cart'),
                                'payment_methods'  => esc_html__('Payment Methods', 'fluent-cart'),
                                'agree_terms'      => esc_html__('Agree to Terms', 'fluent-cart'),
                                'order_notes'      => esc_html__('Order Notes', 'fluent-cart'),
                                'submit_button'    => esc_html__('Submit Button', 'fluent-cart'),
                        ],
                ]
        );

        $repeater->add_control(
                'element_visibility',
                [
                        'label'        => esc_html__('Visible', 'fluent-cart'),
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => esc_html__('Yes', 'fluent-cart'),
                        'label_off'    => esc_html__('No', 'fluent-cart'),
                        'return_value' => 'yes',
                        'default'      => 'yes',
                ]
        );

        // Address Fields specific controls
        $repeater->add_control(
                'address_type',
                [
                        'label'     => esc_html__('Address Display', 'fluent-cart'),
                        'type'      => Controls_Manager::SELECT,
                        'default'   => 'both',
                        'options'   => [
                                'both'     => esc_html__('Billing + Shipping', 'fluent-cart'),
                                'billing'  => esc_html__('Billing Only', 'fluent-cart'),
                                'shipping' => esc_html__('Shipping Only', 'fluent-cart'),
                        ],
                        'condition' => [
                                'element_type' => 'address_fields',
                        ],
                ]
        );

        $repeater->add_control(
                'show_ship_to_different',
                [
                        'label'        => esc_html__('Show "Ship to Different Address"', 'fluent-cart'),
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => esc_html__('Yes', 'fluent-cart'),
                        'label_off'    => esc_html__('No', 'fluent-cart'),
                        'return_value' => 'yes',
                        'default'      => 'yes',
                        'condition'    => [
                                'element_type' => 'address_fields',
                                'address_type' => 'both',
                        ],
                ]
        );

        // Custom labels
        $repeater->add_control(
                'custom_heading',
                [
                        'label'       => esc_html__('Custom Section Heading', 'fluent-cart'),
                        'type'        => Controls_Manager::TEXT,
                        'placeholder' => esc_html__('Leave empty for default', 'fluent-cart'),
                ]
        );

        $this->add_control(
                'form_elements',
                [
                        'label'       => esc_html__('Form Sections', 'fluent-cart'),
                        'type'        => Controls_Manager::REPEATER,
                        'fields'      => $repeater->get_controls(),
                        'default'     => [
                                ['element_type' => 'name_fields', 'element_visibility' => 'yes'],
                                ['element_type' => 'create_account', 'element_visibility' => 'yes'],
                                ['element_type' => 'address_fields', 'element_visibility' => 'yes', 'address_type' => 'both', 'show_ship_to_different' => 'yes'],
                                ['element_type' => 'agree_terms', 'element_visibility' => 'yes'],
                                ['element_type' => 'shipping_methods', 'element_visibility' => 'yes'],
                                ['element_type' => 'payment_methods', 'element_visibility' => 'yes'],
                                ['element_type' => 'submit_button', 'element_visibility' => 'yes'],
                        ],
                        'title_field' => '{{{ {"name_fields":"Name Fields","create_account":"Create Account","address_fields":"Address Fields","shipping_methods":"Shipping Methods","payment_methods":"Payment Methods","agree_terms":"Agree to Terms","order_notes":"Order Notes","submit_button":"Submit Button"}[element_type] || element_type }}}',
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Summary Repeater Controls
     */
    private function registerSummaryControls()
    {
        $this->start_controls_section(
                'summary_section',
                [
                        'label' => esc_html__('Order Summary', 'fluent-cart'),
                        'tab'   => Controls_Manager::TAB_CONTENT,
                ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
                'element_type',
                [
                        'label'   => esc_html__('Section', 'fluent-cart'),
                        'type'    => Controls_Manager::SELECT,
                        'default' => 'order_summary',
                        'options' => [
                                'order_summary'   => esc_html__('Order Summary (Items)', 'fluent-cart'),
                                'subtotal'        => esc_html__('Subtotal', 'fluent-cart'),
                                'shipping'        => esc_html__('Shipping', 'fluent-cart'),
                                'coupon'          => esc_html__('Coupon Field', 'fluent-cart'),
                                'manual_discount' => esc_html__('Manual Discount', 'fluent-cart'),
                                'tax'             => esc_html__('Tax', 'fluent-cart'),
                                'shipping_tax'    => esc_html__('Shipping Tax', 'fluent-cart'),
                                'total'           => esc_html__('Total', 'fluent-cart'),
                                'order_bump'      => esc_html__('Order Bump (Pro)', 'fluent-cart'),
                        ],
                ]
        );

        $repeater->add_control(
                'element_visibility',
                [
                        'label'        => esc_html__('Visible', 'fluent-cart'),
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => esc_html__('Yes', 'fluent-cart'),
                        'label_off'    => esc_html__('No', 'fluent-cart'),
                        'return_value' => 'yes',
                        'default'      => 'yes',
                ]
        );

        // Coupon specific controls
        $repeater->add_control(
                'coupon_collapsible',
                [
                        'label'        => esc_html__('Collapsible', 'fluent-cart'),
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => esc_html__('Yes', 'fluent-cart'),
                        'label_off'    => esc_html__('No', 'fluent-cart'),
                        'return_value' => 'yes',
                        'default'      => 'yes',
                        'condition'    => [
                                'element_type' => 'coupon',
                        ],
                ]
        );

        $repeater->add_control(
                'coupon_label',
                [
                        'label'     => esc_html__('Coupon Label', 'fluent-cart'),
                        'type'      => Controls_Manager::TEXT,
                        'default'   => esc_html__('Have a Coupon?', 'fluent-cart'),
                        'condition' => [
                                'element_type' => 'coupon',
                        ],
                ]
        );

        $this->add_control(
                'summary_elements',
                [
                        'label'       => esc_html__('Summary Sections', 'fluent-cart'),
                        'type'        => Controls_Manager::REPEATER,
                        'fields'      => $repeater->get_controls(),
                        'default'     => [
                                ['element_type' => 'order_summary', 'element_visibility' => 'yes'],
                                ['element_type' => 'subtotal', 'element_visibility' => 'yes'],
                                ['element_type' => 'shipping', 'element_visibility' => 'yes'],
                                ['element_type' => 'coupon', 'element_visibility' => 'yes', 'coupon_collapsible' => 'yes'],
                                ['element_type' => 'manual_discount', 'element_visibility' => 'yes'],
                                ['element_type' => 'tax', 'element_visibility' => 'yes'],
                                ['element_type' => 'shipping_tax', 'element_visibility' => 'yes'],
                                ['element_type' => 'total', 'element_visibility' => 'yes'],
                                ['element_type' => 'order_bump', 'element_visibility' => 'yes'],
                        ],
                        'title_field' => '{{{ {"order_summary":"Order Summary","subtotal":"Subtotal","shipping":"Shipping","coupon":"Coupon","manual_discount":"Manual Discount","tax":"Tax","shipping_tax":"Shipping Tax","total":"Total","order_bump":"Order Bump"}[element_type] || element_type }}}',
                ]
        );

        $this->add_control(
                'summary_heading',
                [
                        'label'   => esc_html__('Summary Heading', 'fluent-cart'),
                        'type'    => Controls_Manager::TEXT,
                        'default' => esc_html__('Order Summary', 'fluent-cart'),
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Layout Controls
     */
    private function registerLayoutControls()
    {
        $this->start_controls_section(
                'layout_section',
                [
                        'label' => esc_html__('Layout Options', 'fluent-cart'),
                        'tab'   => Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'sticky_summary',
                [
                        'label'        => esc_html__('Sticky Summary', 'fluent-cart'),
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => esc_html__('Yes', 'fluent-cart'),
                        'label_off'    => esc_html__('No', 'fluent-cart'),
                        'return_value' => 'yes',
                        'default'      => '',
                        'condition'    => [
                                'layout_type' => 'two-column',
                        ],
                ]
        );

        $this->add_responsive_control(
                'sticky_offset',
                [
                        'label'      => esc_html__('Sticky Offset', 'fluent-cart'),
                        'type'       => Controls_Manager::SLIDER,
                        'size_units' => ['px'],
                        'range'      => [
                                'px' => ['min' => 0, 'max' => 200],
                        ],
                        'default'    => ['size' => 20, 'unit' => 'px'],
                        'selectors'  => [
                                '{{WRAPPER}} .fce-checkout-summary-column.is-sticky' => 'top: {{SIZE}}{{UNIT}};',
                        ],
                        'condition'  => [
                                'layout_type'    => 'two-column',
                                'sticky_summary' => 'yes',
                        ],
                ]
        );

        $this->add_control(
                'empty_cart_message',
                [
                        'label'   => esc_html__('Empty Cart Message', 'fluent-cart'),
                        'type'    => Controls_Manager::TEXT,
                        'default' => esc_html__('Your cart is empty.', 'fluent-cart'),
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Form Field Style Controls
     */
    private function registerFormFieldStyleControls()
    {
        $this->start_controls_section(
                'form_field_style_section',
                [
                        'label' => esc_html__('Form Fields', 'fluent-cart'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'input_typography',
                        'label'    => esc_html__('Input Typography', 'fluent-cart'),
                        'selector' => '{{WRAPPER}} .fct_checkout input, {{WRAPPER}} .fct_checkout select, {{WRAPPER}} .fct_checkout textarea',
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'label_typography',
                        'label'    => esc_html__('Label Typography', 'fluent-cart'),
                        'selector' => '{{WRAPPER}} .fct_checkout label',
                ]
        );

        $this->start_controls_tabs('input_style_tabs');

        // Normal State
        $this->start_controls_tab(
                'input_normal_tab',
                ['label' => esc_html__('Normal', 'fluent-cart')]
        );

        $this->add_control(
                'input_bg_color',
                [
                        'label'     => esc_html__('Background Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout input, {{WRAPPER}} .fct_checkout select, {{WRAPPER}} .fct_checkout textarea' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'input_text_color',
                [
                        'label'     => esc_html__('Text Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout input, {{WRAPPER}} .fct_checkout select, {{WRAPPER}} .fct_checkout textarea' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'input_placeholder_color',
                [
                        'label'     => esc_html__('Placeholder Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout input::placeholder, {{WRAPPER}} .fct_checkout textarea::placeholder' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                        'name'     => 'input_border',
                        'selector' => '{{WRAPPER}} .fct_checkout input, {{WRAPPER}} .fct_checkout select, {{WRAPPER}} .fct_checkout textarea',
                ]
        );

        $this->end_controls_tab();

        // Focus State
        $this->start_controls_tab(
                'input_focus_tab',
                ['label' => esc_html__('Focus', 'fluent-cart')]
        );

        $this->add_control(
                'input_focus_bg_color',
                [
                        'label'     => esc_html__('Background Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout input:focus, {{WRAPPER}} .fct_checkout select:focus, {{WRAPPER}} .fct_checkout textarea:focus' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'input_focus_border_color',
                [
                        'label'     => esc_html__('Border Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout input:focus, {{WRAPPER}} .fct_checkout select:focus, {{WRAPPER}} .fct_checkout textarea:focus' => 'border-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Box_Shadow::get_type(),
                [
                        'name'     => 'input_focus_shadow',
                        'selector' => '{{WRAPPER}} .fct_checkout input:focus, {{WRAPPER}} .fct_checkout select:focus, {{WRAPPER}} .fct_checkout textarea:focus',
                ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
                'input_border_radius',
                [
                        'label'      => esc_html__('Border Radius', 'fluent-cart'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', '%'],
                        'separator'  => 'before',
                        'selectors'  => [
                                '{{WRAPPER}} .fct_checkout input, {{WRAPPER}} .fct_checkout select, {{WRAPPER}} .fct_checkout textarea' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'input_padding',
                [
                        'label'      => esc_html__('Padding', 'fluent-cart'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_checkout input, {{WRAPPER}} .fct_checkout select, {{WRAPPER}} .fct_checkout textarea' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'input_height',
                [
                        'label'      => esc_html__('Input Height', 'fluent-cart'),
                        'type'       => Controls_Manager::SLIDER,
                        'size_units' => ['px'],
                        'range'      => [
                                'px' => ['min' => 30, 'max' => 80],
                        ],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_checkout input:not([type="checkbox"]):not([type="radio"]), {{WRAPPER}} .fct_checkout select' => 'height: {{SIZE}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'field_spacing',
                [
                        'label'      => esc_html__('Field Spacing', 'fluent-cart'),
                        'type'       => Controls_Manager::SLIDER,
                        'size_units' => ['px', 'em'],
                        'range'      => [
                                'px' => ['min' => 0, 'max' => 50],
                        ],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_checkout .fct_form_group' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_control(
                'transition_duration',
                [
                        'label'     => esc_html__('Transition Duration (ms)', 'fluent-cart'),
                        'type'      => Controls_Manager::NUMBER,
                        'default'   => 200,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout input, {{WRAPPER}} .fct_checkout select, {{WRAPPER}} .fct_checkout textarea' => 'transition: all {{VALUE}}ms ease;',
                        ],
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Section Heading Style Controls
     */
    private function registerSectionHeadingStyleControls()
    {
        $this->start_controls_section(
                'section_heading_style_section',
                [
                        'label' => esc_html__('Section Headings', 'fluent-cart'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'section_heading_typography',
                        'selector' => '{{WRAPPER}} .fct_checkout .fct_form_section_heading, {{WRAPPER}} .fct_checkout h3, {{WRAPPER}} .fct_checkout h4',
                ]
        );

        $this->add_control(
                'section_heading_color',
                [
                        'label'     => esc_html__('Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout .fct_form_section_heading, {{WRAPPER}} .fct_checkout h3, {{WRAPPER}} .fct_checkout h4' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'section_heading_bg_color',
                [
                        'label'     => esc_html__('Background Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout .fct_form_section_heading' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'section_heading_padding',
                [
                        'label'      => esc_html__('Padding', 'fluent-cart'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_checkout .fct_form_section_heading' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'section_heading_margin',
                [
                        'label'      => esc_html__('Margin', 'fluent-cart'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_checkout .fct_form_section_heading' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                        'name'     => 'section_heading_border',
                        'selector' => '{{WRAPPER}} .fct_checkout .fct_form_section_heading',
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Submit Button Style Controls
     */
    private function registerSubmitButtonStyleControls()
    {
        $this->start_controls_section(
                'submit_button_style_section',
                [
                        'label' => esc_html__('Submit Button', 'fluent-cart'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'submit_button_typography',
                        'selector' => '{{WRAPPER}} .fct_checkout .fct_checkout_btn, {{WRAPPER}} .fct_checkout button[type="submit"]',
                ]
        );

        $this->add_control(
                'submit_button_width',
                [
                        'label'                => esc_html__('Button Width', 'fluent-cart'),
                        'type'                 => Controls_Manager::SELECT,
                        'default'              => 'full',
                        'options'              => [
                                'auto' => esc_html__('Auto', 'fluent-cart'),
                                'full' => esc_html__('Full Width', 'fluent-cart'),
                        ],
                        'selectors_dictionary' => [
                                'auto' => 'auto',
                                'full' => '100%',
                        ],
                        'selectors'            => [
                                '{{WRAPPER}} .fct_checkout .fct_checkout_btn, {{WRAPPER}} .fct_checkout button[type="submit"]' => 'width: {{VALUE}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'submit_button_alignment',
                [
                        'label'     => esc_html__('Alignment', 'fluent-cart'),
                        'type'      => Controls_Manager::CHOOSE,
                        'options'   => [
                                'left'   => ['title' => esc_html__('Left', 'fluent-cart'), 'icon' => 'eicon-text-align-left'],
                                'center' => ['title' => esc_html__('Center', 'fluent-cart'), 'icon' => 'eicon-text-align-center'],
                                'right'  => ['title' => esc_html__('Right', 'fluent-cart'), 'icon' => 'eicon-text-align-right'],
                        ],
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout .fct_checkout_btn_wrap' => 'text-align: {{VALUE}};',
                        ],
                        'condition' => [
                                'submit_button_width' => 'auto',
                        ],
                ]
        );

        $this->start_controls_tabs('submit_button_style_tabs');

        // Normal State
        $this->start_controls_tab(
                'submit_button_normal_tab',
                ['label' => esc_html__('Normal', 'fluent-cart')]
        );

        $this->add_control(
                'submit_button_text_color',
                [
                        'label'     => esc_html__('Text Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout .fct_checkout_btn, {{WRAPPER}} .fct_checkout button[type="submit"]' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Background::get_type(),
                [
                        'name'     => 'submit_button_background',
                        'types'    => ['classic', 'gradient'],
                        'selector' => '{{WRAPPER}} .fct_checkout .fct_checkout_btn, {{WRAPPER}} .fct_checkout button[type="submit"]',
                ]
        );

        $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                        'name'     => 'submit_button_border',
                        'selector' => '{{WRAPPER}} .fct_checkout .fct_checkout_btn, {{WRAPPER}} .fct_checkout button[type="submit"]',
                ]
        );

        $this->add_group_control(
                Group_Control_Box_Shadow::get_type(),
                [
                        'name'     => 'submit_button_shadow',
                        'selector' => '{{WRAPPER}} .fct_checkout .fct_checkout_btn, {{WRAPPER}} .fct_checkout button[type="submit"]',
                ]
        );

        $this->end_controls_tab();

        // Hover State
        $this->start_controls_tab(
                'submit_button_hover_tab',
                ['label' => esc_html__('Hover', 'fluent-cart')]
        );

        $this->add_control(
                'submit_button_hover_text_color',
                [
                        'label'     => esc_html__('Text Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout .fct_checkout_btn:hover, {{WRAPPER}} .fct_checkout button[type="submit"]:hover' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Background::get_type(),
                [
                        'name'     => 'submit_button_hover_background',
                        'types'    => ['classic', 'gradient'],
                        'selector' => '{{WRAPPER}} .fct_checkout .fct_checkout_btn:hover, {{WRAPPER}} .fct_checkout button[type="submit"]:hover',
                ]
        );

        $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                        'name'     => 'submit_button_hover_border',
                        'selector' => '{{WRAPPER}} .fct_checkout .fct_checkout_btn:hover, {{WRAPPER}} .fct_checkout button[type="submit"]:hover',
                ]
        );

        $this->add_group_control(
                Group_Control_Box_Shadow::get_type(),
                [
                        'name'     => 'submit_button_hover_shadow',
                        'selector' => '{{WRAPPER}} .fct_checkout .fct_checkout_btn:hover, {{WRAPPER}} .fct_checkout button[type="submit"]:hover',
                ]
        );

        $this->add_control(
                'submit_button_hover_animation',
                [
                        'label'   => esc_html__('Hover Animation', 'fluent-cart'),
                        'type'    => Controls_Manager::SELECT,
                        'default' => 'none',
                        'options' => [
                                'none'       => esc_html__('None', 'fluent-cart'),
                                'scale'      => esc_html__('Scale Up', 'fluent-cart'),
                                'scale-down' => esc_html__('Scale Down', 'fluent-cart'),
                                'lift'       => esc_html__('Lift', 'fluent-cart'),
                        ],
                ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
                'submit_button_border_radius',
                [
                        'label'      => esc_html__('Border Radius', 'fluent-cart'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', '%'],
                        'separator'  => 'before',
                        'selectors'  => [
                                '{{WRAPPER}} .fct_checkout .fct_checkout_btn, {{WRAPPER}} .fct_checkout button[type="submit"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'submit_button_padding',
                [
                        'label'      => esc_html__('Padding', 'fluent-cart'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_checkout .fct_checkout_btn, {{WRAPPER}} .fct_checkout button[type="submit"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_control(
                'submit_button_transition',
                [
                        'label'     => esc_html__('Transition Duration (ms)', 'fluent-cart'),
                        'type'      => Controls_Manager::NUMBER,
                        'default'   => 300,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout .fct_checkout_btn, {{WRAPPER}} .fct_checkout button[type="submit"]' => 'transition: all {{VALUE}}ms ease;',
                        ],
                ]
        );

        // Loading State
        $this->add_control(
                'loading_state_heading',
                [
                        'label'     => esc_html__('Loading State', 'fluent-cart'),
                        'type'      => Controls_Manager::HEADING,
                        'separator' => 'before',
                ]
        );

        $this->add_control(
                'loading_opacity',
                [
                        'label'     => esc_html__('Disabled Opacity', 'fluent-cart'),
                        'type'      => Controls_Manager::SLIDER,
                        'range'     => [
                                'px' => ['min' => 0.1, 'max' => 1, 'step' => 0.1],
                        ],
                        'default'   => ['size' => 0.6],
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout .fct_checkout_btn:disabled, {{WRAPPER}} .fct_checkout .fct_checkout_btn.is-loading' => 'opacity: {{SIZE}};',
                        ],
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Summary Box Style Controls
     */
    private function registerSummaryBoxStyleControls()
    {
        $this->start_controls_section(
                'summary_box_style_section',
                [
                        'label' => esc_html__('Summary Box', 'fluent-cart'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_group_control(
                Group_Control_Background::get_type(),
                [
                        'name'     => 'summary_box_background',
                        'types'    => ['classic', 'gradient'],
                        'selector' => '{{WRAPPER}} .fct_checkout_summary, {{WRAPPER}} .fct_summary_box',
                ]
        );

        $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                        'name'     => 'summary_box_border',
                        'selector' => '{{WRAPPER}} .fct_checkout_summary, {{WRAPPER}} .fct_summary_box',
                ]
        );

        $this->add_control(
                'summary_box_border_radius',
                [
                        'label'      => esc_html__('Border Radius', 'fluent-cart'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', '%'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_checkout_summary, {{WRAPPER}} .fct_summary_box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Box_Shadow::get_type(),
                [
                        'name'     => 'summary_box_shadow',
                        'selector' => '{{WRAPPER}} .fct_checkout_summary, {{WRAPPER}} .fct_summary_box',
                ]
        );

        $this->add_responsive_control(
                'summary_box_padding',
                [
                        'label'      => esc_html__('Padding', 'fluent-cart'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_checkout_summary, {{WRAPPER}} .fct_summary_box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Summary Items Style Controls
     */
    private function registerSummaryItemsStyleControls()
    {
        $this->start_controls_section(
                'summary_items_style_section',
                [
                        'label' => esc_html__('Summary Items', 'fluent-cart'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'summary_label_typography',
                        'label'    => esc_html__('Label Typography', 'fluent-cart'),
                        'selector' => '{{WRAPPER}} .fct_summary_items_list li .fct_summary_label',
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'summary_value_typography',
                        'label'    => esc_html__('Value Typography', 'fluent-cart'),
                        'selector' => '{{WRAPPER}} .fct_summary_items_list li .fct_summary_value',
                ]
        );

        $this->add_control(
                'summary_label_color',
                [
                        'label'     => esc_html__('Label Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_summary_items_list li .fct_summary_label' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'summary_value_color',
                [
                        'label'     => esc_html__('Value Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_summary_items_list li .fct_summary_value' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'summary_separator_style',
                [
                        'label'     => esc_html__('Separator Style', 'fluent-cart'),
                        'type'      => Controls_Manager::SELECT,
                        'default'   => 'solid',
                        'options'   => [
                                'none'   => esc_html__('None', 'fluent-cart'),
                                'solid'  => esc_html__('Solid', 'fluent-cart'),
                                'dashed' => esc_html__('Dashed', 'fluent-cart'),
                                'dotted' => esc_html__('Dotted', 'fluent-cart'),
                        ],
                        'selectors' => [
                                '{{WRAPPER}} .fct_summary_items_list li' => 'border-bottom-style: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'summary_separator_color',
                [
                        'label'     => esc_html__('Separator Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_summary_items_list li' => 'border-bottom-color: {{VALUE}};',
                        ],
                        'condition' => [
                                'summary_separator_style!' => 'none',
                        ],
                ]
        );

        $this->add_responsive_control(
                'summary_row_padding',
                [
                        'label'      => esc_html__('Row Padding', 'fluent-cart'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_summary_items_list li' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        // Total Row Special Styling
        $this->add_control(
                'total_row_heading',
                [
                        'label'     => esc_html__('Total Row', 'fluent-cart'),
                        'type'      => Controls_Manager::HEADING,
                        'separator' => 'before',
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'total_typography',
                        'selector' => '{{WRAPPER}} .fct_summary_items_total',
                ]
        );

        $this->add_control(
                'total_color',
                [
                        'label'     => esc_html__('Total Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_summary_items_list li.fct_summary_items_total .fct_summary_label, {{WRAPPER}} .fct_summary_items_list li.fct_summary_items_total .fct_summary_value' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'total_bg_color',
                [
                        'label'     => esc_html__('Total Background', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_summary_items_total' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Coupon Field Style Controls
     */
    private function registerCouponFieldStyleControls()
    {
        $this->start_controls_section(
                'coupon_field_style_section',
                [
                        'label' => esc_html__('Coupon Field', 'fluent-cart'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_control(
                'coupon_toggle_color',
                [
                        'label'     => esc_html__('Toggle Link Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_coupon_toggle' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'coupon_apply_heading',
                [
                        'label'     => esc_html__('Apply Button', 'fluent-cart'),
                        'type'      => Controls_Manager::HEADING,
                        'separator' => 'before',
                ]
        );

        $this->start_controls_tabs('coupon_button_style_tabs');

        $this->start_controls_tab(
                'coupon_button_normal_tab',
                ['label' => esc_html__('Normal', 'fluent-cart')]
        );

        $this->add_control(
                'coupon_button_text_color',
                [
                        'label'     => esc_html__('Text Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_coupon_apply_btn' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'coupon_button_bg_color',
                [
                        'label'     => esc_html__('Background', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_coupon_apply_btn' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
                'coupon_button_hover_tab',
                ['label' => esc_html__('Hover', 'fluent-cart')]
        );

        $this->add_control(
                'coupon_button_hover_text_color',
                [
                        'label'     => esc_html__('Text Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_coupon_apply_btn:hover' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'coupon_button_hover_bg_color',
                [
                        'label'     => esc_html__('Background', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_coupon_apply_btn:hover' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
                'coupon_messages_heading',
                [
                        'label'     => esc_html__('Messages', 'fluent-cart'),
                        'type'      => Controls_Manager::HEADING,
                        'separator' => 'before',
                ]
        );

        $this->add_control(
                'coupon_success_color',
                [
                        'label'     => esc_html__('Success Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_coupon_success' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'coupon_error_color',
                [
                        'label'     => esc_html__('Error Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_coupon_error' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Payment Methods Style Controls
     */
    private function registerPaymentMethodsStyleControls()
    {
        $this->start_controls_section(
                'payment_methods_style_section',
                [
                        'label' => esc_html__('Payment Methods', 'fluent-cart'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_control(
                'payment_method_bg_color',
                [
                        'label'     => esc_html__('Background Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_payment_method_item' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'payment_method_selected_bg_color',
                [
                        'label'     => esc_html__('Selected Background', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_payment_method_item.is-selected' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                        'name'     => 'payment_method_border',
                        'selector' => '{{WRAPPER}} .fct_payment_method_item',
                ]
        );

        $this->add_control(
                'payment_method_selected_border_color',
                [
                        'label'     => esc_html__('Selected Border Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_payment_method_item.is-selected' => 'border-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'payment_method_border_radius',
                [
                        'label'      => esc_html__('Border Radius', 'fluent-cart'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', '%'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_payment_method_item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'payment_method_padding',
                [
                        'label'      => esc_html__('Padding', 'fluent-cart'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_payment_method_item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'payment_method_spacing',
                [
                        'label'      => esc_html__('Spacing', 'fluent-cart'),
                        'type'       => Controls_Manager::SLIDER,
                        'size_units' => ['px'],
                        'range'      => [
                                'px' => ['min' => 0, 'max' => 30],
                        ],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_payment_method_item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'payment_method_title_typography',
                        'label'    => esc_html__('Title Typography', 'fluent-cart'),
                        'selector' => '{{WRAPPER}} .fct_payment_method_title',
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'payment_method_desc_typography',
                        'label'    => esc_html__('Description Typography', 'fluent-cart'),
                        'selector' => '{{WRAPPER}} .fct_payment_method_description',
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Address Fields Style Controls
     */
    private function registerAddressFieldsStyleControls()
    {
        $this->start_controls_section(
                'address_fields_style_section',
                [
                        'label' => esc_html__('Address Fields', 'fluent-cart'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                        'name'     => 'address_group_border',
                        'label'    => esc_html__('Group Border', 'fluent-cart'),
                        'selector' => '{{WRAPPER}} .fct_address_group',
                ]
        );

        $this->add_control(
                'address_group_border_radius',
                [
                        'label'      => esc_html__('Group Border Radius', 'fluent-cart'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', '%'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_address_group' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'address_group_padding',
                [
                        'label'      => esc_html__('Group Padding', 'fluent-cart'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_address_group' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'address_title_typography',
                        'label'    => esc_html__('Address Title Typography', 'fluent-cart'),
                        'selector' => '{{WRAPPER}} .fct_address_title',
                ]
        );

        $this->add_control(
                'address_title_color',
                [
                        'label'     => esc_html__('Address Title Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_address_title' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Error/Validation Style Controls
     */
    private function registerErrorValidationStyleControls()
    {
        $this->start_controls_section(
                'error_validation_style_section',
                [
                        'label' => esc_html__('Error/Validation', 'fluent-cart'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_control(
                'error_message_color',
                [
                        'label'     => esc_html__('Error Message Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'default'   => '#dc3545',
                        'selectors' => [
                                '{{WRAPPER}} .fct_error_message, {{WRAPPER}} .fct_field_error' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'error_border_color',
                [
                        'label'     => esc_html__('Error Border Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'default'   => '#dc3545',
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout input.has-error, {{WRAPPER}} .fct_checkout select.has-error' => 'border-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'success_message_color',
                [
                        'label'     => esc_html__('Success Message Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'default'   => '#28a745',
                        'selectors' => [
                                '{{WRAPPER}} .fct_success_message' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'required_indicator_color',
                [
                        'label'     => esc_html__('Required Indicator Color', 'fluent-cart'),
                        'type'      => Controls_Manager::COLOR,
                        'default'   => '#dc3545',
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout label .required' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'error_message_typography',
                        'label'    => esc_html__('Error Message Typography', 'fluent-cart'),
                        'selector' => '{{WRAPPER}} .fct_error_message, {{WRAPPER}} .fct_field_error',
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Render the widget output on the frontend
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $isEditor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        // Load checkout styles (works even without cart for editor preview)
        $this->loadCheckoutStyles();

        if ($isEditor) {
            $renderer = new DummyCheckoutRenderer($settings);
        } else {
            // Also try loading full checkout assets for frontend (includes JS)
            AssetLoader::loadCheckoutAssets();
            $renderer = new ElementorCheckoutRenderer($settings);
        }

        echo $renderer->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}