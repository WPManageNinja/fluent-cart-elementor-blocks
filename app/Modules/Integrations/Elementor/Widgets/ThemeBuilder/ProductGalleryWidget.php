<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use FluentCart\App\Models\Product;
use FluentCart\App\Modules\Data\ProductDataSetup;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls\ProductSelectControl;

if (!defined('ABSPATH')) {
    exit;
}

class ProductGalleryWidget extends Widget_Base
{
    public function get_name()
    {
        return 'fluentcart_product_gallery';
    }

    public function get_title()
    {
        return esc_html__('Product Gallery', 'fluent-cart');
    }

    public function get_icon()
    {
        return 'eicon-product-images';
    }

    public function get_categories()
    {
        return ['fluentcart-elements-single', 'fluent-cart'];
    }

    public function get_keywords()
    {
        return ['product', 'gallery', 'images', 'photos', 'fluent'];
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

        $this->add_control(
            'thumb_position',
            [
                'label'   => esc_html__('Thumbnail Position', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'bottom',
                'options' => [
                    'bottom' => esc_html__('Bottom', 'fluent-cart'),
                    'left'   => esc_html__('Left', 'fluent-cart'),
                    'right'  => esc_html__('Right', 'fluent-cart'),
                    'top'    => esc_html__('Top', 'fluent-cart'),
                ],
            ]
        );

        $this->add_control(
            'thumbnail_mode',
            [
                'label'   => esc_html__('Thumbnail Mode', 'fluent-cart'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'all',
                'options' => [
                    'all'        => esc_html__('All', 'fluent-cart'),
                    'horizontal' => esc_html__('Horizontal', 'fluent-cart'),
                    'vertical'   => esc_html__('Vertical', 'fluent-cart'),
                ],
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

        $renderer = new ProductRenderer($product);

        echo '<div class="fluentcart-product-gallery">';
        $renderer->renderGallery([
            'thumb_position' => $settings['thumb_position'] ?: 'bottom',
            'thumbnail_mode' => $settings['thumbnail_mode'] ?: 'all',
        ]);
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
