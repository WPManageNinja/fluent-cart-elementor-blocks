<?php
/**
 * The Swatches panel's Gutenberg extras: picture choices for Where It Appears, the list tab's example, value counts
 * on the attribute options, the "pick at least one" warning, and the Masonry grid label. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke swatch-panel-extras
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/swatch-panel-extras.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductAttributeSwatches;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;

$test = new FceTest('smoke/swatch-panel-extras');

// Not a skip: a run without swatches has nothing to prove, so it must not pass.
$test->check(ProductAttributeSwatches::swatchesAvailable() && ProductCardLayouts::cardSettingsAvailable(), 'this FluentCart ships the swatches and the card layout settings');
if (!ProductAttributeSwatches::swatchesAvailable() || !ProductCardLayouts::cardSettingsAvailable()) {
    $test->finish();
}

// The full control definitions, as the editor gets them: the storefront strips options, descriptions and
// conditions, so the controls are recorded straight from the registration code.
$host = new class {
    public $controls = [];
    public function add_control($id, array $args) { $this->controls[$id] = $args; return true; }
    public function add_responsive_control($id, array $args) { return $this->add_control($id, $args); }
    public function __call($name, $args) { return null; }
};
ProductAttributeSwatches::registerSwatchControls($host, ['card_layout' => ''], '.fct-card-swatch-rows');
ProductCardLayouts::registerPartSections($host);
ProductCardLayouts::registerMasonryControl($host);
$c = $host->controls;
$test->check(count($c) > 20, 'the registration code was recorded');

foreach (['swatch_selected_position', 'swatch_simple_selected_position', 'layout_swatches_selected_position_compact', 'layout_swatches_simple_selected_position_compact'] as $key) {
    $control = $c[$key] ?? [];
    $test->same([$control['type'] ?? null, array_keys($control['options'] ?? []), isset($control['options']['below']['icon'])], ['choose', ['name', 'below'], true], "{$key} is a picture choice, as Gutenberg's");
}
foreach (['swatch_simple_show_selected', 'layout_swatches_simple_show_selected_compact'] as $key) {
    $test->check(strpos((string) ($c[$key]['description'] ?? ''), 'e.g.') !== false, "{$key}: the list tab's Show the Selected Choice gives an example");
}
foreach (['swatch_none_warning' => ['swatch_group_ids', 'swatch_show_other_groups'], 'layout_swatches_none_warning_compact' => ['layout_swatches_group_ids_compact', 'layout_swatches_show_other_groups_compact']] as $key => [$picked, $other]) {
    $warning = $c[$key] ?? [];
    $terms = array_column($warning['conditions']['terms'] ?? [], 'value', 'name');
    $test->check(($warning['type'] ?? null) === 'raw_html' && ($terms[$picked] ?? null) === '' && ($terms[$other] ?? null) === 'no', "{$key} warns when nothing is picked and Also Show is No");
}
$test->same($c['layout_masonry']['label'] ?? null, 'Masonry grid', 'the switch reads Masonry grid, as core');

// Value counts on the attribute options, from the store's real count.
$options = ProductAttributeSwatches::groupOptions(true);
$groupId = (int) array_key_first($options);
$test->check($groupId > 0, 'the store has an attribute');
if ($groupId) {
    $count = (int) \FluentCart\App\Models\AttributeTerm::query()->where('group_id', $groupId)->count();
    $expected = $count === 1 ? '1 value' : ($count === 0 ? 'No values' : $count . ' values');
    $test->check(strpos($options[$groupId], $expected) !== false, "an attribute option says how many values it has ({$expected})");
    $test->check(strpos(ProductAttributeSwatches::groupOptions(false)[$groupId], 'value') === false, 'without counts (the storefront) the option is the bare name');
}

$test->finish();
