<?php
/**
 * A category in the URL (reload, Back, a shared link) keeps the Products
 * widget's taxonomy filter settings: the custom label stays and show_empty
 * stays off. Reads only, nothing is saved.
 *
 * Usage:  bash tests/bin/run-all.sh smoke shop-app-url
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/shop-app-url-taxonomy-settings.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

$test = new FceTest('smoke/shop-app-url-taxonomy-settings');

$termIds = get_terms(['taxonomy' => 'product-categories', 'hide_empty' => true, 'fields' => 'ids', 'number' => 1]);
if (is_wp_error($termIds) || !$termIds) {
    echo "  (skipped: this store has no category with products)\n";
    $test->finish();
}

$label = 'Shop by category (url test)';

$render = function (array $urlFilters) use ($label) {
    \FluentCart\App\App::request()->set('filters', $urlFilters);
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fceurl',
        'elType'     => 'widget',
        'widgetType' => 'fluent_cart_shop_app',
        'settings'   => [
            'enable_filter'                          => 'yes',
            'enable_taxonomy_product_categories'     => 'yes',
            'taxonomy_label_product_categories'      => $label,
            'show_empty_taxonomy_product_categories' => '',
        ],
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};

$clean = $render([]);
$withUrl = $render(['product-categories' => (string) $termIds[0]]);
\FluentCart\App\App::request()->set('filters', null);

$test->check(strpos($clean, $label) !== false, 'the custom category label shows on a clean load');
$test->check(strpos($withUrl, $label) !== false, 'the custom category label still shows with a category in the URL');

$test->finish();
