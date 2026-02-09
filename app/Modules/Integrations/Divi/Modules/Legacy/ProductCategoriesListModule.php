<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Divi\Modules\Legacy;

use FluentCart\App\Services\Renderer\ProductCategoriesListRenderer;
use FluentCart\App\Vite;

class ProductCategoriesListModule extends \ET_Builder_Module
{
    public $slug       = 'fceb_product_categories_list';
    public $vb_support = 'partial';

    public function init()
    {
        $this->name = esc_html__('FluentCart Product Categories', 'fluentcart-elementor-blocks');
        $this->icon = 'N';
    }

    public function get_fields()
    {
        return [
            'display_style' => [
                'label'           => esc_html__('Display Style', 'fluentcart-elementor-blocks'),
                'type'            => 'select',
                'option_category' => 'basic_option',
                'options'         => [
                    'list'     => esc_html__('List', 'fluentcart-elementor-blocks'),
                    'dropdown' => esc_html__('Dropdown', 'fluentcart-elementor-blocks'),
                ],
                'default'         => 'list',
                'toggle_slug'     => 'main_content',
            ],
            'show_product_count' => [
                'label'           => esc_html__('Show Product Count', 'fluentcart-elementor-blocks'),
                'type'            => 'yes_no_button',
                'option_category' => 'basic_option',
                'options'         => [
                    'off' => esc_html__('No', 'fluentcart-elementor-blocks'),
                    'on'  => esc_html__('Yes', 'fluentcart-elementor-blocks'),
                ],
                'default'         => 'on',
                'toggle_slug'     => 'main_content',
            ],
            'show_hierarchy' => [
                'label'           => esc_html__('Show Hierarchy', 'fluentcart-elementor-blocks'),
                'type'            => 'yes_no_button',
                'option_category' => 'basic_option',
                'options'         => [
                    'off' => esc_html__('No', 'fluentcart-elementor-blocks'),
                    'on'  => esc_html__('Yes', 'fluentcart-elementor-blocks'),
                ],
                'default'         => 'on',
                'toggle_slug'     => 'main_content',
            ],
            'show_empty' => [
                'label'           => esc_html__('Show Empty Categories', 'fluentcart-elementor-blocks'),
                'type'            => 'yes_no_button',
                'option_category' => 'basic_option',
                'options'         => [
                    'off' => esc_html__('No', 'fluentcart-elementor-blocks'),
                    'on'  => esc_html__('Yes', 'fluentcart-elementor-blocks'),
                ],
                'default'         => 'off',
                'toggle_slug'     => 'main_content',
            ],
        ];
    }

    public function render($attrs, $content, $render_slug)
    {
        $app = \FluentCart\App\App::getInstance();
        $slug = $app->config->get('app.slug');

        Vite::enqueueStyle(
            $slug . '-product-categories-list',
            'public/product-categories-list/product-categories-list.scss'
        );

        Vite::enqueueScript(
            $slug . '-product-categories-list-js',
            'public/product-categories-list/product-categories-list.js'
        );

        $atts = [
            'display_style'      => $this->props['display_style'] ?? 'list',
            'show_product_count' => ($this->props['show_product_count'] ?? 'on') === 'on',
            'show_hierarchy'     => ($this->props['show_hierarchy'] ?? 'on') === 'on',
            'show_empty'         => ($this->props['show_empty'] ?? 'off') === 'on',
            'is_shortcode'       => true,
        ];

        ob_start();
        (new ProductCategoriesListRenderer())->render($atts);
        $html = ob_get_clean();

        return sprintf('<div class="fluent-cart-divi-categories-list">%s</div>', $html);
    }
}
