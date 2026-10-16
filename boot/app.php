<?php

use FluentCartElementorBlocks\App\Core\Application;

return function($file) {
    // Self-hosted updater — registered unconditionally (outside the
    // fluentcart_loaded gate below) so update checks keep working on every
    // site. This addon is FREE (is_free => true): no license is read or sent,
    // and no license-activation notice is ever shown.
    //
    // Unconditional on purpose: behind the gate, a site whose FluentCart
    // install was deactivated would silently stop receiving updates for this
    // plugin with nothing on screen to say why.
    add_action('plugins_loaded', function () use ($file) {
        new \FluentCartElementorBlocks\App\Services\PluginManager\Updater(
            'https://fluentcart.com/',
            $file,
            [
                'version'           => FLUENTCART_ELEMENTOR_BLOCKS_VERSION,
                'addon_slug'        => 'fluent-cart-elementor-blocks',
                'parent_product_id' => 21480,
                'plugin_title'      => 'FluentCart Elementor Blocks',
                'is_free'           => true,
            ]
        );

        add_filter('plugin_row_meta', function ($links, $pluginFile) use ($file) {
            if (plugin_basename($file) !== $pluginFile) {
                return $links;
            }

            // Nonced because the handler acts on the request rather than just
            // reading it (Updater::check_transient_data). Its
            // check_admin_referer() verifies this nonce — change one and the
            // link stops working.
            $checkUpdateUrl = esc_url(wp_nonce_url(
                admin_url('plugins.php?fluent-cart-elementor-blocks-check-update=' . time()),
                'fluent-cart-elementor-blocks-check-update'
            ));

            $links['check_update'] = '<a style="color: #583fad;font-weight: 600;" href="' . $checkUpdateUrl . '" aria-label="' . esc_attr__('Check Update', 'fluent-cart-elementor-blocks') . '">' . esc_html__('Check Update', 'fluent-cart-elementor-blocks') . '</a>';

            return $links;
        }, 10, 2);
    }, 9);

    add_action('fluentcart_loaded', function($app) use ($file) {
        new Application($app, $file);
    });
};
