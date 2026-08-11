<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use FluentCart\App\Modules\Templating\AssetLoader;

/**
 * Order Receipt / thank-you widget — renders FluentCart core's
 * [fluent_cart_receipt]. The handler resolves the order from the request's
 * order_hash / trx_hash (the URL the checkout redirects to), renders the
 * thank-you view (order summary, totals, customer + shipping details), and
 * falls back to a "not found" message when no order is in context. A thin
 * wrapper like the Divi Receipt module: all markup/styling/behaviour come
 * from core.
 */
class ReceiptWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_receipt';
    }

    public function get_title()
    {
        return esc_html__('Order Receipt', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-check-circle fluent-cart-widget-icon';
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
        // Enqueue the thank-you CSS so the widget renders correctly in the
        // editor preview iframe (on the front end the shortcode enqueues them).
        AssetLoader::enqueueThankYouPageAssets();

        return [];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_receipt',
            [
                'label' => esc_html__('Order Receipt', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'receipt_info',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('Renders the FluentCart order receipt / thank-you page. It resolves the order from the checkout redirect URL (order_hash); styling and behaviour come from FluentCart core.', 'fluent-cart'),
                'content_classes' => 'elementor-descriptor',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        // Same delegation as the [fluent_cart_receipt] shortcode / the Divi
        // Receipt module — core resolves the order from the request and renders
        // the thank-you view (or the not-found state).
        echo do_shortcode('[fluent_cart_receipt]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
