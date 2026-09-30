<?php
/**
 * A bundled template deleted from the library comes back on request, not
 * on its own.
 *
 * The seeder leaves a deleted template deleted. The Saved Templates screen
 * lists the missing ones and, on a click, seedMissing() creates those and
 * only those — a present template is not touched, and nothing duplicates.
 *
 * Usage:  wp eval-file tests/template-library-self-heal.php
 * Runs against the live library: it seeds (idempotently), deletes one
 * seeded item, and adds it back. It touches only items this addon created.
 */
if (!defined('ABSPATH')) {
    fwrite(STDERR, "Run via: wp eval-file tests/template-library-self-heal.php\n");
    exit(1);
}

use FluentCartElementorBlocks\App\Services\TemplateLibrary\TemplateLibrary;
use FluentCartElementorBlocks\App\Services\TemplateLibrary\TemplateManifest;
use FluentCartElementorBlocks\App\Services\TemplateLibrary\TemplateSeeder;

if (!post_type_exists(TemplateSeeder::CPT)) {
    echo "SKIP: Elementor's library post type is not registered.\n";
    exit(1);
}

$admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
if (!$admins) {
    echo "SKIP: no administrator to seed as.\n";
    exit(1);
}
wp_set_current_user((int) $admins[0]);

$failures = 0;
$checks   = 0;
$check    = function ($cond, $label) use (&$failures, &$checks) {
    $checks++;
    echo ($cond ? "  ok  " : "  FAIL ") . $label . "\n";
    if (!$cond) {
        $failures++;
    }
};

$ownItems = function () {
    return get_posts([
        'post_type'        => TemplateSeeder::CPT,
        'post_status'      => 'any',
        'numberposts'      => -1,
        'fields'           => 'ids',
        'no_found_rows'    => true,
        'suppress_filters' => true,
        'meta_key'         => TemplateSeeder::META_SLUG,
    ]);
};

$library   = new TemplateLibrary();
$templates = TemplateManifest::loadAll()['templates'];
$expected  = count($templates);

echo "Template library: adding back a deleted template ({$expected} bundled templates)\n";

// Start from a full library, whatever an earlier run or a merchant left.
$library->maybeSeed();
$library->seedMissing();
$check(count($ownItems()) === $expected, 'the library holds exactly one item per template');
$check($library->missingTemplates() === [], 'nothing is missing');
$check($library->seedMissing() === [], 'so the action creates nothing');

$slug    = $templates[0]['slug'];
$deleted = TemplateSeeder::ownItemId($slug);
$others  = array_diff($ownItems(), [$deleted]);
wp_delete_post($deleted, true);
$check(get_post($deleted) === null, "seeded item '{$slug}' deleted from the library");

$library->maybeSeed();
$check(!TemplateSeeder::ownItemId($slug), 'an admin load does not bring it back on its own');

$missing = $library->missingTemplates();
$check(count($missing) === 1 && $missing[0]['slug'] === $slug, 'the notice would list exactly that template');

$created = $library->seedMissing();
$check($created === [$templates[0]['title']], 'the action reports the one it created');
$restored = TemplateSeeder::ownItemId($slug);
$check($restored && $restored !== $deleted, "'{$slug}' is back as a new library item");
$check(array_diff($others, $ownItems()) === [], 'the other items are untouched');
$check(count($ownItems()) === $expected, 'still exactly one item per template');
$check($library->seedMissing() === [], 'a second click creates nothing');

// An outdated copy is offered for update, and updated in place on request.
$shopId = TemplateSeeder::ownItemId($slug);
update_post_meta($shopId, TemplateSeeder::META_VERSION, '0.0.1');
$outdated = $library->outdatedTemplates();
$check(count($outdated) === 1 && $outdated[0]['post_id'] === $shopId && $outdated[0]['installed'] === '0.0.1', 'a copy stamped older than the bundle is listed as outdated');
$check($library->updateTemplate('no-such-slug') === null, 'an unknown slug updates nothing');
$updated = $library->updateTemplate($slug);
$check(is_array($updated) && $updated['post_id'] === $shopId && $updated['version'] === $templates[0]['version'], 'the update keeps the post ID and reports the bundled version');
$check(TemplateSeeder::installedVersion($shopId) === $templates[0]['version'], 'the version stamp is the bundled one');
$check($library->outdatedTemplates() === [], 'nothing is outdated any more');
$check($library->updateTemplate($slug) === null, 'a second update has nothing to do');
$check(count($ownItems()) === $expected, 'still exactly one item per template');

echo "\n{$checks} checks, {$failures} failures\n";
exit($failures ? 1 : 0);
