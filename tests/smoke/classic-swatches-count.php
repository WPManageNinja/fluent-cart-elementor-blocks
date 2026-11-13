<?php
/**
 * Classic's swatch counts: up to 12, or every option with Show All. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke classic-swatches-count
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/classic-swatches-count.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductAttributeSwatches;

$test = new FceTest('smoke/classic-swatches-count');

// Not a skip: a run without swatches has nothing to prove, so it must not pass.
$test->check(ProductAttributeSwatches::swatchesAvailable(), 'this FluentCart ships the swatches');
if (!ProductAttributeSwatches::swatchesAvailable()) {
    $test->finish();
}

$render = function (array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fcecc' . substr(md5(wp_json_encode($settings)), 0, 4),
        'elType'     => 'widget',
        'widgetType' => 'fluent_cart_product_card',
        'settings'   => $settings,
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};
$row = function (array $swatch) {
    return [['_id' => 'im', 'element_type' => 'image'], ['_id' => 'sw1', 'element_type' => 'swatches'] + $swatch];
};

foreach (['fluent_cart_shop_app', 'fluent_cart_product_carousel', 'fluent_cart_product_card', 'fluentcart_related_products'] as $type) {
    $c = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($type)->get_controls();
    $test->same([$c['swatch_max_visible_all']['type'] ?? null, $c['swatch_max_visible_all']['default'] ?? null, $c['swatch_simple_max_visible_all']['default'] ?? null], ['switcher', '', ''], "{$type}: a Show All switch for each tab, off by default");
    $test->same([array_key_exists('swatch_max_visible_all', $c['swatch_max_visible']['condition'] ?? []), array_key_exists('swatch_simple_max_visible_all', $c['swatch_simple_max_visible']['condition'] ?? [])], [true, true], "{$type}: the number is hidden while Show All is on");
}

// A product whose swatches have more options than one, so "+N" shows when the count is 1. Attribute products
// use the first tab's count, simple-variation products the second's.
$ids = \FluentCart\App\Models\ProductDetail::query()->where('variation_type', '!=', 'simple')->whereHas('product', function ($q) { $q->where('post_status', 'publish'); })->limit(30)->pluck('post_id')->all();
$productId = '';
foreach ($ids as $id) {
    $probe = $render(['product_id' => (string) $id, 'card_elements' => $row(['swatch_max_visible' => ['size' => 1, 'unit' => ''], 'swatch_simple_max_visible' => ['size' => 1, 'unit' => '']])]);
    if (strpos($probe, 'data-fct-card-swatch-more') !== false) {
        $productId = (string) $id;
        break;
    }
}
$test->check($productId !== '', 'the store has a published variable product with more than one swatch option');

if ($productId) {
    $isSimple = \FluentCart\App\Models\ProductDetail::query()->where('post_id', $productId)->value('variation_type') === \FluentCart\App\Helpers\Helper::PRODUCT_TYPE_SIMPLE_VARIATION;
    $own = $isSimple ? 'swatch_simple_max_visible' : 'swatch_max_visible';
    $other = $isSimple ? 'swatch_max_visible' : 'swatch_simple_max_visible';
    $one = ['swatch_max_visible' => ['size' => 1, 'unit' => ''], 'swatch_simple_max_visible' => ['size' => 1, 'unit' => '']];
    $card = function (array $swatch) use ($render, $row, $productId) {
        return $render(['product_id' => $productId, 'card_elements' => $row($swatch)]);
    };
    $test->check(strpos($card($one), 'data-fct-card-swatch-more') !== false, 'a count of 1 shows "+N" for the rest');
    $all = $card($one + [$own . '_all' => 'yes']);
    $test->check(strpos($all, 'data-fct-card-swatch-rows') !== false && strpos($all, 'data-fct-card-swatch-more') === false, 'Show All shows every option, whatever the number says');
    $test->check(strpos($card($one + [$other . '_all' => 'yes']), 'data-fct-card-swatch-more') !== false, "the other tab's Show All is not used");
}

// The cap is 12, not the old 6: a product with a row of more than six options shows them all at 12, and 13 is cut
// to 12. A product whose rows are all six or fewer cannot tell the two caps apart, so the check needs one that can.
$swatches = function (array $swatch) use ($render, $row) {
    return function ($productId) use ($render, $row, $swatch) {
        return preg_match_all('/data-fct-card-swatch(?![-\w])/', $render(['product_id' => (string) $productId, 'card_elements' => $row($swatch)]));
    };
};
$sized = function ($count) use ($swatches) {
    $size = ['size' => $count, 'unit' => ''];
    return $swatches(['swatch_max_visible' => $size, 'swatch_simple_max_visible' => $size]);
};
$everything = $swatches(['swatch_max_visible_all' => 'yes', 'swatch_simple_max_visible_all' => 'yes']);
$sixOnly = $sized(6);
$manyId = '';
foreach ($ids as $id) {
    if ($everything($id) > $sixOnly($id)) {
        $manyId = (string) $id;
        break;
    }
}
$test->check($manyId !== '', 'the store has a published variable product with a row of more than six options');
if ($manyId) {
    $test->same([$sized(12)($manyId), $sized(13)($manyId)], [$everything($manyId), $everything($manyId)], 'a count of 12 shows what Show All shows, and 13 is cut to 12');
    $test->check($sized(3)($manyId) < $sixOnly($manyId), 'a smaller count shows fewer');
}

$test->finish();
