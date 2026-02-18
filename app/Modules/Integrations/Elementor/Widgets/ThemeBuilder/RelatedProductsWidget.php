<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ProductWidgetTrait;

if (!defined('ABSPATH')) {
    exit;
}

class RelatedProductsWidget extends Widget_Base
{
    use ProductWidgetTrait;

    public function get_name()
    {
        return 'fluentcart_related_products';
    }

    public function get_title()
    {
        return esc_html__('Related Products', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-products-related';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['product', 'related', 'similar', 'carousel', 'fluent'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'fluent-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->registerProductSourceControls();

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $product = $this->getProduct($settings);

        if (!$product) {
            $this->renderPlaceholder(__('Please select a product or use this widget inside a product template.', 'fluent-cart'));
            return;
        }

        AssetLoader::loadSingleProductAssets();

        $shortcodeAtts = 'id="' . (int) $product->ID . '"';

        echo '<div class="fluentcart-related-products">';
        echo do_shortcode('[fluent_cart_related_products ' . $shortcodeAtts . ']');
        echo '</div>';
    }
}
