<?php



/**
 * @var $router Router
 */

use FluentCart\Framework\Http\Router;
use FluentCartElementorBlocks\App\Http\Controllers\ProductController;
use FluentCartElementorBlocks\App\Http\Policies\UserPolicy;

$router->prefix('products')->withPolicy('ProductPolicy')->group(function (Router $router) {
    $router->get('/search-product-variant-options', [ProductController::class, 'searchProductVariantOptions'])->meta([
        'permissions' => 'products/view'
    ]);
});
