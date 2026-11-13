<?php
/**
 * The Floating layout is a grid only: the Products widget hides View Mode and shows no Grid/List switch for it.
 * Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke products-floating-grid-only
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/products-floating-grid-only.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;
use FluentCart\App\Services\Renderer\ProductCardLayoutPresets;

$test = new FceTest('smoke/products-floating-grid-only');

// Not a skip: a core without grid-only layouts has nothing to prove here, so it must not pass.
$test->check(ProductCardLayouts::available() && method_exists(ProductCardLayoutPresets::class, 'gridOnlyLayouts') && in_array('floating', ProductCardLayoutPresets::gridOnlyLayouts(), true), 'this FluentCart marks Floating as a grid-only layout');
if (!ProductCardLayouts::available() || !method_exists(ProductCardLayoutPresets::class, 'gridOnlyLayouts')) {
    $test->finish();
}

$render = function (array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fcegrid' . substr(md5(wp_json_encode($settings)), 0, 4),
        'elType'     => 'widget',
        'widgetType' => 'fluent_cart_shop_app',
        'settings'   => $settings,
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};

$control = \Elementor\Plugin::$instance->widgets_manager->get_widget_types('fluent_cart_shop_app')->get_controls()['view_mode'] ?? [];
$test->same($control['condition'] ?? null, ['card_layout!' => ProductCardLayoutPresets::gridOnlyLayouts()], 'View Mode is hidden for a grid-only layout');

$shop = ['per_page' => 4, 'paginator' => 'numbers', 'view_mode' => 'list'];
$gridButton = 'data-fluent-cart-shop-app-grid-view-button';

$floating = $render($shop + ['card_layout' => 'floating']);
$test->check(strpos($floating, 'mode-grid') !== false && strpos($floating, 'mode-list') === false, 'Floating: a saved List view shows as a grid');
$test->check(strpos($floating, $gridButton) === false, 'Floating: no Grid/List switch');
$test->check(strpos($floating, 'fct-shop-view-switcher-wrap') === false, 'Floating: no empty switcher box where there is nothing to switch (no filters)');

$withFilter = $render($shop + ['card_layout' => 'floating', 'enable_filter' => 'yes']);
$test->check(strpos($withFilter, 'data-fluent-cart-shop-app-filter-toggle-button') !== false && strpos($withFilter, $gridButton) === false, 'Floating with filters: the filter toggle stays, the Grid/List switch does not');

$compact = $render($shop + ['card_layout' => 'compact']);
$test->check(strpos($compact, $gridButton) !== false && strpos($compact, 'mode-list') !== false, 'Compact: keeps the Grid/List switch and the List view');
$classic = $render($shop);
$test->check(strpos($classic, $gridButton) !== false && strpos($classic, 'mode-list') !== false, 'Classic: keeps the Grid/List switch and the List view');

$test->finish();
