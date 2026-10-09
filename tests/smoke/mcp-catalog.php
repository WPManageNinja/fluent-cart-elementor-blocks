<?php
/**
 * Smoke test for the catalogue MCP abilities (CatalogTools).
 *
 * Standalone on purpose: it loads no fixtures and creates nothing outside its
 * own throwaway attribute group, so it runs against a real install without the
 * disposable-DB tiers. Products are only ever read.
 *
 * Usage:
 *   wp eval-file wp-content/plugins/fluent-cart/tests/smoke/mcp-catalog.php
 */

use FluentCart\App\Models\AttributeGroup;
use FluentCart\App\Models\AttributeTerm;
use FluentCartElementorBlocks\App\Modules\MCP\Tools\CatalogTools;

if (!class_exists(CatalogTools::class)) {
    echo "CatalogTools is not autoloaded — run composer dump-autoload -o.\n";
    return;
}

// WP-CLI runs with no current user, so every write ability would be refused
// as forbidden and the capability checks would be the only thing under test.
$admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);

if (!$admins) {
    echo "No administrator to run as; the write abilities cannot be exercised.\n";
    return;
}

wp_set_current_user((int) $admins[0]);

$pass = 0;
$fail = 0;
$check = function ($condition, $label) use (&$pass, &$fail) {
    if ($condition) {
        $pass++;
        echo "  ok   {$label}\n";
    } else {
        $fail++;
        echo "  FAIL {$label}\n";
    }
};

$isError = function ($result) {
    return is_wp_error($result) || (is_array($result) && isset($result['error']));
};
$errorCode = function ($result) {
    if (is_wp_error($result)) {
        return $result->get_error_code();
    }

    return is_array($result) ? (isset($result['error']['code']) ? $result['error']['code'] : '') : '';
};
$data = function ($result) {
    return is_array($result) && isset($result['data']) ? $result['data'] : [];
};

echo "[smoke/mcp-catalog]\n";

// --- read ------------------------------------------------------------------

$groups = $data(CatalogTools::listGroups(['include_terms' => false]));
$check(!empty($groups['groups']), 'list-attribute-groups returns the seeded library (' . count($groups['groups']) . ')');

$hasSystem = false;
foreach ($groups['groups'] as $row) {
    if (!empty($row['is_system'])) {
        $hasSystem = true;
        break;
    }
}
$check($hasSystem, 'system groups are flagged so an agent reuses rather than duplicates them');

$colour = null;
foreach ($groups['groups'] as $row) {
    if ($row['type'] === 'color') {
        $colour = $row;
        break;
    }
}
$check($colour !== null, 'a colour-typed group is reported with type=color');
$check($colour === null || in_array($colour['styling'], ['swatch', 'dropdown'], true), 'styling is normalised to swatch or dropdown');

// --- validation that protects the renderer ---------------------------------

$bad = CatalogTools::manageGroup(['action' => 'create', 'title' => 'Smoke Bad Type', 'type' => 'rainbow']);
$check($isError($bad) && $errorCode($bad) === 'invalid_type', 'an unknown group type is refused');

$noTitle = CatalogTools::manageGroup(['action' => 'create']);
$check($isError($noTitle) && $errorCode($noTitle) === 'title_required', 'creating a group with no title is refused');

// --- create a throwaway group and exercise the term rules ------------------

$created = $data(CatalogTools::manageGroup([
    'action' => 'create', 'title' => 'Smoke Colourway', 'type' => 'color', 'styling' => 'swatch',
]));
$groupId = isset($created['group']['group_id']) ? (int) $created['group']['group_id'] : 0;
$check($groupId > 0, 'created a throwaway colour group');

if ($groupId) {
    // A colour group whose term has no hex draws an empty dot — the exact
    // failure that looks like a rendering bug rather than missing data.
    $missingHex = CatalogTools::manageTerms([
        'action' => 'create', 'group_id' => $groupId, 'terms' => [['title' => 'No Hex']],
    ]);
    $check(
        $isError($missingHex) && $errorCode($missingHex) === 'term_color_required',
        'a colour term with no hex is refused, naming the group'
    );

    $badHex = CatalogTools::manageTerms([
        'action' => 'create', 'group_id' => $groupId, 'terms' => [['title' => 'Bad', 'color' => 'not-a-colour']],
    ]);
    $check($isError($badHex), 'a malformed hex is refused');

    $tooMany = CatalogTools::manageTerms([
        'action'   => 'create',
        'group_id' => $groupId,
        'terms'    => array_fill(0, 11, ['title' => 'x', 'color' => '#112233']),
    ]);
    $check($isError($tooMany) && $errorCode($tooMany) === 'too_many_terms', 'more than ten terms in one call is refused');

    $terms = $data(CatalogTools::manageTerms([
        'action' => 'create', 'group_id' => $groupId,
        'terms'  => [
            ['title' => 'Smoke Mushroom', 'color' => '#B5A897'],
            ['title' => 'Smoke Anthracite', 'color' => '#3A3A3C'],
        ],
    ]));
    $check(count($terms['terms']) === 2, 'created two colour terms');
    // sanitize_hex_color preserves case, so the contract is a faithful
    // round-trip rather than a lowercased one.
    $check(
        isset($terms['terms'][0]['color']) && strtolower($terms['terms'][0]['color']) === '#b5a897',
        'the hex round-trips intact (' . (isset($terms['terms'][0]['color']) ? $terms['terms'][0]['color'] : 'none') . ')'
    );

    // Two terms with the same title must not collide on slug.
    $dupe = $data(CatalogTools::manageTerms([
        'action' => 'create', 'group_id' => $groupId,
        'terms'  => [['title' => 'Smoke Mushroom', 'color' => '#C0B5A0']],
    ]));
    $check(
        isset($dupe['terms'][0]['slug']) && $dupe['terms'][0]['slug'] !== $terms['terms'][0]['slug'],
        'a repeated term title gets a distinct slug'
    );

    // Switching a group's type must report which terms that invalidates.
    $switched = $data(CatalogTools::manageGroup([
        'action' => 'update', 'group_id' => $groupId, 'type' => 'image',
    ]));
    $check(
        isset($switched['terms_incomplete']) && count($switched['terms_incomplete']) === 3,
        'switching colour group to image reports the terms now missing an image'
    );

    $deleteNoConfirm = $data(CatalogTools::manageGroup(['action' => 'delete', 'group_id' => $groupId]));
    $check(!empty($deleteNoConfirm['dry_run']), 'deleting a group without confirm only reports');

    $deleted = CatalogTools::manageGroup(['action' => 'delete', 'group_id' => $groupId, 'confirm' => true]);
    $check(!$isError($deleted), 'the throwaway group deletes with confirm');
    $check(
        AttributeGroup::query()->find($groupId) === null,
        'the group is gone'
    );
    $check(
        AttributeTerm::query()->where('group_id', $groupId)->count() === 0,
        'its terms went with it'
    );
}

// --- system groups are protected -------------------------------------------

$system = AttributeGroup::query()->where('is_system', 1)->first();
if ($system) {
    $refused = CatalogTools::manageGroup(['action' => 'delete', 'group_id' => $system->id, 'confirm' => true]);
    $check($isError($refused) && $errorCode($refused) === 'system_group', 'a built-in group cannot be deleted even with confirm');
}

// --- product variation diagnostics ------------------------------------------

$product = \FluentCart\App\Models\Product::query()->where('post_status', 'publish')->orderBy('ID', 'DESC')->first();

if ($product) {
    $v = $data(CatalogTools::getVariations(['product_id' => $product->ID]));
    $check(isset($v['variants']), "get-product-variations reads product {$product->ID}");
    $check(isset($v['findings']) && is_array($v['findings']), 'it reports findings rather than leaving them to be inferred');

    // A dry run must never write.
    $before = \FluentCart\App\Models\ProductVariation::query()->where('post_id', $product->ID)->count();
    $dry = $data(CatalogTools::manageVariations([
        'product_id' => $product->ID,
        'variants'   => [['title' => 'Smoke Should Not Exist', 'price_cents' => 100]],
        'dry_run'    => true,
    ]));
    $after = \FluentCart\App\Models\ProductVariation::query()->where('post_id', $product->ID)->count();
    $check(!empty($dry['dry_run']), 'manage-product-variations dry run reports a plan');
    $check($before === $after, 'the dry run wrote nothing');
}

$missing = CatalogTools::getVariations(['product_id' => 99999999]);
$check($isError($missing) && $errorCode($missing) === 'product_not_found', 'an unknown product id is refused');

$badType = CatalogTools::manageVariations(['product_id' => $product ? $product->ID : 1, 'variation_type' => 'nonsense']);
$check($isError($badType), 'an unknown variation_type is refused');

echo "\n[smoke/mcp-catalog] {$pass} passed, {$fail} failed\n";

if ($fail > 0) {
    exit(1);
}
