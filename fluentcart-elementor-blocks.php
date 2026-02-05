<?php defined('ABSPATH') or die;

/*
Plugin Name: FluentCart Elementor Blocks
Description: FluentCart Elementor Blocks WordPress plugin to extend Elementor with FluentCart specific widgets and features.
Version: 1.0.0
Author:
Author URI:
Plugin URI:
License: GPLv2 or later
Text Domain: fluentcart-elementor-blocks
Domain Path: /language
*/

/**
 * Check if the main plugin is loaded and active
 * @see https://developer.wordpress.org/reference/functions/is_plugin_active
 */
// add_action('admin_init', function() {
//     if (!is_plugin_active(plugin_dir_path(__DIR__) . 'plugin_dir/plugin.php')) {
//         throw new RuntimeException(
//             'Fix the check and replace this throw with an appropriate action.'
//         );
//     }
// });

require __DIR__.'/vendor/autoload.php';

call_user_func(function($bootstrap) {
    $bootstrap(__FILE__);
}, require(__DIR__.'/boot/app.php'));
