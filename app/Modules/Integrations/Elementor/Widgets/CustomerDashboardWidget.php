<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use FluentCart\App\Modules\Templating\AssetLoader;

/**
 * Customer Dashboard widget — renders FluentCart core's
 * [fluent_cart_customer_profile]: the logged-in customer's account area
 * (dashboard, purchase history, subscriptions, licenses, downloads, profile),
 * and the logged-out login prompt otherwise. A thin wrapper like the Divi
 * Customer Dashboard module: all markup/styling/behaviour come from core.
 *
 * This is the full account area — distinct from the existing Customer
 * Dashboard *Button* widget, which only renders a link to it.
 */
class CustomerDashboardWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_customer_dashboard';
    }

    public function get_title()
    {
        return esc_html__('Customer Dashboard', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-lock-user fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['dashboard', 'account', 'customer', 'profile', 'orders', 'commerce', 'fluent'];
    }

    public function get_style_depends()
    {
        // Enqueue the customer dashboard CSS/JS so the widget renders correctly
        // in the editor preview iframe (on the front end the shortcode enqueues
        // them).
        AssetLoader::loadCustomerDashboardAssets();

        return [];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_dashboard',
            [
                'label' => esc_html__('Customer Dashboard', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'dashboard_info',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('Renders the FluentCart customer account area — dashboard, orders, subscriptions, downloads and profile — for the logged-in customer, or a login prompt otherwise. Styling and behaviour come from FluentCart core.', 'fluent-cart'),
                'content_classes' => 'elementor-descriptor',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        // Same delegation as the [fluent_cart_customer_profile] shortcode / the
        // Divi Customer Dashboard module — core renders the account area or the
        // logged-out login prompt.
        echo do_shortcode('[fluent_cart_customer_profile]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
