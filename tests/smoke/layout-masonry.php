<?php
/**
 * The Masonry switch on the Products and Related Products widgets' layout cards. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke layout-masonry
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/layout-masonry.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;

$test = new FceTest('smoke/layout-masonry');

// Not a skip: a run without core's card layouts has nothing to prove, so it must not pass.
$test->check(ProductCardLayouts::available(), 'this FluentCart ships the product card layouts');
if (!ProductCardLayouts::available()) {
    $test->finish();
}

$render = function ($widgetType, array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fcemm' . substr(md5(wp_json_encode($settings)), 0, 4),
        'elType'     => 'widget',
        'widgetType' => $widgetType,
        'settings'   => $settings,
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};
$controlsOf = function ($widgetType) {
    return \Elementor\Plugin::$instance->widgets_manager->get_widget_types($widgetType)->get_controls();
};

// Gutenberg shows the switch only where cards sit in a grid: Products and Related Products.
foreach (['fluent_cart_shop_app' => 'Products', 'fluentcart_related_products' => 'Related Products'] as $type => $name) {
    $control = $controlsOf($type)['layout_masonry'] ?? [];
    $test->same([$control['type'] ?? null, $control['default'] ?? null], ['switcher', ''], "{$name}: a Masonry switch, off by default");
    $layouts = (array) ($control['condition']['card_layout'] ?? []);
    // Classic too: Gutenberg's Classic card can be staggered (fluent-cart 2c7a41e64c), and so can the widget's own card.
    $test->same($layouts, array_keys(ProductCardLayouts::options()), "{$name}: shown for every layout, Classic included");
    $test->same(array_diff($layouts, array_keys(ProductCardLayouts::options())), [], "{$name}: only for layouts the picker offers");
}
foreach (['fluent_cart_product_carousel' => 'Carousel', 'fluent_cart_product_card' => 'Product Card'] as $type => $name) {
    $test->check(!isset($controlsOf($type)['layout_masonry']), "{$name}: no Masonry switch, as in Gutenberg");
}

// A value left in a widget with no switch (copied from a Products widget) does nothing.
$carouselProductId = (string) \FluentCart\App\Models\Product::query()->where('post_status', 'publish')->orderBy('ID', 'DESC')->value('ID');
$stray = ['card_layout' => 'compact', 'layout_masonry' => 'yes'];
$test->same(substr_count($render('fluent_cart_product_carousel', $stray + ['product_ids' => [$carouselProductId]]), 'fct-card-masonry'), 0, 'Carousel: a stray Masonry value is ignored');
$test->same(substr_count($render('fluent_cart_product_card', $stray + ['product_id' => $carouselProductId]), 'fct-card-masonry'), 0, 'Product Card: a stray Masonry value is ignored');

$shop = ['per_page' => 4, 'paginator' => 'numbers'];
$test->same(substr_count($render('fluent_cart_shop_app', $shop + ['card_layout' => 'compact']), 'fct-card-masonry'), 0, 'Products: a layout is not staggered by default');
$stagger = $render('fluent_cart_shop_app', $shop + ['card_layout' => 'compact', 'layout_masonry' => 'yes']);
$test->check(substr_count($stagger, 'fct-card-masonry') > 0, 'Products: Masonry staggers the layout cards');
$test->check(strpos($stagger, '&quot;masonry&quot;:true') !== false, 'Products: later AJAX pages keep Masonry');
$classicCards = $render('fluent_cart_shop_app', $shop + ['layout_masonry' => 'yes']);
$test->same(substr_count($classicCards, 'fct-card-masonry'), substr_count($classicCards, '<article'), 'Products: Classic cards stagger with the switch');
$test->same(substr_count($render('fluent_cart_shop_app', $shop), 'fct-card-masonry'), 0, 'Products: Classic cards are not staggered by default');

$relatedProductId = 0;
foreach (\FluentCart\App\Models\Product::query()->where('post_status', 'publish')->orderBy('ID', 'DESC')->limit(40)->pluck('ID') as $candidateId) {
    if (\FluentCart\Api\Resource\ShopResource::getSimilarProducts((int) $candidateId, false)) {
        $relatedProductId = (int) $candidateId;
        break;
    }
}
$test->check($relatedProductId > 0, 'the store has a product with related products');
if ($relatedProductId) {
    $related = ['source' => 'custom', 'product_id' => (string) $relatedProductId, 'card_layout' => 'compact'];
    $test->same(substr_count($render('fluentcart_related_products', $related), 'fct-card-masonry'), 0, 'Related Products: a layout is not staggered by default');
    $test->check(substr_count($render('fluentcart_related_products', $related + ['layout_masonry' => 'yes']), 'fct-card-masonry') > 0, 'Related Products: Masonry staggers the layout cards');
    // A widget with its own Card Elements draws the Classic card itself; one saved without them keeps core's card,
    // which the switch does not reach yet.
    $classic = ['source' => 'custom', 'product_id' => (string) $relatedProductId, 'card_elements' => [
        ['_id' => 'ri', 'element_type' => 'image'], ['_id' => 'rt', 'element_type' => 'title'], ['_id' => 'rp', 'element_type' => 'price'], ['_id' => 'rb', 'element_type' => 'button'],
    ]];
    $relatedClassic = $render('fluentcart_related_products', $classic + ['layout_masonry' => 'yes']);
    $test->check(substr_count($relatedClassic, 'fct-card-masonry') > 0 && substr_count($relatedClassic, 'fct-card-masonry') === substr_count($relatedClassic, '<article'), 'Related Products: Classic cards stagger with the switch');
    $test->same(substr_count($render('fluentcart_related_products', $classic), 'fct-card-masonry'), 0, 'Related Products: Classic cards are not staggered by default');
}

$test->finish();
