<?php
/**
 * The card's Swatches settings for each variation type, as the Gutenberg Product
 * Swatches block has them: Advanced Variations (attribute rows) and Simple
 * Variations (one row of variant names). Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke swatch-variation
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/swatch-variation-types.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCart\App\Models\AttributeRelation;
use FluentCart\App\Models\Product;
use FluentCart\App\Models\ProductDetail;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductAttributeSwatches;

$test = new FceTest('smoke/swatch-variation-types');

if (!ProductAttributeSwatches::swatchesAvailable()) {
    echo "  (skipped: this FluentCart has no card swatches)\n";
    $test->finish();
}

// A published product with several plain variants and no attribute rows.
$simpleProduct = null;
foreach (ProductDetail::query()->where('variation_type', 'simple_variations')->limit(20)->pluck('post_id') as $postId) {
    $candidate = Product::query()->with(['detail', 'variants'])->where('post_status', 'publish')->find($postId);
    if ($candidate && $candidate->variants->count() > 1
        && !AttributeRelation::query()->whereIn('object_id', $candidate->variants->pluck('id')->all())->exists()) {
        $simpleProduct = $candidate;
        break;
    }
}

$attributeRelation = AttributeRelation::query()->from('fct_atts_relations as rel')
    ->join('fct_product_variations as var', 'var.id', '=', 'rel.object_id')
    ->join('posts as p', 'p.ID', '=', 'var.post_id')
    ->where('p.post_status', 'publish')
    ->select(['var.post_id'])
    ->first();
$attributeProduct = $attributeRelation ? Product::query()->with(['detail', 'variants'])->find((int) $attributeRelation->post_id) : null;

if (!$simpleProduct || !$attributeProduct) {
    echo "  (skipped: needs a published simple-variation product and one with attributes)\n";
    $test->finish();
}

// Simple Variations: Max Options, Show Row Label and Row Label reach the row.
$simpleRow = ProductAttributeSwatches::render($simpleProduct, [
    'swatch_simple_max_visible' => ['size' => 1, 'unit' => ''],
    'swatch_simple_show_label'  => 'yes',
    'swatch_simple_label'       => 'Pack Size',
]);
$test->check(strpos($simpleRow, 'Pack Size') !== false, 'Row Label captions the simple-variation row');
$visibleOptions = function ($html) {
    return substr_count($html, 'class="fct-card-swatch-text"');
};
$simpleDefault = ProductAttributeSwatches::render($simpleProduct, []);
$test->same($visibleOptions($simpleRow), 1, 'Max Options 1 shows one variant name');
$test->check($visibleOptions($simpleDefault) > 1, 'the default shows more than one variant name');

$test->check($simpleDefault !== '', 'simple-variation swatches show by default, as in Gutenberg');
$test->check(strpos($simpleDefault, 'Pack Size') === false, 'no row label by default');

$simpleOff = ProductAttributeSwatches::render($simpleProduct, ['swatch_simple_enabled' => '']);
$test->same($simpleOff, '', 'Simple Variations "Show Swatches" off hides the row');

// Advanced Variations: "Show Swatches" off hides attribute rows but not simple ones.
$test->check(ProductAttributeSwatches::render($attributeProduct, []) !== '', 'attribute swatches show by default');
$test->same(ProductAttributeSwatches::render($attributeProduct, ['swatch_advanced_enabled' => '']), '', 'Advanced Variations "Show Swatches" off hides the attribute rows');
$test->check(ProductAttributeSwatches::render($simpleProduct, ['swatch_advanced_enabled' => '']) !== '', 'turning off attribute rows keeps the simple-variation row');

$test->finish();
