<?php
/**
 * Classic's Sold-out Options: one segmented choice for both product types. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke classic-swatches-sold-out
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/classic-swatches-sold-out.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductAttributeSwatches;

$test = new FceTest('smoke/classic-swatches-sold-out');

// Not a skip: a run without swatches has nothing to prove, so it must not pass.
$test->check(ProductAttributeSwatches::swatchesAvailable(), 'this FluentCart ships the swatches');
if (!ProductAttributeSwatches::swatchesAvailable()) {
    $test->finish();
}

$render = function (array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fcecs' . substr(md5(wp_json_encode($settings)), 0, 4),
        'elType'     => 'widget',
        'widgetType' => 'fluent_cart_product_card',
        'settings'   => $settings,
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};

foreach (['fluent_cart_shop_app', 'fluent_cart_product_carousel', 'fluent_cart_product_card', 'fluentcart_related_products'] as $type) {
    $c = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($type)->get_controls();
    $test->same([$c['swatch_sold_out_display']['type'] ?? null, $c['swatch_sold_out_display']['default'] ?? null, isset($c['swatch_sold_out_display']['inner_tab'])], ['choose', 'normal', false], "{$type}: Sold-out Options is one segmented choice below the tabs, starting at No Change");
}

$ids = \FluentCart\App\Models\ProductDetail::query()->where('variation_type', '!=', 'simple')->whereHas('product', function ($q) { $q->where('post_status', 'publish'); })->limit(30)->pluck('post_id')->all();
$productId = '';
foreach ($ids as $id) {
    if (strpos($render(['product_id' => (string) $id, 'card_elements' => [['_id' => 'sw1', 'element_type' => 'swatches']]]), 'data-fct-card-swatch-rows') !== false) {
        $productId = (string) $id;
        break;
    }
}
$test->check($productId !== '', 'the store has a published variable product that draws swatches');

if ($productId) {
    $card = function (array $swatch) use ($render, $productId) {
        return $render(['product_id' => $productId, 'card_elements' => [['_id' => 'sw1', 'element_type' => 'swatches'] + $swatch]]);
    };
    $default = $card([]);
    $test->check(strpos($default, 'data-fct-card-swatch-rows') !== false && strpos($default, 'data-sold-out-display') === false, 'by default sold-out options show as before (the rows carry no marker)');
    foreach (['cross', 'hide'] as $mode) {
        $test->check(strpos($card(['swatch_sold_out_display' => $mode]), 'data-sold-out-display="' . $mode . '"') !== false, "Sold-out Options set to {$mode} reaches the swatches");
    }
    $test->check(strpos($card(['swatch_sold_out_display' => 'bogus']), 'data-sold-out-display') === false, 'an unknown value keeps the swatches as before');
}

$test->finish();
