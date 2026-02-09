<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Divi;

use FluentCartElementorBlocks\App\Modules\Integrations\Divi\Modules\Legacy\AddToCartModule;
use FluentCartElementorBlocks\App\Modules\Integrations\Divi\Modules\Legacy\BuyNowModule;
use FluentCartElementorBlocks\App\Modules\Integrations\Divi\Modules\Legacy\CheckoutModule;
use FluentCartElementorBlocks\App\Modules\Integrations\Divi\Modules\Legacy\MiniCartModule;
use FluentCartElementorBlocks\App\Modules\Integrations\Divi\Modules\Legacy\ProductCarouselModule;
use FluentCartElementorBlocks\App\Modules\Integrations\Divi\Modules\Legacy\ProductCategoriesListModule;

class DiviIntegration
{
    public function register()
    {
        \add_action('et_builder_ready', [$this, 'registerModules']);
    }

    public function registerModules()
    {
        new MiniCartModule();
        new AddToCartModule();
        new BuyNowModule();
        new ProductCategoriesListModule();
        new ProductCarouselModule();
        new CheckoutModule();
    }
}
