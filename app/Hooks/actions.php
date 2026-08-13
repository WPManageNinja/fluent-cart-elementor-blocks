<?php

(new \FluentCartElementorBlocks\App\Modules\Integrations\Elementor\ElementorIntegration())->register();

// Seed the bundled page templates into Elementor's native library
// (version-gated on admin_init; no-op until Elementor is active).
(new \FluentCartElementorBlocks\App\Services\TemplateLibrary\TemplateLibrary())->register();

// Clear ShopApp Elementor widget transient cache when products change
$shopAppCacheClear = function () {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_fce_shop_app_%' OR option_name LIKE '_transient_timeout_fce_shop_app_%'");
};

\add_action('fluent_cart_product_created', $shopAppCacheClear);
\add_action('fluent_cart_product_updated', $shopAppCacheClear);
\add_action('fluent_cart_product_deleted', $shopAppCacheClear);