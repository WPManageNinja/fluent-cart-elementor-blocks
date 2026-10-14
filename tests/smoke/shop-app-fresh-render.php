<?php
/**
 * The Shop App widget renders its products fresh each time, like core's
 * block: a second render in another currency shows that currency's prices,
 * not the first render's HTML. Reads only; nothing is saved.
 *
 * Usage:  bash tests/bin/run-all.sh smoke shop-app
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/shop-app-fresh-render.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

$test = new FceTest('smoke/shop-app-fresh-render');

$context = '\FluentCart\App\Modules\MultiCurrency\Services\CurrencyContext';
if (!class_exists($context) || count($context::selectableCurrencies()) < 2) {
    echo "  (skipped: the store offers one currency)\n";
    $test->finish();
}

$currencies = $context::selectableCurrencies();
$base = $currencies[0];
$other = $currencies[1];

$render = function () {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fceshop',
        'elType'     => 'widget',
        'widgetType' => 'fluent_cart_shop_app',
        'settings'   => [],
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};

$first = $context::withCurrency($base, $render);
$second = $context::withCurrency($other, $render);

$test->check(strpos($first, 'fct-product-card') !== false, "the widget renders products ({$base})");
$test->check($first !== $second, "a render in {$other} is not the {$base} render served again");

$test->finish();
