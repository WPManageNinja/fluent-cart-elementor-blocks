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

// Core adds layouts (Masonry), so the picker follows core's list rather than a fixed one.
// Classic ('') is the widget's own card, so core's Classic preset is not offered.
$test->same(array_keys(ProductCardLayouts::options()), array_merge([''], array_values(array_diff(array_keys(\FluentCart\App\Services\Renderer\ProductCardLayoutPresets::all()), ['classic']))), 'the picker lists Classic, then core\'s other layouts in core\'s order');

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
$test->check(strpos($classic, 'data-card-layout') === false && strpos($classic, 'data-template-provider="elementor"') !== false, 'a saved classic is the widget\'s own card');

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
foreach (['compact' => 'fct-card-layout--compact', 'framed' => 'fct-card-layout--framed'] as $layout => $marker) {
    $html = $render('fluent_cart_product_carousel', $carouselSettings + ['card_layout' => $layout]);
    $test->same(substr_count($html, $marker), count($carouselIds), 'Carousel ' . $layout . ': every slide uses the layout');
    $test->same(substr_count($html, '<article'), count($carouselIds), 'Carousel ' . $layout . ': one card per slide, not one inside another');
}
$carouselBadged = $render('fluent_cart_product_carousel', $carouselSettings + ['card_layout' => 'compact', 'show_sale_badge' => 'yes']);
preg_match_all('/<article\b.*?<\/article>/s', $carouselBadged, $carouselCards);
$test->same(count(array_filter($carouselCards[0], function ($card) {
    return substr_count($card, 'class="fct-sale-badge') > 1;
})), 0, 'Carousel: a layout card never shows two Sale badges');

// Masonry (fluent-cart#3503): the Compact card in a staggered grid; core's card script staggers the grid.
if (!isset(\FluentCart\App\Services\Renderer\ProductCardLayoutPresets::all()['masonry'])) {
    echo "  (skipped Masonry: this FluentCart has no Masonry layout)\n";
} else {
    $masonry = $render('fluent_cart_shop_app', $shopSettings + ['card_layout' => 'masonry']);
    $test->check(substr_count($masonry, 'fct-card-layout--masonry') === substr_count($masonry, '<article') && strpos($masonry, '<article') !== false, 'Masonry: every Products card uses the layout');
    $test->check(strpos($masonry, 'data-card-layout="masonry"') !== false, 'Masonry: AJAX pages keep the layout');
    $test->check(wp_script_is('fluent-cart-product-card-js', 'enqueued'), 'Masonry: the card script that staggers the grid is queued');
    $masonryCarousel = $render('fluent_cart_product_carousel', $carouselSettings + ['card_layout' => 'masonry']);
    $test->same(substr_count($masonryCarousel, 'fct-card-layout--masonry'), count($carouselIds), 'Masonry: every Carousel slide uses the layout');
    $masonryCard = $render('fluent_cart_product_card', ['product_id' => (string) \FluentCart\App\Models\Product::query()->where('post_status', 'publish')->orderBy('ID', 'DESC')->value('ID'), 'card_layout' => 'masonry']);
    $test->same(substr_count($masonryCard, 'fct-card-layout--masonry'), 1, 'Masonry: the Product Card widget draws one Masonry card');
}

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

// A layout card's Swatches, Badge and Product Rating switches (core's layout_options), on until switched off.
if (ProductCardLayouts::cardOptionsAvailable()) {
    $saleCards = function ($html) { return substr_count($html, 'class="fct-sale-badge'); };
    $wideShop = ['per_page' => 40, 'paginator' => 'numbers', 'card_layout' => 'floating'];
    $shopOn = $render('fluent_cart_shop_app', $wideShop);
    $shopOff = $render('fluent_cart_shop_app', $wideShop + ['layout_show_sale_badge' => '', 'layout_show_rating' => '']);
    $test->check($saleCards($shopOn) > 0, 'layout switches: a layout shows its Sale badge by default');
    $test->same($saleCards($shopOff), 0, 'layout switches: Show Sale Badge off removes it from the Products cards');
    $test->check(strpos($shopOn, 'fct-product-card-rating') !== false && strpos($shopOff, 'fct-product-card-rating') === false, 'layout switches: Show Rating off removes the rating');
    $test->check(strpos($shopOff, 'data-layout-options') !== false, 'layout switches: the grid hands them to later AJAX pages');

    $saleId = (string) \FluentCart\App\Models\ProductVariation::query()->whereColumn('compare_price', '>', 'item_price')->whereHas('product', function ($q) { $q->where('post_status', 'publish'); })->value('post_id');
    if ($saleId) {
        $carouselSale = ['product_ids' => [$saleId], 'card_layout' => 'compact'];
        $test->check($saleCards($render('fluent_cart_product_carousel', $carouselSale)) > 0 && $saleCards($render('fluent_cart_product_carousel', $carouselSale + ['layout_show_sale_badge' => ''])) === 0, 'layout switches: the Carousel follows Show Sale Badge');
        $test->check(strpos($render('fluent_cart_product_carousel', $carouselSale + ['layout_show_swatches' => '']), 'data-fct-card-swatch-rows') === false, 'layout switches: the Carousel follows Show Swatches');
        $cardSale = ['product_id' => $saleId, 'card_layout' => 'floating'];
        $test->check($saleCards($render('fluent_cart_product_card', $cardSale)) > 0 && $saleCards($render('fluent_cart_product_card', $cardSale + ['layout_show_sale_badge' => ''])) === 0, 'layout switches: the Product Card follows Show Sale Badge');
    }
    if ($relatedProductId) {
        $relatedOff = $render('fluentcart_related_products', $relatedSettings + ['card_layout' => 'compact', 'layout_show_rating' => '']);
        $test->check(strpos($relatedOff, 'fct-card-layout--compact') !== false && strpos($relatedOff, 'fct-product-card-rating') === false, 'layout switches: Related Products follows Show Rating');
    }
} else {
    echo "  (skipped layout switches: this FluentCart has no layout_options)\n";
}

// Product Card widget: one product, drawn by the layout.
$cardProductId = (string) \FluentCart\App\Models\Product::query()->where('post_status', 'publish')->orderBy('ID', 'DESC')->value('ID');
$singleCustom = $render('fluent_cart_product_card', ['product_id' => $cardProductId]);
$test->check(strpos($singleCustom, 'fct-product-card') !== false && strpos($singleCustom, 'fct-card-layout') === false, 'Product Card: no layout by default');
foreach (['floating' => 'fct-card-layout--floating'] as $layout => $marker) {
    $html = $render('fluent_cart_product_card', ['product_id' => $cardProductId, 'card_layout' => $layout, 'show_sale_badge' => 'yes']);
    $test->check(strpos($html, $marker) !== false, 'Product Card ' . $layout . ': the card uses the layout');
    $test->same(substr_count($html, '<article'), 1, 'Product Card ' . $layout . ': one card');
    $test->check(substr_count($html, 'class="fct-sale-badge') <= 1, 'Product Card ' . $layout . ': no second Sale badge from the widget');
}

$test->finish();
