<?php defined('ABSPATH') or die;

/*
Plugin Name: FluentCart Elementor Blocks
Description: FluentCart Elementor Blocks WordPress plugin to extend Elementor with FluentCart specific widgets and features.
Version: 1.0.3
Author:
Author URI:
Plugin URI:
License: GPLv2 or later
Text Domain: fluentcart-elementor-blocks
Domain Path: /language
*/

require __DIR__.'/vendor/autoload.php';

call_user_func(function($bootstrap) {
    $bootstrap(__FILE__);
}, require(__DIR__.'/boot/app.php'));
