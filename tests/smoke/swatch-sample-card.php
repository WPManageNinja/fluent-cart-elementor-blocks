<?php
/**
 * Gutenberg's sample card in the Swatches tabs: each tab of Classic and of every layout copy has one, right
 * under its switch, naming the keys swatch-sample-card.js reads. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke swatch-sample-card
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/swatch-sample-card.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductAttributeSwatches;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;

$test = new FceTest('smoke/swatch-sample-card');

// Not a skip: a run without swatches has nothing to prove, so it must not pass.
$test->check(ProductAttributeSwatches::swatchesAvailable() && ProductCardLayouts::cardSettingsAvailable(), 'this FluentCart ships the swatches and the card layout settings');
if (!ProductAttributeSwatches::swatchesAvailable() || !ProductCardLayouts::cardSettingsAvailable()) {
    $test->finish();
}

$layouts = array_values(array_filter(array_keys(ProductCardLayouts::options()), function ($layout) {
    return $layout !== '' && in_array('swatches', \FluentCart\App\Services\Renderer\ProductCardLayoutPresets::cardParts($layout), true);
}));
$test->check(in_array('compact', $layouts, true), 'Compact has swatches');

// The full control definitions, as the editor gets them: the storefront strips raw HTML and the tabs of UI
// controls, so the controls are recorded straight from the registration code, with the tab each sits in.
$host = new class {
    public $controls = [];
    private $tab = null;
    public function add_control($id, array $args) { $this->controls[$id] = $args + ['inner_tab' => $this->tab]; return true; }
    public function add_responsive_control($id, array $args) { return $this->add_control($id, $args); }
    public function start_controls_tab($id, array $args = []) { $this->tab = $id; }
    public function end_controls_tab() { $this->tab = null; }
    public function __call($name, $args) { return null; }
};
ProductAttributeSwatches::registerSwatchControls($host, ['card_layout' => ''], '.fct-card-swatch-rows');
ProductCardLayouts::registerPartSections($host);
$c = $host->controls;
$keys = array_keys($c);
$test->check(count($c) > 20, 'the registration code was recorded');

// Each tab's sample: [its key, the tab it sits in, the switch it follows, the type, the key prefix and suffix].
foreach (['attribute' => 'advanced_enabled', 'list' => 'simple_enabled'] as $tab => $switch) {
    $samples = [['swatch_sample_' . $tab, 'swatch_tab_' . $tab, 'swatch_' . $switch, 'swatch_', '']];
    foreach ($layouts as $layout) {
        $samples[] = ['layout_swatches_sample_' . $tab . '_' . $layout, 'layout_swatches_tab_' . $tab . '_' . $layout, 'layout_swatches_' . $switch . '_' . $layout, 'layout_swatches_', '_' . $layout];
    }
    foreach ($samples as list($key, $tabKey, $switchKey, $prefix, $suffix)) {
        $control = $c[$key] ?? [];
        $raw = (string) ($control['raw'] ?? '');
        $test->check(($control['type'] ?? null) === 'raw_html' && ($control['inner_tab'] ?? null) === $tabKey, "{$key} sits in its tab");
        $test->same(array_search($key, $keys, true) - array_search($switchKey, $keys, true), 1, "{$key} sits right under the tab's switch, as Gutenberg's card");
        $test->check(!array_key_exists($switchKey, (array) ($control['condition'] ?? [])), "{$key} stays while the switch is off, to say so");
        $test->check(strpos($raw, 'data-type="' . $tab . '"') !== false && strpos($raw, 'data-prefix="' . $prefix . '"') !== false && strpos($raw, 'data-suffix="' . $suffix . '"') !== false, "{$key} names its tab's keys");
    }
}

// Every widget with the swatch tabs carries the samples.
foreach (['fluent_cart_shop_app', 'fluent_cart_product_carousel', 'fluent_cart_product_card', 'fluentcart_related_products'] as $type) {
    $widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($type)->get_controls();
    $test->check(isset($widget['swatch_sample_attribute'], $widget['swatch_sample_list'], $widget['layout_swatches_sample_attribute_compact'], $widget['layout_swatches_sample_list_compact']), "{$type}: Classic and Compact have the sample card");
}

// Every key the script reads exists under that prefix and suffix, so a renamed setting cannot leave the card stale.
$read = ['advanced_enabled', 'max_visible', 'max_visible_all', 'show_group_name', 'show_label', 'selected_position', 'simple_enabled', 'simple_max_visible', 'simple_max_visible_all', 'simple_show_label', 'simple_label', 'simple_show_selected', 'simple_selected_position', 'sold_out_display'];
$script = (string) file_get_contents(dirname(__DIR__, 2) . '/resources/elementor/swatch-sample-card.js');
foreach ($read as $name) {
    $test->check(strpos($script, "get('" . $name . "')") !== false, "the script reads {$name}");
    $test->check(isset($c['swatch_' . $name], $c['layout_swatches_' . $name . '_compact']), "{$name} exists for Classic and Compact");
}

$test->finish();
