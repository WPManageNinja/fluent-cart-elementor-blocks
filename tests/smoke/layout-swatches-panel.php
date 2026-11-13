<?php
/**
 * The layout Swatches section's structure: a tab per product type, each with its own switch and a
 * "How it shows on the card" group, and sold-out options as one segmented choice. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke layout-swatches-panel
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/layout-swatches-panel.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;

$test = new FceTest('smoke/layout-swatches-panel');

// Not a skip: a run without core's card layout settings has nothing to prove, so it must not pass.
$test->check(ProductCardLayouts::cardSettingsAvailable(), 'this FluentCart ships the card layout settings');
if (!ProductCardLayouts::cardSettingsAvailable()) {
    $test->finish();
}

// The picker's layouts whose card has swatches, other than Compact: each draws its own tabs.
$otherLayouts = array_values(array_filter(array_keys(ProductCardLayouts::options()), function ($layout) {
    return $layout !== 'compact' && in_array('swatches', \FluentCart\App\Services\Renderer\ProductCardLayoutPresets::cardParts($layout), true);
}));
$test->check(in_array('swatches', \FluentCart\App\Services\Renderer\ProductCardLayoutPresets::cardParts('compact'), true) && count($otherLayouts) > 0, 'Compact and at least one other layout have swatches');

foreach (['fluent_cart_shop_app', 'fluent_cart_product_carousel', 'fluent_cart_product_card', 'fluentcart_related_products'] as $type) {
    $c = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($type)->get_controls();
    $test->same([$c['layout_swatches_tabs_compact']['type'] ?? null, $c['layout_swatches_tab_attribute_compact']['type'] ?? null, $c['layout_swatches_tab_list_compact']['type'] ?? null], ['tabs', 'tab', 'tab'], "{$type}: a tab for attribute products and one for list products");
    $test->check(!isset($c['layout_swatches_advanced_heading_compact']) && !isset($c['layout_swatches_simple_heading_compact']), "{$type}: the tabs replace the two headings");

    $attributeTab = $c['layout_swatches_tab_attribute_compact']['name'] ?? '';
    $listTab = $c['layout_swatches_tab_list_compact']['name'] ?? '';
    $inTab = function (array $names, $tab) use ($c) {
        return array_map(function ($name) use ($c, $tab) {
            return ($c[$name]['inner_tab'] ?? null) === $tab;
        }, $names);
    };
    $test->same($inTab(['layout_swatches_advanced_enabled_compact', 'layout_swatches_group_ids_compact', 'layout_swatches_max_visible_compact', 'layout_swatches_show_group_name_compact', 'layout_swatches_show_label_compact'], $attributeTab), [true, true, true, true, true], "{$type}: the attribute settings sit in the attribute tab");
    $test->same($inTab(['layout_swatches_simple_enabled_compact', 'layout_swatches_simple_max_visible_compact', 'layout_swatches_simple_show_label_compact', 'layout_swatches_simple_label_compact'], $listTab), [true, true, true, true], "{$type}: the option list settings sit in the list tab");

    $test->same(array_key_exists('layout_swatches_advanced_enabled_compact', $c['layout_swatches_group_ids_compact']['condition'] ?? []), true, "{$type}: the attribute tab's settings hide while its switch is off");
    $test->same(array_key_exists('layout_swatches_simple_show_label_compact', $c['layout_swatches_simple_label_compact']['condition'] ?? []), true, "{$type}: the label name shows only while the label is on");
    // Elementor draws a tab's label even where its tabs wrapper is hidden, so each tab needs the layout condition too.
    foreach ($otherLayouts as $layout) {
        $test->same([$c['layout_swatches_tab_attribute_' . $layout]['condition']['card_layout'] ?? null, $c['layout_swatches_tab_list_' . $layout]['condition']['card_layout'] ?? null], [$layout, $layout], "{$type}: {$layout}'s tabs show only for {$layout}");
    }
    // Elementor strips a heading's tab and condition outside the editor, so only its place is checked here;
    // that it hides with its tab's switch is checked in the editor.
    $test->same([$c['layout_swatches_group_card_attribute_compact']['type'] ?? null, $c['layout_swatches_group_card_list_compact']['type'] ?? null], ['heading', 'heading'], "{$type}: each tab has a How It Shows on the Card heading");
    $test->same([$c['layout_swatches_sold_out_display_compact']['type'] ?? null, $c['layout_swatches_sold_out_display_compact']['default'] ?? null, isset($c['layout_swatches_sold_out_display_compact']['inner_tab'])], ['choose', 'normal', false], "{$type}: sold-out options are one segmented choice, below the tabs, for both product types");
}

$test->finish();
