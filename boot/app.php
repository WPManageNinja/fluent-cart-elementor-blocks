<?php

use FluentCartElementorBlocks\App\Core\Application;

return function($file) {
    add_action('fluentcart_loaded', function($app) use ($file) {
        new Application($app, $file);

        /**
         * Plugin Updater V2 — checks for updates from FluentCart store via addon_slug
         */
        new \FluentCartElementorBlocks\App\Services\PluginManager\Updater(
            'https://cart.wp1.site',
            $file,
            [
                'version'           => FLUENTCART_ELEMENTOR_BLOCKS_VERSION,
                'addon_slug'        => 'fluent-cart-elementor-blocks',
                'parent_product_id' => 155,
                'plugin_title'      => 'FluentCart Elementor Blocks',
            ]
        );

        add_filter('plugin_row_meta', function ($links, $pluginFile) use ($file) {
            if (plugin_basename($file) !== $pluginFile) {
                return $links;
            }

            $checkUpdateUrl = esc_url(admin_url('plugins.php?fluent-cart-elementor-blocks-check-update=' . time()));

            $row_meta = array(
                'check_update' => '<a style="color: #583fad;font-weight: 600;" href="' . $checkUpdateUrl . '" aria-label="' . esc_attr__('Check Update', 'fluent-cart-elementor-blocks') . '">' . esc_html__('Check Update', 'fluent-cart-elementor-blocks') . '</a>',
            );

            return array_merge($links, $row_meta);

        }, 10, 2);
    });
};
