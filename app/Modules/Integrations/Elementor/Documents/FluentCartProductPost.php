<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Documents;

use Elementor\Core\DocumentTypes\Post;
use Elementor\Utils;

if (!defined('ABSPATH')) {
    exit;
}

class FluentCartProductPost extends Post
{
    public static function get_properties()
    {
        $properties = parent::get_properties();

        $properties['cpt'] = [
            'fluent-products',
        ];

        return $properties;
    }

    public function get_name()
    {
        return 'fluentcart-product-post';
    }

    public static function get_title()
    {
        return esc_html__('FluentCart Product Post', 'fluent-cart');
    }

    public static function get_plural_title()
    {
        return esc_html__('FluentCart Product Posts', 'fluent-cart');
    }

    protected static function get_editor_panel_categories()
    {
        $categories = parent::get_editor_panel_categories();

        unset($categories['theme-elements-single']);

        $categories = Utils::array_inject(
            $categories,
            'theme-elements',
            [
                'fluentcart-elements-single' => [
                    'title'  => esc_html__('Product', 'fluent-cart'),
                    'active' => false,
                ],
            ]
        );

        return $categories;
    }
}
