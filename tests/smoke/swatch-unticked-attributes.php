<?php
/**
 * Never Show These Attributes (Gutenberg's unticked rows): they stay off the card even when the other
 * attributes are added, on the Classic card and on a layout card. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke swatch-unticked-attributes
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/swatch-unticked-attributes.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductAttributeSwatches;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;

$test = new FceTest('smoke/swatch-unticked-attributes');

// Not a skip: a run without swatches has nothing to prove, so it must not pass.
$test->check(ProductAttributeSwatches::swatchesAvailable() && ProductCardLayouts::cardSettingsAvailable(), 'this FluentCart ships the swatches and the card layout settings');
if (!ProductAttributeSwatches::swatchesAvailable() || !ProductCardLayouts::cardSettingsAvailable()) {
    $test->finish();
}

$render = function (array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fceun' . substr(md5(wp_json_encode($settings)), 0, 4),
        'elType'     => 'widget',
        'widgetType' => 'fluent_cart_product_card',
        'settings'   => $settings,
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};
$groupsIn = function ($html) {
    preg_match_all('/data-group-id="(\d+)"/', $html, $m);

    return array_values(array_unique(array_map('intval', $m[1])));
};

foreach (['fluent_cart_shop_app', 'fluent_cart_product_carousel', 'fluent_cart_product_card', 'fluentcart_related_products'] as $type) {
    $c = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($type)->get_controls();
    $classic = $c['swatch_hidden_group_ids'] ?? [];
    $test->same([$classic['type'] ?? null, $classic['multiple'] ?? null], ['select2', true], "{$type}: the Classic card has the unticked attributes");
    // Core adds the other attributes only on Yes (or Automatic with nothing picked), so it shows only on Yes, as in Bricks.
    $test->same([$classic['condition']['swatch_show_other_groups'] ?? null, isset($classic['condition']['swatch_show_other_groups!'])], ['yes', false], "{$type}: Classic shows it only while Also Show Attributes Not Listed Here is Yes");
    $layout = $c['layout_swatches_hidden_group_ids_compact'] ?? [];
    $test->same([$layout['type'] ?? null, $layout['multiple'] ?? null], ['select2', true], "{$type}: a layout card has it too");
    $test->same($layout['condition']['layout_swatches_show_other_groups_compact'] ?? null, 'yes', "{$type}: a layout card shows it only while Also Show is Yes");
}

// A variable product that draws two or more attribute rows with every attribute added.
$productId = '';
$groups = [];
foreach (\FluentCart\App\Models\ProductDetail::query()->where('variation_type', '!=', 'simple')->whereHas('product', function ($q) { $q->where('post_status', 'publish'); })->limit(40)->pluck('post_id')->all() as $id) {
    $found = $groupsIn($render(['product_id' => (string) $id, 'card_elements' => [['_id' => 'sw1', 'element_type' => 'swatches'] + ['swatch_show_other_groups' => 'yes']]]));
    if (count($found) >= 2) {
        $productId = (string) $id;
        $groups = $found;
        break;
    }
}
$test->check($productId !== '', 'the store has a published product with two or more attributes');

if ($productId) {
    $hide = $groups[0];
    $keep = $groups[1];

    $classic = $render(['product_id' => $productId, 'card_elements' => [['_id' => 'sw1', 'element_type' => 'swatches', 'swatch_show_other_groups' => 'yes', 'swatch_hidden_group_ids' => [(string) $hide]]]]);
    $shown = $groupsIn($classic);
    $test->check(!in_array($hide, $shown, true) && in_array($keep, $shown, true), 'Classic: an unticked attribute stays off the card while the others show');

    $card = ['product_id' => $productId, 'card_layout' => 'compact', 'layout_swatches_show_other_groups_compact' => 'yes'];
    $all = $groupsIn($render($card));
    $test->check(in_array($hide, $all, true), 'a layout card draws the attribute when nothing is unticked');
    $hidden = $groupsIn($render($card + ['layout_swatches_hidden_group_ids_compact' => [(string) $hide]]));
    $test->check(!in_array($hide, $hidden, true) && in_array($keep, $hidden, true), 'a layout card: an unticked attribute stays off while the others show');
    $other = $groupsIn($render($card + ['layout_swatches_hidden_group_ids_framed' => [(string) $hide]]));
    $test->check(in_array($hide, $other, true), "another layout's unticked attributes are not used");

    // The same attribute in both pickers stays off: never wins, as the label says.
    $both = $groupsIn($render(['product_id' => $productId, 'card_elements' => [['_id' => 'sw1', 'element_type' => 'swatches', 'swatch_show_other_groups' => 'yes', 'swatch_group_ids' => [(string) $hide, (string) $keep], 'swatch_hidden_group_ids' => [(string) $hide]]]]));
    $test->check(!in_array($hide, $both, true) && in_array($keep, $both, true), 'Classic: an attribute picked and unticked stays off');
    $bothLayout = $groupsIn($render($card + ['layout_swatches_group_ids_compact' => [(string) $hide, (string) $keep], 'layout_swatches_hidden_group_ids_compact' => [(string) $hide]]));
    $test->check(!in_array($hide, $bothLayout, true) && in_array($keep, $bothLayout, true), 'a layout card: an attribute picked and unticked stays off');

    // Past the group limit a long list keeps every id, up to core's 1000.
    $test->same(count(ProductAttributeSwatches::groupIds(range(1, 40), 1000)), 40, 'the unticked list is not cut at the group limit');
    $test->same(count(ProductAttributeSwatches::groupIds(range(1, 1500), 1000)), 1000, 'and is bounded at 1000, as core reads it');
}

$test->finish();
