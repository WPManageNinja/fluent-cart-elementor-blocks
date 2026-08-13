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
    const TEMPLATES_VERSION = '1.5.0';

    /** Option storing the last-seeded template-set version. */
    const VERSION_OPTION = 'fluent_cart_elementor_templates_version';

    /** Atomic seeding mutex (unique option_name = DB-level lock). */
    const LOCK_OPTION = 'fluent_cart_elementor_templates_seeding_lock';

    /** Seconds after which a lock left by a crashed pass is reclaimed. */
    const LOCK_TTL = 300;

    /** Transient throttling retries after a failed / incomplete pass. */
    const RETRY_COOLDOWN_KEY = 'fluent_cart_elementor_templates_seeding_retry';

    /** Seconds to wait before retrying after a failed / incomplete pass. */
    const RETRY_COOLDOWN = 900;

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

        // Back off after a failed / incomplete pass so a persistent problem (a
        // damaged bundled file, a permanent Elementor write error) does not
        // reprocess the whole manifest — filesystem reads + per-template DB
        // lookups + writes — on every admin page load. Retries resume after the
        // cooldown; a clean pass clears it. The cooldown stores the version it
        // was armed for, so a NEW template-set version bypasses it immediately
        // rather than waiting out a backoff from the previous version.
        if (get_transient(self::RETRY_COOLDOWN_KEY) === $version) {
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

            // loadAll() reports whether the on-disk set was read in full, so a
            // missing/unreadable/malformed manifest or a damaged bundled file is
            // NOT mistaken for a valid empty set. A validly-empty manifest is
            // `complete` and legitimately advances the gate below.
            $result    = TemplateManifest::loadAll();
            $templates = $result['templates'];
            $complete  = $result['complete'];

            $failed = false;
            foreach ($templates as $template) {
                // Renew the lock before each seed. This refreshes its timestamp
                // via an ownership-scoped CAS, so while we keep making progress
                // (each seed is far shorter than LOCK_TTL) the lock never looks
                // stale and cannot be reclaimed by another request — closing the
                // window where two passes both run the check-then-create path and
                // duplicate a library item (ownership is post-meta, not a DB
                // uniqueness constraint). If renewal fails we already lost the
                // lock to a reclaim, so stop writing immediately.
                if (!$this->renewLock()) {
                    $failed = true;
                    break;
                }

                if (TemplateSeeder::seed($template) === 'failed') {
                    $failed = true;
                }
            }

            // Advance the gate only when the on-disk set loaded in full, every
            // template seeded (or was a valid skip), AND we still hold the lock.
            // Anything else leaves the gate behind and backs off via the cooldown
            // — so retries recover eventually without hammering every admin load.
            // The per-item version meta keeps that retry idempotent.
            if ($complete && !$failed && $this->ownsLock()) {
                update_option(self::VERSION_OPTION, $version, false);
                delete_transient(self::RETRY_COOLDOWN_KEY);
            } else {
                set_transient(self::RETRY_COOLDOWN_KEY, $version, self::RETRY_COOLDOWN);
            }
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Whether this request still holds the seeding lock (its stored value is
     * unchanged). Returns false once another request reclaimed a stale lock from
     * us, so a slow / hung pass stops writing instead of racing the new owner.
     *
     * @return bool
     */
    private function ownsLock()
    {
        return $this->lockValue !== '' && get_option(self::LOCK_OPTION) === $this->lockValue;
    }

    /**
     * Refresh the lock's timestamp via an ownership-scoped compare-and-swap,
     * keeping our token. Returns true if we still own it (and its TTL is now
     * reset), false if another request already reclaimed it. Called before each
     * seed so an actively-progressing pass never lets its lock go stale.
     *
     * @return bool
     */
    private function renewLock()
    {
        global $wpdb;

        if ($this->lockValue === '') {
            return false;
        }

        $parts = explode('|', $this->lockValue, 2);
        $token = isset($parts[1]) ? $parts[1] : '';
        $next  = time() . '|' . $token;

        // Only refresh the row still holding our exact value; a reclaim will
        // have changed it, so this matches zero rows.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- ownership-scoped lock renewal CAS; cache cleared on the next line
        $renewed = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
                $next,
                self::LOCK_OPTION,
                $this->lockValue
            )
        );
        wp_cache_delete(self::LOCK_OPTION, 'options');

        if (1 === $renewed) {
            $this->lockValue = $next;
            return true;
        }

        // Zero rows changed is ambiguous: either this renew ran within the same
        // second as the last one (so $next already equals the stored value — a
        // no-op, still ours) or another request reclaimed the lock. Disambiguate
        // by reading the current value: if it is still ours we keep the lock.
        if (get_option(self::LOCK_OPTION) === $this->lockValue) {
            $this->lockValue = $next;
            return true;
        }

        return false;
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
