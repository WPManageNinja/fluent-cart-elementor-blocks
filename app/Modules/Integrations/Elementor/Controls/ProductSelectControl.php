<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls;

use Elementor\Control_Select2;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ProductSelectControl extends Control_Select2
{
    public function get_type()
    {
        return 'fluent_product_select';
    }
}
