<?php

use FluentCartElementorBlocks\App\Core\Application;
use FluentCartElementorBlocks\App\Services\PluginManager\PluginUpdater;

return function($file) {
    add_action('fluentcart_loaded', function($app) use ($file) {
        new Application($app, $file);
        PluginUpdater::init($file);
    });
};
