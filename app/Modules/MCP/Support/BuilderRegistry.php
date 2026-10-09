<?php

namespace FluentCartElementorBlocks\App\Modules\MCP\Support;

use FluentCart\App\App;

/**
 * What FluentCart can build with on this site.
 *
 * Elementor's MCP exposes its own widgets but knows nothing about ours — it
 * refuses to configure them and has no schema for them. So an agent reaching
 * FluentCart widgets needs this list from us: which exist, which are usable
 * right now, and which need a product template rather than a plain page.
 */
class BuilderRegistry
{
    const ELEMENTOR_ADDON = 'fluent-cart-elementor-blocks/fluent-cart-elementor-blocks.php';

    /** Theme Builder widgets render a placeholder outside product context. */
    const CONTEXT_PRODUCT = 'product_template';
    const CONTEXT_PAGE    = 'page';

    /**
     * Elementor widget catalog.
     *
     * Names are the widget's own get_name(). The two prefixes are real and
     * easy to get wrong: general widgets use `fluent_cart_`, Theme Builder
     * widgets use `fluentcart_` with no underscore after "fluent".
     *
     * `reviews` marks the six widgets that only register when core review
     * support is present.
     */
    private static function elementorCatalog()
    {
        return [
            // General — any page.
            'fluent_cart_shop_app'                  => ['Shop / product grid', self::CONTEXT_PAGE],
            'fluent_cart_product_card'              => ['A single product card', self::CONTEXT_PAGE],
            'fluent_cart_product_carousel'          => ['Carousel of products', self::CONTEXT_PAGE],
            'fluent_cart_product_categories_list'   => ['Product category list', self::CONTEXT_PAGE],
            'fluent_cart_add_to_cart'               => ['Add-to-cart button', self::CONTEXT_PAGE],
            'fluent_cart_buy_now'                   => ['Buy-now button', self::CONTEXT_PAGE],
            'fluent_cart_mini_cart'                 => ['Mini cart', self::CONTEXT_PAGE],
            'fluent_cart_cart'                      => ['Full cart', self::CONTEXT_PAGE],
            'fluent_cart_checkout'                  => ['Checkout', self::CONTEXT_PAGE],
            'fluent_cart_receipt'                   => ['Order receipt', self::CONTEXT_PAGE],
            'fluent_cart_customer_dashboard'        => ['Customer dashboard', self::CONTEXT_PAGE],
            'fluent_cart_customer_dashboard_button' => ['Link to the customer dashboard', self::CONTEXT_PAGE],
            'fluent_cart_search_bar'                => ['Product search bar', self::CONTEXT_PAGE],
            'fluent_cart_store_logo'                => ['Store logo', self::CONTEXT_PAGE],

            // Theme Builder — need a FluentCart product document.
            'fluentcart_product_title'               => ['Product title', self::CONTEXT_PRODUCT],
            'fluentcart_product_gallery'             => ['Product image gallery', self::CONTEXT_PRODUCT],
            'fluentcart_product_price'               => ['Product price', self::CONTEXT_PRODUCT],
            'fluentcart_product_stock'               => ['Stock status', self::CONTEXT_PRODUCT],
            'fluentcart_product_sku'                 => ['Product SKU', self::CONTEXT_PRODUCT],
            'fluentcart_product_excerpt'             => ['Short description', self::CONTEXT_PRODUCT],
            'fluentcart_product_content'             => ['Full description', self::CONTEXT_PRODUCT],
            'fluentcart_product_package_description' => ['Package/variant description', self::CONTEXT_PRODUCT],
            'fluentcart_product_buy_section'         => ['Variations + add to cart', self::CONTEXT_PRODUCT],
            'fluentcart_product_info'                => ['Whole product block in one widget, with the summary rows reorderable — use this OR the individual product widgets, never both', self::CONTEXT_PRODUCT],
            'fluentcart_related_products'            => ['Related products', self::CONTEXT_PRODUCT],
            'fluentcart_product_rating'              => ['Star rating', self::CONTEXT_PRODUCT, 'reviews'],
            'fluentcart_product_review_summary'      => ['Review summary', self::CONTEXT_PRODUCT, 'reviews'],
            'fluentcart_write_a_review_button'       => ['Write-a-review button', self::CONTEXT_PRODUCT, 'reviews'],
            'fluentcart_product_review_form'         => ['Review form', self::CONTEXT_PRODUCT, 'reviews'],
            'fluentcart_product_review_list'         => ['List of reviews', self::CONTEXT_PRODUCT, 'reviews'],
            'fluentcart_product_reviews'             => ['Reviews (combined)', self::CONTEXT_PRODUCT, 'reviews'],
        ];
    }

    /**
     * Composite widgets: one widget that already renders what several others do.
     *
     * `fluentcart_product_info` renders the whole single-product block — gallery,
     * description, related products, plus title/stock/sku/excerpt/price/
     * package-description/buy-section summary rows. Placing it NEXT TO the
     * individual widgets renders the product twice, which is a confusing bug to
     * diagnose from the front end and easy to walk into: the widget's own label
     * ("Product Info") sounds like a small meta block.
     *
     * Keyed by the composite; the value is what it already covers.
     */
    public static function composites()
    {
        return [
            'fluentcart_product_info' => [
                'fluentcart_product_gallery',
                'fluentcart_product_title',
                'fluentcart_product_price',
                'fluentcart_product_stock',
                'fluentcart_product_sku',
                'fluentcart_product_excerpt',
                'fluentcart_product_package_description',
                'fluentcart_product_buy_section',
                'fluentcart_product_content',
                'fluentcart_related_products',
            ],
        ];
    }

    /**
     * Widgets that would double up with $name, in either direction.
     *
     * Returns the composite's covered list when $name is a composite, or the
     * composites that already cover $name when it is a part.
     */
    public static function conflictsFor($name)
    {
        $composites = self::composites();

        if (isset($composites[$name])) {
            return ['role' => 'composite', 'widgets' => $composites[$name]];
        }

        $covering = [];

        foreach ($composites as $composite => $covered) {
            if (in_array($name, $covered, true)) {
                $covering[] = $composite;
            }
        }

        return $covering ? ['role' => 'part', 'widgets' => $covering] : null;
    }

    /**
     * The settings a widget uses to reorder its own sub-elements.
     *
     * Read from the widget rather than listed here. A hand-kept list was
     * written first and was already wrong within the hour — it missed the
     * checkout's `form_elements`/`summary_elements` and the review list's
     * `row_fields` — which is the same drift the schema reader exists to
     * avoid. Any repeater on the content tab is a layout control by
     * definition: rows that render in order.
     *
     * Without this an agent cannot tell a deeply configurable widget from a
     * single-purpose one. The control sits among 300+ style controls, so it
     * reaches for several widgets instead of the one that already reorders.
     */
    public static function layoutControlsFor($name)
    {
        if (!self::isFluentCartWidget($name)) {
            return [];
        }

        $schema = WidgetSchemaReader::schemaFor($name);

        if (!$schema || empty($schema['properties'])) {
            return [];
        }

        $controls = [];

        foreach ($schema['properties'] as $key => $property) {
            // A repeater the reader could expand: an array of rows with a
            // known shape. An opaque array tells the agent nothing orderable.
            if (
                isset($property['type'], $property['items']['properties'])
                && $property['type'] === 'array'
            ) {
                $controls[] = $key;
            }
        }

        return $controls;
    }

    /** Every widget that can reorder its own parts, keyed by widget name. */
    public static function layoutControls()
    {
        $map = [];

        foreach (array_keys(self::elementorCatalog()) as $name) {
            $controls = self::layoutControlsFor($name);

            if ($controls) {
                $map[$name] = $controls;
            }
        }

        return $map;
    }

    /** Is a widget name one of ours? Guards every write. */
    public static function isFluentCartWidget($name)
    {
        return is_string($name) && isset(self::elementorCatalog()[$name]);
    }

    public static function elementorActive()
    {
        return defined('ELEMENTOR_VERSION');
    }

    public static function elementorProActive()
    {
        return defined('ELEMENTOR_PRO_VERSION');
    }

    public static function addonActive()
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active(self::ELEMENTOR_ADDON);
    }

    /**
     * Widget rows for list-builder-widgets.
     *
     * `available` is the honest answer to "can the agent place this now" —
     * a registered widget whose gate is off still renders nothing useful, so
     * we say why instead of letting it place an empty element.
     */
    public static function widgets($context = null)
    {
        $rows = [];

        foreach (self::elementorCatalog() as $name => $meta) {
            list($purpose, $widgetContext) = $meta;
            $gate = isset($meta[2]) ? $meta[2] : null;

            if ($context && $widgetContext !== $context) {
                continue;
            }

            $registered = (bool) WidgetSchemaReader::widget($name);
            $reason     = null;

            if (!$registered) {
                $reason = $gate === 'reviews'
                    ? __('Product reviews are not available in this FluentCart build.', 'fluent-cart-elementor-blocks')
                    : ($widgetContext === self::CONTEXT_PRODUCT && !self::elementorProActive()
                        ? __('Theme Builder widgets need Elementor Pro.', 'fluent-cart-elementor-blocks')
                        : __('Not registered — check the FluentCart Elementor Blocks addon is active.', 'fluent-cart-elementor-blocks'));
            }

            $conflict = self::conflictsFor($name);

            $rows[] = [
                'name'                     => $name,
                'purpose'                  => $purpose,
                'context'                  => $widgetContext,
                'requires_product_context' => $widgetContext === self::CONTEXT_PRODUCT,
                'available'                => $registered,
                'unavailable_reason'       => $reason,
                'is_composite'             => $conflict && $conflict['role'] === 'composite',
                'do_not_combine_with'      => $conflict ? $conflict['widgets'] : [],
                'layout_controls'          => self::layoutControlsFor($name),
            ];
        }

        return $rows;
    }

    /** Builder/environment summary for get-builder-context. */
    public static function context()
    {
        $widgets = self::widgets();
        $usable  = array_filter($widgets, function ($w) {
            return $w['available'];
        });

        return [
            'builders' => [
                'elementor' => [
                    'active'      => self::elementorActive(),
                    'version'     => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : null,
                    'pro'         => self::elementorProActive(),
                    'addon'       => self::addonActive(),
                    'widgets'     => count($widgets),
                    'usable_now'  => count($usable),
                ],
            ],
            'fluent_cart_pro' => App::isProActive(),
        ];
    }
}
