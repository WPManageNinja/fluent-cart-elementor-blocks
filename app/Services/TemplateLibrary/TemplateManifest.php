<?php

namespace FluentCartElementorBlocks\App\Services\TemplateLibrary;

use FluentCart\Framework\Support\Arr;

/**
 * Reads and validates the bundled template set under templates/.
 *
 * The set is manifest-driven: manifest.json lists the template slugs, and each
 * slug is a self-contained directory:
 * - manifest.json  — metadata (slug, title, type, category, version, preview)
 * - template.json  — the Elementor export payload ({version,title,type,content,
 *                    page_settings}), i.e. exactly what "Export Template"
 *                    produces in the editor
 * - preview.webp   — optional library-card image (co-located, added when the
 *                    layout is authored)
 *
 * Adding a template later needs no code change:
 * 1. Create a new `<slug>/` directory.
 * 2. Add `manifest.json` + `template.json` (+ `preview.webp`).
 * 3. Add the slug to `templates/manifest.json`.
 *
 * This class only reads and normalizes — it does not seed. "Readiness" (a
 * non-empty content tree) is the seeder's concern, so an authored-but-empty
 * placeholder still loads here with an empty `content` array.
 */
class TemplateManifest
{
    /**
     * Per-request cache of the loaded template set — all() reads every
     * template directory, so repeat callers get the parsed result.
     *
     * @var array<int, array>|null
     */
    protected static $templates = null;

    /** Absolute path to the bundled template directory. */
    public static function dir()
    {
        return __DIR__ . '/templates';
    }

    /**
     * Absolute path to a template's directory (basename-guarded against path
     * traversal). Previews and the payload live inside it.
     *
     * @param string $slug
     * @return string
     */
    public static function templateDir($slug)
    {
        return self::dir() . '/' . basename($slug);
    }

    /**
     * Load + validate every template listed in manifest.json.
     *
     * Invalid or duplicate-slug entries are skipped (never fatal), so one bad
     * file cannot block the rest of the set. Third parties can add their own
     * templates via the filter.
     *
     * @return array<int, array> normalized template arrays
     */
    public static function all()
    {
        if (self::$templates !== null) {
            return self::$templates;
        }

        $result          = self::loadAll();
        self::$templates = $result['templates'];

        return self::$templates;
    }

    /**
     * Load the bundled set AND report whether the on-disk load was complete.
     *
     * `complete` is false when the root manifest is missing, unreadable, or
     * malformed, or when any slug it lists fails to load (a missing/unreadable/
     * invalid bundled file). It stays true for a validly-empty manifest and for
     * deliberately-empty template payloads (those load fine). The orchestrator
     * uses this to tell a real read/deploy failure apart from a valid empty set,
     * so it never records a transient failure as a finished seed.
     *
     * Third-party templates added via the filter never affect `complete` — they
     * are not bundled files, so an invalid one is simply dropped.
     *
     * @return array{templates: array<int, array>, complete: bool}
     */
    public static function loadAll()
    {
        $manifestFile = self::dir() . '/manifest.json';
        $complete     = true;
        $templates    = [];

        $raw = file_exists($manifestFile) ? file_get_contents($manifestFile) : false;

        if ($raw === false) {
            $complete = false; // root manifest missing or unreadable
            $entries  = [];
        } else {
            $entries = json_decode($raw, true);
            if (!is_array($entries)) {
                $complete = false; // malformed root manifest
                $entries  = [];
            }
        }

        foreach ($entries as $entry) {
            if (!is_string($entry) || $entry === '') {
                $complete = false; // bad index entry
                continue;
            }

            $template = self::load($entry);
            if ($template === null) {
                $complete = false; // a listed bundled entry failed to load/validate
                continue;
            }

            $templates[] = $template;
        }

        /**
         * Filter the bundled template set before seeding. Third parties may add
         * their own templates here.
         *
         * @param array $templates Normalized template arrays (slug, title, type,
         *                         category, version, preview, content,
         *                         page_settings, path).
         */
        $templates = apply_filters('fluent_cart_elementor/template_library/templates', $templates);

        // Re-normalize + de-duplicate AFTER the filter: entries added by third
        // parties bypass load()'s validation, so run every entry through the
        // same normalizer here. The seeder can then trust the array shape.
        $normalized = [];
        $seenSlugs  = [];

        if (is_array($templates)) {
            foreach ($templates as $entry) {
                $template = is_array($entry) ? self::normalize($entry) : null;
                if (!$template || isset($seenSlugs[$template['slug']])) {
                    continue;
                }
                $seenSlugs[$template['slug']] = true;
                $normalized[]                 = $template;
            }
        }

        return ['templates' => $normalized, 'complete' => $complete];
    }

    /**
     * Load a bundled template from its directory.
     *
     * Each template consists of:
     * - manifest.json (metadata)
     * - template.json (Elementor export payload)
     *
     * Both files are required; returns null when either is missing, unreadable,
     * or fails validation.
     *
     * @param string $slug Bare directory name inside templates/ (basename-
     *                     guarded against path traversal).
     * @return array|null
     */
    public static function load($slug)
    {
        $dir = self::templateDir($slug);

        $manifestFile = $dir . '/manifest.json';
        if (!file_exists($manifestFile)) {
            return null;
        }

        $manifestRaw = file_get_contents($manifestFile);
        if ($manifestRaw === false) {
            return null;
        }

        $data = json_decode($manifestRaw, true);
        if (!is_array($data)) {
            return null;
        }

        // The root index references each template by directory name, but the
        // seeded identity (ownership markers, de-dup key) comes from the
        // manifest's own `slug`. Enforce they match, so a copy-paste typo in the
        // inner manifest can't silently seed a template from one folder under a
        // different slug. Skip on divergence rather than fatal — one bad file
        // must not block the rest of the set.
        $folder = basename($slug);
        if (sanitize_key((string) Arr::get($data, 'slug', '')) !== $folder) {
            return null;
        }

        // Load the Elementor export payload from template.json. Keeping it in a
        // separate file (not inlined in the manifest) keeps the metadata small
        // and the element tree diffable on its own.
        $payloadFile = $dir . '/template.json';
        if (!file_exists($payloadFile)) {
            return null;
        }

        $payloadRaw = file_get_contents($payloadFile);
        if ($payloadRaw === false) {
            return null;
        }

        $payload = json_decode($payloadRaw, true);
        if (!is_array($payload)) {
            return null;
        }

        // The seeder feeds this exact file to Elementor's import_template(),
        // which remaps image references and writes the correctly-slashed
        // _elementor_data — so carry the path through, plus the decoded pieces
        // for validation/inspection.
        $data['path']          = $payloadFile;
        $data['content']       = Arr::get($payload, 'content', []);
        $data['page_settings'] = Arr::get($payload, 'page_settings', []);

        return self::normalize($data);
    }

    /**
     * Normalize + validate a raw template array (from a bundled file or the
     * filter). Returns null when the required fields are missing/invalid, so
     * downstream code can trust every returned entry's shape.
     *
     * @param array $data
     * @return array|null
     */
    public static function normalize(array $data)
    {
        // Accept the file's `template_version` key or an already-normalized
        // `version` key (entries added via the filter may use either).
        $rawVersion = Arr::get($data, 'template_version', Arr::get($data, 'version', ''));

        $slug     = sanitize_key((string) Arr::get($data, 'slug', ''));
        $title    = sanitize_text_field((string) Arr::get($data, 'title', ''));
        $type     = sanitize_key((string) Arr::get($data, 'type', ''));
        $category = sanitize_text_field((string) Arr::get($data, 'category', ''));
        $version  = sanitize_text_field((string) $rawVersion);
        // Optional preview image: bare filename inside the template's own
        // directory (basename-guarded against traversal). Empty = no thumbnail.
        $preview  = basename(sanitize_file_name((string) Arr::get($data, 'preview', '')));

        // content is the Elementor element tree (array of nodes) and
        // page_settings the page-level settings — both trusted, authored data,
        // stored as-is (an empty tree is a valid placeholder).
        $content      = Arr::get($data, 'content', []);
        $pageSettings = Arr::get($data, 'page_settings', []);
        $path         = (string) Arr::get($data, 'path', '');

        if ($slug === '' || $title === '' || $version === '') {
            return null;
        }

        // content must be an array (the element tree); page_settings too.
        if (!is_array($content) || !is_array($pageSettings)) {
            return null;
        }

        return [
            'slug'          => $slug,
            'title'         => $title,
            'type'          => $type !== '' ? $type : 'page',
            'category'      => $category !== '' ? $category : 'FluentCart',
            'version'       => $version,
            'preview'       => $preview,
            'content'       => $content,
            'page_settings' => $pageSettings,
            'path'          => $path,
        ];
    }
}
