<?php

use FluentCartElementorBlocks\App\Core\Application;

return function($file) {
    add_action('fluentcart_loaded', function($app) use ($file) {
        new Application($app, $file);

        /**
         * Plugin Updater
         */
        $apiUrl = 'https://fluentcart.com/wp-admin/admin-ajax.php?action=fluent_cart_addon_update&time=' . time();
        new \FluentCartElementorBlocks\App\Services\PluginManager\Updater($apiUrl, $file, array(
            'version'   => FLUENTCART_ELEMENTOR_BLOCKS_VERSION,
            'license'   => '',
            'item_name' => 'FluentCart Elementor Blocks',
            'item_id'   => 'fluent-cart-elementor-blocks',
            'author'    => 'wpmanageninja'
        ),
            array(
                'license_status' => 'valid',
                'admin_page_url' => admin_url('admin.php?page=fluent-cart#/'),
                'purchase_url'   => 'https://fluentcart.com',
                'plugin_title'   => 'FluentCart Elementor Blocks'
            )
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
