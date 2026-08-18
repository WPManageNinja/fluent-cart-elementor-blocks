<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Conditions;

use ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Builder condition matching FluentCart's product taxonomy archives
 * (category/brand pages). Registered under the generic Archive group so an
 * Archive template can target exactly these URLs — Elementor Pro's own
 * "Products Archive" condition is WooCommerce-only (is_shop/is_product_taxonomy)
 * and never matches FluentCart's taxonomies.
 */
class FluentCartArchiveCondition extends Condition_Base
{
    public static function get_type()
    {
        return 'archive';
    }

    public function get_name()
    {
        return 'fluentcart_product_archive';
    }

    public function get_label()
    {
        return esc_html__('FluentCart Product Archives', 'fluent-cart');
    }

    public function get_all_label()
    {
        return esc_html__('All FluentCart Product Archives', 'fluent-cart');
    }

    public function check($args)
    {
        $taxonomies = get_object_taxonomies('fluent-products');

        return !empty($taxonomies) && is_tax($taxonomies);
    }
}
