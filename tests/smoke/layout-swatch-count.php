<?php
/**
 * The Swatches section's "Show up to" number and All switch, for a layout card. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke layout-swatch-count
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/layout-swatch-count.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;

$test = new FceTest('smoke/layout-swatch-count');

// Not a skip: a run without core's card layout settings has nothing to prove, so it must not pass.
$test->check(ProductCardLayouts::cardSettingsAvailable(), 'this FluentCart ships the card layout settings');
if (!ProductCardLayouts::cardSettingsAvailable()) {
    $test->finish();
}

$render = function ($widgetType, array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fcesc' . substr(md5(wp_json_encode($settings)), 0, 4),
        'elType'     => 'widget',
        'widgetType' => $widgetType,
        'settings'   => $settings,
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};

foreach (['fluent_cart_shop_app', 'fluent_cart_product_carousel', 'fluent_cart_product_card', 'fluentcart_related_products'] as $type) {
    $controls = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($type)->get_controls();
    $test->same([
        $controls['layout_swatches_max_visible_compact']['type'] ?? null,
        $controls['layout_swatches_max_visible_all_compact']['default'] ?? null,
        $controls['layout_swatches_simple_max_visible_all_compact']['default'] ?? null,
    ], ['number', '', ''], "{$type}: Show up to is a number, with an All switch for each tab, off by default");
    $test->same(array_keys($controls['layout_swatches_max_visible_compact']['condition'] ?? []), ['card_layout', 'layout_swatches_max_visible_all_compact'], "{$type}: the number is hidden while All is on");
}

// A product whose swatch row has more options than one, so "+N" shows when the count is 1. Attribute products
// use the first tab's count, simple-variation products the second's.
$ids = \FluentCart\App\Models\ProductDetail::query()->where('variation_type', '!=', 'simple')->whereHas('product', function ($q) { $q->where('post_status', 'publish'); })->limit(20)->pluck('post_id')->all();
$productId = '';
foreach ($ids as $id) {
    if (strpos($render('fluent_cart_product_card', ['product_id' => (string) $id, 'card_layout' => 'compact', 'layout_swatches_max_visible_compact' => 1, 'layout_swatches_simple_max_visible_compact' => 1]), 'data-fct-card-swatch-more') !== false) {
        $productId = (string) $id;
        break;
    }
}
$test->check($productId !== '', 'the store has a published variable product with more than one swatch option');

if ($productId) {
    $isSimple = \FluentCart\App\Models\ProductDetail::query()->where('post_id', $productId)->value('variation_type') === \FluentCart\App\Helpers\Helper::PRODUCT_TYPE_SIMPLE_VARIATION;
    $own = $isSimple ? 'simple_max_visible' : 'max_visible';
    $other = $isSimple ? 'max_visible' : 'simple_max_visible';
    $card = ['product_id' => $productId, 'card_layout' => 'compact', 'layout_swatches_max_visible_compact' => 1, 'layout_swatches_simple_max_visible_compact' => 1];
    $test->check(strpos($render('fluent_cart_product_card', $card), 'data-fct-card-swatch-more') !== false, 'a count of 1 shows "+N" for the rest');
    $all = $render('fluent_cart_product_card', $card + ['layout_swatches_' . $own . '_all_compact' => 'yes']);
    $test->check(strpos($all, 'data-fct-card-swatch-rows') !== false && strpos($all, 'data-fct-card-swatch-more') === false, 'All shows every option, whatever the number says');
    $wrongTab = $render('fluent_cart_product_card', $card + ['layout_swatches_' . $other . '_all_compact' => 'yes']);
    $test->check(strpos($wrongTab, 'data-fct-card-swatch-more') !== false, "the other tab's All switch is not used");
    $typedZero = $render('fluent_cart_product_card', ['product_id' => $productId, 'card_layout' => 'compact', 'layout_swatches_' . $own . '_compact' => 0]);
    $test->check(strpos($typedZero, 'data-fct-card-swatch-more') !== false, 'a typed 0 is one option: only the Show All switch means All');
    $cleared = $render('fluent_cart_product_card', ['product_id' => $productId, 'card_layout' => 'compact', 'layout_swatches_' . $own . '_compact' => '']);
    $test->check(strpos($cleared, 'data-fct-card-swatch-rows') !== false, 'a cleared number keeps the layout\'s own count');
    $twelve = $render('fluent_cart_product_card', ['product_id' => $productId, 'card_layout' => 'compact', 'layout_swatches_' . $own . '_compact' => 12]);
    $test->check(strpos($twelve, 'data-fct-card-swatch-rows') !== false, 'a count of 12 is accepted');
    $off = $render('fluent_cart_product_card', $card + ['layout_swatches_' . $own . '_all_framed' => 'yes']);
    $test->check(strpos($off, 'data-fct-card-swatch-more') !== false, "another layout's All switch is not used");
}

$test->finish();
