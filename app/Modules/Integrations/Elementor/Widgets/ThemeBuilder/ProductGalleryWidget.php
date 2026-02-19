<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Services\Renderer\ProductRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ProductWidgetTrait;

if (!defined('ABSPATH')) {
    exit;
}

class ProductGalleryWidget extends Widget_Base
{
    use ProductWidgetTrait;

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
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['product', 'gallery', 'images', 'photos', 'fluent'];
    }

    public static function registerGalleryContentControls($widget)
    {
        $widget->add_control(
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

        $widget->add_control(
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

        $widget->add_control(
            'scrollable_thumbs',
            [
                'label'        => esc_html__('Scrollable Thumbnails', 'fluent-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart'),
                'label_off'    => esc_html__('No', 'fluent-cart'),
                'return_value' => 'yes',
                'default'      => '',
                'description'  => esc_html__('Enable scrolling when thumbnails exceed the main image dimensions.', 'fluent-cart'),
            ]
        );

        $widget->add_control(
            'max_thumbnails',
            [
                'label'       => esc_html__('Max Thumbnails', 'fluent-cart'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 1,
                'step'        => 1,
                'default'     => '',
                'description' => esc_html__('Leave empty for no limit. Excess images accessible via "See More" button.', 'fluent-cart'),
            ]
        );
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

        static::registerGalleryContentControls($this);

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
            'thumb_position'    => $settings['thumb_position'] ?: 'bottom',
            'thumbnail_mode'    => $settings['thumbnail_mode'] ?: 'all',
            'scrollable_thumbs' => !empty($settings['scrollable_thumbs']) ? 'yes' : 'no',
            'max_thumbnails'    => !empty($settings['max_thumbnails']) ? (int) $settings['max_thumbnails'] : null,
        ]);
        echo '</div>';
    }
}
