<?php
/**
 * The Swatches section's Sold Out setting, for a layout card. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke layout-swatch-sold-out
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/layout-swatch-sold-out.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;

$test = new FceTest('smoke/layout-swatch-sold-out');

// Not a skip: a run without core's card layout settings has nothing to prove, so it must not pass.
$test->check(ProductCardLayouts::cardSettingsAvailable(), 'this FluentCart ships the card layout settings');
if (!ProductCardLayouts::cardSettingsAvailable()) {
    $test->finish();
}

$render = function ($widgetType, array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fcess' . substr(md5(wp_json_encode($settings)), 0, 4),
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
    $test->same([$controls['layout_swatches_sold_out_display_compact']['default'] ?? null, $controls['layout_swatches_sold_out_display_framed']['default'] ?? null], ['normal', 'normal'], "{$type}: each layout has a Sold Out setting, starting at the block's Normal");
}

$variableId = (string) \FluentCart\App\Models\ProductDetail::query()->where('variation_type', '!=', 'simple')->whereHas('product', function ($q) { $q->where('post_status', 'publish'); })->value('post_id');
$test->check($variableId !== '', 'the store has a published variable product to draw swatches on');

if ($variableId) {
    $card = ['product_id' => $variableId, 'card_layout' => 'compact'];
    $default = $render('fluent_cart_product_card', $card);
    $test->check(strpos($default, 'data-fct-card-swatch-rows') !== false && strpos($default, 'data-sold-out-display') === false, 'a layout card shows sold-out options as before by default (the swatch rows carry no marker)');
    foreach (['cross', 'hide'] as $mode) {
        $html = $render('fluent_cart_product_card', $card + ['layout_swatches_sold_out_display_compact' => $mode]);
        $test->check(strpos($html, 'data-sold-out-display="' . $mode . '"') !== false, "Sold Out set to {$mode} reaches the card's swatches");
    }
    // A setting for another layout does not leak into this card.
    $other = $render('fluent_cart_product_card', $card + ['layout_swatches_sold_out_display_framed' => 'hide']);
    $test->check(strpos($other, 'data-fct-card-swatch-rows') !== false && strpos($other, 'data-sold-out-display') === false, "another layout's Sold Out setting is not used");
}

$test->finish();
