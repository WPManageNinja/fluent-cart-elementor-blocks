<?php
/**
 * The Related Products widget's Columns setting. Core prints the list's column
 * variable inline, so the widget sets the grid itself. Reads only: the CSS is
 * built in memory and no file is written.
 *
 * Usage:  bash tests/bin/run-all.sh smoke related-products-columns
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/related-products-columns.php', ['\Elementor\Plugin', '\Elementor\Core\Files\CSS\Post']);

$test = new FceTest('smoke/related-products-columns');

$widget = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
    'id'         => 'fcerpc1',
    'elType'     => 'widget',
    'widgetType' => 'fluentcart_related_products',
    'settings'   => [
        'grid_columns' => ['unit' => 'px', 'size' => 2, 'sizes' => []],
    ],
]);
$test->check($widget instanceof \Elementor\Widget_Base, 'the Related Products widget is registered');

// The same call Elementor's post CSS file makes for each widget. Elementor only lists
// style controls while it builds CSS, so switch that on the way it does.
$performance = '\Elementor\Core\Frontend\Performance';
if (class_exists($performance)) {
    $performance::set_use_style_controls(true);
}
$file = new \Elementor\Core\Files\CSS\Post(0);
$file->add_controls_stack_style_rules(
    $widget,
    $file->get_style_controls($widget, null, $widget->get_parsed_dynamic_settings()),
    $widget->get_settings(),
    ['{{ID}}', '{{WRAPPER}}'],
    [$widget->get_id(), $file->get_element_unique_selector($widget)]
);
if (class_exists($performance)) {
    $performance::set_use_style_controls(false);
}
$css = (string) $file->get_stylesheet();
$test->check(strpos($css, '.fct-product-list') !== false, 'Elementor builds CSS for the widget\'s grid');

$test->check(
    (bool) preg_match('/\.fct-product-list\{[^}]*grid-template-columns:\s*repeat\(2,\s*1fr\)/', $css),
    'Columns 2 sets the grid itself, which core\'s inline column variable cannot override'
);
$test->check(
    (bool) preg_match('/\.fct-product-list\{[^}]*--fct-layout-columns:\s*2\b/', $css),
    'Columns 2 also sets the column count that core\'s card layouts read'
);
$test->check(
    strpos($css, '--fct-product-list-columns') === false,
    'the widget does not set the column variable that core prints inline on the list'
);

$test->finish();
