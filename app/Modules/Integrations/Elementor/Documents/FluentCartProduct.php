<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Documents;

use Elementor\Controls_Manager;
use ElementorPro\Modules\ThemeBuilder\Documents\Single_Base;
use FluentCart\App\Modules\Data\ProductDataSetup;
use FluentCart\App\Modules\Templating\AssetLoader;

if (!defined('ABSPATH')) {
    exit;
}

class FluentCartProduct extends Single_Base
{
    public static function get_properties()
    {
        $properties = parent::get_properties();

        $properties['location'] = 'single';
        $properties['condition_type'] = 'fluentcart_product';

        return $properties;
    }

    public static function get_type()
    {
        return 'fluentcart-product';
    }

    public static function get_title()
    {
        return esc_html__('FluentCart Product', 'fluent-cart');
    }

    public static function get_plural_title()
    {
        return esc_html__('FluentCart Products', 'fluent-cart');
    }

    protected static function get_site_editor_icon()
    {
        return 'eicon-single-product';
    }

    protected static function get_site_editor_tooltip_data()
    {
        return [
            'title'   => esc_html__('What is a FluentCart Product Template?', 'fluent-cart'),
            'content' => esc_html__('A FluentCart product template allows you to design the layout and style of single product pages, and apply that template to various conditions.', 'fluent-cart'),
            'tip'     => esc_html__('You can create multiple product templates, and assign each to different types of products.', 'fluent-cart'),
        ];
    }

    protected static function get_editor_panel_categories()
    {
        $categories = [
            'fluent-cart' => [
                'title'  => esc_html__('FluentCart', 'fluent-cart'),
                'active' => true,
            ],
        ];

        $categories += parent::get_editor_panel_categories();

        unset($categories['theme-elements-single']);

        return $categories;
    }

    public function enqueue_scripts()
    {
        if (\ElementorPro\Plugin::elementor()->preview->is_preview_mode($this->get_main_id())) {
            AssetLoader::loadSingleProductAssets();
        }
    }

    public function get_container_attributes()
    {
        $attributes = parent::get_container_attributes();

        $attributes['class'] .= ' fluentcart-product';

        return $attributes;
    }

    public function filter_body_classes($body_classes)
    {
        $body_classes = parent::filter_body_classes($body_classes);

        if (get_the_ID() === $this->get_main_id() || \ElementorPro\Plugin::elementor()->preview->is_preview_mode($this->get_main_id())) {
            $body_classes[] = 'fluentcart';
        }

        return $body_classes;
    }

    public function before_get_content()
    {
        parent::before_get_content();

        $product = ProductDataSetup::getProductModel(get_the_ID());

        if ($product) {
            $GLOBALS['fct_product'] = $product;
        }
    }

    public function after_get_content()
    {
        parent::after_get_content();

        unset($GLOBALS['fct_product']);
    }

    public function print_content()
    {
        if (post_password_required()) {
            echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }

        parent::print_content();
    }

    public function __construct(array $data = [])
    {
        parent::__construct($data);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 11);
    }

    protected function register_controls()
    {
        parent::register_controls();

        $this->update_control(
            'preview_type',
            [
                'type'    => Controls_Manager::HIDDEN,
                'default' => 'single/fluent-products',
            ]
        );

        $latest_posts = get_posts([
            'posts_per_page' => 1,
            'post_type'      => 'fluent-products',
            'post_status'    => 'publish',
        ]);

        if (!empty($latest_posts)) {
            $this->update_control(
                'preview_id',
                [
                    'default' => $latest_posts[0]->ID,
                ]
            );
        }
    }

    protected function get_remote_library_config()
    {
        $config = parent::get_remote_library_config();

        $config['category'] = 'single product';

        return $config;
    }
}
