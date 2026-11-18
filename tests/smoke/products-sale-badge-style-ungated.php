<?php
/**
 * The Products widget's Sale Badge style section does not depend on "Allow Out of Stock"; only the
 * Sold Out Badge section does. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke products-sale-badge-style-ungated
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/products-sale-badge-style-ungated.php', ['\Elementor\Plugin']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;

$test = new FceTest('smoke/products-sale-badge-style-ungated');

$test->check(ProductCardLayouts::cardSettingsAvailable(), 'this FluentCart has layout card settings (the code path under test)');

$widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types('fluent_cart_shop_app');
$sections = $widget->get_stack()['controls'];
$sectionConditions = [];
foreach (['sale_badge_style_section', 'sold_out_badge_style_section'] as $id) {
    $sectionConditions[$id] = wp_json_encode($sections[$id]['conditions'] ?? $sections[$id]['condition'] ?? null);
}

$test->check($sectionConditions['sale_badge_style_section'] !== 'null' && strpos($sectionConditions['sale_badge_style_section'], 'default_filter_allow_out_of_stock') === false, 'Sale Badge style section is not gated on Allow Out of Stock');
$test->check(strpos($sectionConditions['sold_out_badge_style_section'], 'default_filter_allow_out_of_stock') !== false, 'Sold Out Badge style section stays gated on Allow Out of Stock');
$test->finish();
