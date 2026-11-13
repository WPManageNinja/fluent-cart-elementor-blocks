<?php
/**
 * Classic's Swatches settings: a tab per product type, each with its own switch that hides the rest of
 * its settings, and a How It Shows on the Card group. Same setting names as before. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke classic-swatches-panel
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/classic-swatches-panel.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductAttributeSwatches;

$test = new FceTest('smoke/classic-swatches-panel');

// Not a skip: a run without swatches has nothing to prove, so it must not pass.
$test->check(ProductAttributeSwatches::swatchesAvailable(), 'this FluentCart ships the swatches');
if (!ProductAttributeSwatches::swatchesAvailable()) {
    $test->finish();
}

foreach (['fluent_cart_shop_app', 'fluent_cart_product_carousel', 'fluent_cart_product_card', 'fluentcart_related_products'] as $type) {
    $c = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($type)->get_controls();
    $test->same([$c['swatch_tabs']['type'] ?? null, $c['swatch_tab_attribute']['type'] ?? null, $c['swatch_tab_list']['type'] ?? null], ['tabs', 'tab', 'tab'], "{$type}: Classic has a tab for attribute products and one for list products");
    $test->check(!isset($c['swatch_advanced_heading']) && !isset($c['swatch_simple_heading']), "{$type}: the tabs replace the two headings");

    $attributeTab = $c['swatch_tab_attribute']['name'] ?? '';
    $listTab = $c['swatch_tab_list']['name'] ?? '';
    $inTab = function (array $names, $tab) use ($c) {
        return array_map(function ($name) use ($c, $tab) {
            return ($c[$name]['inner_tab'] ?? null) === $tab;
        }, $names);
    };
    $test->same($inTab(['swatch_advanced_enabled', 'swatch_group_ids', 'swatch_max_visible', 'swatch_show_group_name', 'swatch_show_label'], $attributeTab), [true, true, true, true, true], "{$type}: the attribute settings sit in the attribute tab");
    $test->same($inTab(['swatch_simple_enabled', 'swatch_simple_max_visible', 'swatch_simple_show_label', 'swatch_simple_label'], $listTab), [true, true, true, true], "{$type}: the option list settings sit in the list tab");
    $test->same(array_key_exists('swatch_advanced_enabled', $c['swatch_group_ids']['condition'] ?? []), true, "{$type}: the attribute tab's settings hide while its switch is off");
    $test->same(array_key_exists('swatch_simple_enabled', $c['swatch_simple_max_visible']['condition'] ?? []), true, "{$type}: the list tab's settings hide while its switch is off (the number used to show regardless)");
    $test->same(array_key_exists('swatch_simple_show_label', $c['swatch_simple_label']['condition'] ?? []), true, "{$type}: the label name shows only while the label is on");
    $test->same([$c['swatch_group_card_attribute']['type'] ?? null, $c['swatch_group_card_list']['type'] ?? null], ['heading', 'heading'], "{$type}: each tab has a How It Shows on the Card heading");
}

$test->finish();
