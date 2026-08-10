<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use FluentCart\App\Modules\Templating\AssetLoader;

/**
 * Full shopping-cart widget — renders FluentCart core's [fluent_cart_cart]
 * (item rows, quantity steppers, totals, and the empty-cart state). A thin
 * wrapper like the Divi Cart module: all markup, styling and behaviour come
 * from core, so the template stays portable and always reflects the live cart.
 */
class CartWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluent_cart_cart';
    }

    public function get_title()
    {
        return esc_html__('Cart', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-cart-medium fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['cart', 'shopping cart', 'basket', 'checkout', 'commerce', 'fluent'];
    }

    public function get_style_depends()
    {
        // Enqueue the cart CSS/JS so the widget renders correctly in the
        // editor preview iframe (on the front end the shortcode enqueues them).
        AssetLoader::loadCartAssets();

        return [];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_cart',
            [
                'label' => esc_html__('Cart', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'cart_info',
            [
                'type'            => Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('Renders the FluentCart shopping cart — item rows, quantities, totals and the empty-cart state. Styling and behaviour come from FluentCart core.', 'fluent-cart'),
                'content_classes' => 'elementor-descriptor',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        // Same delegation as the [fluent_cart_cart] shortcode / the Divi Cart
        // module — core renders the item rows and the empty-cart state.
        echo do_shortcode('[fluent_cart_cart]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
