<?php

namespace FluentCartElementorBlocks\App\Services\PluginManager;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PluginUpdater
{
    public static function init($pluginFile)
    {
        new Updater(
            'https://cart.wp1.site',
            $pluginFile,
            [
                'version'           => FLUENTCART_ELEMENTOR_BLOCKS_VERSION,
                'addon_slug'        => 'fluent-cart-elementor-blocks',
                'parent_product_id' => 155,
                'plugin_title'      => 'FluentCart Elementor Blocks',
            ]
        );

        add_filter('plugin_row_meta', function ($links, $pluginFilePath) use ($pluginFile) {
            if (plugin_basename($pluginFile) !== $pluginFilePath) {
                return $links;
            }

            $checkUpdateUrl = esc_url(admin_url('plugins.php?fluent-cart-elementor-blocks-check-update=' . time()));

            $links['check_update'] = '<a style="color: #583fad;font-weight: 600;" href="' . $checkUpdateUrl . '" aria-label="' . esc_attr__('Check Update', 'fluent-cart-elementor-blocks') . '">' . esc_html__('Check Update', 'fluent-cart-elementor-blocks') . '</a>';

            return $links;
        }, 10, 2);
    }
}
