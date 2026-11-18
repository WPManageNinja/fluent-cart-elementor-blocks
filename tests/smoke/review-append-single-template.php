<?php
/**
 * A product page that an Elementor Pro single template renders turns off core's review append, so the
 * Product Reviews widget is the only copy. Any other page keeps the append. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke review-append-single-template
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/review-append-single-template.php', ['\Elementor\Plugin', '\ElementorPro\Modules\ThemeBuilder\Module']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\ElementorIntegration;

$test = new FceTest('smoke/review-append-single-template');

$integration = new ElementorIntegration();
$callback = [$integration, 'maybeSkipReviewAppendForSingleTemplate'];
$test->check(is_callable($callback), 'the append gate is registered on the core filter');

$products = get_posts(['post_type' => 'fluent-products', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids']);
$test->check(!empty($products), 'a published product exists to test against');

$pages = get_posts(['post_type' => 'page', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids']);
$test->check(!empty($pages), 'a published page exists to test against');

if (empty($products) || empty($pages)) {
    $test->finish();
}

global $wp_query;
$wp_query = new WP_Query(['p' => (int) $products[0], 'post_type' => 'fluent-products']);
$wp_query->the_post();

$singleDocuments = \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_documents_for_location('single');
$test->check(!empty($singleDocuments), 'Elementor Pro has a single template for the product page');
$test->check(false === call_user_func($callback, true, (int) $products[0]), 'a product page under a single template does not get the appended reviews');

$wp_query = new WP_Query(['p' => (int) $pages[0], 'post_type' => 'page']);
$wp_query->the_post();
$test->check(true === call_user_func($callback, true, (int) $pages[0]), 'a page that is not a product keeps the appended reviews');

$test->finish();
