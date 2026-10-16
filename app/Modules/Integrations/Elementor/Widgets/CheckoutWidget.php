<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Repeater;
use FluentCart\App\App;
use FluentCart\App\Models\Cart;
use FluentCart\App\Services\Renderer\CheckoutRenderer;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls\ProductVariationSelectControl;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\InlineCheckoutPreview;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProFeature;
use FluentCartElementorBlocks\App\Utils\Enqueuer\Enqueue;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers\ElementorCheckoutRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers\DummyCheckoutRenderer;

class CheckoutWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_checkout';
    }

    public function get_title()
    {
        return esc_html__('Checkout', 'fluent-cart-elementor-blocks');
    }

    public function get_icon()
    {
        return 'eicon-checkout fluent-cart-widget-icon';
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

        $styles = [
            // Checkout styles
                'fce-checkout-page-css',
                'fce-checkout-select-css',
            // Cart base styles
                $slug . '-fluentcart-drawer',
                $slug . '-global-styles',
                $slug . '-fluentcart-toastify-notify-js',
        ];

        // Elementor prints only the handles listed here in the editor preview, so a style
        // enqueued from render() never reaches it.
        if (self::hasInlineCheckoutStyles()) {
            $styles[] = 'fct-inline-checkout-items';
        } elseif (self::isEditorPreview()) {
            // Without Pro's stylesheet the sample rows are unstyled, so this add-on supplies its own.
            Enqueue::staticStyle('fce-inline-preview-css', 'css/inline-checkout-preview.css', [], FLUENTCART_ELEMENTOR_BLOCKS_VERSION);
            $styles[] = 'fce-inline-preview-css';
        }

        return $styles;
    }

    /**
     * Load checkout styles directly using FluentCart core's Vite
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

        // Use FluentCart core's Vite since these SCSS files live in the core plugin
        \FluentCart\App\Vite::enqueueStyle(
                'fce-checkout-page-css',
                'public/checkout/style/checkout.scss'
        );

        \FluentCart\App\Vite::enqueueStyle(
                'fce-checkout-select-css',
                'public/components/select/style/style.scss'
        );

        if (self::hasInlineCheckoutStyles()) {
            \FluentCartPro\App\Utils\Enqueuer\Vite::enqueueStyle(
                'fct-inline-checkout-items',
                'public/inline-checkout/style/inline-checkout-items.scss'
            );
        }

        \wp_enqueue_style(
            'fce-elementor-css',
            FLUENTCART_ELEMENTOR_BLOCKS_URL . 'assets/css/elementor.css',
            [],
            FLUENTCART_ELEMENTOR_BLOCKS_VERSION
        );
    }

    private static function isEditorPreview(): bool
    {
        return \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode();
    }

    private static function hasInlineCheckoutStyles(): bool
    {
        return class_exists('\FluentCartPro\App\Utils\Enqueuer\Vite');
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
        $this->registerInlineCheckoutControls();
        $this->registerFormFieldsControls();
        $this->registerSummaryControls();
        $this->registerLayoutControls();
        $this->registerFormFieldStyleControls();
        $this->registerSectionHeadingStyleControls();
        $this->registerSubmitButtonStyleControls();
        $this->registerSummaryBoxStyleControls();
        $this->registerSummaryItemsStyleControls();
        $this->registerLineItemsStyleControls();
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
                        'label' => esc_html__('General Settings', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_CONTENT,
                        'condition' => ['inline_checkout_mode!' => 'custom'],
                ]
        );

        $this->add_control(
                'layout_type',
                [
                        'label'   => esc_html__('Layout', 'fluent-cart-elementor-blocks'),
                        'type'    => Controls_Manager::SELECT,
                        'default' => 'two-column',
                        'options' => [
                                'one-column' => esc_html__('One Column', 'fluent-cart-elementor-blocks'),
                                'two-column' => esc_html__('Two Column', 'fluent-cart-elementor-blocks'),
                        ],
                ]
        );

        $this->add_responsive_control(
                'form_column_width',
                [
                        'label'      => esc_html__('Form Column Width (%)', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::SLIDER,
                        'size_units' => ['%'],
                        'range'      => [
                                '%' => ['min' => 30, 'max' => 100],
                        ],
                        'default'        => ['size' => 65, 'unit' => '%'],
                        'mobile_default' => ['size' => 100, 'unit' => '%'],
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
                        'label'      => esc_html__('Summary Column Width (%)', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::SLIDER,
                        'size_units' => ['%'],
                        'range'      => [
                                '%' => ['min' => 20, 'max' => 100],
                        ],
                        'default'        => ['size' => 35, 'unit' => '%'],
                        'mobile_default' => ['size' => 100, 'unit' => '%'],
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
                        'label'      => esc_html__('Column Gap', 'fluent-cart-elementor-blocks'),
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
                        'label'        => esc_html__('Use Default FluentCart Styles', 'fluent-cart-elementor-blocks'),
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                        'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                        'return_value' => 'yes',
                        'default'      => 'yes',
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Products controls: inline checkout (FluentCart Pro)
     */
    private function registerInlineCheckoutControls()
    {
        $this->start_controls_section(
                'inline_checkout_section',
                [
                        'label' => esc_html__('Products', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_CONTENT,
                ]
        );

        // Keep saved settings available to section conditions while Pro controls are hidden.
        if (!App::isProActive()) {
            $this->registerProNoticeControls();
            $this->end_controls_section();

            return;
        }

        $this->add_control(
                'inline_checkout_mode',
                [
                        'label'       => esc_html__('Checkout Source', 'fluent-cart-elementor-blocks'),
                        'type'        => Controls_Manager::SELECT,
                        'default'     => 'cart',
                        'options'     => [
                                'cart'   => esc_html__('Default Cart', 'fluent-cart-elementor-blocks'),
                                'custom' => esc_html__('Custom Product', 'fluent-cart-elementor-blocks'),
                        ],
                        'description' => esc_html__('"Default Cart" checks out the shopper\'s cart. "Custom Product" lets them pick from products you choose.', 'fluent-cart-elementor-blocks'),
                ]
        );

        $this->add_control(
                'inline_core_layout_notice',
                [
                        'type'            => Controls_Manager::RAW_HTML,
                        'raw'             => esc_html__('The live Custom Product checkout uses FluentCart\'s own layout, so this preview is only an approximation.', 'fluent-cart-elementor-blocks'),
                        'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                        'condition'       => ['inline_checkout_mode' => 'custom'],
                ]
        );

        $this->add_control(
                'inline_variant_ids',
                [
                        'label'       => esc_html__('Products', 'fluent-cart-elementor-blocks'),
                        'type'        => (new ProductVariationSelectControl())->get_type(),
                        'multiple'    => true,
                        'label_block' => true,
                        'default'     => [],
                        'placeholder' => esc_html__('Search for a variation...', 'fluent-cart-elementor-blocks'),
                        'condition'   => ['inline_checkout_mode' => 'custom'],
                ]
        );

        $this->add_control(
                'inline_selection_mode',
                [
                        'label'       => esc_html__('Selection Mode', 'fluent-cart-elementor-blocks'),
                        'type'        => Controls_Manager::SELECT,
                        'default'     => 'single',
                        'options'     => [
                                'single'   => esc_html__('Select one', 'fluent-cart-elementor-blocks'),
                                'multiple' => esc_html__('Select many', 'fluent-cart-elementor-blocks'),
                        ],
                        'description' => esc_html__('"Select one" shows radio buttons, "Select many" shows checkboxes.', 'fluent-cart-elementor-blocks'),
                        'condition'   => ['inline_checkout_mode' => 'custom'],
                ]
        );

        $this->add_control(
                'inline_max_items',
                [
                        'label'       => esc_html__('Maximum Selectable Items', 'fluent-cart-elementor-blocks'),
                        'type'        => Controls_Manager::NUMBER,
                        'default'     => 5,
                        'min'         => 1,
                        'max'         => 20,
                        'step'        => 1,
                        'description' => esc_html__('How many of the products above a shopper may tick at once. Every product you added is always shown.', 'fluent-cart-elementor-blocks'),
                        'condition'   => [
                                'inline_checkout_mode'  => 'custom',
                                'inline_selection_mode' => 'multiple',
                        ],
                ]
        );

        $this->add_control(
                'inline_selector_label',
                [
                        'label'       => esc_html__('Selector Heading', 'fluent-cart-elementor-blocks'),
                        'type'        => Controls_Manager::TEXT,
                        'placeholder' => esc_html__('Choose your plan', 'fluent-cart-elementor-blocks'),
                        'description' => esc_html__('Heading shown above the selectable list.', 'fluent-cart-elementor-blocks'),
                        'condition'   => ['inline_checkout_mode' => 'custom'],
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Hidden defaults keep Elementor's section conditions working without Pro,
     * while retaining saved custom checkout settings.
     */
    private function registerProNoticeControls()
    {
        $this->add_control(
                'inline_checkout_mode',
                [
                        'type'    => Controls_Manager::HIDDEN,
                        'default' => 'cart',
                ]
        );

        $this->add_control(
                'inline_variant_ids',
                [
                        'type'    => Controls_Manager::HIDDEN,
                        'default' => [],
                ]
        );

        $note = __('Let shoppers choose from products you pick, right here on the checkout — no product page visit needed. Available in FluentCart Pro.', 'fluent-cart-elementor-blocks');

        $this->add_control(
                'inline_pro_notice',
                [
                        'type'       => Controls_Manager::RAW_HTML,
                        'raw'        => ProFeature::panelNotice($note),
                        'conditions' => ['terms' => [['name' => 'inline_checkout_mode', 'operator' => '!=', 'value' => 'custom']]],
                ]
        );

        $this->add_control(
                'inline_pro_notice_kept',
                [
                        'type'       => Controls_Manager::RAW_HTML,
                        'raw'        => ProFeature::panelNotice($note, __('With custom products configured, this checkout displays nothing on the live page while FluentCart Pro is inactive. Reactivate FluentCart Pro to restore checkout. Your custom-product settings are kept.', 'fluent-cart-elementor-blocks')),
                        'conditions' => ['terms' => [['name' => 'inline_checkout_mode', 'operator' => '==', 'value' => 'custom']]],
                ]
        );
    }

    /**
     * Form Fields Repeater Controls
     */
    private function registerFormFieldsControls()
    {
        $this->start_controls_section(
                'form_fields_section',
                [
                        'label' => esc_html__('Form Fields', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_CONTENT,
                        'condition' => ['inline_checkout_mode!' => 'custom'],
                ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
                'element_type',
                [
                        'label'   => esc_html__('Section', 'fluent-cart-elementor-blocks'),
                        'type'    => Controls_Manager::SELECT,
                        'default' => 'name_fields',
                        'options' => [
                                'name_fields'      => esc_html__('Name Fields', 'fluent-cart-elementor-blocks'),
                                'create_account'   => esc_html__('Create Account', 'fluent-cart-elementor-blocks'),
                                'address_fields'   => esc_html__('Address Fields', 'fluent-cart-elementor-blocks'),
                                'shipping_methods'  => esc_html__('Shipping Methods', 'fluent-cart-elementor-blocks'),
                                'business_details' => esc_html__('Business Details', 'fluent-cart-elementor-blocks'),
                                'payment_methods'  => esc_html__('Payment Methods', 'fluent-cart-elementor-blocks'),
                                'agree_terms'      => esc_html__('Agree to Terms', 'fluent-cart-elementor-blocks'),
                                'order_notes'      => esc_html__('Order Notes', 'fluent-cart-elementor-blocks'),
                                'submit_button'    => esc_html__('Submit Button', 'fluent-cart-elementor-blocks'),
                        ],
                ]
        );

        $repeater->add_control(
                'element_visibility',
                [
                        'label'        => esc_html__('Visible', 'fluent-cart-elementor-blocks'),
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                        'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                        'return_value' => 'yes',
                        'default'      => 'yes',
                ]
        );

        // Address Fields specific controls
        $repeater->add_control(
                'address_type',
                [
                        'label'     => esc_html__('Address Display', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::SELECT,
                        'default'   => 'both',
                        'options'   => [
                                'both'     => esc_html__('Billing + Shipping', 'fluent-cart-elementor-blocks'),
                                'billing'  => esc_html__('Billing Only', 'fluent-cart-elementor-blocks'),
                                'shipping' => esc_html__('Shipping Only', 'fluent-cart-elementor-blocks'),
                        ],
                        'condition' => [
                                'element_type' => 'address_fields',
                        ],
                ]
        );

        $repeater->add_control(
                'show_ship_to_different',
                [
                        'label'        => esc_html__('Show "Ship to Different Address"', 'fluent-cart-elementor-blocks'),
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                        'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
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
                        'label'       => esc_html__('Custom Section Heading', 'fluent-cart-elementor-blocks'),
                        'type'        => Controls_Manager::TEXT,
                        'placeholder' => esc_html__('Leave empty for default', 'fluent-cart-elementor-blocks'),
                ]
        );

        $this->add_control(
                'form_elements',
                [
                        'label'       => esc_html__('Form Sections', 'fluent-cart-elementor-blocks'),
                        'type'        => Controls_Manager::REPEATER,
                        'fields'      => $repeater->get_controls(),
                        'default'     => [
                                ['element_type' => 'name_fields', 'element_visibility' => 'yes'],
                                ['element_type' => 'create_account', 'element_visibility' => 'yes'],
                                ['element_type' => 'address_fields', 'element_visibility' => 'yes', 'address_type' => 'both', 'show_ship_to_different' => 'yes'],
                                ['element_type' => 'agree_terms', 'element_visibility' => 'yes'],
                                ['element_type' => 'shipping_methods', 'element_visibility' => 'yes'],
                                ['element_type' => 'business_details', 'element_visibility' => 'yes'],
                                ['element_type' => 'payment_methods', 'element_visibility' => 'yes'],
                                ['element_type' => 'submit_button', 'element_visibility' => 'yes'],
                        ],
                        'title_field' => '{{{ {"name_fields":"Name Fields","create_account":"Create Account","address_fields":"Address Fields","shipping_methods":"Shipping Methods","business_details":"Business Details","payment_methods":"Payment Methods","agree_terms":"Agree to Terms","order_notes":"Order Notes","submit_button":"Submit Button"}[element_type] || element_type }}}',
                ]
        );

        $this->add_control(
                'field_label_style',
                [
                        'label'       => esc_html__('Field Labels', 'fluent-cart-elementor-blocks'),
                        'type'        => Controls_Manager::SELECT,
                        'default'     => 'docked',
                        'options'     => [
                                'docked'      => esc_html__('Docked (name on the right)', 'fluent-cart-elementor-blocks'),
                                'floating'    => esc_html__('Floating (name above the value)', 'fluent-cart-elementor-blocks'),
                                'placeholder' => esc_html__('Placeholder only', 'fluent-cart-elementor-blocks'),
                        ],
                        'description' => esc_html__('How checkout fields show their name once the customer has typed in them.', 'fluent-cart-elementor-blocks'),
                        'label_block' => true,
                        'separator'   => 'before',
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
                        'label' => esc_html__('Order Summary', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_CONTENT,
                        'condition' => ['inline_checkout_mode!' => 'custom'],
                ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
                'element_type',
                [
                        'label'   => esc_html__('Section', 'fluent-cart-elementor-blocks'),
                        'type'    => Controls_Manager::SELECT,
                        'default' => 'order_summary',
                        'options' => [
                                'order_summary'   => esc_html__('Order Summary (Items)', 'fluent-cart-elementor-blocks'),
                                'subtotal'        => esc_html__('Subtotal', 'fluent-cart-elementor-blocks'),
                                'shipping'        => esc_html__('Shipping', 'fluent-cart-elementor-blocks'),
                                'coupon'          => esc_html__('Coupon Field', 'fluent-cart-elementor-blocks'),
                                'manual_discount' => esc_html__('Manual Discount', 'fluent-cart-elementor-blocks'),
                                'tax'             => esc_html__('Tax', 'fluent-cart-elementor-blocks'),
                                'shipping_tax'    => esc_html__('Shipping Tax', 'fluent-cart-elementor-blocks'),
                                'total'           => esc_html__('Total', 'fluent-cart-elementor-blocks'),
                                'order_bump'      => esc_html__('Order Bump (Pro)', 'fluent-cart-elementor-blocks'),
                        ],
                ]
        );

        $repeater->add_control(
                'element_visibility',
                [
                        'label'        => esc_html__('Visible', 'fluent-cart-elementor-blocks'),
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                        'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                        'return_value' => 'yes',
                        'default'      => 'yes',
                ]
        );

        // Coupon specific controls
        $repeater->add_control(
                'coupon_collapsible',
                [
                        'label'        => esc_html__('Collapsible', 'fluent-cart-elementor-blocks'),
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                        'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
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
                        'label'     => esc_html__('Coupon Label', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::TEXT,
                        'default'   => esc_html__('Have a Coupon?', 'fluent-cart-elementor-blocks'),
                        'condition' => [
                                'element_type' => 'coupon',
                        ],
                ]
        );

        $this->add_control(
                'summary_elements',
                [
                        'label'       => esc_html__('Summary Sections', 'fluent-cart-elementor-blocks'),
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
                        'label'   => esc_html__('Summary Heading', 'fluent-cart-elementor-blocks'),
                        'type'    => Controls_Manager::TEXT,
                        'default' => esc_html__('Order Summary', 'fluent-cart-elementor-blocks'),
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
                        'label' => esc_html__('Layout Options', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_CONTENT,
                        'condition' => ['inline_checkout_mode!' => 'custom'],
                ]
        );

        $this->add_control(
                'sticky_summary',
                [
                        'label'        => esc_html__('Sticky Summary', 'fluent-cart-elementor-blocks'),
                        'type'         => Controls_Manager::SWITCHER,
                        'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                        'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
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
                        'label'      => esc_html__('Sticky Offset', 'fluent-cart-elementor-blocks'),
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
                        'label'   => esc_html__('Empty Cart Message', 'fluent-cart-elementor-blocks'),
                        'type'    => Controls_Manager::TEXT,
                        'default' => esc_html__('Your cart is empty.', 'fluent-cart-elementor-blocks'),
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
                        'label' => esc_html__('Form Fields', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'input_typography',
                        'label'    => esc_html__('Input Typography', 'fluent-cart-elementor-blocks'),
                        'selector' => '{{WRAPPER}} .fct_checkout input, {{WRAPPER}} .fct_checkout select, {{WRAPPER}} .fct_checkout textarea',
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'label_typography',
                        'label'    => esc_html__('Label Typography', 'fluent-cart-elementor-blocks'),
                        'selector' => '{{WRAPPER}} .fct_checkout label, {{WRAPPER}} .fct_checkout .fct_input_label',
                ]
        );

        $this->add_control(
                'label_color',
                [
                        'label'     => esc_html__('Label Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout label, {{WRAPPER}} .fct_checkout .fct_input_label' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->start_controls_tabs('input_style_tabs');

        // Normal State
        $this->start_controls_tab(
                'input_normal_tab',
                ['label' => esc_html__('Normal', 'fluent-cart-elementor-blocks')]
        );

        $this->add_control(
                'input_bg_color',
                [
                        'label'     => esc_html__('Background Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout input, {{WRAPPER}} .fct_checkout select, {{WRAPPER}} .fct_checkout textarea' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'input_text_color',
                [
                        'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout input, {{WRAPPER}} .fct_checkout select, {{WRAPPER}} .fct_checkout textarea' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'input_placeholder_color',
                [
                        'label'     => esc_html__('Placeholder Color', 'fluent-cart-elementor-blocks'),
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
                ['label' => esc_html__('Focus', 'fluent-cart-elementor-blocks')]
        );

        $this->add_control(
                'input_focus_bg_color',
                [
                        'label'     => esc_html__('Background Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout input:focus, {{WRAPPER}} .fct_checkout select:focus, {{WRAPPER}} .fct_checkout textarea:focus' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'input_focus_border_color',
                [
                        'label'     => esc_html__('Border Color', 'fluent-cart-elementor-blocks'),
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
                        'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
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
                        'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
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
                        'label'      => esc_html__('Input Height', 'fluent-cart-elementor-blocks'),
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
                        'label'      => esc_html__('Field Spacing', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::SLIDER,
                        'size_units' => ['px', 'em'],
                        'range'      => [
                                'px' => ['min' => 0, 'max' => 50],
                        ],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_checkout .fct_input_wrapper' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_control(
                'transition_duration',
                [
                        'label'     => esc_html__('Transition Duration (ms)', 'fluent-cart-elementor-blocks'),
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
                        'label' => esc_html__('Section Headings', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $headingTextSelector = '{{WRAPPER}} .fct_checkout .fct_form_section_header_label, {{WRAPPER}} .fct_checkout .fct_form_section_header h3, {{WRAPPER}} .fct_checkout .fct_form_section_header h4';

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'section_heading_typography',
                        'selector' => $headingTextSelector,
                ]
        );

        $this->add_control(
                'section_heading_color',
                [
                        'label'     => esc_html__('Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                $headingTextSelector => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'section_heading_bg_color',
                [
                        'label'     => esc_html__('Background Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout .fct_form_section_header' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'section_heading_padding',
                [
                        'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_checkout .fct_form_section_header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'section_heading_margin',
                [
                        'label'      => esc_html__('Margin', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_checkout .fct_form_section_header' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                        'name'     => 'section_heading_border',
                        'selector' => '{{WRAPPER}} .fct_checkout .fct_form_section_header',
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
                        'label' => esc_html__('Submit Button', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $btnSelector = '{{WRAPPER}} .fct_checkout .fct_place_order_btn, {{WRAPPER}} .fct_checkout .fct_place_order_btn_wrap button[type="submit"]';
        $btnHoverSelector = '{{WRAPPER}} .fct_checkout .fct_place_order_btn:hover, {{WRAPPER}} .fct_checkout .fct_place_order_btn_wrap button[type="submit"]:hover';

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'submit_button_typography',
                        'selector' => $btnSelector,
                ]
        );

        $this->add_control(
                'submit_button_width',
                [
                        'label'                => esc_html__('Button Width', 'fluent-cart-elementor-blocks'),
                        'type'                 => Controls_Manager::SELECT,
                        'default'              => 'full',
                        'options'              => [
                                'auto' => esc_html__('Auto', 'fluent-cart-elementor-blocks'),
                                'full' => esc_html__('Full Width', 'fluent-cart-elementor-blocks'),
                        ],
                        'selectors_dictionary' => [
                                'auto' => 'auto',
                                'full' => '100%',
                        ],
                        'selectors'            => [
                                $btnSelector => 'width: {{VALUE}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'submit_button_alignment',
                [
                        'label'     => esc_html__('Alignment', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::CHOOSE,
                        'options'   => [
                                'left'   => ['title' => esc_html__('Left', 'fluent-cart-elementor-blocks'), 'icon' => 'eicon-text-align-left'],
                                'center' => ['title' => esc_html__('Center', 'fluent-cart-elementor-blocks'), 'icon' => 'eicon-text-align-center'],
                                'right'  => ['title' => esc_html__('Right', 'fluent-cart-elementor-blocks'), 'icon' => 'eicon-text-align-right'],
                        ],
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout .fct_place_order_btn_wrap' => 'text-align: {{VALUE}};',
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
                ['label' => esc_html__('Normal', 'fluent-cart-elementor-blocks')]
        );

        $this->add_control(
                'submit_button_text_color',
                [
                        'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                $btnSelector => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Background::get_type(),
                [
                        'name'     => 'submit_button_background',
                        'types'    => ['classic', 'gradient'],
                        'selector' => $btnSelector,
                ]
        );

        $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                        'name'     => 'submit_button_border',
                        'selector' => $btnSelector,
                ]
        );

        $this->add_group_control(
                Group_Control_Box_Shadow::get_type(),
                [
                        'name'     => 'submit_button_shadow',
                        'selector' => $btnSelector,
                ]
        );

        $this->end_controls_tab();

        // Hover State
        $this->start_controls_tab(
                'submit_button_hover_tab',
                ['label' => esc_html__('Hover', 'fluent-cart-elementor-blocks')]
        );

        $this->add_control(
                'submit_button_hover_text_color',
                [
                        'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                $btnHoverSelector => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Background::get_type(),
                [
                        'name'     => 'submit_button_hover_background',
                        'types'    => ['classic', 'gradient'],
                        'selector' => $btnHoverSelector,
                ]
        );

        $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                        'name'     => 'submit_button_hover_border',
                        'selector' => $btnHoverSelector,
                ]
        );

        $this->add_group_control(
                Group_Control_Box_Shadow::get_type(),
                [
                        'name'     => 'submit_button_hover_shadow',
                        'selector' => $btnHoverSelector,
                ]
        );

        $this->add_control(
                'submit_button_hover_animation',
                [
                        'label'   => esc_html__('Hover Animation', 'fluent-cart-elementor-blocks'),
                        'type'    => Controls_Manager::SELECT,
                        'default' => 'none',
                        'options' => [
                                'none'       => esc_html__('None', 'fluent-cart-elementor-blocks'),
                                'scale'      => esc_html__('Scale Up', 'fluent-cart-elementor-blocks'),
                                'scale-down' => esc_html__('Scale Down', 'fluent-cart-elementor-blocks'),
                                'lift'       => esc_html__('Lift', 'fluent-cart-elementor-blocks'),
                        ],
                ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
                'submit_button_border_radius',
                [
                        'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', '%'],
                        'separator'  => 'before',
                        'selectors'  => [
                                $btnSelector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'submit_button_padding',
                [
                        'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                $btnSelector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_control(
                'submit_button_transition',
                [
                        'label'     => esc_html__('Transition Duration (ms)', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::NUMBER,
                        'default'   => 300,
                        'selectors' => [
                                $btnSelector => 'transition: all {{VALUE}}ms ease;',
                        ],
                ]
        );

        // Loading State
        $this->add_control(
                'loading_state_heading',
                [
                        'label'     => esc_html__('Loading State', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::HEADING,
                        'separator' => 'before',
                ]
        );

        $this->add_control(
                'loading_opacity',
                [
                        'label'     => esc_html__('Disabled Opacity', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::SLIDER,
                        'range'     => [
                                'px' => ['min' => 0.1, 'max' => 1, 'step' => 0.1],
                        ],
                        'default'   => ['size' => 0.6],
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout .fct_place_order_btn:disabled, {{WRAPPER}} .fct_checkout .fct_place_order_btn_wrap button[type="submit"]:disabled' => 'opacity: {{SIZE}};',
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
                        'label' => esc_html__('Summary Box', 'fluent-cart-elementor-blocks'),
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
                        'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
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
                        'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
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
                        'label' => esc_html__('Summary Items', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'summary_label_typography',
                        'label'    => esc_html__('Label Typography', 'fluent-cart-elementor-blocks'),
                        'selector' => '{{WRAPPER}} .fct_summary_items_list li .fct_summary_label',
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'summary_value_typography',
                        'label'    => esc_html__('Value Typography', 'fluent-cart-elementor-blocks'),
                        'selector' => '{{WRAPPER}} .fct_summary_items_list li .fct_summary_value',
                ]
        );

        $this->add_control(
                'summary_label_color',
                [
                        'label'     => esc_html__('Label Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_summary_items_list li .fct_summary_label' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'summary_value_color',
                [
                        'label'     => esc_html__('Value Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_summary_items_list li .fct_summary_value' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'summary_separator_style',
                [
                        'label'     => esc_html__('Separator Style', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::SELECT,
                        'default'   => 'none',
                        'options'   => [
                                'none'   => esc_html__('None', 'fluent-cart-elementor-blocks'),
                                'solid'  => esc_html__('Solid', 'fluent-cart-elementor-blocks'),
                                'dashed' => esc_html__('Dashed', 'fluent-cart-elementor-blocks'),
                                'dotted' => esc_html__('Dotted', 'fluent-cart-elementor-blocks'),
                        ],
                        'selectors' => [
                                '{{WRAPPER}} .fct_summary_items_list li' => 'border-bottom-style: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'summary_separator_width',
                [
                        'label'      => esc_html__('Separator Width', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::SLIDER,
                        'size_units' => ['px'],
                        'range'      => [
                                'px' => ['min' => 0, 'max' => 10],
                        ],
                        'default'    => ['size' => 1, 'unit' => 'px'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_summary_items_list li' => 'border-bottom-width: {{SIZE}}{{UNIT}};',
                        ],
                        'condition'  => [
                                'summary_separator_style!' => 'none',
                        ],
                ]
        );

        $this->add_control(
                'summary_separator_color',
                [
                        'label'     => esc_html__('Separator Color', 'fluent-cart-elementor-blocks'),
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
                        'label'      => esc_html__('Row Padding', 'fluent-cart-elementor-blocks'),
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
                        'label'     => esc_html__('Total Row', 'fluent-cart-elementor-blocks'),
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
                        'label'     => esc_html__('Total Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_summary_items_list li.fct_summary_items_total .fct_summary_label, {{WRAPPER}} .fct_summary_items_list li.fct_summary_items_total .fct_summary_value' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'total_bg_color',
                [
                        'label'     => esc_html__('Total Background', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_summary_items_total' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Line Items Style Controls (product cards in order summary)
     */
    private function registerLineItemsStyleControls()
    {
        $this->start_controls_section(
                'line_items_style_section',
                [
                        'label' => esc_html__('Line Items', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                        'condition' => ['inline_checkout_mode!' => 'custom'],
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'line_item_title_typography',
                        'label'    => esc_html__('Title Typography', 'fluent-cart-elementor-blocks'),
                        'selector' => '{{WRAPPER}} .fct_item_title, {{WRAPPER}} .fct_item_title a',
                ]
        );

        $this->add_control(
                'line_item_title_color',
                [
                        'label'     => esc_html__('Title Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_item_title, {{WRAPPER}} .fct_item_title a' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'line_item_price_typography',
                        'label'    => esc_html__('Price Typography', 'fluent-cart-elementor-blocks'),
                        'selector' => '{{WRAPPER}} .fct_line_item_price, {{WRAPPER}} .fct_line_item_total',
                ]
        );

        $this->add_control(
                'line_item_price_color',
                [
                        'label'     => esc_html__('Price Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_line_item_price, {{WRAPPER}} .fct_line_item_total' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'line_item_image_border_radius',
                [
                        'label'      => esc_html__('Image Border Radius', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', '%'],
                        'separator'  => 'before',
                        'selectors'  => [
                                '{{WRAPPER}} .fct_item_image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'line_item_spacing',
                [
                        'label'      => esc_html__('Item Spacing', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::SLIDER,
                        'size_units' => ['px'],
                        'range'      => [
                                'px' => ['min' => 0, 'max' => 30],
                        ],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_line_item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                        'name'     => 'line_item_border',
                        'label'    => esc_html__('Item Border', 'fluent-cart-elementor-blocks'),
                        'selector' => '{{WRAPPER}} .fct_line_item',
                ]
        );

        $this->add_responsive_control(
                'line_item_padding',
                [
                        'label'      => esc_html__('Item Padding', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_line_item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                        'label' => esc_html__('Coupon Field', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $couponBtnSelector = '{{WRAPPER}} .fct_coupon_field button[type="submit"]';
        $couponBtnHoverSelector = '{{WRAPPER}} .fct_coupon_field button[type="submit"]:hover';

        $this->add_control(
                'coupon_toggle_color',
                [
                        'label'     => esc_html__('Toggle Link Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_coupon_toggle, {{WRAPPER}} .fct_coupon_toggle a' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'coupon_apply_heading',
                [
                        'label'     => esc_html__('Apply Button', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::HEADING,
                        'separator' => 'before',
                ]
        );

        $this->start_controls_tabs('coupon_button_style_tabs');

        $this->start_controls_tab(
                'coupon_button_normal_tab',
                ['label' => esc_html__('Normal', 'fluent-cart-elementor-blocks')]
        );

        $this->add_control(
                'coupon_button_text_color',
                [
                        'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                $couponBtnSelector => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'coupon_button_bg_color',
                [
                        'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                $couponBtnSelector => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
                'coupon_button_hover_tab',
                ['label' => esc_html__('Hover', 'fluent-cart-elementor-blocks')]
        );

        $this->add_control(
                'coupon_button_hover_text_color',
                [
                        'label'     => esc_html__('Text Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                $couponBtnHoverSelector => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'coupon_button_hover_bg_color',
                [
                        'label'     => esc_html__('Background', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                $couponBtnHoverSelector => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_control(
                'coupon_messages_heading',
                [
                        'label'     => esc_html__('Messages', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::HEADING,
                        'separator' => 'before',
                ]
        );

        $this->add_control(
                'coupon_success_color',
                [
                        'label'     => esc_html__('Success Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_coupon_success' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'coupon_error_color',
                [
                        'label'     => esc_html__('Error Color', 'fluent-cart-elementor-blocks'),
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
                        'label' => esc_html__('Payment Methods', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_control(
                'payment_method_bg_color',
                [
                        'label'     => esc_html__('Background Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_payment_method_wrapper' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'payment_method_selected_bg_color',
                [
                        'label'     => esc_html__('Selected Background', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_payment_method_wrapper.active' => 'background-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                        'name'     => 'payment_method_border',
                        'selector' => '{{WRAPPER}} .fct_payment_method_wrapper',
                ]
        );

        $this->add_control(
                'payment_method_selected_border_color',
                [
                        'label'     => esc_html__('Selected Border Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_payment_method_wrapper.active' => 'border-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'payment_method_border_radius',
                [
                        'label'      => esc_html__('Border Radius', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', '%'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_payment_method_wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'payment_method_padding',
                [
                        'label'      => esc_html__('Padding', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_payment_method_wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'payment_method_spacing',
                [
                        'label'      => esc_html__('Spacing', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::SLIDER,
                        'size_units' => ['px'],
                        'range'      => [
                                'px' => ['min' => 0, 'max' => 30],
                        ],
                        'selectors'  => [
                                '{{WRAPPER}} .fct_payment_method_wrapper' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'payment_method_title_typography',
                        'label'    => esc_html__('Title Typography', 'fluent-cart-elementor-blocks'),
                        'selector' => '{{WRAPPER}} .fct_payment_method_wrapper label',
                ]
        );

        $this->add_control(
                'payment_method_title_color',
                [
                        'label'     => esc_html__('Title Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_payment_method_wrapper label' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'payment_method_desc_typography',
                        'label'    => esc_html__('Description Typography', 'fluent-cart-elementor-blocks'),
                        'selector' => '{{WRAPPER}} .fct_payment_method_instructions',
                ]
        );

        $this->add_control(
                'payment_method_desc_color',
                [
                        'label'     => esc_html__('Description Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                '{{WRAPPER}} .fct_payment_method_instructions' => 'color: {{VALUE}};',
                        ],
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
                        'label' => esc_html__('Address Fields', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $addressSectionSelector = '{{WRAPPER}} .fct_checkout_billing_and_shipping .fct_checkout_form_section';
        $addressTitleSelector = '{{WRAPPER}} .fct_checkout_billing_and_shipping .fct_form_section_header_label';

        $this->add_group_control(
                Group_Control_Border::get_type(),
                [
                        'name'     => 'address_group_border',
                        'label'    => esc_html__('Section Border', 'fluent-cart-elementor-blocks'),
                        'selector' => $addressSectionSelector,
                ]
        );

        $this->add_control(
                'address_group_border_radius',
                [
                        'label'      => esc_html__('Section Border Radius', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', '%'],
                        'selectors'  => [
                                $addressSectionSelector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_responsive_control(
                'address_group_padding',
                [
                        'label'      => esc_html__('Section Padding', 'fluent-cart-elementor-blocks'),
                        'type'       => Controls_Manager::DIMENSIONS,
                        'size_units' => ['px', 'em'],
                        'selectors'  => [
                                $addressSectionSelector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'address_title_typography',
                        'label'    => esc_html__('Address Title Typography', 'fluent-cart-elementor-blocks'),
                        'selector' => $addressTitleSelector,
                ]
        );

        $this->add_control(
                'address_title_color',
                [
                        'label'     => esc_html__('Address Title Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'selectors' => [
                                $addressTitleSelector => 'color: {{VALUE}};',
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
                        'label' => esc_html__('Error/Validation', 'fluent-cart-elementor-blocks'),
                        'tab'   => Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_control(
                'error_message_color',
                [
                        'label'     => esc_html__('Error Message Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'default'   => '#dc3545',
                        'selectors' => [
                                '{{WRAPPER}} .fct_form_error' => 'color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_control(
                'error_border_color',
                [
                        'label'     => esc_html__('Error Field Border Color', 'fluent-cart-elementor-blocks'),
                        'type'      => Controls_Manager::COLOR,
                        'default'   => '#dc3545',
                        'selectors' => [
                                '{{WRAPPER}} .fct_checkout .has-error input, {{WRAPPER}} .fct_checkout .has-error select, {{WRAPPER}} .fct_checkout .has-error textarea' => 'border-color: {{VALUE}};',
                        ],
                ]
        );

        $this->add_group_control(
                Group_Control_Typography::get_type(),
                [
                        'name'     => 'error_message_typography',
                        'label'    => esc_html__('Error Message Typography', 'fluent-cart-elementor-blocks'),
                        'selector' => '{{WRAPPER}} .fct_form_error',
                ]
        );

        $this->end_controls_section();
    }

    /**
     * Ensure older saved widgets get newly introduced required form sections.
     */
    /**
     * Map the Products controls onto the attribute names Pro's inline checkout reads.
     */
    private function getInlineCheckoutAttributes(array $settings): array
    {
        if (($settings['inline_checkout_mode'] ?? '') !== 'custom') {
            return ['checkout_mode' => 'cart'];
        }

        return [
                'checkout_mode'  => 'custom',
                'variant_ids'    => (array)($settings['inline_variant_ids'] ?? []),
                'selection_mode' => $settings['inline_selection_mode'] ?? 'single',
                'max_items'      => $settings['inline_max_items'] ?? 5,
                'selector_label' => $settings['inline_selector_label'] ?? '',
        ];
    }

    /**
     * A custom-product checkout is only ever rendered by the add-on that supplies its products,
     * so without Pro this renders nothing and never the shopper's own cart.
     */
    private function renderCustomProductCheckout(array $settings, array $attributes): string
    {
        // An older core has no custom-product routing, so nothing can supply the products.
        if (!method_exists(CheckoutRenderer::class, 'renderCustomProductCheckout')) {
            return '';
        }

        // Core treats a checkout with no products as an ordinary one, so show the empty state ourselves.
        if (!CheckoutRenderer::isCustomProductCheckout($attributes)) {
            return (new ElementorCheckoutRenderer($settings, $attributes, new Cart()))->render();
        }

        // The shortcode surface is the one that renders a full checkout without a WP_Block; the block surface needs inner blocks.
        return CheckoutRenderer::renderCustomProductCheckout([
            'surface'    => 'checkout-shortcode',
            'attributes' => $attributes,
            'block'      => null,
        ]);
    }

    private function normalizeCheckoutSettings(array $settings): array
    {
        $settings['form_elements'] = $this->normalizeFormElements($settings['form_elements'] ?? []);

        return $settings;
    }

    private function normalizeFormElements($formElements): array
    {
        if (!is_array($formElements)) {
            return [];
        }

        $hasBusinessDetails = false;

        foreach ($formElements as &$element) {
            if (($element['element_type'] ?? '') === 'eu_vat') {
                $element['element_type'] = 'business_details';
            }
            if (($element['element_type'] ?? '') === 'business_details') {
                $hasBusinessDetails = true;
            }
        }
        unset($element);

        if (!$hasBusinessDetails) {
            $insertAt = count($formElements);
            foreach ($formElements as $index => $element) {
                if (($element['element_type'] ?? '') === 'payment_methods') {
                    $insertAt = $index;
                    break;
                }
            }
            array_splice($formElements, $insertAt, 0, [[
                'element_type'       => 'business_details',
                'element_visibility' => 'yes',
            ]]);
        }

        return $formElements;
    }

    /**
     * Render the widget output on the frontend
     */
    protected function render()
    {
        $settings = $this->normalizeCheckoutSettings($this->get_settings_for_display());
        $isEditor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        // Load checkout styles (works even without cart for editor preview)
        $this->loadCheckoutStyles();

        if ($isEditor) {
            $inlineAttributes = $this->getInlineCheckoutAttributes($settings);

            if (($inlineAttributes['checkout_mode'] ?? '') === 'custom') {
                echo InlineCheckoutPreview::render($inlineAttributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                return;
            }

            $renderer = new DummyCheckoutRenderer($settings);
        } else {
            $inlineAttributes = $this->getInlineCheckoutAttributes($settings);

            if (($inlineAttributes['checkout_mode'] ?? '') === 'custom') {
                echo $this->renderCustomProductCheckout($settings, $inlineAttributes); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                return;
            }

            // Built first: the cart resolves here and the asset loader localizes it only once per request.
            $renderer = new ElementorCheckoutRenderer($settings, $inlineAttributes);
            AssetLoader::loadCheckoutAssets($renderer->getCart());
        }

        echo $renderer->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
