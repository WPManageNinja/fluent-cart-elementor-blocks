<?php
/**
 * Core's product card layouts in the Products widget. Reads only.
 *
 * Usage:  bash tests/bin/run-all.sh smoke card-layouts
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/card-layouts.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ProductCardLayouts;

$test = new FceTest('smoke/card-layouts');

if (!ProductCardLayouts::available()) {
    echo "  (skipped: this FluentCart has no product card layouts)\n";
    $test->finish();
}

$render = function ($widgetType, array $settings) {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fcecl' . substr(md5(wp_json_encode($settings)), 0, 4),
        'elType'     => 'widget',
        'widgetType' => $widgetType,
        'settings'   => $settings,
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};

$test->same(array_keys(ProductCardLayouts::options()), ['', 'classic', 'compact', 'floating', 'framed'], 'the picker lists the card elements option and core\'s four layouts');

$shopSettings = ['per_page' => 4, 'paginator' => 'numbers'];

$custom = $render('fluent_cart_shop_app', $shopSettings);
$test->check(strpos($custom, 'fct-product-card') !== false, 'the Products widget renders cards');
$test->check(strpos($custom, 'fct-card-layout') === false && strpos($custom, 'data-card-layout') === false, 'no layout by default: the widget\'s own card elements');
$test->check(strpos($custom, 'data-template-provider="elementor"') !== false, 'own card elements keep the Elementor pagination provider');

foreach (['compact', 'floating', 'framed'] as $layout) {
    $html = $render('fluent_cart_shop_app', $shopSettings + ['card_layout' => $layout]);
    $test->check(strpos($html, 'fct-card-layout--' . $layout) !== false, $layout . ': the cards use the layout');
    $test->check(strpos($html, 'data-card-layout="' . $layout . '"') !== false, $layout . ': the wrapper tells AJAX pages the layout');
    $test->check(strpos($html, 'data-template-provider="elementor"') === false, $layout . ': AJAX pages render through core, not the card elements');
}

$classic = $render('fluent_cart_shop_app', $shopSettings + ['card_layout' => 'classic']);
$test->check(strpos($classic, 'fct-product-card-template') !== false, 'classic: the cards use core\'s Classic template');

$unknown = $render('fluent_cart_shop_app', $shopSettings + ['card_layout' => 'not-a-layout']);
$test->check(strpos($unknown, 'data-card-layout') === false && strpos($unknown, 'data-template-provider="elementor"') !== false, 'an unknown layout falls back to the card elements');

// Layout cards draw their own Sale badge; the widget's badge setting must not add a second.
$badged = $render('fluent_cart_shop_app', $shopSettings + ['card_layout' => 'compact', 'show_sale_badge' => 'yes']);
preg_match_all('/<article\b.*?<\/article>/s', $badged, $cards);
$doubled = array_filter($cards[0], function ($card) {
    return substr_count($card, 'fct-sale-badge') > 1 && substr_count($card, 'class="fct-sale-badge') > 1;
});
$test->same(count($doubled), 0, 'a layout card never shows two Sale badges');

// Product Carousel: a layout replaces the card elements in every slide.
$carouselIds = array_map('strval', \FluentCart\App\Models\Product::query()->where('post_status', 'publish')->orderBy('ID', 'DESC')->limit(3)->pluck('ID')->all());
$carouselSettings = ['product_ids' => $carouselIds];
$carouselCustom = $render('fluent_cart_product_carousel', $carouselSettings);
$test->check(strpos($carouselCustom, 'fct-product-card') !== false && strpos($carouselCustom, 'fct-card-layout') === false, 'Carousel: no layout by default');
foreach (['classic' => 'fct-product-card-template', 'compact' => 'fct-card-layout--compact', 'framed' => 'fct-card-layout--framed'] as $layout => $marker) {
    $html = $render('fluent_cart_product_carousel', $carouselSettings + ['card_layout' => $layout]);
    $test->same(substr_count($html, $marker), count($carouselIds), 'Carousel ' . $layout . ': every slide uses the layout');
    $test->same(substr_count($html, '<article'), count($carouselIds), 'Carousel ' . $layout . ': one card per slide, not one inside another');
}
$carouselBadged = $render('fluent_cart_product_carousel', $carouselSettings + ['card_layout' => 'compact', 'show_sale_badge' => 'yes']);
preg_match_all('/<article\b.*?<\/article>/s', $carouselBadged, $carouselCards);
$test->same(count(array_filter($carouselCards[0], function ($card) {
    return substr_count($card, 'class="fct-sale-badge') > 1;
})), 0, 'Carousel: a layout card never shows two Sale badges');

// Related Products: needs core's [fluent_cart_related_products card_layout] (fluent-cart#3486).
$relatedProductId = 0;
foreach (\FluentCart\App\Models\Product::query()->where('post_status', 'publish')->orderBy('ID', 'DESC')->limit(40)->pluck('ID') as $candidateId) {
    if (\FluentCart\Api\Resource\ShopResource::getSimilarProducts((int) $candidateId, false)) {
        $relatedProductId = (int) $candidateId;
        break;
    }
}
if (!$relatedProductId) {
    echo "  (skipped Related Products: no product has related products)\n";
} else {
    $relatedSettings = ['source' => 'custom', 'product_id' => (string) $relatedProductId];
    $relatedCustom = $render('fluentcart_related_products', $relatedSettings);
    $test->check(strpos($relatedCustom, 'fct-product-card') !== false && strpos($relatedCustom, 'fct-card-layout') === false, 'Related Products: no layout by default');
    $relatedFramed = $render('fluentcart_related_products', $relatedSettings + ['card_layout' => 'framed', 'show_swatches' => 'yes', 'show_sale_badge' => 'yes']);
    $test->check(substr_count($relatedFramed, 'fct-card-layout--framed') > 0 && substr_count($relatedFramed, 'fct-card-layout--framed') === substr_count($relatedFramed, '<article'), 'Related Products framed: every card uses the layout');
    preg_match_all('/<article\b.*?<\/article>/s', $relatedFramed, $relatedCards);
    $test->same(count(array_filter($relatedCards[0], function ($card) {
        return substr_count($card, 'data-fct-card-swatch-rows') > 1 || substr_count($card, 'class="fct-sale-badge') > 1;
    })), 0, 'Related Products: a layout card gets no second swatch rows or Sale badge from the widget');
}

$test->finish();
