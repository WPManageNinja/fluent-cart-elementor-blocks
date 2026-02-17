<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use FluentCart\App\Models\Product;
use FluentCart\App\Modules\Data\ProductDataSetup;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls\ProductSelectControl;

if (!defined('ABSPATH')) {
    exit;
}

class RelatedProductsWidget extends Widget_Base
{
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
        return ['fluentcart-elements-single', 'fluent-cart'];
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

        $this->add_control(
            'product_id',
            [
                'label'       => esc_html__('Select Product', 'fluent-cart'),
                'type'        => (new ProductSelectControl())->get_type(),
                'label_block' => true,
                'multiple'    => false,
                'description' => esc_html__('Leave empty to auto-detect from current product context.', 'fluent-cart'),
                'default'     => '',
            ]
        );

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

    protected function getProduct($settings)
    {
        $productId = !empty($settings['product_id']) ? (int) $settings['product_id'] : 0;

        if ($productId) {
            return ProductDataSetup::getProductModel($productId);
        }

        if (isset($GLOBALS['fct_product']) && $GLOBALS['fct_product'] instanceof Product) {
            return $GLOBALS['fct_product'];
        }

        $postId = get_the_ID();
        if ($postId && get_post_type($postId) === 'fluent-products') {
            return ProductDataSetup::getProductModel($postId);
        }

        return null;
    }

    protected function renderPlaceholder($message)
    {
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            echo '<div class="fluent-cart-placeholder" style="text-align:center; padding: 20px; background: #f0f0f1; border: 1px dashed #ccc;">';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
        }
    }
}
