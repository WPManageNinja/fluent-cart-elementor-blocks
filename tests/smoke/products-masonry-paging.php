<?php
/**
 * A Classic Products grid with Masonry keeps the stagger on its AJAX pages (page 2 onwards), as page 1 does.
 * Reads only: the transients it writes are its own, under a test client id, and are removed at the end.
 *
 * Usage:  bash tests/bin/run-all.sh smoke products-masonry-paging
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/products-masonry-paging.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCart\Framework\Support\Collection;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\ElementorIntegration;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Renderers\ElementorShopAppRenderer;

$test = new FceTest('smoke/products-masonry-paging');

$clientId = 'fcepmp' . substr(md5((string) microtime(true)), 0, 6);
$cardElements = [['_id' => 'ti1', 'element_type' => 'title']];

// Page 1 caches what the AJAX pages need, as the widget does on every render.
new ElementorShopAppRenderer([], ['card_elements' => $cardElements, 'client_id' => $clientId, 'masonry' => true]);

$products = \FluentCart\App\Models\Product::query()->where('post_status', 'publish')->limit(2)->get();
$test->check($products->count() === 2, 'the store has two published products to page through');

// The AJAX pages render through the same hook, from the cached settings.
$page2 = (new ReflectionClass(ElementorIntegration::class))->newInstanceWithoutConstructor()
    ->preloadProductCollectionsAjax(null, ['products' => $products, 'client_id' => $clientId]);

$test->same(substr_count((string) $page2, 'data-fct-product-card'), 2, 'page 2 draws every card');
$test->same(substr_count((string) $page2, 'fct-card-masonry'), 2, 'page 2 keeps the Masonry stagger on every card, as page 1');

// A widget without Masonry does not stagger its later pages either.
new ElementorShopAppRenderer([], ['card_elements' => $cardElements, 'client_id' => $clientId . 'n', 'masonry' => false]);
$plain = (new ReflectionClass(ElementorIntegration::class))->newInstanceWithoutConstructor()
    ->preloadProductCollectionsAjax(null, ['products' => $products, 'client_id' => $clientId . 'n']);
$test->same(substr_count((string) $plain, 'fct-card-masonry'), 0, 'with Masonry off, no later page is staggered');

delete_transient('fc_el_collection_' . $clientId);
delete_transient('fc_el_masonry_' . $clientId);
delete_transient('fc_el_collection_' . $clientId . 'n');
delete_transient('fc_el_masonry_' . $clientId . 'n');

$test->finish();
