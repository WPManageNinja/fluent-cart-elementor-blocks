<?php
/**
 * Without FluentCart Pro the Checkout widget's Products section holds only the Pro
 * notice, as in Gutenberg; with Pro, the product controls and no notice. Read-only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke checkout-pro-gate
 */

require __DIR__ . '/../lib/assert.php';

use FluentCart\App\App;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProFeature;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\CheckoutWidget;

FceTest::requireLive('tests/smoke/checkout-pro-gate.php', ['\Elementor\Plugin', CheckoutWidget::class]);

$test = new FceTest('smoke/checkout-pro-gate');

// Outside the editor Elementor strips RAW_HTML controls to type and section. Build the
// controls the way the editor panel sees them.
$frontendFlag = new \ReflectionProperty(\Elementor\Core\Frontend\Performance::class, 'is_frontend');
$frontendFlag->setAccessible(true);
$frontendFlag->setValue(null, false);

$widget = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
    'id'         => 'fcprog1',
    'elType'     => 'widget',
    'widgetType' => 'fluent_cart_checkout',
    'settings'   => [],
]);
$controls = $widget ? $widget->get_controls() : [];
$test->check((bool)$controls, 'the Checkout widget loads its controls');

$productControls = ['inline_checkout_mode', 'inline_core_layout_notice', 'inline_selection_mode', 'inline_max_items', 'inline_selector_label'];
$present = array_values(array_intersect($productControls, array_keys($controls)));

if (!App::isProActive()) {
    $test->same($present, [], 'without Pro no product control is registered');

    // Kept hidden so a saved selection survives and the notices can tell empty from saved.
    $test->same($controls['inline_variant_ids']['type'] ?? null, \Elementor\Controls_Manager::HIDDEN, 'the products setting is kept as a hidden control');
    $test->same($controls['inline_variant_ids']['default'] ?? null, [], 'it defaults to an empty list');

    $plain = $controls['inline_pro_notice'] ?? [];
    $kept = $controls['inline_pro_notice_kept'] ?? [];
    $test->same($plain['conditions']['terms'][0] ?? null, ['name' => 'inline_variant_ids', 'operator' => '==', 'value' => ''], 'the plain notice shows when no products are saved');
    $test->same($kept['conditions']['terms'][0] ?? null, ['name' => 'inline_variant_ids', 'operator' => '!=', 'value' => ''], 'the kept notice shows when products are saved');
    $test->check(strpos($kept['raw'] ?? '', 'fct-elementor-pro-panel-warning') !== false, 'the kept notice carries the warning line');
    $test->check(strpos($plain['raw'] ?? '', 'fct-elementor-pro-panel-warning') === false, 'the plain notice has no warning line');
    $test->check(strpos($plain['raw'] ?? '', 'href="' . ProFeature::UPGRADE_URL . '"') !== false, 'it links to the pricing page');
} else {
    $test->same($present, $productControls, 'with Pro every product control is registered');
    $test->check(!isset($controls['inline_pro_notice']) && !isset($controls['inline_pro_notice_kept']), 'with Pro there is no Pro notice');
    $test->check(($controls['inline_variant_ids']['type'] ?? '') !== \Elementor\Controls_Manager::HIDDEN, 'with Pro the products control is the picker');
}

// The note and warning are text, never markup.
$html = ProFeature::panelNotice('<b>note</b>', '<i>warn</i>');
$test->check(strpos($html, '<b>') === false && strpos($html, '&lt;b&gt;note') !== false, 'the note is escaped');
$test->check(strpos($html, '<i>') === false && strpos($html, '&lt;i&gt;warn') !== false, 'the warning is escaped');

$test->finish();
