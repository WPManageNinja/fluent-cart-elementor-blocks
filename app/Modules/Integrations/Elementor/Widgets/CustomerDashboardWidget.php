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
        $html = do_shortcode('[fluent_cart_customer_profile]');

        if (!\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

            return;
        }

        // Editor canvas: the dashboard is core's Vue SPA — it mounts at page
        // load (Start.js queries its container at script evaluation), so the
        // AJAX-injected canvas markup can never boot it and only the skeleton
        // shell renders. Present that shell as an intentional wireframe:
        // freeze the pulse animation and badge it, instead of an endless load.
        printf(
            '<div class="fce-customer-dashboard-preview" style="position:relative;">'
            . '<style>.fce-customer-dashboard-preview .el-skeleton.is-animated .el-skeleton__item{animation:none !important;}</style>'
            . '<div style="position:absolute;top:10px;left:50%%;transform:translateX(-50%%);z-index:10;background:#1f2124;color:#fff;font-size:11px;font-weight:600;letter-spacing:.3px;padding:6px 14px;border-radius:999px;white-space:nowrap;">%s</div>'
            . '%s</div>',
            esc_html__('Layout preview — the interactive dashboard loads on the frontend', 'fluent-cart'),
            $html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-rendered markup
        );
    }
}
