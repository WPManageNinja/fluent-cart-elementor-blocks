<?php

namespace FluentCartElementorBlocks\App\Services\TemplateLibrary;

use Elementor\Plugin as ElementorPlugin;

/**
 * Seeds one bundled template into Elementor's native library (the
 * `elementor_library` CPT), so it shows up in the "Insert Template →
 * My Templates" modal.
 *
 * Guarantees:
 *  - Idempotent + version-aware — re-running never duplicates an item, and an
 *    item is only replaced when the bundled copy's version is strictly newer.
 *  - Never touches user-created library items — matching is by a private meta
 *    marker this addon writes, never by title, so a user template that happens
 *    to share a name is safe.
 *
 * Writes go through Elementor's own document layer (`save_item()` on create,
 * `$document->save()` on update) rather than hand-writing post meta: that is
 * what correctly `wp_slash`es the JSON into `_elementor_data`, assigns the
 * `elementor_library_type` term, and sets `_elementor_edit_mode`. We only add
 * our ownership markers and the branded category term on top.
 *
 * Note on images: the bundled store templates are dynamic layouts (product
 * media is pulled from the store at render time), so they carry no hardcoded
 * attachment references and need no image remapping. A future template that
 * bundles static images should instead be routed through Elementor's
 * `import_template()`, which downloads and re-maps those attachments.
 */
class TemplateSeeder
{
    /** Elementor's library CPT. */
    const CPT = 'elementor_library';

    /** Elementor's category taxonomy (admin "Filter by category"). */
    const CATEGORY_TAXONOMY = 'elementor_library_category';

    /** Private markers identifying an item this addon created. */
    const META_SLUG = '_fluent_cart_elementor_template_slug';
    const META_VERSION = '_fluent_cart_elementor_template_version';

    /**
     * Create or update one template in the library.
     *
     * @param array $template Normalized template (see TemplateManifest::load()).
     * @return string 'created' | 'updated' | 'skipped' | 'failed'
     */
    public static function seed(array $template)
    {
        // Readiness: an empty element tree is an unauthored placeholder. The
        // loader still returns it (it is a pure reader); there is nothing to
        // seed until the layout is authored.
        if (empty($template['content'])) {
            return 'skipped';
        }

        if (!self::localSource()) {
            return 'failed';
        }

        $existing = self::findOwnItem($template['slug']);

        if ($existing) {
            $installed = (string) get_post_meta($existing, self::META_VERSION, true);

            // Version-aware: only replace when the bundled copy is strictly newer.
            if ($installed !== '' && version_compare($template['version'], $installed, '<=')) {
                return 'skipped';
            }

            return self::update($existing, $template) ? 'updated' : 'failed';
        }

        return self::create($template) ? 'created' : 'failed';
    }

    /**
     * Find a library item THIS addon previously seeded for the slug. Matching on
     * our private meta marker (not the title) is what guarantees we never
     * collide with a user-created library item.
     *
     * @param string $slug
     * @return int|null post ID
     */
    private static function findOwnItem($slug)
    {
        $ids = get_posts([
            'post_type'        => self::CPT,
            'post_status'      => 'any',
            'numberposts'      => 1,
            'fields'           => 'ids',
            'no_found_rows'    => true,
            'suppress_filters' => true,
            'meta_key'         => self::META_SLUG,
            'meta_value'       => $slug,
        ]);

        return !empty($ids) ? (int) $ids[0] : null;
    }

    /**
     * Create a new library item via Elementor's local source, then stamp our
     * ownership markers and the branded category.
     *
     * @param array $template
     * @return int|false New post ID on success, false on failure.
     */
    private static function create(array $template)
    {
        $source = self::localSource();
        if (!$source) {
            return false;
        }

        // save_item() creates an elementor_library document: it writes
        // _elementor_edit_mode, _elementor_template_type, the
        // elementor_library_type term, and the correctly-slashed _elementor_data.
        $result = $source->save_item([
            'title'         => $template['title'],
            'type'          => $template['type'],
            'content'       => $template['content'],
            'page_settings' => $template['page_settings'],
        ]);

        if (is_wp_error($result) || !$result) {
            return false;
        }

        $postId = (int) $result;

        // If the ownership markers don't persist, the item is unmanageable —
        // findOwnItem() can't see it, so the next pass would create a duplicate.
        // Remove the half-created post and fail so the gate does not advance.
        if (!self::stampOwnership($postId, $template)) {
            wp_delete_post($postId, true);
            return false;
        }

        self::applyCategory($postId, $template['category']);

        return $postId;
    }

    /**
     * Update an existing owned item in place — rewrite its element tree and
     * bump the stored version.
     *
     * @param int   $postId
     * @param array $template
     * @return bool
     */
    private static function update($postId, array $template)
    {
        // Re-verify ownership before mutating. update() is only reached for
        // items carrying our marker, but never trust that blindly.
        if ((string) get_post_meta($postId, self::META_SLUG, true) !== $template['slug']) {
            return false;
        }

        $document = ElementorPlugin::$instance->documents->get($postId);
        if (!$document) {
            return false;
        }

        // save() re-encodes + wp_slash's the element tree into _elementor_data.
        // It returns false when the write did NOT happen (e.g. the document
        // isn't editable by the current user). Bail before stamping the version:
        // otherwise a failed write would still bump the installed-version meta,
        // the gate would advance, and the stale content would never be repaired
        // — a failed partial update marked complete.
        if (!$document->save([
            'elements' => $template['content'],
            'settings' => $template['page_settings'],
        ])) {
            return false;
        }

        // Title may have changed between versions. wp_update_post unslashes, so
        // pass it slashed. A failure here also aborts before the version stamp.
        $updated = wp_update_post([
            'ID'         => $postId,
            'post_title' => wp_slash($template['title']),
        ], true);

        if (is_wp_error($updated) || !$updated) {
            return false;
        }

        // Stamp the version LAST — its presence is what marks the item fully
        // updated. If the markers don't persist, fail so the gate stays behind
        // and the next admin load retries (idempotently) rather than recording
        // the item as updated with a stale/missing version.
        if (!self::stampOwnership($postId, $template)) {
            return false;
        }

        // Category is cosmetic (admin filter only), so it runs after the version
        // stamp and never fails the update.
        self::applyCategory($postId, $template['category']);

        return true;
    }

    /**
     * Write the private ownership markers (slug + version) that identify this as
     * an item this addon manages, and confirm they persisted.
     *
     * Returns whether BOTH markers now hold the intended values. We verify with
     * get_post_meta() rather than trusting update_post_meta()'s return, because
     * that returns false both on a genuine write failure AND when the stored
     * value is already identical — so its return alone can't tell success from
     * failure. The caller treats a false here as a failed seed, so the version
     * gate never advances over an item whose markers did not stick (which would
     * leave it unmanageable — findOwnItem() could never see it again).
     *
     * @param int   $postId
     * @param array $template
     * @return bool
     */
    private static function stampOwnership($postId, array $template)
    {
        update_post_meta($postId, self::META_SLUG, $template['slug']);
        update_post_meta($postId, self::META_VERSION, $template['version']);

        return (string) get_post_meta($postId, self::META_SLUG, true) === (string) $template['slug']
            && (string) get_post_meta($postId, self::META_VERSION, true) === (string) $template['version'];
    }

    /**
     * Assign the branded category term so all bundled templates group under one
     * "Filter by category" entry in the admin Templates list. Best-effort — the
     * Insert-Template modal filters by type, not category, so a failure here is
     * cosmetic and never fails the seed.
     *
     * @param int    $postId
     * @param string $category
     * @return void
     */
    private static function applyCategory($postId, $category)
    {
        if ($category === '' || !taxonomy_exists(self::CATEGORY_TAXONOMY)) {
            return;
        }

        wp_set_object_terms($postId, $category, self::CATEGORY_TAXONOMY, false);
    }

    /**
     * Elementor's local template source, or null when Elementor isn't loaded.
     *
     * @return \Elementor\TemplateLibrary\Source_Local|null
     */
    private static function localSource()
    {
        if (!class_exists('\Elementor\Plugin') || !ElementorPlugin::$instance) {
            return null;
        }

        $manager = ElementorPlugin::$instance->templates_manager;
        if (!$manager) {
            return null;
        }

        $source = $manager->get_source('local');

        return $source ?: null;
    }
}
