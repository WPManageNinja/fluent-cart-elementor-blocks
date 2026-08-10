<?php

namespace FluentCartElementorBlocks\App\Services\TemplateLibrary;

/**
 * Template Library orchestrator.
 *
 * Seeds the bundled FluentCart layouts into Elementor's native library
 * (`elementor_library`) once per template-set version. Runs version-gated on
 * `admin_init` (not an activation hook) so directly-updated / drifted installs
 * self-heal on the next admin load — the same "run on every load, gated by a
 * stored version" pattern the addon uses elsewhere.
 */
class TemplateLibrary
{
    /**
     * Bump this whenever the bundled template set changes (a new template, or a
     * new version of an existing one). The seeder runs a single pass the first
     * time the effective version (see effectiveVersion()) differs from the
     * stored option, then goes idle.
     */
    const TEMPLATES_VERSION = '1.0.0';

    /** Option storing the last-seeded template-set version. */
    const VERSION_OPTION = 'fluent_cart_elementor_templates_version';

    /** Atomic seeding mutex (unique option_name = DB-level lock). */
    const LOCK_OPTION = 'fluent_cart_elementor_templates_seeding_lock';

    /** Seconds after which a lock left by a crashed pass is reclaimed. */
    const LOCK_TTL = 300;

    /** The exact lock value this request wrote, used to release only our own. */
    private $lockValue = '';

    public function register()
    {
        add_action('admin_init', [$this, 'maybeSeed']);
    }

    /**
     * The version the gate compares against. Filterable so a plugin that adds
     * templates via `fluent_cart_elementor/template_library/templates` can also
     * bump this to trigger a re-seed on already-seeded installs — otherwise the
     * gate would stay satisfied and its templates would never be discovered.
     *
     * @return string
     */
    public function effectiveVersion()
    {
        $version = apply_filters('fluent_cart_elementor/template_library/version', self::TEMPLATES_VERSION);

        return is_string($version) && $version !== '' ? $version : self::TEMPLATES_VERSION;
    }

    /**
     * Seed the bundled templates if the stored version is behind. Cheap
     * early-outs keep the steady-state cost to a single get_option(); the actual
     * seeding pass is guarded by an atomic lock so two concurrent admin requests
     * cannot both insert the same template.
     *
     * @return void
     */
    public function maybeSeed()
    {
        if (wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        $version = $this->effectiveVersion();

        if (get_option(self::VERSION_OPTION) === $version) {
            return;
        }

        // Seeding writes posts + terms — require an admin-capable context.
        if (!current_user_can('edit_theme_options')) {
            return;
        }

        // Elementor's library CPT registers on `init`; if it is absent there is
        // nothing to seed into (e.g. Elementor inactive).
        if (!post_type_exists(TemplateSeeder::CPT)) {
            return;
        }

        // Atomic mutex: only one request seeds at a time. Without it, two admin
        // loads that both pass the gate could each find no existing row and
        // insert the same template, leaving duplicate library entries.
        if (!$this->acquireLock()) {
            return;
        }

        try {
            // Double-checked: another request may have finished seeding between
            // the gate check above and acquiring the lock.
            if (get_option(self::VERSION_OPTION) === $version) {
                return;
            }

            $templates = TemplateManifest::all();

            if (empty($templates)) {
                // Nothing to seed — record the version so we don't re-scan.
                update_option(self::VERSION_OPTION, $version, false);
                return;
            }

            $failed = false;
            foreach ($templates as $template) {
                if (TemplateSeeder::seed($template) === 'failed') {
                    $failed = true;
                }
            }

            // Advance the gate only on a clean pass; a failure retries on the
            // next admin load. The per-item version meta makes that retry
            // idempotent (already-seeded items are skipped), so it can never
            // duplicate entries.
            if (!$failed) {
                update_option(self::VERSION_OPTION, $version, false);
            }
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Acquire the seeding mutex. The lock value is "{timestamp}|{token}" with a
     * per-request unique token.
     *
     * Two exclusive acquire paths:
     *  1. add_option() is atomic (unique option_name index), so a fresh insert
     *     wins outright.
     *  2. If a lock already exists but is older than LOCK_TTL (left by a crashed
     *     pass), reclaim it with a compare-and-swap keyed on the EXACT stored
     *     value: a single conditional UPDATE. Only one racing request matches
     *     the old value and changes a row; the rest match zero rows because the
     *     value has already flipped.
     *
     * @return bool
     */
    private function acquireLock()
    {
        global $wpdb;

        $value = time() . '|' . self::lockToken();

        // Path 1: fresh insert wins the lock outright.
        if (add_option(self::LOCK_OPTION, $value, '', false)) {
            $this->lockValue = $value;
            return true;
        }

        // Path 2: reclaim only if the existing lock is stale.
        $current = get_option(self::LOCK_OPTION);
        if (!is_string($current) || $current === '') {
            return false;
        }

        $parts = explode('|', $current, 2);
        $since = (int) $parts[0];
        if ($since <= 0 || (time() - $since) <= self::LOCK_TTL) {
            return false; // a fresh lock is held by someone else
        }

        // Atomic CAS: succeeds for exactly one request. A direct query is
        // required — the Options API has no compare-and-swap; update_option()
        // would set unconditionally and let every racer take the lock. Values
        // are fully parameterized via $wpdb->prepare(); the object cache is
        // invalidated immediately below.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- atomic lock CAS; cache cleared on the next line
        $swapped = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
                $value,
                self::LOCK_OPTION,
                $current
            )
        );
        wp_cache_delete(self::LOCK_OPTION, 'options');

        if (1 === $swapped) {
            $this->lockValue = $value;
            return true;
        }

        return false;
    }

    /**
     * Release the lock only if this request still owns it (value unchanged) — so
     * a lock reclaimed from us after LOCK_TTL is never clobbered.
     *
     * @return void
     */
    private function releaseLock()
    {
        global $wpdb;

        if ($this->lockValue === '') {
            return;
        }

        // Direct query required — delete_option() is unconditional and would
        // wipe a lock another request legitimately reclaimed from us after the
        // TTL; this deletes only the row still holding our exact value. Fully
        // parameterized; object cache invalidated immediately below.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- ownership-scoped conditional delete; cache cleared on the next line
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
                self::LOCK_OPTION,
                $this->lockValue
            )
        );
        wp_cache_delete(self::LOCK_OPTION, 'options');
        $this->lockValue = '';
    }

    /**
     * Per-request unique lock token.
     *
     * @return string
     */
    private static function lockToken()
    {
        if (function_exists('wp_generate_uuid4')) {
            return wp_generate_uuid4();
        }

        return uniqid('fceb', true);
    }
}
