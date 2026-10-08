<?php
/**
 * Renders every FluentCart Elementor widget with its default settings on a real
 * product: no exceptions, no PHP warnings from this add-on, and the key markup of
 * the product and review widgets. Reads only; nothing is saved.
 *
 * Usage:  bash tests/bin/run-all.sh smoke
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/render-widgets.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

$test = new FceTest('smoke/render-widgets');

$widgets = array_filter(
    \Elementor\Plugin::$instance->widgets_manager->get_widget_types(),
    function ($widget, $name) {
        return strpos($name, 'fluent_cart_') === 0 || strpos($name, 'fluentcart_') === 0;
    },
    ARRAY_FILTER_USE_BOTH
);
$test->check(count($widgets) >= 31, 'all FluentCart widgets are registered (' . count($widgets) . ')');

// The most-reviewed published product, as the current post, the way a product template renders.
$productId = (int) \FluentCart\App\Models\ProductReview::query()
    ->where('status', 'approved')->whereNull('parent_id')
    ->selectRaw('post_id, COUNT(*) as review_total')->groupBy('post_id')
    ->orderByDesc('review_total')->limit(1)->value('post_id');
if (!$productId || get_post_status($productId) !== 'publish') {
    $productId = (int) \FluentCart\App\Models\Product::query()->where('post_status', 'publish')->orderBy('ID', 'DESC')->value('ID');
}
$test->check($productId > 0, "found a product to render ({$productId})");

global $post;
$post = get_post($productId);
setup_postdata($post);

$expect = [
    'fluentcart_product_title'          => get_the_title($productId),
    'fluentcart_product_rating'         => 'fct-star',
    'fluentcart_product_reviews'        => 'fct-product-reviews-section',
    'fluentcart_product_review_summary' => 'fct-reviews-average-number',
    'fluentcart_product_review_list'    => 'fct-review-item',
    'fluentcart_write_a_review_button'  => 'fct-review-cta-btn',
];

$warnings = [];
set_error_handler(function ($severity, $message, $file, $line) use (&$warnings) {
    if (strpos($file, 'fluent-cart-elementor-blocks') !== false) {
        $warnings[] = basename($file) . ":{$line} {$message}";
    }
    return false;
});

$rendered = 0;
foreach ($widgets as $name => $widget) {
    $before = count($warnings);
    $level = ob_get_level();
    $error = '';
    $html = '';

    try {
        $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
            'id'         => substr(md5($name), 0, 7),
            'elType'     => 'widget',
            'widgetType' => $name,
            'settings'   => [],
        ]);
        ob_start();
        $element->print_element();
        $html = (string) ob_get_clean();
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }

    while (ob_get_level() > $level) {
        ob_end_clean();
    }

    $test->check($error === '', "{$name} renders without an exception" . ($error ? ": {$error}" : ''));
    $test->check(count($warnings) === $before, "{$name} raises no PHP warnings" . (count($warnings) > $before ? ': ' . implode('; ', array_slice($warnings, $before, 3)) : ''));

    if (trim($html) !== '') {
        $rendered++;
    }

    if (isset($expect[$name])) {
        $test->check(strpos($html, $expect[$name]) !== false, "{$name} prints " . $expect[$name]);
    }
}

restore_error_handler();
wp_reset_postdata();

$test->check($rendered >= 20, "most widgets print output with their default settings ({$rendered} of " . count($widgets) . ')');

$test->finish();
