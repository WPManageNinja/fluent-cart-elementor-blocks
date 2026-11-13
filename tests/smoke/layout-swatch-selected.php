<?php
/**
 * Where a layout card shows the selected choice (after the label or below the options), and the List
 * Products tab's own Show the Selected Choice. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke layout-swatch-selected
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/layout-swatch-selected.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;

$test = new FceTest('smoke/layout-swatch-selected');

// Not a skip: a run without core's card layout settings has nothing to prove, so it must not pass.
$test->check(ProductCardLayouts::cardSettingsAvailable(), 'this FluentCart ships the card layout settings');
if (!ProductCardLayouts::cardSettingsAvailable()) {
    $test->finish();
}

$render = function ($widgetType, array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fcesel' . substr(md5(wp_json_encode($settings)), 0, 4),
        'elType'     => 'widget',
        'widgetType' => $widgetType,
        'settings'   => $settings,
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};

foreach (['fluent_cart_shop_app', 'fluent_cart_product_carousel', 'fluent_cart_product_card', 'fluentcart_related_products'] as $type) {
    $c = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($type)->get_controls();
    $test->same([$c['layout_swatches_selected_position_compact']['default'] ?? null, $c['layout_swatches_selected_position_compact']['inner_tab'] ?? null, $c['layout_swatches_selected_position_compact']['condition']['layout_swatches_show_label_compact'] ?? null], ['name', 'layout_swatches_tab_attribute_compact', 'yes'], "{$type}: attribute products choose where the selected choice appears, while it is shown");
    $test->same([$c['layout_swatches_simple_show_selected_compact']['default'] ?? null, $c['layout_swatches_simple_show_selected_compact']['inner_tab'] ?? null], ['', 'layout_swatches_tab_list_compact'], "{$type}: list products have their own Show the Selected Choice, following the attribute tab until set");
    // Elementor drops a control's visibility rules outside the editor, so only the default is checked here; the rules are checked in the editor.
    $test->same($c['layout_swatches_simple_selected_position_compact']['default'] ?? null, 'name', "{$type}: list products choose where the selected choice appears too");
}

// One product of each type that draws swatches on a Compact card.
$find = function ($simple) use ($render) {
    $query = \FluentCart\App\Models\ProductDetail::query()->whereHas('product', function ($q) { $q->where('post_status', 'publish'); })->limit(30);
    $simple ? $query->where('variation_type', \FluentCart\App\Helpers\Helper::PRODUCT_TYPE_SIMPLE_VARIATION) : $query->whereNotIn('variation_type', ['simple', \FluentCart\App\Helpers\Helper::PRODUCT_TYPE_SIMPLE_VARIATION]);
    foreach ($query->pluck('post_id') as $id) {
        if (strpos($render('fluent_cart_product_card', ['product_id' => (string) $id, 'card_layout' => 'compact']), 'data-fct-card-swatch-rows') !== false) {
            return (string) $id;
        }
    }
    return '';
};
$attributeId = $find(false);
$listId = $find(true);
$test->check($attributeId !== '' && $listId !== '', 'the store has an attribute product and a list product with swatches');

$below = 'data-fct-card-swatch-selected-values';
if ($attributeId) {
    $card = ['product_id' => $attributeId, 'card_layout' => 'compact', 'layout_swatches_show_label_compact' => 'yes'];
    $afterLabel = $render('fluent_cart_product_card', $card);
    $test->check(strpos($afterLabel, 'data-fct-card-swatch-label') !== false && strpos($afterLabel, $below) === false, 'attribute products: the selected choice starts after the label');
    $test->check(strpos($render('fluent_cart_product_card', $card + ['layout_swatches_selected_position_compact' => 'below']), $below) !== false, 'attribute products: Below the Options moves it under the rows');
}
if ($listId) {
    $card = ['product_id' => $listId, 'card_layout' => 'compact', 'layout_swatches_show_label_compact' => 'yes', 'layout_swatches_simple_selected_position_compact' => 'below'];
    $followed = $render('fluent_cart_product_card', array_diff_key($card, ['layout_swatches_simple_selected_position_compact' => 1]));
    $test->check(strpos($followed, 'data-fct-card-swatch-label') !== false && strpos($followed, $below) === false, 'list products: unset follows the attribute tab, and the choice starts after the label');
    $test->check(strpos($render('fluent_cart_product_card', $card), $below) !== false, 'list products: Below moves it under the row');
    $test->check(strpos($render('fluent_cart_product_card', $card + ['layout_swatches_simple_show_selected_compact' => 'no']), $below) === false, 'list products: Hide leaves it out, whatever the attribute tab says');
    $off = ['product_id' => $listId, 'card_layout' => 'compact', 'layout_swatches_show_label_compact' => '', 'layout_swatches_simple_selected_position_compact' => 'below'];
    $test->check(strpos($render('fluent_cart_product_card', $off + ['layout_swatches_simple_show_selected_compact' => 'yes']), $below) !== false, 'list products: Show shows it, whatever the attribute tab says');
}

$test->finish();
