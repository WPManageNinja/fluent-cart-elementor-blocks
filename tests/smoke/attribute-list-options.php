<?php
/**
 * The attribute list's "Also show attributes not listed here", for layout cards and Classic. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke attribute-list-options
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/attribute-list-options.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductAttributeSwatches;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;

$test = new FceTest('smoke/attribute-list-options');

// Not a skip: a run without swatches has nothing to prove, so it must not pass.
$test->check(ProductAttributeSwatches::swatchesAvailable() && ProductCardLayouts::cardSettingsAvailable(), 'this FluentCart ships the swatches and card settings');
if (!ProductAttributeSwatches::swatchesAvailable() || !ProductCardLayouts::cardSettingsAvailable()) {
    $test->finish();
}

$render = function ($widgetType, array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fceal' . substr(md5(wp_json_encode($settings)), 0, 4),
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
    // The options (Automatic, Yes, No) are not visible here; the editor check covers them.
    $test->same([$c['layout_swatches_show_other_groups_compact']['type'] ?? null, $c['layout_swatches_show_other_groups_compact']['default'] ?? null], ['select', ''], "{$type}: Also show attributes not listed here is a select, starting at Automatic, for layout cards");
    $test->same([$c['swatch_show_other_groups']['type'] ?? null, $c['swatch_show_other_groups']['default'] ?? null], ['select', ''], "{$type}: the same setting for Classic");
    $test->same([$c['layout_swatches_show_other_groups_compact']['inner_tab'] ?? null, $c['swatch_show_other_groups']['inner_tab'] ?? null], [$c['layout_swatches_tab_attribute_compact']['name'] ?? '', $c['swatch_tab_attribute']['name'] ?? ''], "{$type}: both sit in the attribute tab");
}

// A product with at least two attribute groups, so "other attributes" can show.
$groupsOf = function ($productId) use ($render) {
    $html = $render('fluent_cart_product_card', ['product_id' => (string) $productId, 'card_elements' => [['_id' => 'sw1', 'element_type' => 'swatches', 'swatch_group_ids' => [], 'swatch_max_visible' => ['size' => 12, 'unit' => ''], 'swatch_simple_enabled' => '']]]);
    preg_match_all('/data-group-id="(\d+)"/', $html, $found);

    return array_values(array_unique($found[1]));
};
$ids = \FluentCart\App\Models\ProductDetail::query()->where('variation_type', '!=', 'simple')->whereHas('product', function ($q) { $q->where('post_status', 'publish'); })->limit(60)->pluck('post_id')->all();
$productId = '';
$groups = [];
foreach ($ids as $id) {
    $g = $groupsOf($id);
    if (count($g) >= 2) {
        $productId = (string) $id;
        $groups = $g;
        break;
    }
}
$test->check($productId !== '', 'the store has a published product with two attribute groups');

if ($productId) {
    $first = (string) $groups[0];
    $second = (string) $groups[1];
    $card = function (array $swatch) use ($render, $productId) {
        return $render('fluent_cart_product_card', ['product_id' => $productId, 'card_elements' => [['_id' => 'sw1', 'element_type' => 'swatches', 'swatch_max_visible' => ['size' => 12, 'unit' => '']] + $swatch]]);
    };
    $shown = function ($html) {
        preg_match_all('/data-group-id="(\d+)"/', $html, $found);

        return array_values(array_unique($found[1]));
    };
    // Picking one attribute with Yes shows the others after it; No keeps only the picked one.
    $test->same($shown($card(['swatch_group_ids' => [$first], 'swatch_show_other_groups' => 'yes'])), [$first, $second], 'Also show: Yes adds the attributes not listed, after the picked one');
    $test->same($shown($card(['swatch_group_ids' => [$first], 'swatch_show_other_groups' => 'no'])), [$first], 'Also show: No keeps only the picked attribute');
    // Automatic (unset) keeps the old rule: a pick means only the picks.
    $test->same($shown($card(['swatch_group_ids' => [$first]])), [$first], 'Also show: Automatic keeps the old rule, only the picks');
}

$test->finish();
