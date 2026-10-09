<?php
/**
 * StyleHooks reports selectors a designer can actually use.
 *
 * The gap this guards: the settings schema tells an agent how to configure a
 * widget and nothing about how to style it, so building a storefront meant
 * inspecting the DOM to find class names. The two failures that cost the most
 * were the cart buttons, which emit WordPress button classes and silently take
 * the theme's colours, and the product image wrappers, which differ between
 * the card and the shop grid.
 *
 * Reads only; nothing is saved.
 *
 * Usage:  bash tests/bin/run-all.sh smoke
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/style-hooks.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

use FluentCartElementorBlocks\App\Modules\MCP\Support\BuilderRegistry;
use FluentCartElementorBlocks\App\Modules\MCP\Support\StyleHooks;

$test = new FceTest('smoke/style-hooks');

$test->check(class_exists(StyleHooks::class), 'StyleHooks is autoloaded');

$widgets  = array_filter(BuilderRegistry::widgets(), function ($row) {
    return $row['available'];
});
$test->check(count($widgets) >= 31, 'the widget catalog is available (' . count($widgets) . ')');

$rendered = 0;
$buttons  = [];
$bad      = [];

foreach ($widgets as $row) {
    $hooks = StyleHooks::forWidget($row['name']);

    if (!is_array($hooks)) {
        $bad[] = $row['name'] . ' returned no hooks block';
        continue;
    }

    if (!empty($hooks['rendered'])) {
        $rendered++;
    }

    // Anything reported must be FluentCart's own markup — reporting a theme's
    // class would send an agent off to restyle the whole site.
    foreach ($hooks['selectors'] as $selector) {
        if (!preg_match('/^\.(fct-|fluent-cart-|fluent_cart_)/', $selector)) {
            $bad[] = $row['name'] . ' reported a non-FluentCart selector: ' . $selector;
        }
    }

    foreach ($hooks['theme_styled_buttons'] as $button) {
        $buttons[$row['name']] = $button['selector'];
    }
}

$test->check(!$bad, 'every reported selector belongs to FluentCart' . ($bad ? ': ' . implode('; ', array_slice($bad, 0, 3)) : ''));
$test->check($rendered >= 25, "most widgets render and report hooks ({$rendered} of " . count($widgets) . ')');

// The three that take the theme's colours. Each must be scoped: a bare
// `.wp-block-button__link` repaints every button on the site, which is worse
// than saying nothing.
$expected = [
    'fluent_cart_add_to_cart',
    'fluent_cart_buy_now',
    'fluent_cart_customer_dashboard_button',
];

foreach ($expected as $name) {
    $selector = isset($buttons[$name]) ? $buttons[$name] : '';

    $test->check($selector !== '', "{$name} is flagged as theme-styled");
    $test->check(
        $selector !== '' && strpos($selector, ' ') !== false && strpos($selector, '.') === 0,
        "{$name} gives a scoped selector, not a bare button class ({$selector})"
    );
}

// The discovery that started this: these two are not the same class, and
// styling only one leaves the other unstyled.
$card = StyleHooks::forWidget('fluent_cart_product_card');
$test->check(
    in_array('.fct-product-card-image-wrap', $card['selectors'], true),
    'the product card reports its image wrapper'
);

$shop = StyleHooks::forWidget('fluent_cart_shop_app');
$test->check(!empty($shop['selectors']), 'the shop app reports selectors (' . count($shop['selectors']) . ')');

// Memoised: a second read must not re-render.
$first  = StyleHooks::forWidget('fluent_cart_mini_cart');
$second = StyleHooks::forWidget('fluent_cart_mini_cart');
$test->same($second, $first, 'repeat reads are stable');

$test->finish();
