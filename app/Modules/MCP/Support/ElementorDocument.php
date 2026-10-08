<?php

namespace FluentCartElementorBlocks\App\Modules\MCP\Support;

/**
 * Reads and writes an Elementor document's element tree.
 *
 * Elementor's own MCP refuses to modify V3 (classic) elements, and every
 * FluentCart widget is one — so placing a configured FluentCart widget means
 * writing `_elementor_data` ourselves. That is a supported WordPress
 * operation on our own widgets' settings; we deliberately never touch
 * elements belonging to Elementor or anyone else.
 */
class ElementorDocument
{
    const META_DATA = '_elementor_data';
    const META_EDIT = '_elementor_edit_mode';
    const META_TYPE = '_elementor_template_type';
    const META_BACKUP = '_fluent_cart_elementor_backup';

    /**
     * Document type per post type, matching what Elementor writes itself.
     *
     * A post Elementor has never opened has no template type, and a document
     * written without one is only half initialised — Elementor's own
     * create-page sets `wp-page`, ours was leaving it empty. `fluent-products`
     * maps to the per-product document our addon registers, which is a
     * different thing from the reusable `fluentcart-product` template.
     */
    const TYPE_BY_POST_TYPE = [
        'page'            => 'wp-page',
        'post'            => 'wp-post',
        'fluent-products' => 'fluentcart-product-post',
    ];

    /** The document tree, or a WP_Error. */
    public static function read($postId)
    {
        $postId = (int) $postId;

        if (!$postId || !get_post($postId)) {
            return MCPHelper::error(
                'post_not_found',
                sprintf(/* translators: %d: post id */ __('No post with id %d.', 'fluent-cart-elementor-blocks'), $postId)
            );
        }

        $raw = get_post_meta($postId, self::META_DATA, true);

        if (!$raw) {
            return [];
        }

        $data = json_decode($raw, true);

        if (!is_array($data)) {
            return MCPHelper::error(
                'invalid_document',
                __('This post\'s Elementor data could not be read. Open it in the Elementor editor once and save.', 'fluent-cart-elementor-blocks')
            );
        }

        return $data;
    }

    /**
     * Persist a tree.
     *
     * The previous value is kept in its own meta key first: a bad write to a
     * live layout is otherwise unrecoverable, and the agent cannot judge that
     * risk for the user.
     */
    public static function write($postId, array $tree)
    {
        $postId = (int) $postId;

        $previous = get_post_meta($postId, self::META_DATA, true);
        if ($previous) {
            update_post_meta($postId, self::META_BACKUP, $previous);
        }

        // Elementor stores this slashed; wp_slash keeps JSON escaping intact
        // through update_post_meta's unslashing.
        update_post_meta($postId, self::META_DATA, wp_slash(wp_json_encode($tree)));
        update_post_meta($postId, self::META_EDIT, 'builder');

        // Only when absent: an elementor_library template already carries its
        // own type and must not be rewritten.
        if (!get_post_meta($postId, self::META_TYPE, true)) {
            $postType = get_post_type($postId);

            if (isset(self::TYPE_BY_POST_TYPE[$postType])) {
                update_post_meta($postId, self::META_TYPE, self::TYPE_BY_POST_TYPE[$postType]);
            }
        }

        self::flushCss($postId);

        return true;
    }

    /**
     * Drop Elementor's generated CSS so the frontend reflects the new
     * settings. Without this the page serves the previous render's styles.
     */
    public static function flushCss($postId)
    {
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }

        if (class_exists('\Elementor\Core\Files\CSS\Post')) {
            $css = new \Elementor\Core\Files\CSS\Post((int) $postId);
            $css->delete();
        }

        $files = \Elementor\Plugin::$instance->files_manager;
        if ($files && method_exists($files, 'clear_cache')) {
            $files->clear_cache();
        }
    }

    /** Elementor element ids are 8 hex chars and unique within a document. */
    public static function newId(array $tree)
    {
        $taken = [];
        self::walk($tree, function ($node) use (&$taken) {
            if (!empty($node['id'])) {
                $taken[$node['id']] = true;
            }
        });

        do {
            $id = substr(md5(uniqid('fc', true)), 0, 8);
        } while (isset($taken[$id]));

        return $id;
    }

    /** Depth-first visit of every node. */
    public static function walk(array $nodes, callable $fn)
    {
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            $fn($node);

            if (!empty($node['elements']) && is_array($node['elements'])) {
                self::walk($node['elements'], $fn);
            }
        }
    }

    /** Find one node by id, by reference, so callers can mutate it. */
    public static function &find(array &$nodes, $id)
    {
        $null = null;

        foreach ($nodes as &$node) {
            if (!is_array($node)) {
                continue;
            }

            if (isset($node['id']) && $node['id'] === $id) {
                return $node;
            }

            if (!empty($node['elements']) && is_array($node['elements'])) {
                $found = &self::find($node['elements'], $id);
                if ($found !== null) {
                    return $found;
                }
                unset($found);
            }
        }

        return $null;
    }

    /** A widget node in the shape Elementor expects. */
    public static function widgetNode($id, $widgetType, array $settings)
    {
        return [
            'id'         => $id,
            'elType'     => 'widget',
            'widgetType' => $widgetType,
            'settings'   => (object) $settings,
            'elements'   => [],
        ];
    }

    /**
     * Append into $parentId, or at the document root.
     *
     * A widget cannot sit at the root of an Elementor document — it needs a
     * container — so a root-level insert gets wrapped, mirroring what
     * Elementor's own build tool does.
     */
    public static function append(array $tree, array $node, $parentId = null)
    {
        if ($parentId) {
            $parent = &self::find($tree, $parentId);

            if ($parent === null) {
                return MCPHelper::error(
                    'parent_not_found',
                    sprintf(/* translators: %s: element id */ __('No element "%s" in this document.', 'fluent-cart-elementor-blocks'), $parentId)
                );
            }

            if (!isset($parent['elements']) || !is_array($parent['elements'])) {
                $parent['elements'] = [];
            }

            $parent['elements'][] = $node;

            return $tree;
        }

        $tree[] = [
            'id'       => self::newId($tree),
            'elType'   => 'container',
            'settings' => (object) [],
            'elements' => [$node],
        ];

        return $tree;
    }

    /**
     * Remove nodes the predicate accepts, anywhere in the tree.
     *
     * Returns the pruned tree and the removed nodes, so the caller can report
     * exactly what went. Children of a removed node go with it.
     */
    public static function removeWhere(array $nodes, callable $match, array &$removed = [])
    {
        $kept = [];

        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }

            if ($match($node)) {
                $removed[] = $node;
                continue;
            }

            if (!empty($node['elements']) && is_array($node['elements'])) {
                $node['elements'] = self::removeWhere($node['elements'], $match, $removed);
            }

            $kept[] = $node;
        }

        return $kept;
    }

    /** Compact tree summary for responses — ids and types only. */
    public static function outline(array $tree)
    {
        $out = [];

        foreach ($tree as $node) {
            if (!is_array($node)) {
                continue;
            }

            $row = [
                'id'   => isset($node['id']) ? $node['id'] : null,
                'type' => !empty($node['widgetType']) ? $node['widgetType'] : (isset($node['elType']) ? $node['elType'] : null),
            ];

            // Key this `elements`, matching Elementor's own get-page-structure.
            // An agent reading both tools in one session should not have to
            // remember which one calls the child list something different.
            if (!empty($node['elements']) && is_array($node['elements'])) {
                $row['elements'] = self::outline($node['elements']);
            }

            $out[] = $row;
        }

        return $out;
    }
}
