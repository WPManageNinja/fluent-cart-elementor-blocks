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
$test->same(array_keys(ProductCardLayouts::options()), array_merge([''], array_keys(\FluentCart\App\Services\Renderer\ProductCardLayoutPresets::all())), 'the picker lists the card elements option, then core\'s layouts in core\'s order');

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

    // Masonry Grid: the widget's own card elements stagger in the same grid.
    $gridOwn = $render('fluent_cart_shop_app', $shopSettings + ['masonry_grid' => 'yes']);
    $test->check(strpos($gridOwn, 'data-fct-masonry') !== false && strpos($gridOwn, 'fct-card-layout') === false, 'Masonry Grid: the Products widget staggers its own cards');
    $gridLayout = $render('fluent_cart_shop_app', $shopSettings + ['masonry_grid' => 'yes', 'card_layout' => 'compact']);
    $test->check(strpos($gridLayout, 'data-fct-masonry') === false, 'Masonry Grid: a card layout keeps its own grid');
    $test->check(strpos($render('fluent_cart_shop_app', $shopSettings), 'data-fct-masonry') === false, 'Masonry Grid: off by default');
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
    $relatedGrid = $render('fluentcart_related_products', $relatedSettings + ['masonry_grid' => 'yes']);
    $test->check(!isset(\FluentCart\App\Services\Renderer\ProductCardLayoutPresets::all()['masonry']) || strpos($relatedGrid, 'data-fct-masonry') !== false, 'Masonry Grid: Related Products staggers its own cards');
    $relatedFramed = $render('fluentcart_related_products', $relatedSettings + ['card_layout' => 'framed', 'show_swatches' => 'yes', 'show_sale_badge' => 'yes']);
    $test->check(substr_count($relatedFramed, 'fct-card-layout--framed') > 0 && substr_count($relatedFramed, 'fct-card-layout--framed') === substr_count($relatedFramed, '<article'), 'Related Products framed: every card uses the layout');
    preg_match_all('/<article\b.*?<\/article>/s', $relatedFramed, $relatedCards);
    $test->same(count(array_filter($relatedCards[0], function ($card) {
        return substr_count($card, 'data-fct-card-swatch-rows') > 1 || substr_count($card, 'class="fct-sale-badge') > 1;
    })), 0, 'Related Products: a layout card gets no second swatch rows or Sale badge from the widget');
}

// Product Card widget: one product, drawn by the layout.
$cardProductId = (string) \FluentCart\App\Models\Product::query()->where('post_status', 'publish')->orderBy('ID', 'DESC')->value('ID');
$singleCustom = $render('fluent_cart_product_card', ['product_id' => $cardProductId]);
$test->check(strpos($singleCustom, 'fct-product-card') !== false && strpos($singleCustom, 'fct-card-layout') === false, 'Product Card: no layout by default');
foreach (['classic' => 'fct-product-card-template', 'floating' => 'fct-card-layout--floating'] as $layout => $marker) {
    $html = $render('fluent_cart_product_card', ['product_id' => $cardProductId, 'card_layout' => $layout, 'show_sale_badge' => 'yes']);
    $test->check(strpos($html, $marker) !== false, 'Product Card ' . $layout . ': the card uses the layout');
    $test->same(substr_count($html, '<article'), 1, 'Product Card ' . $layout . ': one card');
    $test->check(substr_count($html, 'class="fct-sale-badge') <= 1, 'Product Card ' . $layout . ': no second Sale badge from the widget');
}

$test->finish();
