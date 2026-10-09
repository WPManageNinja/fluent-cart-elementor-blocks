<?php defined('ABSPATH') or die;

/*
Plugin Name: FluentCart Elementor Blocks
Description: FluentCart Elementor Blocks WordPress plugin to extend Elementor with FluentCart specific widgets and features.
Version: 1.1.1
Author: FluentCart Team
Author URI: https://fluentcart.com/about-us
Plugin URI: https://fluentcart.com
License: GPLv2 or later
Text Domain: fluent-cart-elementor-blocks
Domain Path: /language
*/

if (!defined('FLUENTCART_ELEMENTOR_BLOCKS_VERSION')) {
    define('FLUENTCART_ELEMENTOR_BLOCKS_VERSION', '1.1.1');
    define('FLUENTCART_ELEMENTOR_BLOCKS_URL', plugin_dir_url(__FILE__));
}


require __DIR__.'/vendor/autoload.php';

call_user_func(function($bootstrap) {
    $bootstrap(__FILE__);
}, require(__DIR__.'/boot/app.php'));
