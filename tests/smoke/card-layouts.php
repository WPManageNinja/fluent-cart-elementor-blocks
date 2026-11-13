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

// The picker follows core's list rather than a fixed one. Classic ('') is the widget's own card, so core's Classic
// preset is not offered, and a builder-only layout (Masonry) is not either: every layout has the Masonry switch, as in Gutenberg.
$expected = array_keys(array_filter(\FluentCart\App\Services\Renderer\ProductCardLayoutPresets::all(), function ($preset) {
    return empty($preset['builder_only']);
}));
$test->same(array_keys(ProductCardLayouts::options()), array_merge([''], array_values(array_diff($expected, ['classic']))), 'the picker lists Classic, then core\'s other layouts in core\'s order, without the builder-only Masonry');

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

// Masonry is a switch on every layout now, as in Gutenberg, not a layout of its own. A widget saved with the Masonry
// layout (before release) falls back to Classic, as a Gutenberg card saved with it does.
$test->check(!isset(ProductCardLayouts::options()['masonry']), 'Masonry: not offered as a layout');
$savedMasonry = $render('fluent_cart_shop_app', $shopSettings + ['card_layout' => 'masonry']);
$test->check(strpos($savedMasonry, 'fct-card-layout') === false && strpos($savedMasonry, 'data-card-layout') === false && strpos($savedMasonry, 'fct-product-card') !== false, 'Masonry: a widget saved with it shows the widget\'s own Classic card');
$switched = $render('fluent_cart_shop_app', $shopSettings + ['card_layout' => 'compact', 'layout_masonry' => 'yes']);
$test->check(substr_count($switched, 'fct-card-masonry') === substr_count($switched, '<article') && strpos($switched, '<article') !== false, 'Masonry: the switch staggers every Compact card');
$test->check(wp_script_is('fluent-cart-product-card-js', 'enqueued'), 'Masonry: the card script that staggers the grid is queued');

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

// Classic and every layout share the same card settings (core's layout_options and card parts).
if (ProductCardLayouts::cardSettingsAvailable()) {
    $controlsOf = function ($widgetType) {
        $widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types($widgetType);

        return $widget ? $widget->get_controls() : [];
    };
    $compactParts = \FluentCart\App\Services\Renderer\ProductCardLayoutPresets::cardParts('compact');
    foreach (['fluent_cart_shop_app' => 'Products', 'fluent_cart_product_carousel' => 'Carousel', 'fluent_cart_product_card' => 'Product Card', 'fluentcart_related_products' => 'Related Products'] as $type => $name) {
        $c = $controlsOf($type);
        foreach (['sale_badge_settings_section', 'sold_out_badge_settings_section', 'layout_swatches_section', 'layout_rating_section', 'layout_quick_view_section'] as $section) {
            $test->check(isset($c[$section]) && ($c[$section]['tab'] ?? '') === 'content', "{$name}: {$section} in the Content tab");
        }
        $test->same(array_column($c['layout_elements_compact']['default'] ?? [], 'element_type'), $compactParts, "{$name}: Compact's Card Elements, in its order");
        $test->same([$c['layout_show_sale_badge']['default'] ?? null, $c['layout_sale_badge_text']['default'] ?? null, $c['show_sale_badge']['default'] ?? null], ['yes', 'Sale!', ''], "{$name}: a layout's badge is on with its own text; Classic's stays off until opted in");
        $test->same([$c['layout_swatches_max_visible_compact']['default'] ?? null, $c['layout_quick_view_position_framed']['default'] ?? null, $c['layout_quick_view_position_classic']['default'] ?? null], [2, 'button', 'image'], "{$name}: each layout's and Classic's own values");
        $test->check(!isset($c['layout_show_rating']) && !isset($c['layout_show_swatches']), "{$name}: swatches and rating are Card Elements, not switches");
        $test->same([$c['layout_quick_view_image_position_compact']['default'] ?? null, $c['layout_quick_view_image_position_classic']['default'] ?? null], ['bottom-center', 'bottom-center'], "{$name}: Quick View's image position starts at the block's bottom middle, for a layout and for Classic");
    }
    $test->same(array_column($controlsOf('fluent_cart_product_card')['card_elements']['default'] ?? [], 'element_type'), ['image', 'title', 'rating', 'price', 'swatches', 'button', 'quick_view'], 'Product Card: a new widget starts as core\'s Classic layout');

    $saleCards = function ($html) { return substr_count($html, 'class="fct-sale-badge'); };
    $rows = function (array $types) { return array_map(function ($type, $i) { return ['_id' => 'r' . $i, 'element_type' => $type]; }, $types, array_keys($types)); };
    $wideShop = ['per_page' => 40, 'paginator' => 'numbers', 'card_layout' => 'compact'];
    $shopOn = $render('fluent_cart_shop_app', $wideShop);
    $shopOff = $render('fluent_cart_shop_app', $wideShop + ['layout_show_sale_badge' => '', 'layout_elements_compact' => $rows(array_diff($compactParts, ['rating']))]);
    $test->check($saleCards($shopOn) > 0 && $saleCards($shopOff) === 0, 'layout settings: Show Sale Badge off removes it from the Products cards');
    $test->check(strpos($shopOn, 'fct-product-card-rating') !== false && strpos($shopOff, 'fct-product-card-rating') === false, 'layout settings: Rating left out of Card Elements removes it');
    $test->check(strpos($shopOff, 'data-layout-options') !== false, 'layout settings: the grid hands them to later AJAX pages');

    $saleId = (string) \FluentCart\App\Models\ProductVariation::query()->whereColumn('compare_price', '>', 'item_price')->whereHas('product', function ($q) { $q->where('post_status', 'publish'); })->value('post_id');
    if ($saleId) {
        $styled = $render('fluent_cart_product_carousel', ['product_ids' => [$saleId], 'card_layout' => 'compact', 'layout_sale_badge_text' => 'Deal', 'layout_sale_badge_style' => 'ribbon', 'layout_sale_badge_position' => 'top-right']);
        $test->check(strpos($styled, 'fct-sale-badge--ribbon fct-sale-badge--top-right') !== false && preg_match('/fct-sale-badge[^>]*>\s*Deal\s*</', $styled) === 1, 'layout settings: the Carousel uses the layout\'s badge text, style and position');
    }
    $variableId = (string) \FluentCart\App\Models\ProductDetail::query()->where('variation_type', '!=', 'simple')->whereHas('product', function ($q) { $q->where('post_status', 'publish'); })->value('post_id');
    if ($variableId) {
        $test->check(strpos($render('fluent_cart_product_card', ['product_id' => $variableId, 'card_layout' => 'compact', 'layout_quick_view_position_compact' => 'button']), 'data-position="button"') !== false, 'layout settings: Quick View moves beside the button');
        $onImage = $render('fluent_cart_product_card', ['product_id' => $variableId, 'card_layout' => 'compact']);
        $test->check(strpos($onImage, 'data-image-position="bottom-center"') !== false, 'layout settings: Quick View on the image starts at the bottom middle');
        $corner = $render('fluent_cart_product_card', ['product_id' => $variableId, 'card_layout' => 'compact', 'layout_quick_view_image_position_compact' => 'top-right']);
        $test->check(strpos($corner, 'data-image-position="top-right"') !== false, 'layout settings: Quick View moves to the chosen corner of the image');
        $beside = $render('fluent_cart_product_card', ['product_id' => $variableId, 'card_layout' => 'compact', 'layout_quick_view_position_compact' => 'button', 'layout_quick_view_image_position_compact' => 'top-right']);
        $test->check(strpos($beside, 'data-image-position') === false, 'layout settings: beside the button the image position is not used');
        // Classic: a widget saved with the new list draws Rating and Quick View with Classic's settings.
        $newList = $rows(['image', 'title', 'rating', 'price', 'swatches', 'button', 'quick_view']);
        $classicNew = $render('fluent_cart_product_card', ['product_id' => $variableId, 'card_elements' => $newList, 'layout_quick_view_position_classic' => 'button']);
        $test->check(strpos($classicNew, 'fct-card-layout') === false && strpos($classicNew, 'data-position="button"') !== false && strpos($classicNew, 'fct-product-card-stars') !== false, 'Classic: its Rating and Quick View settings reach the widget\'s own card');
        // The Product Card had no swatches before, so nothing else on the page queues their stylesheet.
        wp_dequeue_style('fluent-cart-product-swatches');
        $classicSwatches = $render('fluent_cart_product_card', ['product_id' => $variableId, 'card_elements' => $rows(['image', 'title', 'swatches'])]);
        $test->check(strpos($classicSwatches, 'fct-card-swatch-rows') !== false && wp_style_is('fluent-cart-product-swatches', 'enqueued'), 'Classic: a Product Card Swatches row queues the swatch stylesheet');
        $classicOld = $render('fluent_cart_product_card', ['product_id' => $variableId]);
        $test->check(strpos($classicOld, 'data-fct-card-quick-view') === false && strpos($classicOld, 'fct-product-card-stars') === false, 'Classic: a widget saved before keeps its own card');
        $carouselNew = $render('fluent_cart_product_carousel', ['product_ids' => [$variableId], 'card_elements' => $newList, 'swatch_show_label' => '', 'swatch_simple_show_label' => '', 'swatch_show_group_name' => '']);
        $test->check(strpos($carouselNew, 'data-fct-card-swatch-rows') !== false && strpos($carouselNew, 'fct-card-swatch-heading') === false, 'Classic: the Carousel\'s Swatches row uses the Swatches section');
        $shopNew = $render('fluent_cart_shop_app', ['per_page' => 40, 'paginator' => 'numbers', 'card_elements' => $newList, 'layout_rating_min_review_count_classic' => 100000]);
        $test->check(strpos($shopNew, 'data-template-provider="elementor"') !== false && strpos($shopNew, 'fct-product-card-stars') === false, 'Classic: the Products card follows Minimum Reviews, and keeps the Elementor pagination provider');
        // Page 2 redraws from the rows cached under the grid's client id, settings included.
        $shopButton = $render('fluent_cart_shop_app', ['per_page' => 40, 'paginator' => 'numbers', 'card_elements' => $newList, 'layout_quick_view_position_classic' => 'button']);
        preg_match('/data-fluent-client-id="([^"]+)"/', $shopButton, $client);
        $pageTwo = (new \FluentCartElementorBlocks\App\Modules\Integrations\Elementor\ElementorIntegration())->preloadProductCollectionsAjax('', [
            'client_id' => $client[1] ?? '',
            'products'  => [\FluentCart\App\Models\Product::query()->with(['detail', 'variants'])->find((int) $variableId)],
        ]);
        $test->check(strpos($pageTwo, 'data-fct-card-quick-view') !== false && strpos($pageTwo, 'data-position="button"') !== false, 'Classic: AJAX pages draw the Quick View row with Classic\'s settings');
    }
    if ($relatedProductId) {
        $relatedOff = $render('fluentcart_related_products', $relatedSettings + ['card_layout' => 'compact', 'layout_elements_compact' => $rows(array_diff($compactParts, ['rating']))]);
        $test->check(strpos($relatedOff, 'fct-card-layout--compact') !== false && strpos($relatedOff, 'fct-product-card-rating') === false, 'layout settings: Related Products follows its Card Elements');
        // Classic: one saved before keeps core's related card; a list draws this widget's own card in core's grid.
        $coreCard = '/data-fluent-cart-shop-app-single-product(?!-)/';
        $relatedOld = $render('fluentcart_related_products', $relatedSettings);
        $relatedNew = $render('fluentcart_related_products', $relatedSettings + ['card_elements' => $rows(['image', 'title', 'rating', 'price', 'swatches', 'button', 'quick_view']), 'show_sale_badge' => 'yes']);
        $relatedNoSwatches = $render('fluentcart_related_products', $relatedSettings + ['card_elements' => $rows(['image', 'title', 'swatches']), 'show_swatches' => '']);
        $test->check(preg_match($coreCard, $relatedOld) === 1 && strpos($relatedOld, 'data-fct-card-quick-view') === false, 'Related Products Classic: a widget saved before keeps core\'s related card');
        $test->check(preg_match($coreCard, $relatedNew) === 0 && strpos($relatedNew, 'fct-product-list-item') !== false && strpos($relatedNew, 'fct-product-card-rating') !== false, 'Related Products Classic: Card Elements draw its own card in core\'s grid');
        $test->check(strpos($relatedNoSwatches, 'fct-product-list-item') !== false && strpos($relatedNoSwatches, 'data-fct-card-swatch-rows') === false, 'Related Products Classic: Show Swatches off hides a Swatches row');
    }
} else {
    echo "  (skipped card settings: this FluentCart has no layout card settings)\n";
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
