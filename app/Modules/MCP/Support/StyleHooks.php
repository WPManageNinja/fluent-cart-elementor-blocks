<?php

namespace FluentCartElementorBlocks\App\Modules\MCP\Support;

/**
 * The CSS selectors a widget actually renders, read by rendering it.
 *
 * An agent can configure a widget precisely and still be unable to style it:
 * the settings schema says nothing about the markup, so the only way to find a
 * hook is to open the page and inspect the DOM. Building a store that way cost
 * most of a session — the buy-now button came out unreadable because it emits
 * WordPress's `wp-element-button` and silently inherited the theme's colours,
 * and the shop grid and the carousel turned out to wrap images in two
 * different classes.
 *
 * Derived rather than listed, for the same reason as WidgetSchemaReader: a
 * parallel table of class names drifts from the markup the moment a renderer
 * changes, and nothing fails when it does.
 */
class StyleHooks
{
    /** Prefixes that mark markup FluentCart owns and a designer may target. */
    const OWN_PREFIXES = ['fct-', 'fluent-cart-', 'fluent_cart_'];

    /** The per-widget wrapper this add-on emits around every widget's output. */
    const WIDGET_WRAPPER_PREFIX = 'fluent-cart-elementor-';

    /**
     * Classes WordPress and themes style globally. A widget emitting one of
     * these inherits the active theme's colours, and that is the single
     * likeliest reason a FluentCart button looks wrong on a redesigned site.
     */
    const THEME_STYLED = ['wp-element-button', 'wp-block-button__link'];

    /** Long lists stop being useful; the structural hooks come first anyway. */
    const MAX_SELECTORS = 80;

    private static $memo = [];

    /**
     * Selectors for one widget, or null when it is not registered.
     *
     * Rendering is the only honest source, so this really does render. The
     * result is memoised per request because a schema call asks once per
     * widget and rendering a shop grid is not free.
     */
    public static function forWidget($name)
    {
        if (array_key_exists($name, self::$memo)) {
            return self::$memo[$name];
        }

        return self::$memo[$name] = self::read($name);
    }

    private static function read($name)
    {
        if (!WidgetSchemaReader::widget($name)) {
            return null;
        }

        $html = self::render($name);

        if (!is_string($html) || trim($html) === '') {
            return [
                'selectors'            => [],
                'theme_styled_buttons' => [],
                'rendered'             => false,
                'note'                 => __('This widget rendered nothing with its default settings — usually it needs a setting (a product, a variant) or a cart with items. Configure it, place it, then read the page to style it.', 'fluent-cart-elementor-blocks'),
            ];
        }

        $document = self::parse($html);

        if (!$document) {
            return [
                'selectors'            => [],
                'theme_styled_buttons' => [],
                'rendered'             => true,
                'note'                 => __('The widget rendered but its markup could not be parsed, so no selectors are reported.', 'fluent-cart-elementor-blocks'),
            ];
        }

        $selectors = self::ownSelectors($document);
        $buttons   = self::themeStyledButtons($document);

        $truncated = count($selectors) > self::MAX_SELECTORS;

        return [
            'selectors'            => array_slice($selectors, 0, self::MAX_SELECTORS),
            'theme_styled_buttons' => $buttons,
            'rendered'             => true,
            'truncated'            => $truncated,
            'note'                 => $buttons
                ? __('Selectors are read from this widget\'s own rendered markup and are safe to target from the Elementor kit\'s custom CSS. theme_styled_buttons carry WordPress button classes and therefore inherit the active theme\'s colours — style them through the scoped selector given, or they will not match your design.', 'fluent-cart-elementor-blocks')
                : __('Selectors are read from this widget\'s own rendered markup and are safe to target from the Elementor kit\'s custom CSS.', 'fluent-cart-elementor-blocks'),
        ];
    }

    /**
     * Render the widget the way the front end would.
     *
     * Theme Builder widgets read the current post, so one has to be in scope or
     * they render their "no product" placeholder and report no hooks. The
     * most-reviewed product is chosen so the review widgets have rows to draw.
     */
    private static function render($name)
    {
        global $post;

        $previousPost = $post;
        $product      = self::contextProduct();

        if ($product) {
            $post = get_post($product);
            setup_postdata($post);
        }

        $level = ob_get_level();
        $html  = '';

        try {
            $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
                'id'         => substr(md5('fce-hooks-' . $name), 0, 7),
                'elType'     => 'widget',
                'widgetType' => $name,
                'settings'   => self::contextSettings($name, $product),
            ]);

            ob_start();
            $element->print_element();
            $html = (string) ob_get_clean();
        } catch (\Throwable $e) {
            $html = '';
        }

        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        $post = $previousPost;
        wp_reset_postdata();

        return $html;
    }

    /**
     * The minimum settings that make a widget render at all.
     *
     * The three widgets a designer most needs hooks for — product card, add to
     * cart, buy now — print nothing without a product or a variant, which is
     * precisely when their markup is unknowable. Filling the ids the widget
     * declares, rather than a per-widget list, keeps this honest as controls
     * change.
     */
    private static function contextSettings($name, $productId)
    {
        if (!$productId) {
            return [];
        }

        $widget = WidgetSchemaReader::widget($name);

        if (!$widget || !method_exists($widget, 'get_controls')) {
            return [];
        }

        $controls = $widget->get_controls();
        $settings = [];

        if (isset($controls['product_id'])) {
            $settings['product_id'] = (string) $productId;
        }

        if (isset($controls['product_ids'])) {
            $settings['product_ids'] = [(string) $productId];
        }

        if (isset($controls['variant_id'])) {
            $variant = self::firstVariant($productId);

            if ($variant) {
                $settings['variant_id'] = (string) $variant;
            }
        }

        return $settings;
    }

    private static function firstVariant($productId)
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}fct_product_variations WHERE post_id = %d ORDER BY serial_index ASC, id ASC LIMIT 1",
                $productId
            )
        );
    }

    /** The product most likely to make every widget render something. */
    private static function contextProduct()
    {
        $reviewModel = '\FluentCart\App\Models\ProductReview';

        if (class_exists($reviewModel)) {
            $postId = (int) $reviewModel::query()
                ->where('status', 'approved')
                ->whereNull('parent_id')
                ->selectRaw('post_id, COUNT(*) as review_total')
                ->groupBy('post_id')
                ->orderByDesc('review_total')
                ->limit(1)
                ->value('post_id');

            if ($postId && get_post_status($postId) === 'publish') {
                return $postId;
            }
        }

        $posts = get_posts([
            'post_type'        => 'fluent-products',
            'post_status'      => 'publish',
            'numberposts'      => 1,
            'fields'           => 'ids',
            'suppress_filters' => false,
        ]);

        return $posts ? (int) $posts[0] : 0;
    }

    private static function parse($html)
    {
        if (!class_exists('\DOMDocument')) {
            return null;
        }

        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);

        // Widget markup is a fragment, so the wrapper keeps DOMDocument from
        // inventing its own and the encoding hint stops it mangling UTF-8.
        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="fce-hooks-root">' . $html . '</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $document : null;
    }

    /** Every distinct FluentCart class in the markup, outermost first. */
    private static function ownSelectors(\DOMDocument $document)
    {
        $seen = [];

        foreach ($document->getElementsByTagName('*') as $node) {
            foreach (self::classesOf($node) as $class) {
                if (self::isOwn($class) && !isset($seen[$class])) {
                    $seen[$class] = true;
                }
            }
        }

        // Document order is depth-first, so the structural wrappers an agent
        // most likely wants to style already come first.
        return array_map(function ($class) {
            return '.' . $class;
        }, array_keys($seen));
    }

    /**
     * Buttons that will take the theme's colours, each with a selector scoped
     * to the nearest FluentCart ancestor.
     *
     * The bare class is useless on its own — styling `.wp-element-button`
     * repaints every button on the site. The scoped form is what an agent can
     * actually paste into the kit CSS.
     */
    private static function themeStyledButtons(\DOMDocument $document)
    {
        $found = [];

        foreach ($document->getElementsByTagName('*') as $node) {
            $classes = self::classesOf($node);
            $themed  = array_values(array_intersect($classes, self::THEME_STYLED));

            if (!$themed) {
                continue;
            }

            // Prefer the add-on's own widget wrapper. The button's own classes
            // are not always an identity — add-to-cart carries `fct-loader`,
            // a utility shared with anything that spins, so scoping to it
            // would repaint unrelated buttons.
            $own   = array_values(array_filter($classes, [self::class, 'isOwn']));
            $scope = self::wrapperScope($node);

            if (!$scope) {
                $scope = $own ? '.' . $own[0] : self::ancestorScope($node);
            }

            $hook     = '.' . $themed[0];
            $selector = $scope ? $scope . ' ' . $hook : $hook;

            if (isset($found[$selector])) {
                continue;
            }

            $label = trim(preg_replace('/\s+/', ' ', (string) $node->textContent));

            $found[$selector] = [
                'selector'       => $selector,
                'inherits'       => $themed,
                'label'          => $label === '' ? null : self::shorten($label),
                'scoped_to_self' => (bool) $own,
            ];
        }

        return array_values($found);
    }

    /**
     * The add-on wraps each widget's output in `fluent-cart-elementor-<name>`,
     * which is the one class guaranteed to mean "this widget and nothing else".
     */
    private static function wrapperScope(\DOMNode $node)
    {
        for ($n = $node; $n instanceof \DOMElement; $n = $n->parentNode) {
            foreach (self::classesOf($n) as $class) {
                if (strpos($class, self::WIDGET_WRAPPER_PREFIX) === 0) {
                    return '.' . $class;
                }
            }
        }

        return '';
    }

    /** Nearest ancestor carrying a FluentCart class, as a selector. */
    private static function ancestorScope(\DOMNode $node)
    {
        for ($parent = $node->parentNode; $parent instanceof \DOMElement; $parent = $parent->parentNode) {
            foreach (self::classesOf($parent) as $class) {
                if (self::isOwn($class)) {
                    return '.' . $class;
                }
            }
        }

        return '';
    }

    private static function classesOf(\DOMNode $node)
    {
        if (!$node instanceof \DOMElement) {
            return [];
        }

        $value = trim((string) $node->getAttribute('class'));

        return $value === '' ? [] : preg_split('/\s+/', $value);
    }

    private static function isOwn($class)
    {
        foreach (self::OWN_PREFIXES as $prefix) {
            if (strpos($class, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    private static function shorten($text)
    {
        return function_exists('mb_substr') && mb_strlen($text) > 40
            ? mb_substr($text, 0, 40) . '…'
            : $text;
    }
}
