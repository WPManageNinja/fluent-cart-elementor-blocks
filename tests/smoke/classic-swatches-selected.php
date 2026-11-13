<?php
/**
 * Where Classic's swatches show the selected choice (after the label or below the options), and the List
 * Products tab's own Show the Selected Choice. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke classic-swatches-selected
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/classic-swatches-selected.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductAttributeSwatches;

$test = new FceTest('smoke/classic-swatches-selected');

// Not a skip: a run without swatches has nothing to prove, so it must not pass.
$test->check(ProductAttributeSwatches::swatchesAvailable(), 'this FluentCart ships the swatches');
if (!ProductAttributeSwatches::swatchesAvailable()) {
    $test->finish();
}

$render = function (array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fcecsel' . substr(md5(wp_json_encode($settings)), 0, 4),
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
    $test->same([$c['swatch_selected_position']['default'] ?? null, $c['swatch_selected_position']['inner_tab'] ?? null], ['name', $c['swatch_tab_attribute']['name'] ?? ''], "{$type}: attribute products choose where the selected choice appears");
    $test->same(array_key_exists('swatch_advanced_enabled', $c['swatch_selected_position']['condition'] ?? []), true, "{$type}: the attribute tab's Where It Appears hides while its switch is off, since attribute rows are not drawn then");
    $test->same([$c['swatch_simple_show_selected']['default'] ?? null, $c['swatch_simple_show_selected']['inner_tab'] ?? null], ['', $c['swatch_tab_list']['name'] ?? ''], "{$type}: list products have their own Show the Selected Choice, following the attribute tab until set");
    $test->same($c['swatch_simple_selected_position']['default'] ?? null, 'name', "{$type}: list products choose where too");
}

$find = function ($simple) use ($render) {
    $query = \FluentCart\App\Models\ProductDetail::query()->whereHas('product', function ($q) { $q->where('post_status', 'publish'); })->limit(30);
    $simple ? $query->where('variation_type', \FluentCart\App\Helpers\Helper::PRODUCT_TYPE_SIMPLE_VARIATION) : $query->whereNotIn('variation_type', ['simple', \FluentCart\App\Helpers\Helper::PRODUCT_TYPE_SIMPLE_VARIATION]);
    foreach ($query->pluck('post_id') as $id) {
        if (strpos($render(['product_id' => (string) $id, 'card_elements' => [['_id' => 'sw1', 'element_type' => 'swatches']]]), 'data-fct-card-swatch-rows') !== false) {
            return (string) $id;
        }
    }
    return '';
};
$attributeId = $find(false);
$listId = $find(true);
$test->check($attributeId !== '' && $listId !== '', 'the store has an attribute product and a list product with swatches');

$below = 'data-fct-card-swatch-selected-values';
$card = function ($productId, array $swatch) use ($render) {
    return $render(['product_id' => $productId, 'card_elements' => [['_id' => 'sw1', 'element_type' => 'swatches'] + $swatch]]);
};
if ($attributeId) {
    $on = ['swatch_show_label' => 'yes'];
    $afterLabel = $card($attributeId, $on);
    $test->check(strpos($afterLabel, 'data-fct-card-swatch-label') !== false && strpos($afterLabel, $below) === false, 'attribute products: the selected choice starts after the label');
    $test->check(strpos($card($attributeId, $on + ['swatch_selected_position' => 'below']), $below) !== false, 'attribute products: Below the Options moves it under the rows');
}
if ($listId) {
    $on = ['swatch_show_label' => 'yes', 'swatch_simple_selected_position' => 'below'];
    $followed = $card($listId, ['swatch_show_label' => 'yes']);
    $test->check(strpos($followed, 'data-fct-card-swatch-label') !== false && strpos($followed, $below) === false, 'list products: unset follows the attribute tab, and the choice starts after the label');
    $test->check(strpos($card($listId, $on), $below) !== false, 'list products: Below moves it under the row');
    $test->check(strpos($card($listId, $on + ['swatch_simple_show_selected' => 'no']), $below) === false, 'list products: Hide leaves it out, whatever the attribute tab says');
    $test->check(strpos($card($listId, ['swatch_simple_show_selected' => 'yes', 'swatch_simple_selected_position' => 'below']), $below) !== false, 'list products: Show shows it, whatever the attribute tab says');
}

$test->finish();
