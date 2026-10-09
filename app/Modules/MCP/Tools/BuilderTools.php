<?php

namespace FluentCartElementorBlocks\App\Modules\MCP\Tools;

use FluentCartElementorBlocks\App\Modules\MCP\Support\BuilderRegistry;
use FluentCartElementorBlocks\App\Modules\MCP\Support\ElementorDocument;
use FluentCart\App\Modules\MCP\Support\MCPHelper;
use FluentCart\App\Modules\MCP\Support\PermissionGate;
use FluentCartElementorBlocks\App\Modules\MCP\Support\ProductTemplate;
use FluentCartElementorBlocks\App\Modules\MCP\Support\StorefrontRecipes;
use FluentCartElementorBlocks\App\Modules\MCP\Support\StyleConflicts;
use FluentCartElementorBlocks\App\Modules\MCP\Support\StyleHooks;
use FluentCartElementorBlocks\App\Modules\MCP\Support\WidgetSchemaReader;
use FluentCart\Framework\Support\Arr;

/**
 * Page-building tools for FluentCart's own widgets.
 *
 * Elementor's MCP can lay out a page but cannot touch a FluentCart widget:
 * it has no schema for ours and refuses to configure classic elements
 * (`elementor_v3_not_supported`). These abilities fill exactly that gap —
 * discovery, schema and configured placement — while Elementor keeps doing
 * layout, styling and publishing. Because they register through the
 * WordPress Abilities API like the rest of FluentCart's MCP surface, they
 * appear in the agent's catalog alongside Elementor's own tools.
 */
class BuilderTools
{
    public static function definitions()
    {
        return [
            'fluent-cart/get-builder-context' => [
                'label'       => __('Get Builder Context', 'fluent-cart-elementor-blocks'),
                'description' => __('START HERE before building any storefront page. Reports which page builders are active, how many FluentCart widgets are usable, and the workflow to follow: use Elementor\'s own tools for layout/containers/styling, and FluentCart\'s builder tools for FluentCart widgets — Elementor cannot configure ours.', 'fluent-cart-elementor-blocks'),
                'input_schema' => ['type' => 'object', 'properties' => (object) []],
                'execute_callback'    => [self::class, 'getContext'],
                'permission_callback' => function () {
                    return PermissionGate::canAny(PermissionGate::readRoleCaps());
                },
                'annotations' => ['readonly' => true],
            ],

            'fluent-cart/list-builder-widgets' => [
                'label'       => __('List Builder Widgets', 'fluent-cart-elementor-blocks'),
                'description' => __('Every FluentCart widget you can place, with what it renders and whether it is usable right now. Widgets marked requires_product_context only work inside a FluentCart product Theme Builder document — placing one on an ordinary page renders a placeholder. Call get-builder-widget-schema before placing one.', 'fluent-cart-elementor-blocks'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'context' => [
                            'type'        => 'string',
                            'enum'        => ['page', 'product_template'],
                            'description' => 'Only widgets for this context. Omit for all.',
                        ],
                    ],
                ],
                'execute_callback'    => [self::class, 'listWidgets'],
                'permission_callback' => function () {
                    return PermissionGate::canAny(PermissionGate::readRoleCaps());
                },
                'annotations' => ['readonly' => true],
            ],

            'fluent-cart/get-builder-widget-schema' => [
                'label'       => __('Get Builder Widget Schema', 'fluent-cart-elementor-blocks'),
                'description' => __('The settings a FluentCart widget accepts — keys, types, defaults and valid options — read live from the widget itself, plus the CSS selectors it actually renders. Call this before place-builder-widget so settings are correct rather than guessed, and before writing any CSS for it: style_hooks lists the real class names and flags the buttons that inherit the theme\'s colours instead of yours. Content controls by default; pass include:["style"] for design controls too.', 'fluent-cart-elementor-blocks'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'widget'  => [
                            'type'        => 'string',
                            'description' => 'Widget name from list-builder-widgets, e.g. fluent_cart_product_card.',
                        ],
                        'include' => [
                            'type'        => 'array',
                            'items'       => ['type' => 'string', 'enum' => ['style', 'advanced']],
                            'description' => 'Also return design controls.',
                        ],
                        'style_hooks' => [
                            'type'        => 'boolean',
                            'description' => 'The CSS selectors this widget renders. Default true. Pass false to skip the render when you only need settings.',
                        ],
                    ],
                    'required' => ['widget'],
                ],
                'execute_callback'    => [self::class, 'getWidgetSchema'],
                'permission_callback' => function () {
                    return PermissionGate::canAny(PermissionGate::readRoleCaps());
                },
                'annotations' => ['readonly' => true],
            ],

            'fluent-cart/place-builder-widget' => [
                'label'       => __('Place Builder Widget', 'fluent-cart-elementor-blocks'),
                'description' => __('Insert a configured FluentCart widget into an Elementor page. Call get-builder-widget-schema first and pass real setting keys — unknown keys are rejected. Use dry_run:true to preview. Only FluentCart widgets can be placed; use Elementor\'s own tools for its elements.', 'fluent-cart-elementor-blocks'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'post_id'   => ['type' => 'integer', 'description' => 'The page or template to insert into.'],
                        'widget'    => ['type' => 'string', 'description' => 'FluentCart widget name.'],
                        'settings'  => ['type' => 'object', 'description' => 'Widget settings, per get-builder-widget-schema.'],
                        'parent_id' => ['type' => 'string', 'description' => 'Container element id to append into. Omit for the document root.'],
                        'dry_run'   => ['type' => 'boolean', 'description' => 'Validate and preview without saving.'],
                    ],
                    'required' => ['post_id', 'widget'],
                ],
                'execute_callback'    => [self::class, 'placeWidget'],
                'permission_callback' => function () {
                    return PermissionGate::can('store/settings');
                },
                'annotations' => ['destructive' => true],
            ],

            'fluent-cart/update-builder-widget' => [
                'label'       => __('Update Builder Widget', 'fluent-cart-elementor-blocks'),
                'description' => __('Change settings on a FluentCart widget already placed on a page. Pass only the keys you want changed; send a key as null to clear it. Element ids come from place-builder-widget or Elementor\'s get-page-structure. Refuses elements that are not FluentCart widgets.', 'fluent-cart-elementor-blocks'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'post_id'    => ['type' => 'integer', 'description' => 'The page holding the widget.'],
                        'element_id' => ['type' => 'string', 'description' => 'Element id of the FluentCart widget.'],
                        'settings'   => ['type' => 'object', 'description' => 'Partial settings to merge.'],
                        'dry_run'    => ['type' => 'boolean', 'description' => 'Validate without saving.'],
                    ],
                    'required' => ['post_id', 'element_id', 'settings'],
                ],
                'execute_callback'    => [self::class, 'updateWidget'],
                'permission_callback' => function () {
                    return PermissionGate::can('store/settings');
                },
                'annotations' => ['destructive' => true],
            ],

            // Elementor's manage-elements cannot delete our widgets either —
            // it refuses classic elements outright. Without this an agent that
            // places the wrong widget has no way back and has to rebuild the
            // whole document, which is what happened in the first real use.
            'fluent-cart/remove-builder-widget' => [
                'label'       => __('Remove Builder Widget', 'fluent-cart-elementor-blocks'),
                'description' => __('Delete a FluentCart widget from a page or template. Elementor\'s own manage-elements cannot remove FluentCart widgets, so use this. Takes element_id from get-page-structure, or widget to remove every instance of one type. Refuses anything that is not a FluentCart widget.', 'fluent-cart-elementor-blocks'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'post_id'    => ['type' => 'integer', 'description' => 'The page or template to edit.'],
                        'element_id' => ['type' => 'string', 'description' => 'Element id to remove.'],
                        'widget'     => ['type' => 'string', 'description' => 'Remove every instance of this FluentCart widget instead.'],
                        'dry_run'    => ['type' => 'boolean', 'description' => 'Report what would be removed without saving.'],
                    ],
                    'required' => ['post_id'],
                ],
                'execute_callback'    => [self::class, 'removeWidget'],
                'permission_callback' => function () {
                    return PermissionGate::can('store/settings');
                },
                'annotations' => ['destructive' => true],
            ],

            'fluent-cart/build-storefront-page' => [
                'label'       => __('Build Storefront Page', 'fluent-cart-elementor-blocks'),
                'description' => sprintf(
                    /* translators: %s: recipe names */
                    __('Create (or fill) a storefront page and wire it into FluentCart settings in one call — the assignment matters, because cart, checkout and receipt only work when FluentCart knows which page they are. Recipes: %s. Pass post_id to use an existing page, otherwise one is created as a draft.', 'fluent-cart-elementor-blocks'),
                    implode(', ', StorefrontRecipes::names())
                ),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'recipe'  => [
                            'type'        => 'string',
                            'enum'        => StorefrontRecipes::names(),
                            'description' => 'Which storefront page to build.',
                        ],
                        'post_id' => ['type' => 'integer', 'description' => 'Existing page to use. Omit to create one.'],
                        'title'   => ['type' => 'string', 'description' => 'Title for a newly created page.'],
                        'assign'  => ['type' => 'boolean', 'description' => 'Point the FluentCart setting at this page. Default true.'],
                        'dry_run' => ['type' => 'boolean', 'description' => 'Report what would happen without changing anything.'],
                    ],
                    'required' => ['recipe'],
                ],
                'execute_callback'    => [self::class, 'buildStorefrontPage'],
                'permission_callback' => function () {
                    return PermissionGate::can('store/settings');
                },
                'annotations' => ['destructive' => true],
            ],

            // Deliberately a tool, not an MCP resource. Tested: a host server
            // decides its own resource list from how it calls create_server, so
            // an ability cannot add one — setting `mcp.uri` on it changes
            // nothing and it still arrives as a tool. Elementor's 14 resources
            // are its own and Angie's, both first-party. Guidance for the
            // Elementor path therefore has to be callable.
            'fluent-cart/get-storefront-guide' => [
                'label'       => __('FluentCart Storefront Guide', 'fluent-cart-elementor-blocks'),
                'description' => __('How to assemble a FluentCart storefront with Elementor: which page needs which widget, how single product pages work, and the order to do things in. Read this before building a store.', 'fluent-cart-elementor-blocks'),
                'input_schema' => ['type' => 'object', 'properties' => (object) []],
                'execute_callback'    => [self::class, 'getStorefrontGuide'],
                'permission_callback' => function () {
                    return PermissionGate::canAny(PermissionGate::readRoleCaps());
                },
                'annotations' => ['readonly' => true],
            ],

            // The single entry point. Everything below it exists for when an
            // agent needs finer control; this is what "build me a store"
            // should hit, because orchestrating the parts by hand is where
            // real sessions went wrong.
            'fluent-cart/build-storefront' => [
                'label'       => __('Build Storefront', 'fluent-cart-elementor-blocks'),
                'description' => __('Build the whole FluentCart storefront in one call: shop, cart, checkout, receipt and account pages, each wired into FluentCart settings, plus the single product page template with its display condition. Use this FIRST whenever the user wants a store, a storefront, or a FluentCart site built or redesigned — then style the returned containers with Elementor\'s tools. Skips anything already set up, and validates itself at the end.', 'fluent-cart-elementor-blocks'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'publish' => ['type' => 'boolean', 'description' => 'Publish what it creates. Default false (drafts, matching Elementor).'],
                        'force'   => ['type' => 'boolean', 'description' => 'Rebuild parts that already exist. Default false — existing pages are left alone.'],
                        'dry_run' => ['type' => 'boolean', 'description' => 'Report the plan without changing anything.'],
                    ],
                ],
                'execute_callback'    => [self::class, 'buildStorefront'],
                'permission_callback' => function () {
                    return PermissionGate::can('store/settings');
                },
                'annotations' => ['destructive' => true],
            ],

            // One call, because the multi-step version failed silently in real
            // use: a template with no condition, or a second published one
            // shadowing the first, both look like "the edit did nothing".
            'fluent-cart/build-product-template' => [
                'label'       => __('Build Product Page Template', 'fluent-cart-elementor-blocks'),
                'description' => __('Build the single product page. Single product pages are an Elementor Theme Builder document, not a normal page — this creates it, sets the display condition, lays out a two-column product page (gallery left; title, price, excerpt and buy section right; description and related products below) and returns the element ids so you can restyle it. Use this whenever the user asks for a product page or product template. It handles any existing template for you.', 'fluent-cart-elementor-blocks'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'title'            => ['type' => 'string', 'description' => 'Template name. Defaults to "Single Product".'],
                        'layout'           => [
                            'type'        => 'string',
                            'enum'        => ['individual', 'composite'],
                            'description' => 'individual (default): separate widgets for gallery, title, price, excerpt, buy section — each styled independently with Elementor. composite: one fluentcart_product_info widget whose summary rows are reordered through its own summary_sections setting. Pick composite when the user cares about the ORDER of product details, individual when they care about styling each part differently.',
                        ],
                        'replace_existing' => ['type' => 'boolean', 'description' => 'Unpublish any template already claiming product pages. Default true — two live templates conflict.'],
                        'publish'          => ['type' => 'boolean', 'description' => 'Publish immediately. Default false (draft, matching Elementor).'],
                        'dry_run'          => ['type' => 'boolean', 'description' => 'Report what would happen without changing anything.'],
                    ],
                ],
                'execute_callback'    => [self::class, 'buildProductTemplate'],
                'permission_callback' => function () {
                    return PermissionGate::can('store/settings');
                },
                'annotations' => ['destructive' => true],
            ],

            'fluent-cart/validate-storefront' => [
                'label'       => __('Validate Storefront', 'fluent-cart-elementor-blocks'),
                'description' => __('Check the storefront is actually wired up: every required page assigned in FluentCart settings, published, and carrying the widget it needs. Run this after building — a page that looks right but is not assigned will silently fail at checkout.', 'fluent-cart-elementor-blocks'),
                'input_schema' => ['type' => 'object', 'properties' => (object) []],
                'execute_callback'    => [self::class, 'validateStorefront'],
                'permission_callback' => function () {
                    return PermissionGate::canAny(PermissionGate::readRoleCaps());
                },
                'annotations' => ['readonly' => true],
            ],
        ];
    }

    public static function getContext($params = [])
    {
        $context = BuilderRegistry::context();

        if (!BuilderRegistry::elementorActive()) {
            return MCPHelper::envelope(
                __('Elementor is not active on this site.', 'fluent-cart-elementor-blocks'),
                $context + ['workflow' => __('No supported page builder is active. FluentCart pages can still be built with shortcodes.', 'fluent-cart-elementor-blocks')]
            );
        }

        if (!BuilderRegistry::addonActive()) {
            return MCPHelper::envelope(
                __('Elementor is active but FluentCart\'s Elementor widgets are not.', 'fluent-cart-elementor-blocks'),
                $context + ['workflow' => __('Activate the "FluentCart Elementor Blocks" plugin to place FluentCart widgets.', 'fluent-cart-elementor-blocks')]
            );
        }

        $el = $context['builders']['elementor'];

        $context['product_template'] = ProductTemplate::status();

        // Elementor's catalog shows our tools and theirs side by side with
        // nothing explaining they belong together, so spell out the split.
        $context['workflow'] = __(
            'Build pages with BOTH toolsets: use Elementor\'s own tools (create-page, build-composition, manage-elements, manage-site-parts) for containers, layout, headings, styling and document creation; use FluentCart\'s place-builder-widget for every FluentCart widget, because Elementor cannot configure them. Order: get-builder-context → list-builder-widgets → get-builder-widget-schema → place-builder-widget → publish with Elementor.',
            'fluent-cart-elementor-blocks'
        );

        // Theme Builder widgets are nearly half the catalog and do nothing on
        // an ordinary page, so the route to a product template belongs here
        // rather than being discovered by trial and error.
        $context['product_page_workflow'] = __(
            'Single product pages are rendered by an Elementor Theme Builder document, not a normal page. To build one: elementor manage-site-parts {action:"create", type:"fluentcart-product", conditions:["include/fluentcart_product"]} → lay out containers with build-composition → place fluentcart_* widgets into them with place-builder-widget → publish-document. Widgets named fluentcart_* only work there.',
            'fluent-cart-elementor-blocks'
        );

        return MCPHelper::envelope(
            sprintf(
                /* translators: 1: usable widget count, 2: total widget count, 3: Elementor version */
                __('%1$d of %2$d FluentCart widgets usable on Elementor %3$s.', 'fluent-cart-elementor-blocks'),
                $el['usable_now'],
                $el['widgets'],
                $el['version']
            ),
            $context
        );
    }

    public static function listWidgets($params = [])
    {
        $context = Arr::get($params, 'context');
        $widgets = BuilderRegistry::widgets($context ? (string) $context : null);

        $usable = array_filter($widgets, function ($w) {
            return $w['available'];
        });

        return MCPHelper::envelope(
            sprintf(
                /* translators: 1: usable count, 2: total count */
                __('%1$d of %2$d FluentCart widgets available.', 'fluent-cart-elementor-blocks'),
                count($usable),
                count($widgets)
            ),
            ['widgets' => array_values($widgets)]
        );
    }

    public static function getWidgetSchema($params = [])
    {
        $widget = (string) Arr::get($params, 'widget', '');

        if (!BuilderRegistry::isFluentCartWidget($widget)) {
            return MCPHelper::error(
                'unknown_widget',
                sprintf(
                    /* translators: %s: widget name */
                    __('"%s" is not a FluentCart widget. Call list-builder-widgets for the catalog.', 'fluent-cart-elementor-blocks'),
                    $widget
                )
            );
        }

        $include = Arr::get($params, 'include', []);
        $include = is_array($include) ? $include : [];

        $schema = WidgetSchemaReader::schemaFor($widget, $include);

        if ($schema === null) {
            return MCPHelper::error(
                'widget_unavailable',
                sprintf(
                    /* translators: %s: widget name */
                    __('"%s" is not registered on this site right now. Check list-builder-widgets for the reason.', 'fluent-cart-elementor-blocks'),
                    $widget
                )
            );
        }

        $rows = BuilderRegistry::widgets();
        $meta = null;
        foreach ($rows as $row) {
            if ($row['name'] === $widget) {
                $meta = $row;
                break;
            }
        }

        $notes = [];
        if ($meta && $meta['requires_product_context']) {
            $notes[] = __('Place this only inside a FluentCart product Theme Builder document; on an ordinary page it renders a placeholder.', 'fluent-cart-elementor-blocks');
        }

        // Settings alone do not let an agent style the thing it just placed,
        // and the markup is not guessable: two widgets wrap product images in
        // different classes, and several emit WordPress button classes that
        // take the theme's colours. Rendering costs a request; finding this
        // out from a browser costs an afternoon.
        $hooks = Arr::get($params, 'style_hooks', true)
            ? StyleHooks::forWidget($widget)
            : null;

        if ($hooks && !empty($hooks['theme_styled_buttons'])) {
            $notes[] = __('This widget renders WordPress button markup that inherits the active theme\'s colours. Style it through the scoped selectors in style_hooks.theme_styled_buttons, not through the widget settings.', 'fluent-cart-elementor-blocks');
        }

        $data = [
            'widget'          => $widget,
            'settings_schema' => $schema,
            'placement_notes' => $notes,
        ];

        if ($hooks !== null) {
            $data['style_hooks'] = $hooks;
        }

        return MCPHelper::envelope(
            sprintf(
                /* translators: 1: setting count, 2: widget name */
                __('%1$d settings for %2$s.', 'fluent-cart-elementor-blocks'),
                count($schema['properties']),
                $widget
            ),
            $data
        );
    }

    public static function placeWidget($params = [])
    {
        $postId = (int) Arr::get($params, 'post_id', 0);
        $widget = (string) Arr::get($params, 'widget', '');
        $dryRun = (bool) Arr::get($params, 'dry_run', false);

        if (!BuilderRegistry::isFluentCartWidget($widget)) {
            return MCPHelper::error(
                'unknown_widget',
                sprintf(
                    /* translators: %s: widget name */
                    __('"%s" is not a FluentCart widget. Use Elementor\'s own tools for its elements.', 'fluent-cart-elementor-blocks'),
                    $widget
                )
            );
        }

        if (!WidgetSchemaReader::widget($widget)) {
            return MCPHelper::error(
                'widget_unavailable',
                sprintf(
                    /* translators: %s: widget name */
                    __('"%s" is not registered on this site right now.', 'fluent-cart-elementor-blocks'),
                    $widget
                )
            );
        }

        if (!current_user_can('edit_post', $postId)) {
            return MCPHelper::error(
                'forbidden',
                sprintf(/* translators: %d: post id */ __('You cannot edit post %d.', 'fluent-cart-elementor-blocks'), $postId)
            );
        }

        $settings = Arr::get($params, 'settings', []);
        $settings = is_array($settings) ? $settings : [];

        $schema = WidgetSchemaReader::schemaFor($widget, ['style', 'advanced']);
        $known  = array_keys(Arr::get($schema, 'properties', []));
        $unknown = array_diff(array_keys($settings), $known);

        // A silently-ignored key looks like a working call but renders the
        // default, which is the hardest kind of failure for an agent to spot.
        if ($unknown) {
            return MCPHelper::error(
                'unknown_settings',
                sprintf(
                    /* translators: 1: rejected keys, 2: widget name */
                    __('Unknown settings for %2$s: %1$s. Call get-builder-widget-schema for valid keys.', 'fluent-cart-elementor-blocks'),
                    implode(', ', $unknown),
                    $widget
                ),
                ['valid_settings' => $known]
            );
        }

        $tree = ElementorDocument::read($postId);
        if (is_wp_error($tree)) {
            return $tree;
        }

        // Placing a composite next to its parts (or vice versa) renders the
        // product twice. Cheap to detect here; miserable to diagnose from the
        // front end, where it just looks like the theme is broken.
        $present = [];
        ElementorDocument::walk($tree, function ($node) use (&$present) {
            if (!empty($node['widgetType'])) {
                $present[] = $node['widgetType'];
            }
        });

        $warnings = [];
        $conflict = BuilderRegistry::conflictsFor($widget);

        if ($conflict) {
            $clash = array_values(array_intersect($conflict['widgets'], $present));

            if ($clash) {
                $warnings[] = $conflict['role'] === 'composite'
                    ? sprintf(
                        /* translators: 1: widget name, 2: clashing widget names */
                        __('%1$s already renders what these widgets on this document render, so the product will appear twice: %2$s. Remove them with remove-builder-widget, or place the individual widgets instead of this one.', 'fluent-cart-elementor-blocks'),
                        $widget,
                        implode(', ', $clash)
                    )
                    : sprintf(
                        /* translators: 1: widget name, 2: composite widget names */
                        __('%2$s is already on this document and renders %1$s itself, so it will appear twice. Remove %2$s with remove-builder-widget, or skip this widget.', 'fluent-cart-elementor-blocks'),
                        $widget,
                        implode(', ', $clash)
                    );
            }
        }

        $node = ElementorDocument::widgetNode(ElementorDocument::newId($tree), $widget, $settings);
        $next = ElementorDocument::append($tree, $node, Arr::get($params, 'parent_id'));

        if (is_wp_error($next)) {
            return $next;
        }

        if ($dryRun) {
            return MCPHelper::envelope(
                sprintf(/* translators: %s: widget name */ __('Dry run — %s would be placed.', 'fluent-cart-elementor-blocks'), $widget),
                [
                    'dry_run'    => true,
                    'element_id' => $node['id'],
                    'warnings'   => $warnings,
                    'outline'    => ElementorDocument::outline($next),
                ]
            );
        }

        ElementorDocument::write($postId, $next);

        return MCPHelper::envelope(
            $warnings
                ? sprintf(
                    /* translators: 1: widget name, 2: post id */
                    __('Placed %1$s on post %2$d — but check the warning.', 'fluent-cart-elementor-blocks'),
                    $widget,
                    $postId
                )
                : sprintf(
                    /* translators: 1: widget name, 2: post id */
                    __('Placed %1$s on post %2$d.', 'fluent-cart-elementor-blocks'),
                    $widget,
                    $postId
                ),
            [
                'element_id' => $node['id'],
                'post_id'    => $postId,
                'warnings'   => $warnings,
                'edit_url'   => admin_url('post.php?post=' . $postId . '&action=elementor'),
                'outline'    => ElementorDocument::outline($next),
            ]
        );
    }

    public static function getStorefrontGuide($params = [])
    {
        $recipes = StorefrontRecipes::all();
        $lines   = [];

        $lines[] = '# Building a FluentCart storefront with Elementor';
        $lines[] = '';
        $lines[] = 'Two toolsets, each owning what it knows. Elementor does layout, containers, styling, document creation and publishing. FluentCart does FluentCart widgets — Elementor cannot configure them (it refuses classic elements with `elementor_v3_not_supported`).';
        $lines[] = '';
        $lines[] = '## Order of work';
        $lines[] = '';
        $lines[] = '1. `fluent-cart/get-builder-context` — what is active, what is already built.';
        $lines[] = '2. `fluent-cart/list-builder-widgets` — the catalog, and which widgets are usable.';
        $lines[] = '3. `fluent-cart/get-builder-widget-schema` — before placing anything. Do not guess setting keys.';
        $lines[] = '4. Elementor `create-page` / `build-composition` — the containers and layout.';
        $lines[] = '5. `fluent-cart/place-builder-widget` — the FluentCart widgets, into those containers.';
        $lines[] = '6. Elementor `publish-document`.';
        $lines[] = '7. `fluent-cart/validate-storefront` — confirm it is actually wired up.';
        $lines[] = '';
        $lines[] = '## Styling what you placed';
        $lines[] = '';
        $lines[] = 'Widget settings cover layout and content, not appearance — the colours and';
        $lines[] = 'spacing come from CSS in the Elementor kit. Do not guess the class names or';
        $lines[] = 'read them off a rendered page: `get-builder-widget-schema` returns';
        $lines[] = '`style_hooks` with the selectors that widget actually emits.';
        $lines[] = '';
        $lines[] = 'Read `style_hooks.theme_styled_buttons` before writing button CSS. Those';
        $lines[] = 'widgets render WordPress button markup and take the **active theme\'s** colours,';
        $lines[] = 'not yours, so they stay off-palette until you style the scoped selector given';
        $lines[] = 'there. They are the likeliest reason a storefront looks almost right.';
        $lines[] = '';
        $lines[] = '## The pages a store needs';
        $lines[] = '';
        $lines[] = '| Page | Widget | FluentCart setting |';
        $lines[] = '|---|---|---|';

        foreach ($recipes as $name => $recipe) {
            $lines[] = sprintf('| `%s` | `%s` | `%s` |', $name, $recipe['widget'], $recipe['setting_key']);
        }

        $lines[] = '';
        $lines[] = '`fluent-cart/build-storefront-page` does one of these rows in a single call, including the settings assignment. **Layout alone is not enough** — cart, checkout and receipt only function when FluentCart\'s settings point at the right page.';
        $lines[] = '';
        $lines[] = '## Single product pages are different';
        $lines[] = '';
        $lines[] = 'They are rendered by an Elementor Pro Theme Builder document, not a page. The 17 `fluentcart_*` widgets (title, gallery, price, buy section, reviews…) only work there; on an ordinary page they render a placeholder.';
        $lines[] = '';
        $lines[] = '```';
        $lines[] = 'elementor manage-site-parts {action:"create", type:"fluentcart-product",';
        $lines[] = '                             conditions:["include/fluentcart_product"]}';
        $lines[] = 'elementor build-composition   → containers';
        $lines[] = 'fluent-cart place-builder-widget → fluentcart_* widgets';
        $lines[] = 'elementor publish-document';
        $lines[] = '```';
        $lines[] = '';
        $lines[] = 'A product template that stays a draft, or has no display condition, renders nothing and the page silently falls back to FluentCart\'s own output.';
        $lines[] = '';
        $lines[] = '## Category and brand archives';
        $lines[] = '';
        $lines[] = 'FluentCart registers two taxonomies, `product-categories` and `product-brands`. Their archive pages use Elementor\'s **own** `archive` document type with a FluentCart sub-condition:';
        $lines[] = '';
        $lines[] = '```';
        $lines[] = 'elementor manage-site-parts {action:"create", type:"archive",';
        $lines[] = '              conditions:["include/archive/fluentcart_product_archive"]}';
        $lines[] = 'fluent-cart place-builder-widget → fluent_cart_shop_app';
        $lines[] = 'elementor publish-document';
        $lines[] = '```';
        $lines[] = '';
        $lines[] = 'Elementor accepts any condition string on create without validating it, so a typo fails silently — check the archive URL afterwards.';
        $lines[] = '';
        $lines[] = '## Three different product documents';
        $lines[] = '';
        $lines[] = '| Use | Document | Applies to |';
        $lines[] = '|---|---|---|';
        $lines[] = '| One reusable layout for every product | `fluentcart-product` (Theme Builder) | all products matching the condition |';
        $lines[] = '| A one-off layout for a single product | `fluentcart-product-post` | that product only, overriding the template |';
        $lines[] = '| Category / brand listings | `archive` + FluentCart condition | taxonomy archives |';
        $lines[] = '';
        $lines[] = 'Prefer the Theme Builder template. Place widgets directly on a product post only when that one product genuinely needs a different layout — `place-builder-widget` accepts a product `post_id` and sets the document type for you.';
        $lines[] = '';
        $lines[] = '## Reorder with the widget, not with CSS';
        $lines[] = '';
        $lines[] = 'Widgets that lay out sub-elements expose a repeater for it — `card_elements` on the product card, carousel and shop; `shop_layout` on the shop. **Row order is the render order.** Call `get-builder-widget-schema` to see the valid values.';
        $lines[] = '';
        $lines[] = 'Do NOT reorder by writing `order`, `grid-area` or `flex-direction` into Elementor\'s custom CSS against `fct-*` selectors. The CSS wins, the widget setting is silently ignored, and both calls still report success — so the page looks wrong with nothing to point at. `validate-storefront` reports this conflict if it already exists.';
        $lines[] = '';
        $lines[] = '## Naming';
        $lines[] = '';
        $lines[] = '`fluent_cart_*` = general widgets, any page. `fluentcart_*` (no underscore after "fluent") = Theme Builder widgets, product documents only.';
        $lines[] = '';
        $lines[] = 'The store has one currency (FluentCart has no multi-currency); prices come from the store settings, so no widget takes a currency parameter.';

        $guide = implode("\n", $lines);

        return MCPHelper::envelope(
            __('How to build a FluentCart storefront with Elementor.', 'fluent-cart-elementor-blocks'),
            ['guide' => $guide]
        );
    }

    public static function updateWidget($params = [])
    {
        $postId    = (int) Arr::get($params, 'post_id', 0);
        $elementId = (string) Arr::get($params, 'element_id', '');
        $dryRun    = (bool) Arr::get($params, 'dry_run', false);

        if (!current_user_can('edit_post', $postId)) {
            return MCPHelper::error(
                'forbidden',
                sprintf(/* translators: %d: post id */ __('You cannot edit post %d.', 'fluent-cart-elementor-blocks'), $postId)
            );
        }

        $tree = ElementorDocument::read($postId);
        if (is_wp_error($tree)) {
            return $tree;
        }

        $node = &ElementorDocument::find($tree, $elementId);

        if ($node === null) {
            return MCPHelper::error(
                'element_not_found',
                sprintf(
                    /* translators: 1: element id, 2: post id */
                    __('No element "%1$s" on post %2$d.', 'fluent-cart-elementor-blocks'),
                    $elementId,
                    $postId
                )
            );
        }

        $widget = isset($node['widgetType']) ? $node['widgetType'] : '';

        // Elementor owns its own elements; editing them from here would
        // duplicate their tooling and fight their V3/V4 handling.
        if (!BuilderRegistry::isFluentCartWidget($widget)) {
            return MCPHelper::error(
                'not_a_fluent_cart_widget',
                sprintf(
                    /* translators: %s: element type */
                    __('Element "%s" is not a FluentCart widget. Use Elementor\'s own manage-elements for its elements.', 'fluent-cart-elementor-blocks'),
                    $widget ?: Arr::get($node, 'elType', 'unknown')
                )
            );
        }

        $settings = Arr::get($params, 'settings', []);
        $settings = is_array($settings) ? $settings : [];

        $schema  = WidgetSchemaReader::schemaFor($widget, ['style', 'advanced']);
        $known   = array_keys(Arr::get($schema, 'properties', []));
        $unknown = array_diff(array_keys($settings), $known);

        if ($unknown) {
            return MCPHelper::error(
                'unknown_settings',
                sprintf(
                    /* translators: 1: rejected keys, 2: widget name */
                    __('Unknown settings for %2$s: %1$s. Call get-builder-widget-schema for valid keys.', 'fluent-cart-elementor-blocks'),
                    implode(', ', $unknown),
                    $widget
                ),
                ['valid_settings' => $known]
            );
        }

        $current = isset($node['settings']) ? (array) $node['settings'] : [];

        foreach ($settings as $key => $value) {
            if ($value === null) {
                unset($current[$key]);
                continue;
            }

            $current[$key] = $value;
        }

        $node['settings'] = (object) $current;
        unset($node);

        if ($dryRun) {
            return MCPHelper::envelope(
                sprintf(/* translators: %s: widget name */ __('Dry run — %s would be updated.', 'fluent-cart-elementor-blocks'), $widget),
                ['dry_run' => true, 'element_id' => $elementId, 'settings' => $current]
            );
        }

        ElementorDocument::write($postId, $tree);

        return MCPHelper::envelope(
            sprintf(
                /* translators: 1: widget name, 2: element id */
                __('Updated %1$s (%2$s).', 'fluent-cart-elementor-blocks'),
                $widget,
                $elementId
            ),
            [
                'element_id' => $elementId,
                'post_id'    => $postId,
                'settings'   => $current,
                'edit_url'   => admin_url('post.php?post=' . $postId . '&action=elementor'),
            ]
        );
    }

    public static function removeWidget($params = [])
    {
        $postId    = (int) Arr::get($params, 'post_id', 0);
        $elementId = (string) Arr::get($params, 'element_id', '');
        $widget    = (string) Arr::get($params, 'widget', '');
        $dryRun    = (bool) Arr::get($params, 'dry_run', false);

        if (!$elementId && !$widget) {
            return MCPHelper::error(
                'nothing_to_remove',
                __('Pass element_id, or widget to remove every instance of one type.', 'fluent-cart-elementor-blocks')
            );
        }

        if ($widget && !BuilderRegistry::isFluentCartWidget($widget)) {
            return MCPHelper::error(
                'unknown_widget',
                sprintf(
                    /* translators: %s: widget name */
                    __('"%s" is not a FluentCart widget.', 'fluent-cart-elementor-blocks'),
                    $widget
                )
            );
        }

        if (!current_user_can('edit_post', $postId)) {
            return MCPHelper::error(
                'forbidden',
                sprintf(/* translators: %d: post id */ __('You cannot edit post %d.', 'fluent-cart-elementor-blocks'), $postId)
            );
        }

        $tree = ElementorDocument::read($postId);
        if (is_wp_error($tree)) {
            return $tree;
        }

        // Targeting by id must still prove the target is ours before removing —
        // an id alone says nothing about who owns the element.
        if ($elementId) {
            $target = ElementorDocument::find($tree, $elementId);

            if ($target === null) {
                return MCPHelper::error(
                    'element_not_found',
                    sprintf(
                        /* translators: 1: element id, 2: post id */
                        __('No element "%1$s" on post %2$d.', 'fluent-cart-elementor-blocks'),
                        $elementId,
                        $postId
                    )
                );
            }

            $type = isset($target['widgetType']) ? $target['widgetType'] : '';

            if (!BuilderRegistry::isFluentCartWidget($type)) {
                return MCPHelper::error(
                    'not_a_fluent_cart_widget',
                    sprintf(
                        /* translators: %s: element type */
                        __('Element "%s" is not a FluentCart widget. Use Elementor\'s manage-elements for its elements.', 'fluent-cart-elementor-blocks'),
                        $type ?: Arr::get($target, 'elType', 'unknown')
                    )
                );
            }
        }

        $removed = [];
        $next = ElementorDocument::removeWhere($tree, function ($node) use ($elementId, $widget) {
            $type = isset($node['widgetType']) ? $node['widgetType'] : '';

            if (!BuilderRegistry::isFluentCartWidget($type)) {
                return false;
            }

            if ($elementId) {
                return isset($node['id']) && $node['id'] === $elementId;
            }

            return $type === $widget;
        }, $removed);

        if (!$removed) {
            return MCPHelper::error(
                'nothing_matched',
                __('No matching FluentCart widget on that document.', 'fluent-cart-elementor-blocks')
            );
        }

        $names = array_map(function ($n) {
            return $n['widgetType'] . ' (' . $n['id'] . ')';
        }, $removed);

        if ($dryRun) {
            return MCPHelper::envelope(
                sprintf(
                    /* translators: %d: number of widgets */
                    _n('Dry run — %d widget would be removed.', 'Dry run — %d widgets would be removed.', count($removed), 'fluent-cart-elementor-blocks'),
                    count($removed)
                ),
                ['dry_run' => true, 'would_remove' => $names, 'outline' => ElementorDocument::outline($next)]
            );
        }

        ElementorDocument::write($postId, $next);

        return MCPHelper::envelope(
            sprintf(
                /* translators: 1: number of widgets, 2: post id */
                _n('Removed %1$d widget from post %2$d.', 'Removed %1$d widgets from post %2$d.', count($removed), 'fluent-cart-elementor-blocks'),
                count($removed),
                $postId
            ),
            [
                'removed'  => $names,
                'post_id'  => $postId,
                'outline'  => ElementorDocument::outline($next),
                'edit_url' => admin_url('post.php?post=' . $postId . '&action=elementor'),
            ]
        );
    }

    public static function buildStorefrontPage($params = [])
    {
        $name   = (string) Arr::get($params, 'recipe', '');
        $recipe = StorefrontRecipes::get($name);
        $dryRun = (bool) Arr::get($params, 'dry_run', false);
        $assign = Arr::get($params, 'assign', true);

        if (!$recipe) {
            return MCPHelper::error(
                'unknown_recipe',
                sprintf(
                    /* translators: 1: requested recipe, 2: valid recipes */
                    __('Unknown recipe "%1$s". Valid: %2$s.', 'fluent-cart-elementor-blocks'),
                    $name,
                    implode(', ', StorefrontRecipes::names())
                ),
                ['recipes' => StorefrontRecipes::names()]
            );
        }

        if (!WidgetSchemaReader::widget($recipe['widget'])) {
            return MCPHelper::error(
                'widget_unavailable',
                sprintf(
                    /* translators: %s: widget name */
                    __('The %s widget is not registered — check Elementor and the FluentCart Elementor Blocks addon.', 'fluent-cart-elementor-blocks'),
                    $recipe['widget']
                )
            );
        }

        $postId = (int) Arr::get($params, 'post_id', 0);
        $title  = (string) Arr::get($params, 'title', $recipe['title']);

        if ($dryRun) {
            return MCPHelper::envelope(
                sprintf(/* translators: %s: recipe name */ __('Dry run — would build the %s page.', 'fluent-cart-elementor-blocks'), $name),
                [
                    'dry_run'     => true,
                    'recipe'      => $name,
                    'widget'      => $recipe['widget'],
                    'post_id'     => $postId ?: null,
                    'would_create_page' => !$postId,
                    'setting_key' => $recipe['setting_key'],
                    'would_assign' => (bool) $assign,
                ]
            );
        }

        $created = false;

        if (!$postId) {
            $postId = wp_insert_post([
                'post_title'  => $title,
                'post_type'   => 'page',
                'post_status' => 'draft',
                'post_content' => '',
            ]);

            if (is_wp_error($postId) || !$postId) {
                return MCPHelper::error('page_create_failed', __('Could not create the page.', 'fluent-cart-elementor-blocks'));
            }

            $created = true;
        }

        if (!current_user_can('edit_post', $postId)) {
            return MCPHelper::error(
                'forbidden',
                sprintf(/* translators: %d: post id */ __('You cannot edit post %d.', 'fluent-cart-elementor-blocks'), $postId)
            );
        }

        $tree = ElementorDocument::read($postId);
        if (is_wp_error($tree)) {
            return $tree;
        }

        $node = ElementorDocument::widgetNode(ElementorDocument::newId($tree), $recipe['widget'], []);
        $next = ElementorDocument::append($tree, $node);

        if (is_wp_error($next)) {
            return $next;
        }

        ElementorDocument::write($postId, $next);

        if ($assign) {
            StorefrontRecipes::assign($recipe['setting_key'], $postId);
        }

        return MCPHelper::envelope(
            sprintf(
                /* translators: 1: recipe name, 2: post id */
                __('Built the %1$s page (post %2$d).', 'fluent-cart-elementor-blocks'),
                $name,
                $postId
            ),
            [
                'recipe'       => $name,
                'post_id'      => $postId,
                'page_created' => $created,
                'element_id'   => $node['id'],
                'assigned'     => (bool) $assign,
                'setting_key'  => $recipe['setting_key'],
                'edit_url'     => admin_url('post.php?post=' . $postId . '&action=elementor'),
                'next_step'    => $created
                    ? __('The page is a draft — publish it with Elementor\'s publish-document, then run validate-storefront.', 'fluent-cart-elementor-blocks')
                    : __('Run validate-storefront to confirm the storefront is wired up.', 'fluent-cart-elementor-blocks'),
            ]
        );
    }

    public static function buildStorefront($params = [])
    {
        if (!BuilderRegistry::addonActive()) {
            return MCPHelper::error(
                'addon_required',
                __('Activate the FluentCart Elementor Blocks plugin to build a storefront with Elementor.', 'fluent-cart-elementor-blocks')
            );
        }

        $dryRun  = (bool) Arr::get($params, 'dry_run', false);
        $publish = (bool) Arr::get($params, 'publish', false);
        $force   = (bool) Arr::get($params, 'force', false);

        $plan    = [];
        $created = [];
        $skipped = [];

        // Only build what is missing. A store usually has most of these
        // already — FluentCart's installer makes them — and rebuilding a live
        // checkout because someone asked for a redesign is not a repair.
        foreach (StorefrontRecipes::assignments() as $row) {
            $recipe = StorefrontRecipes::get($row['page']);
            $ready  = $row['assigned']
                && $row['page_status'] === 'publish'
                && StorefrontRecipes::pageCarriesContent($row['page_id'], $recipe);

            if ($ready && !$force) {
                $skipped[] = ['part' => $row['page'], 'reason' => __('already set up', 'fluent-cart-elementor-blocks'), 'post_id' => $row['page_id']];
                continue;
            }

            $plan[] = $row['page'];
        }

        $template     = ProductTemplate::status();
        $needTemplate = $template['supported'] && (!$template['has_live'] || $force);

        if ($template['supported'] && !$needTemplate) {
            $skipped[] = ['part' => 'product_template', 'reason' => __('a published template already applies', 'fluent-cart-elementor-blocks')];
        }

        if (!$template['supported']) {
            $skipped[] = ['part' => 'product_template', 'reason' => $template['reason']];
        }

        if ($dryRun) {
            return MCPHelper::envelope(
                sprintf(
                    /* translators: 1: parts to build, 2: parts skipped */
                    __('Dry run — would build %1$d part(s), skip %2$d.', 'fluent-cart-elementor-blocks'),
                    count($plan) + ($needTemplate ? 1 : 0),
                    count($skipped)
                ),
                [
                    'dry_run'          => true,
                    'would_build'      => array_merge($plan, $needTemplate ? ['product_template'] : []),
                    'would_skip'       => $skipped,
                    'would_publish'    => $publish,
                ]
            );
        }

        foreach ($plan as $name) {
            $result = self::buildStorefrontPage(['recipe' => $name, 'assign' => true]);

            if (is_wp_error($result)) {
                $created[] = ['part' => $name, 'error' => $result->get_error_message()];
                continue;
            }

            $data   = Arr::get($result, 'data', []);
            $postId = Arr::get($data, 'post_id');

            if ($postId && $publish) {
                wp_update_post(['ID' => $postId, 'post_status' => 'publish']);
            }

            $created[] = [
                'part'       => $name,
                'post_id'    => $postId,
                'element_id' => Arr::get($data, 'element_id'),
                'edit_url'   => Arr::get($data, 'edit_url'),
            ];
        }

        if ($needTemplate) {
            $result = self::buildProductTemplate(['publish' => $publish, 'replace_existing' => true]);

            if (is_wp_error($result)) {
                $created[] = ['part' => 'product_template', 'error' => $result->get_error_message()];
            } else {
                $data = Arr::get($result, 'data', []);
                $created[] = [
                    'part'        => 'product_template',
                    'post_id'     => Arr::get($data, 'post_id'),
                    'containers'  => Arr::get($data, 'containers'),
                    'element_ids' => Arr::get($data, 'element_ids'),
                    'edit_url'    => Arr::get($data, 'edit_url'),
                ];
            }
        }

        $validation = self::validateStorefront();
        $findings   = Arr::get($validation, 'data.findings', []);

        return MCPHelper::envelope(
            sprintf(
                /* translators: 1: built count, 2: skipped count, 3: findings count */
                __('Storefront: built %1$d part(s), skipped %2$d, %3$d issue(s) remaining.', 'fluent-cart-elementor-blocks'),
                count($created),
                count($skipped),
                count($findings)
            ),
            [
                'built'     => $created,
                'skipped'   => $skipped,
                'published' => $publish,
                'findings'  => $findings,
                'next_step' => __('Now style it with Elementor: use the returned container and element ids with manage-elements and build-composition, and set global colours and fonts on the kit. The homepage is ordinary design work — build it with Elementor\'s own tools and place FluentCart widgets into it with place-builder-widget.', 'fluent-cart-elementor-blocks'),
            ]
        );
    }

    public static function buildProductTemplate($params = [])
    {
        if (!BuilderRegistry::elementorProActive()) {
            return MCPHelper::error(
                'elementor_pro_required',
                __('Theme Builder product templates need Elementor Pro.', 'fluent-cart-elementor-blocks')
            );
        }

        if (!BuilderRegistry::addonActive()) {
            return MCPHelper::error(
                'addon_required',
                __('Activate the FluentCart Elementor Blocks plugin to use FluentCart product widgets.', 'fluent-cart-elementor-blocks')
            );
        }

        $dryRun  = (bool) Arr::get($params, 'dry_run', false);
        $replace = Arr::get($params, 'replace_existing', true);
        $publish = (bool) Arr::get($params, 'publish', false);
        $title   = (string) Arr::get($params, 'title', __('Single Product', 'fluent-cart-elementor-blocks'));

        $existing = ProductTemplate::liveTemplates();
        $style    = Arr::get($params, 'layout', 'individual') === 'composite' ? 'composite' : 'individual';
        $layout   = ProductTemplate::defaultLayout($style);

        if ($dryRun) {
            return MCPHelper::envelope(
                __('Dry run — would build the single product template.', 'fluent-cart-elementor-blocks'),
                [
                    'dry_run'            => true,
                    'title'              => $title,
                    'condition'          => ProductTemplate::CONDITION,
                    'widgets'            => array_merge($layout['left'], $layout['right'], $layout['below']),
                    'existing_live'      => array_map(function ($t) {
                        return ['post_id' => $t['post_id'], 'title' => $t['title']];
                    }, $existing),
                    'would_unpublish'    => $replace ? count($existing) : 0,
                ]
            );
        }

        $unpublished = [];

        if ($replace) {
            foreach ($existing as $t) {
                ProductTemplate::unpublish($t['post_id']);
                $unpublished[] = sprintf('"%s" (#%d)', $t['title'], $t['post_id']);
            }
        }

        $postId = ProductTemplate::createDocument($title);

        if (is_wp_error($postId)) {
            return $postId;
        }

        // The composite widget draws its own two-column layout internally, so
        // wrapping it in empty columns would only add dead containers.
        $tree = $style === 'composite'
            ? [self::container('product', [])]
            : [
                self::container('row', [
                    self::container('col-left', []),
                    self::container('col-right', []),
                ]),
                self::container('below', []),
            ];

        $ids = [];
        $place = function ($widgets, &$parent) use (&$ids, &$tree) {
            foreach ($widgets as $widget) {
                if (!WidgetSchemaReader::widget($widget)) {
                    continue;
                }

                $node = ElementorDocument::widgetNode(ElementorDocument::newId($tree), $widget, []);
                $parent['elements'][] = $node;
                $ids[$widget] = $node['id'];
            }
        };

        if ($style === 'composite') {
            $place($layout['below'], $tree[0]);
        } else {
            $place($layout['left'], $tree[0]['elements'][0]);
            $place($layout['right'], $tree[0]['elements'][1]);
            $place($layout['below'], $tree[1]);
        }

        ElementorDocument::write($postId, $tree);

        if ($publish) {
            wp_update_post(['ID' => $postId, 'post_status' => 'publish']);
            // Elementor caches which template claims which location; a status
            // change does not invalidate it, so the template would be live and
            // silently not applied.
            ProductTemplate::flushConditions();
        }

        return MCPHelper::envelope(
            sprintf(
                /* translators: 1: template title, 2: post id */
                __('Built the "%1$s" product template (#%2$d).', 'fluent-cart-elementor-blocks'),
                $title,
                $postId
            ),
            [
                'post_id'      => $postId,
                'condition'    => ProductTemplate::CONDITION,
                'published'    => $publish,
                'element_ids'  => $ids,
                'layout'       => $style,
                'containers'   => $style === 'composite'
                    ? ['product' => $tree[0]['id']]
                    : [
                        'row'       => $tree[0]['id'],
                        'col_left'  => $tree[0]['elements'][0]['id'],
                        'col_right' => $tree[0]['elements'][1]['id'],
                        'below'     => $tree[1]['id'],
                    ],
                'unpublished'  => $unpublished,
                'edit_url'     => admin_url('post.php?post=' . $postId . '&action=elementor'),
                'next_step'    => $publish
                    ? __('Style the containers with Elementor\'s manage-elements, then run validate-storefront.', 'fluent-cart-elementor-blocks')
                    : __('Style it with Elementor\'s manage-elements, publish it with publish-document, then run validate-storefront.', 'fluent-cart-elementor-blocks'),
            ]
        );
    }

    /** A bare flexbox container node, ready to receive widgets. */
    private static function container($label, array $children)
    {
        return [
            'id'       => substr(md5($label . uniqid('', true)), 0, 8),
            'elType'   => 'e-flexbox',
            'settings' => (object) [],
            'elements' => $children,
        ];
    }

    public static function validateStorefront($params = [])
    {
        $rows     = StorefrontRecipes::assignments();
        $recipes  = StorefrontRecipes::all();
        $findings = [];

        foreach ($rows as $row) {
            $recipe = $recipes[$row['page']];

            if (!$row['assigned']) {
                $findings[] = [
                    'page'     => $row['page'],
                    'severity' => 'error',
                    'issue'    => sprintf(
                        /* translators: 1: page name, 2: setting key */
                        __('No page assigned for %1$s (%2$s).', 'fluent-cart-elementor-blocks'),
                        $row['page'],
                        $row['setting_key']
                    ),
                    'fix'      => sprintf(
                        /* translators: %s: recipe name */
                        __('Run build-storefront-page with recipe "%s".', 'fluent-cart-elementor-blocks'),
                        $row['page']
                    ),
                ];
                continue;
            }

            if ($row['page_status'] !== 'publish') {
                $findings[] = [
                    'page'     => $row['page'],
                    'severity' => 'error',
                    'issue'    => sprintf(
                        /* translators: 1: page title, 2: status */
                        __('%1$s is assigned but its status is "%2$s" — customers cannot reach it.', 'fluent-cart-elementor-blocks'),
                        $row['page_title'],
                        $row['page_status']
                    ),
                    'fix'      => __('Publish the page (Elementor\'s publish-document, or the WordPress editor).', 'fluent-cart-elementor-blocks'),
                ];
            }

            // An assigned, published page that never got its content renders an
            // empty screen at the worst possible moment — checkout. The content
            // may be an Elementor widget, a block or a shortcode; all three count.
            if (!StorefrontRecipes::pageCarriesContent($row['page_id'], $recipe)) {
                $findings[] = [
                    'page'     => $row['page'],
                    'severity' => 'warning',
                    'issue'    => sprintf(
                        /* translators: 1: page title, 2: widget name */
                        __('%1$s has none of the %2$s widget, block or shortcode.', 'fluent-cart-elementor-blocks'),
                        $row['page_title'],
                        $recipe['widget']
                    ),
                    'fix'      => __('Place it with place-builder-widget, or add the equivalent block/shortcode.', 'fluent-cart-elementor-blocks'),
                ];
            }
        }

        // A product template that is a draft, or has no display condition, is
        // inert: product pages quietly fall back to FluentCart's own rendering,
        // which reads as "my template was ignored" rather than "not finished".
        $template = ProductTemplate::status();

        if ($template['supported']) {
            // Two published templates with the same condition both claim every
            // product page. Elementor silently picks one, so an edit to the
            // other looks like it did nothing — the exact symptom of a rebuild
            // that left the first version behind.
            $live = array_values(array_filter($template['templates'], function ($t) {
                return $t['published'] && !empty($t['conditions']);
            }));

            if (count($live) > 1) {
                $findings[] = [
                    'page'     => 'product_template',
                    'severity' => 'error',
                    'issue'    => sprintf(
                        /* translators: 1: count, 2: template list */
                        __('%1$d published product templates target the same products: %2$s. Only one applies and Elementor chooses which, so edits to the others appear to do nothing.', 'fluent-cart-elementor-blocks'),
                        count($live),
                        implode(', ', array_map(function ($t) {
                            return sprintf('"%s" (#%d)', $t['title'], $t['post_id']);
                        }, $live))
                    ),
                    'fix'      => __('Keep one. Unpublish or delete the rest with Elementor\'s manage-site-parts, or narrow their conditions.', 'fluent-cart-elementor-blocks'),
                ];
            }

            if (empty($template['templates'])) {
                $findings[] = [
                    'page'     => 'product_template',
                    'severity' => 'info',
                    'issue'    => __('No FluentCart product template — single product pages use FluentCart\'s default rendering.', 'fluent-cart-elementor-blocks'),
                    'fix'      => $template['how_to_create'],
                ];
            } elseif (empty($template['has_live'])) {
                foreach ($template['templates'] as $tpl) {
                    if (!$tpl['published']) {
                        $findings[] = [
                            'page'     => 'product_template',
                            'severity' => 'warning',
                            'issue'    => sprintf(
                                /* translators: 1: template title, 2: status */
                                __('Product template "%1$s" is %2$s, so it does not render.', 'fluent-cart-elementor-blocks'),
                                $tpl['title'],
                                $tpl['status']
                            ),
                            'fix'      => __('Publish it with Elementor\'s publish-document.', 'fluent-cart-elementor-blocks'),
                        ];
                    } elseif (empty($tpl['conditions'])) {
                        $findings[] = [
                            'page'     => 'product_template',
                            'severity' => 'warning',
                            'issue'    => sprintf(
                                /* translators: %s: template title */
                                __('Product template "%s" is published but has no display condition, so it never applies.', 'fluent-cart-elementor-blocks'),
                                $tpl['title']
                            ),
                            'fix'      => __('Set conditions ["include/fluentcart_product"] via Elementor\'s manage-site-parts update.', 'fluent-cart-elementor-blocks'),
                        ];
                    }
                }
            }
        }

        // Custom CSS that re-positions FluentCart markup defeats the layout
        // controls without either side reporting a problem. Reported here
        // because the page looks wrong while every call looked successful.
        foreach (StyleConflicts::summarise() as $conflict) {
            $findings[] = [
                'page'     => 'theme_styles',
                'severity' => 'warning',
                'issue'    => sprintf(
                    /* translators: 1: CSS property, 2: number of selectors, 3: the selectors */
                    __('Custom CSS in the Elementor kit sets %1$s on %2$d FluentCart selector(s), which overrides the widget layout settings: %3$s', 'fluent-cart-elementor-blocks'),
                    $conflict['property'],
                    $conflict['count'],
                    implode(', ', array_slice($conflict['selectors'], 0, 5))
                ),
                'fix'      => sprintf(
                    /* translators: %s: CSS property */
                    __('Reorder with the widget\'s own layout control (card_elements, shop_layout) and drop the %s rules, or accept that the CSS wins and leave the layout control alone.', 'fluent-cart-elementor-blocks'),
                    $conflict['property']
                ),
            ];
        }

        $errors = array_filter($findings, function ($f) {
            return $f['severity'] === 'error';
        });

        return MCPHelper::envelope(
            $findings
                ? sprintf(
                    /* translators: 1: total findings, 2: error count */
                    __('%1$d storefront issue(s), %2$d blocking.', 'fluent-cart-elementor-blocks'),
                    count($findings),
                    count($errors)
                )
                : __('Storefront looks correctly wired up.', 'fluent-cart-elementor-blocks'),
            [
                'pages'    => $rows,
                'findings' => array_values($findings),
                'healthy'  => empty($findings),
            ]
        );
    }
}
