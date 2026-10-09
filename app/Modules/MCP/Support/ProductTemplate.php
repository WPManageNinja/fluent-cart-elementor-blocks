<?php

namespace FluentCartElementorBlocks\App\Modules\MCP\Support;

/**
 * The Elementor Pro Theme Builder document that renders single product pages.
 *
 * Creating the document is Elementor's job — its `manage-site-parts` tool
 * already offers `fluentcart-product` as a type and handles the display
 * conditions, so duplicating that here would fight their tooling. What is
 * missing is the FluentCart side: whether a template exists, whether it is
 * live, and which of our widgets it actually contains.
 *
 * Templates are `elementor_library` posts carrying `_elementor_template_type`
 * = the document type our Elementor addon registers (FluentCartProduct).
 */
class ProductTemplate
{
    const DOCUMENT_TYPE = 'fluentcart-product';
    const CONDITION     = 'include/fluentcart_product';

    /**
     * Taxonomy archives (product categories and brands) are a separate
     * Theme Builder document: Elementor's own `archive` type, carrying the
     * sub-condition our addon registers. Verified live — this string applies.
     */
    const ARCHIVE_DOCUMENT_TYPE = 'archive';
    const ARCHIVE_CONDITION     = 'include/archive/fluentcart_product_archive';

    /** Every FluentCart product template on the site. */
    public static function all()
    {
        return self::templatesOfType(self::DOCUMENT_TYPE);
    }

    /**
     * Archive templates that actually target FluentCart.
     *
     * The `archive` document type is Elementor's and is used for any archive,
     * so the type alone proves nothing — only a FluentCart condition does.
     */
    public static function archives()
    {
        return array_values(array_filter(
            self::templatesOfType(self::ARCHIVE_DOCUMENT_TYPE),
            function ($row) {
                foreach ($row['conditions'] as $condition) {
                    if (strpos((string) $condition, 'fluentcart') !== false) {
                        return true;
                    }
                }

                return false;
            }
        ));
    }

    private static function templatesOfType($documentType)
    {
        $posts = get_posts([
            'post_type'      => 'elementor_library',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'numberposts'    => 20,
            'meta_key'       => '_elementor_template_type',
            'meta_value'     => $documentType,
            'suppress_filters' => false,
        ]);

        $rows = [];

        foreach ($posts as $post) {
            $conditions = get_post_meta($post->ID, '_elementor_conditions', true);

            $rows[] = [
                'post_id'    => $post->ID,
                'title'      => $post->post_title,
                'status'     => $post->post_status,
                'published'  => $post->post_status === 'publish',
                'conditions' => is_array($conditions) ? $conditions : [],
                'widgets'    => self::fluentCartWidgets($post->ID),
                'edit_url'   => admin_url('post.php?post=' . $post->ID . '&action=elementor'),
            ];
        }

        return $rows;
    }

    /**
     * Create the Theme Builder document for single product pages.
     *
     * Elementor's manage-site-parts can do this, but only as one step of
     * several — and any of them going wrong fails silently: a template with no
     * condition never applies, and a second published template makes edits to
     * the first look like they did nothing. Doing it all here means "build me
     * a product page" is one call that cannot half-work.
     *
     * The taxonomy term matters as well as the meta — Elementor's Theme
     * Builder listing reads the term, which is why Angie ships a sync tool.
     */
    public static function createDocument($title, array $conditions = null)
    {
        $postId = wp_insert_post([
            'post_title'   => $title,
            'post_type'    => 'elementor_library',
            'post_status'  => 'draft',
            'post_content' => '',
        ]);

        if (is_wp_error($postId) || !$postId) {
            return MCPHelper::error('template_create_failed', __('Could not create the template document.', 'fluent-cart-elementor-blocks'));
        }

        update_post_meta($postId, '_elementor_template_type', self::DOCUMENT_TYPE);
        update_post_meta($postId, '_elementor_edit_mode', 'builder');
        update_post_meta($postId, '_elementor_conditions', $conditions === null ? [self::CONDITION] : $conditions);

        if (taxonomy_exists('elementor_library_type')) {
            wp_set_object_terms($postId, self::DOCUMENT_TYPE, 'elementor_library_type');
        }

        self::flushConditions();

        return $postId;
    }

    /** Published templates already claiming product pages. */
    public static function liveTemplates()
    {
        return array_values(array_filter(self::all(), function ($t) {
            return $t['published'] && !empty($t['conditions']);
        }));
    }

    public static function unpublish($postId)
    {
        wp_update_post(['ID' => (int) $postId, 'post_status' => 'draft']);
        self::flushConditions();
    }

    /**
     * Rebuild Elementor Pro's cached map of which template claims which location.
     *
     * Publishing a template through the editor updates this cache; publishing it
     * by changing post_status does not. Without a rebuild a freshly built
     * template is live, correctly conditioned, and silently not applied — the
     * page just keeps rendering FluentCart's own output, which reads as "the
     * template was ignored" rather than "a cache is stale".
     *
     * Deleting the option is NOT invalidation, and that mistake is expensive:
     * Conditions_Cache::refresh() reads get_option($key, []) and nothing ever
     * rebuilds it lazily, so a missing option means "no template claims any
     * location". The entire Theme Builder goes dark site-wide — header, footer
     * and every unrelated template, not just FluentCart's. Regenerating is what
     * Elementor Pro's own documents do (see Documents\Section::save).
     */
    public static function flushConditions()
    {
        if (class_exists('\ElementorPro\Modules\ThemeBuilder\Module')) {
            $module = \ElementorPro\Modules\ThemeBuilder\Module::instance();

            if ($module && method_exists($module, 'get_conditions_manager')) {
                $manager = $module->get_conditions_manager();

                if ($manager && method_exists($manager, 'get_cache')) {
                    // regenerate() persists on its own.
                    $manager->get_cache()->regenerate();
                }
            }
        }

        if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->files_manager) {
            \Elementor\Plugin::$instance->files_manager->clear_cache();
        }
    }

    /**
     * Two ways to build a product page, and they are a genuine trade-off.
     *
     * `individual` gives each part its own Elementor widget, so each can be
     * styled separately — but the order is fixed by where they sit in the
     * containers. `composite` is one widget whose `summary_sections` repeater
     * reorders the detail rows at will, at the cost of styling them as a
     * group. Neither is strictly better, so the caller chooses.
     */
    public static function defaultLayout($style = 'individual')
    {
        if ($style === 'composite') {
            return [
                'left'  => [],
                'right' => [],
                'below' => ['fluentcart_product_info'],
            ];
        }

        return [
            'left'  => ['fluentcart_product_gallery'],
            'right' => [
                'fluentcart_product_title',
                'fluentcart_product_price',
                'fluentcart_product_excerpt',
                'fluentcart_product_buy_section',
            ],
            'below' => [
                'fluentcart_product_content',
                'fluentcart_related_products',
            ],
        ];
    }

    /** FluentCart widget names used inside one template. */
    public static function fluentCartWidgets($postId)
    {
        $tree  = ElementorDocument::read($postId);
        $found = [];

        if (is_wp_error($tree)) {
            return $found;
        }

        ElementorDocument::walk($tree, function ($node) use (&$found) {
            if (empty($node['widgetType'])) {
                return;
            }

            if (BuilderRegistry::isFluentCartWidget($node['widgetType'])) {
                $found[] = $node['widgetType'];
            }
        });

        return array_values(array_unique($found));
    }

    /**
     * Template health, for get-builder-context and validate-storefront.
     *
     * A template that exists but is a draft, or carries no display condition,
     * silently does nothing — the product page falls back to FluentCart's own
     * rendering, which looks like "my template was ignored".
     */
    public static function status()
    {
        if (!BuilderRegistry::elementorProActive()) {
            return [
                'supported' => false,
                'reason'    => __('Theme Builder product templates need Elementor Pro.', 'fluent-cart-elementor-blocks'),
                'templates' => [],
            ];
        }

        $templates = self::all();
        $live      = array_filter($templates, function ($t) {
            return $t['published'] && !empty($t['conditions']);
        });

        $archives     = self::archives();
        $liveArchives = array_filter($archives, function ($t) {
            return $t['published'] && !empty($t['conditions']);
        });

        return [
            'supported'     => true,
            'templates'     => $templates,
            'has_template'  => !empty($templates),
            'has_live'      => !empty($live),
            'document_type' => self::DOCUMENT_TYPE,
            'how_to_create' => __('Create it with Elementor\'s manage-site-parts: action "create", type "fluentcart-product", conditions ["include/fluentcart_product"]. Then place FluentCart product widgets into it with place-builder-widget and publish it.', 'fluent-cart-elementor-blocks'),

            'archive' => [
                'templates'     => $archives,
                'has_template'  => !empty($archives),
                'has_live'      => !empty($liveArchives),
                'document_type' => self::ARCHIVE_DOCUMENT_TYPE,
                'taxonomies'    => ['product-categories', 'product-brands'],
                'how_to_create' => __('Category and brand archives use Elementor\'s "archive" type: manage-site-parts {action:"create", type:"archive", conditions:["include/archive/fluentcart_product_archive"]}, then place fluent_cart_shop_app into it.', 'fluent-cart-elementor-blocks'),
            ],
        ];
    }
}
