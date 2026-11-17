<?php
/**
 * The swatch Attributes picker keeps the order you pick: every copy of it carries the class
 * swatch-pick-order.js binds to, and the script is built and enqueued. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke swatch-pick-order
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/swatch-pick-order.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductAttributeSwatches;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;

$test = new FceTest('smoke/swatch-pick-order');

// Not a skip: a run without swatches has nothing to prove, so it must not pass.
$test->check(ProductAttributeSwatches::swatchesAvailable() && ProductCardLayouts::cardSettingsAvailable(), 'this FluentCart ships the swatches and the card layout settings');
if (!ProductAttributeSwatches::swatchesAvailable() || !ProductCardLayouts::cardSettingsAvailable()) {
    $test->finish();
}

// Recorded straight from the registration code: the storefront strips the controls' extras.
$host = new class {
    public $controls = [];
    public function add_control($id, array $args) { $this->controls[$id] = $args; return true; }
    public function add_responsive_control($id, array $args) { return $this->add_control($id, $args); }
    public function __call($name, $args) { return null; }
};
ProductAttributeSwatches::registerSwatchControls($host, ['card_layout' => ''], '.fct-card-swatch-rows');
ProductCardLayouts::registerPartSections($host);
$c = $host->controls;

$pickers = array_values(array_filter(array_keys($c), function ($key) {
    return preg_match('/^(swatch|layout_swatches)_group_ids(_|$)/', $key);
}));
$test->check(count($pickers) >= 2, 'Classic and the layouts have an Attributes picker (' . count($pickers) . ')');
foreach ($pickers as $key) {
    $test->check(strpos((string) ($c[$key]['classes'] ?? ''), 'fct-swatch-pick-order') !== false, "{$key} keeps the order you pick");
}
// The never-show list has no order to keep.
$test->check(strpos((string) ($c['swatch_hidden_group_ids']['classes'] ?? ''), 'fct-swatch-pick-order') === false, 'the never-show list is left alone');

$root = dirname(__DIR__, 2);
$test->check(is_file($root . '/resources/elementor/swatch-pick-order.js'), 'the script exists');
$test->check(strpos((string) file_get_contents($root . '/config/vite_config.php'), "'resources/elementor/swatch-pick-order.js'") !== false, 'the script has its manifest entry');
$test->check(strpos((string) file_get_contents($root . '/app/Modules/Integrations/Elementor/ElementorIntegration.php'), "'elementor/swatch-pick-order.js'") !== false, 'the script is enqueued in the editor');

$test->finish();
