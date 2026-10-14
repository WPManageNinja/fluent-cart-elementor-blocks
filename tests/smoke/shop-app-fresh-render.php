<?php
/**
 * The Shop App widget renders its products fresh each time, like core's
 * block: a render in a second currency shows that currency's prices, not
 * the first render's HTML. The second currency comes from core's own
 * filters (no Pro, no stored rates); reads only, nothing is saved.
 *
 * Usage:  bash tests/bin/run-all.sh smoke shop-app
 */

require __DIR__ . '/../lib/assert.php';

FceTest::requireLive('tests/smoke/shop-app-fresh-render.php', ['\Elementor\Plugin', '\FluentCart\App\Models\Product']);

$test = new FceTest('smoke/shop-app-fresh-render');

$context = '\FluentCart\App\Modules\MultiCurrency\Services\CurrencyContext';
if (!class_exists($context)) {
    echo "  (skipped: this FluentCart has no multi-currency)\n";
    $test->finish();
}

$base = \FluentCart\App\Modules\MultiCurrency\Services\BaseCurrency::get();
$quote = $base === 'EUR' ? 'GBP' : 'EUR';

// A second currency at twice the base price, the way Pro supplies one
$available = '__return_true';
$enabled = function ($codes) use ($quote) {
    return array_merge((array) $codes, [$quote]);
};
$rate = function ($answer, $rateContext = []) use ($quote) {
    if (strtoupper((string) ($rateContext['quote'] ?? '')) !== $quote) {
        return $answer;
    }

    return ['rate' => '2', 'rate_id' => null, 'source' => 'manual', 'margin' => '0', 'effective_at' => gmdate('Y-m-d H:i:s')];
};
add_filter('fluent_cart/currency/multi_currency_available', $available);
add_filter('fluent_cart/currency/enabled_currencies', $enabled);
add_filter('fluent_cart/currency/exchange_rate', $rate, 5, 2);

$render = function () {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id'         => 'fceshop',
        'elType'     => 'widget',
        'widgetType' => 'fluent_cart_shop_app',
        'settings'   => [],
    ]);
    ob_start();
    $element->print_element();

    return (string) ob_get_clean();
};

// Only the card prices: the rest of the HTML carries a per-render client ID.
// Each .fct-item-price's full text, nested screen-reader labels included.
$prices = function (string $html): array {
    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
    libxml_clear_errors();
    $found = [];
    foreach ((new \DOMXPath($dom))->query('//span[contains(concat(" ", normalize-space(@class), " "), " fct-item-price ")]') as $node) {
        $found[] = trim(preg_replace('/\s+/u', ' ', $node->textContent));
    }

    return $found;
};

// The amount alone, in minor units ("From $5,00" -> 500), whatever the separators
$amount = function (string $price): int {
    return (int) preg_replace('/\D/', '', $price);
};

$baseFirst = $prices($context::withCurrency($base, $render));
$baseAgain = $prices($context::withCurrency($base, $render));
$quoted = $prices($context::withCurrency($quote, $render));

remove_filter('fluent_cart/currency/multi_currency_available', $available);
remove_filter('fluent_cart/currency/enabled_currencies', $enabled);
remove_filter('fluent_cart/currency/exchange_rate', $rate, 5);

$baseSign = html_entity_decode(\FluentCart\App\Helpers\CurrenciesHelper::getCurrencySign($base));
$quoteSign = html_entity_decode(\FluentCart\App\Helpers\CurrenciesHelper::getCurrencySign($quote));
$firstQuoted = $quoted[0] ?? '';

$test->check(count($baseFirst) > 0, "the widget renders product prices ({$base})");
$test->same($baseAgain, $baseFirst, 'two renders in one currency show the same prices, so the comparison ignores per-render noise');
$test->check(strpos($baseFirst[0] ?? '', $baseSign) !== false, "the {$base} render shows {$baseSign} prices: " . ($baseFirst[0] ?? 'none'));
$test->check(strpos($firstQuoted, $quoteSign) !== false, "the {$quote} render shows {$quoteSign} prices, not the {$base} render served again: " . ($firstQuoted ?: 'none'));
$test->check($quoted !== $baseFirst, "the {$quote} prices differ from the {$base} prices");
$test->same(
    array_map($amount, $quoted),
    array_map(function ($price) use ($amount) {
        return $amount($price) * 2;
    }, $baseFirst),
    "each {$quote} price is the {$base} price at the test's rate of 2"
);

$test->finish();
