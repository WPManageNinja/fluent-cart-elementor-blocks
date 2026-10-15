<?php
/**
 * The Checkout widget's Field Labels setting reaches the checkout wrapper as
 * core's fct-field-labels-* class, on the front end and in the editor preview.
 * Renders from an unsaved cart; nothing is saved.
 *
 * Usage:  bash tests/bin/run-all.sh smoke checkout-field-labels
 */

require __DIR__ . '/../lib/assert.php';

use FluentCart\App\Helpers\CartHelper;
use FluentCart\App\Models\ProductVariation;
use FluentCart\App\Services\Renderer\CheckoutRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers\DummyCheckoutRenderer;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers\ElementorCheckoutRenderer;

FceTest::requireLive('tests/smoke/checkout-field-labels.php', ['\Elementor\Plugin', ElementorCheckoutRenderer::class]);

$test = new FceTest('smoke/checkout-field-labels');

if (!method_exists(CheckoutRenderer::class, 'resolveFieldLabelStyle')) {
    echo "  (skipped: this FluentCart has no field label styles)\n";
    $test->finish();
}

$widget = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
    'id'         => 'fcelbl1',
    'elType'     => 'widget',
    'widgetType' => 'fluent_cart_checkout',
    'settings'   => [],
]);
$control = $widget ? ($widget->get_controls()['field_label_style'] ?? null) : null;
$test->check((bool) $control, 'the widget has a field_label_style control');
// Options aren't asserted: Elementor's optimized control loading drops them outside the editor.
$test->same($control['default'] ?? null, 'docked', 'it defaults to docked, like the block');

$variation = ProductVariation::query()->where('item_status', 'active')->first();
$test->check((bool) $variation, 'found an active variation to check out');
if (!$variation) {
    $test->finish();
}

global $wpdb;
$cartRows = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fct_carts");

$cart = CartHelper::generateCartFromVariation($variation, 1);
$supplyCart = function () use ($cart) {
    return $cart;
};
add_filter('fluent_cart/checkout/render_cart', $supplyCart);

$wrapperClass = function ($html) {
    return preg_match('/class="([^"]*fce-checkout-wrapper[^"]*)"/', $html, $m) ? explode(' ', $m[1]) : [];
};

// '' is an unsaved widget; anything unknown falls back to docked, as core does.
$cases = ['docked' => 'docked', 'floating' => 'floating', 'placeholder' => 'placeholder', '' => 'docked', 'bogus' => 'docked'];
foreach ($cases as $setting => $expected) {
    $settings = ['field_label_style' => $setting];
    $front = $wrapperClass((new ElementorCheckoutRenderer($settings))->render());
    $preview = $wrapperClass((new DummyCheckoutRenderer($settings))->render());
    $label = $setting === '' ? 'unset' : "'{$setting}'";

    $test->check(in_array("fct-field-labels-{$expected}", $front, true), "{$label} renders fct-field-labels-{$expected} on the front end");
    $test->check(in_array("fct-field-labels-{$expected}", $preview, true), "{$label} renders fct-field-labels-{$expected} in the editor preview");
    $test->same(count(preg_grep('/^fct-field-labels-/', $front)), 1, "{$label} adds exactly one label style class");
}

remove_filter('fluent_cart/checkout/render_cart', $supplyCart);

$test->same((int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fct_carts"), $cartRows, 'no cart row was written');

$test->finish();
