<?php
/**
 * Attribute filters in the Products widget and the Swatches card element in
 * Products, Product Carousel and Related Products. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke attribute
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/attribute-swatches.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductAttributeSwatches;

$test = new FceTest('smoke/attribute-swatches');

if (!ProductAttributeSwatches::swatchesAvailable()) {
    echo "  (skipped: this FluentCart has no attribute filters or card swatches)\n";
    $test->finish();
}

$groups = ProductAttributeSwatches::groupOptions();
$relation = \FluentCart\App\Models\AttributeRelation::query()
    ->from('fct_atts_relations as rel')
    ->join('fct_product_variations as var', 'var.id', '=', 'rel.object_id')
    ->join('posts as p', 'p.ID', '=', 'var.post_id')
    ->where('p.post_status', 'publish')
    ->select(['var.post_id', 'rel.group_id'])
    ->first();

if (!$groups || !$relation) {
    echo "  (skipped: no attribute groups or no published product uses one)\n";
    $test->finish();
}

$productId = (int) $relation->post_id;
$groupId = (int) $relation->group_id;

$render = function ($widgetType, array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fcesw' . substr(md5($widgetType), 0, 4),
        'elType'     => 'widget',
        'widgetType' => $widgetType,
        'settings'   => $settings,
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};

$swatchElement = [
    '_id'                    => 'sw1',
    'element_type'           => 'swatches',
    'swatch_group_ids'       => [(string) $groupId],
    'swatch_max_visible'     => ['size' => 2, 'unit' => ''],
    'swatch_show_group_name' => 'yes',
    'swatch_show_label'      => 'yes',
];

// Products widget, scoped to the product so its card is on page one
$shop = $render('fluent_cart_shop_app', [
    'enable_filter'              => 'yes',
    'enable_attribute_filters'   => 'yes',
    'attribute_filter_groups'    => [(string) $groupId],
    'attribute_filter_show_count' => 'yes',
    'card_elements'              => [['_id' => 'im', 'element_type' => 'image'], $swatchElement],
]);
$test->check(strpos($shop, 'name="attr_' . $groupId . '"') !== false, "the sidebar has the attr_{$groupId} filter");
$test->check(strpos($shop, 'fct-shop-attribute-count') !== false, 'Show counts prints the counts');

$off = $render('fluent_cart_shop_app', ['enable_filter' => 'yes', 'attribute_filter_groups' => [(string) $groupId]]);
$test->check(strpos($off, 'fct-shop-attribute-filter') === false, 'no attribute filter while the toggle is off');

$carousel = $render('fluent_cart_product_carousel', [
    'product_ids'   => [(string) $productId],
    'card_elements' => [['_id' => 'im', 'element_type' => 'image'], $swatchElement],
]);
$test->check(strpos($carousel, 'class="fct-card-swatch-rows elementor-repeater-item-sw1"') !== false, 'the carousel card has the swatch rows with its repeater item class');
$test->check(strpos($carousel, 'fct-card-swatch-group-name') !== false, 'Show Attribute Label prints the caption');
$test->check(strpos($carousel, 'data-fct-card-swatch-label') !== false, 'Show Selected Name prints the value name');
preg_match('/<div class="fct-card-swatch-list">(.*?)<\/div>/s', $carousel, $list);
$test->check(substr_count($list[1] ?? '', 'data-fct-card-swatch') <= 2, 'Max Swatches limits the row to 2');

$plain = $render('fluent_cart_product_carousel', [
    'product_ids'   => [(string) $productId],
    'card_elements' => [['_id' => 'sw2', 'element_type' => 'swatches']],
]);
$test->check(strpos($plain, 'fct-card-swatch-group-name') === false && strpos($plain, 'data-fct-card-swatch-label') === false, 'both labels stay off by default');

$related = $render('fluentcart_related_products', [
    'source'        => 'custom',
    'product_id'    => (string) $productId,
    'show_swatches' => 'yes',
]);
$relatedOff = $render('fluentcart_related_products', ['source' => 'custom', 'product_id' => (string) $productId]);
$test->check(strpos($relatedOff, 'fct-card-swatch-rows') === false, 'Related Products shows no swatches until turned on');
if (strpos($related, 'fct-product-card') !== false && strpos($related, 'data-fct-card-swatches') === false) {
    echo "  (note: the related products here use no attributes)\n";
}

$test->finish();
