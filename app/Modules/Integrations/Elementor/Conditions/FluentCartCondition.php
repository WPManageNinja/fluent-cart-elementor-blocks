<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Conditions;

use ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base;
use ElementorPro\Modules\ThemeBuilder\Conditions\Post;

if (!defined('ABSPATH')) {
    exit;
}

class FluentCartCondition extends Condition_Base
{
    public static function get_type()
    {
        return 'fluentcart_product';
    }

    public function get_name()
    {
        return 'fluentcart_product';
    }

    public function get_label()
    {
        return esc_html__('FluentCart', 'fluent-cart');
    }

    public function get_all_label()
    {
        return esc_html__('All Products', 'fluent-cart');
    }

    public function register_sub_conditions()
    {
        $product_single = new Post([
            'post_type' => 'fluent-products',
        ]);

        $this->register_sub_condition($product_single);
    }

    public function check($args)
    {
        return is_singular('fluent-products');
    }
}
