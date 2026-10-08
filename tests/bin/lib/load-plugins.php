<?php
/**
 * WP-CLI --require file: loads Elementor and this add-on for the test process only,
 * so the live tiers run even when they are inactive on the site. Nothing is saved.
 *
 * Usage:  wp --require=tests/bin/lib/load-plugins.php eval-file <test>
 */

WP_CLI::add_wp_hook('option_active_plugins', function ($plugins) {
    $plugins = (array) $plugins;

    foreach (['elementor/elementor.php', 'fluent-cart-elementor-blocks/fluent-cart-elementor-blocks.php'] as $plugin) {
        if (!in_array($plugin, $plugins, true)) {
            $plugins[] = $plugin;
        }
    }

    return $plugins;
});
