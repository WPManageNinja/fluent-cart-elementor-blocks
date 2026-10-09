<?php
/**
 * The review widgets' Show reviews from setting: the current product, selected
 * products (one or several) and all products, rendered the way the Gutenberg
 * review blocks render them. Reads only; nothing is saved.
 *
 * Usage:  bash tests/bin/run-all.sh smoke review-product-sources
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/review-product-sources.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

$test = new FceTest('smoke/review-product-sources');

if (!\FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ReviewSupport::coreHasMultiProductReviews()) {
    $test->check(true, 'skipped: this FluentCart core has no multi-product reviews');
    $test->finish();
}

// The two most-reviewed published products that take reviews.
$productIds = \FluentCart\App\Models\ProductReview::query()
    ->where('status', 'approved')->whereNull('parent_id')
    ->selectRaw('post_id, COUNT(*) as review_total')->groupBy('post_id')
    ->orderByDesc('review_total')->limit(10)->pluck('post_id')->all();
$productIds = array_values(array_slice(array_filter(array_map('intval', $productIds), function ($id) {
    return get_post_status($id) === 'publish'
        && \FluentCart\App\Services\ProductReviewService::isReviewEnabledForProduct($id);
}), 0, 2));
$test->check(count($productIds) === 2, 'found two reviewed products (' . implode(', ', $productIds) . ')');
list($first, $second) = $productIds + [0, 0];

$render = function ($widgetType, array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => substr(md5($widgetType . wp_json_encode($settings)), 0, 7),
        'elType'     => 'widget',
        'widgetType' => $widgetType,
        'settings'   => $settings,
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};

$totalIn = function ($html) {
    return preg_match('#data-reviews-total>\s*Based on ([\d,]+) reviews#', $html, $match)
        ? (int) str_replace(',', '', $match[1])
        : -1;
};

$combined = \FluentCart\App\Services\ProductReviewService::getProductsRatingSummary(['product_ids' => [$first, $second]]);
$everything = \FluentCart\App\Services\ProductReviewService::getProductsRatingSummary([]);
$pair = ['source' => 'custom', 'product_id' => [(string) $first, (string) $second]];

// Rating Summary: one card added up across the selection.
$html = $render('fluentcart_product_review_summary', $pair);
$test->same($totalIn($html), (int) $combined['total'], 'Rating Summary combines the two selected products');
$test->check(strpos($html, 'data-scope="all"') !== false, 'the combined card refreshes from the all-products endpoint');

$html = $render('fluentcart_product_review_summary', ['source' => 'all']);
$test->same($totalIn($html), (int) $everything['total'], 'Rating Summary for all products counts every product');

// Product Reviews: list and combined summary, nothing that writes a review.
$html = $render('fluentcart_product_reviews', $pair);
$test->check(strpos($html, 'data-post-id="0"') !== false, 'Product Reviews lists several products under post id 0');
$test->same($totalIn($html), (int) $combined['total'], 'Product Reviews shows the combined summary');
$test->check(strpos($html, 'fct-reviews-summary-write-btn') === false, 'Product Reviews has no Write a Review button for several products');
preg_match_all('#data-review-post-id="(\d+)"#', $html, $rows);
$test->check($rows[1] && !array_diff(array_map('intval', $rows[1]), [$first, $second]), 'only the selected products\' reviews are listed');

$html = $render('fluentcart_product_reviews', ['source' => 'all']);
$test->check(strpos($html, 'data-scope="all"') !== false && $totalIn($html) === (int) $everything['total'], 'Product Reviews for all products');

// Review List: the block's own multi-product query.
$html = $render('fluentcart_product_review_list', $pair);
$test->check(strpos($html, 'data-scope="all"') !== false, 'Review List lists several products');
preg_match_all('#data-review-post-id="(\d+)"#', $html, $rows);
$test->check($rows[1] && !array_diff(array_map('intval', $rows[1]), [$first, $second]), 'Review List shows only the selected products');

// One selected product, saved as one id (older widgets) or a list of one, stays single-product.
foreach (['one id' => (string) $first, 'a list of one' => [(string) $first]] as $label => $value) {
    $html = $render('fluentcart_product_reviews', ['source' => 'custom', 'product_id' => $value]);
    $test->check(strpos($html, 'data-post-id="' . $first . '"') !== false, "one product saved as {$label} renders that product");
    $test->check(strpos($html, 'data-scope="all"') === false, "one product saved as {$label} is not a multi-product list");
}

// A selection with nothing valid shows nothing on the page, never every review.
$html = $render('fluentcart_product_reviews', ['source' => 'custom', 'product_id' => ['abc', '0']]);
$test->check(strpos($html, 'fct-review-item') === false && strpos($html, 'data-scope="all"') === false, 'an empty selection renders no reviews');

$test->finish();
