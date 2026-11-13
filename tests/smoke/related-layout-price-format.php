<?php
/**
 * The Related Products widget's Price Format, for a layout card. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke related-layout-price-format
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/related-layout-price-format.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;

$test = new FceTest('smoke/related-layout-price-format');

// Not a skip: a run without core's card layouts has nothing to prove, so it must not pass.
$test->check(ProductCardLayouts::available(), 'this FluentCart ships the product card layouts');
if (!ProductCardLayouts::available()) {
    $test->finish();
}

$render = function (array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fcerp' . substr(md5(wp_json_encode($settings)), 0, 4),
        'elType'     => 'widget',
        'widgetType' => 'fluentcart_related_products',
        'settings'   => $settings,
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};

$control = \Elementor\Plugin::$instance->widgets_manager->get_widget_types('fluentcart_related_products')->get_controls()['price_format'] ?? [];
$test->same([$control['type'] ?? null, $control['default'] ?? null], ['select', 'starts_from'], 'Related Products: a Price Format, starting at Starts From');
$layouts = (array) ($control['condition']['card_layout'] ?? []);
$test->same($layouts, array_values(array_diff(array_keys(ProductCardLayouts::options()), [''])), "Related Products: shown for the layouts the picker offers, never for Classic's own card");

$relatedProductId = 0;
foreach (\FluentCart\App\Models\Product::query()->where('post_status', 'publish')->orderBy('ID', 'DESC')->limit(40)->pluck('ID') as $candidateId) {
    if (\FluentCart\Api\Resource\ShopResource::getSimilarProducts((int) $candidateId, false)) {
        $relatedProductId = (int) $candidateId;
        break;
    }
}
$test->check($relatedProductId > 0, 'the store has a product with related products');

if ($relatedProductId) {
    // The card's price block reads the format from the block context, so watch that.
    $formatsFor = function (array $settings) use ($render) {
        $seen = [];
        $watch = function ($context) use (&$seen) {
            if (isset($context['fluent-cart/price_format'])) {
                $seen[] = $context['fluent-cart/price_format'];
            }

            return $context;
        };
        add_filter('render_block_context', $watch, 10, 1);
        $render($settings);
        remove_filter('render_block_context', $watch, 10);

        return array_values(array_unique($seen));
    };
    $related = ['source' => 'custom', 'product_id' => (string) $relatedProductId, 'card_layout' => 'compact'];
    $test->same($formatsFor($related), ['starts_from'], 'a layout card starts at Starts From, as before');
    $test->same($formatsFor($related + ['price_format' => 'range']), ['range'], 'Range reaches the layout cards');
    $test->same($formatsFor(['source' => 'custom', 'product_id' => (string) $relatedProductId, 'price_format' => 'range']), [], "Classic's own card does not use the setting");
}

$test->finish();
