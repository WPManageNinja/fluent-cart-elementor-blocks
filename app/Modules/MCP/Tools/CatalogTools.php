<?php

namespace FluentCartElementorBlocks\App\Modules\MCP\Tools;

use FluentCart\App\Models\AttributeGroup;
use FluentCart\App\Models\AttributeRelation;
use FluentCart\App\Models\AttributeTerm;
use FluentCart\App\Models\Product;
use FluentCart\App\Models\ProductDetail;
use FluentCart\App\Models\ProductMeta;
use FluentCart\App\Models\ProductVariation;
use FluentCart\App\Modules\MCP\Support\MCPHelper;
use FluentCart\App\Modules\MCP\Support\PermissionGate;
use FluentCart\Framework\Support\Arr;

/**
 * Reading and writing the variation catalogue: attribute groups, their terms,
 * and how a product's variants map onto them.
 *
 * Every other write ability here works on records a store already has — an
 * order's status, a coupon's amount. This one exists because an agent asked to
 * build a storefront could not build the thing the storefront is for. It could
 * place a product page, style it, publish it, and still had no way to give the
 * product the colour swatches that page was designed around: swatches come
 * from advanced variations, and nothing in the MCP could create an attribute
 * group, define a colour term, or point a variant at one.
 *
 * The shape being written, in FluentCart's own terms:
 *
 *   fct_atts_groups      a dimension — "Colour", "Size"
 *   fct_atts_terms       a value in it — "Mushroom" (#B5A897), "US 10"
 *   attribute_config     which groups/terms a PRODUCT offers, in other_info
 *   fct_atts_relations   which terms a VARIANT is, one row per group
 *   variations.media_id  the image a variant swaps the gallery to
 *
 * All five have to agree or the selector renders half-built, so the write path
 * updates them together rather than exposing five endpoints that can disagree.
 */
class CatalogTools
{
    /** Group settings.type — decides what a term must carry and how it renders. */
    const GROUP_TYPES = ['options', 'color', 'image', 'text'];

    /** Group settings.styling — swatches unless explicitly a dropdown. */
    const GROUP_STYLINGS = ['swatch', 'dropdown'];

    const VARIATION_TYPES = ['simple_variations', 'advanced_variations'];

    /**
     * Coarse gate for the write abilities; the precise capability is checked
     * per action inside each handler, because core's own attribute routes
     * separate create, edit and delete and this must not be looser than them.
     */
    const WRITE_CAPS = ['products/create', 'products/edit', 'products/delete'];

    /** The capability core requires for the same operation over REST. */
    private static function requireAction($action)
    {
        $map = [
            'create' => 'products/create',
            'update' => 'products/edit',
            'delete' => 'products/delete',
        ];

        $permission = isset($map[$action]) ? $map[$action] : null;

        if (!$permission) {
            return MCPHelper::error('unknown_action', __('action must be create, update or delete.', 'fluent-cart-elementor-blocks'));
        }

        if (!PermissionGate::can($permission)) {
            return MCPHelper::error('forbidden', sprintf(
                /* translators: %s: capability name */
                __('This action needs the %s capability.', 'fluent-cart-elementor-blocks'),
                $permission
            ));
        }

        return null;
    }

    public static function definitions()
    {
        return [
            'fluent-cart/list-attribute-groups' => [
                'label'       => __('List Attribute Groups', 'fluent-cart-elementor-blocks'),
                'description' => __('The variation attribute library: groups such as Colour or Size, each with its terms. type tells you what a term carries and how it renders — color terms hold a hex value and draw a dot, image terms hold an image URL and draw a thumbnail, options and text draw a labelled chip. styling "dropdown" renders a select instead of swatches. Read this before building a product page with swatches, and before creating a group, because the store is seeded with system groups you should reuse rather than duplicate.', 'fluent-cart-elementor-blocks'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'group_id'      => ['type' => 'integer', 'description' => 'One group only. Omit for all.'],
                        'search'        => ['type' => 'string', 'description' => 'Matches group title or slug.'],
                        'include_terms' => ['type' => 'boolean', 'description' => 'Include each group\'s terms. Default true.'],
                    ],
                ],
                'execute_callback'    => [self::class, 'listGroups'],
                'permission_callback' => function () {
                    return PermissionGate::canAny(PermissionGate::readRoleCaps());
                },
                'annotations' => ['readonly' => true],
            ],

            'fluent-cart/manage-attribute-group' => [
                'label'       => __('Manage Attribute Group', 'fluent-cart-elementor-blocks'),
                'description' => __('Create, update or delete a variation attribute group. type decides what its terms must carry: "color" requires a hex value per term, "image" requires an image URL, "options" and "text" need neither. styling "dropdown" renders a select rather than swatches. Deleting a group deletes its terms and unlinks every variant using them, so it refuses system groups outright and needs confirm:true for the rest.', 'fluent-cart-elementor-blocks'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'action'   => ['type' => 'string', 'enum' => ['create', 'update', 'delete']],
                        'group_id' => ['type' => 'integer', 'description' => 'Required for update and delete.'],
                        'title'    => ['type' => 'string', 'description' => 'Required for create. Max 50 characters.'],
                        'slug'     => ['type' => 'string', 'description' => 'Defaults to a slug of the title.'],
                        'type'     => ['type' => 'string', 'enum' => self::GROUP_TYPES, 'description' => 'Default options.'],
                        'styling'  => ['type' => 'string', 'enum' => self::GROUP_STYLINGS, 'description' => 'Default swatch.'],
                        'confirm'  => ['type' => 'boolean', 'description' => 'Required for delete.'],
                        'dry_run'  => ['type' => 'boolean'],
                    ],
                    'required' => ['action'],
                ],
                'execute_callback'    => [self::class, 'manageGroup'],
                'permission_callback' => function () {
                    return PermissionGate::canAny(self::WRITE_CAPS);
                },
                'annotations' => ['destructive' => true],
            ],

            'fluent-cart/manage-attribute-terms' => [
                'label'       => __('Manage Attribute Terms', 'fluent-cart-elementor-blocks'),
                'description' => __('Create, update or delete the terms inside one attribute group — the individual colours, sizes or materials. A term in a "color" group needs settings.color as a hex value (#rrggbb); a term in an "image" group needs settings.image as a URL. Create accepts up to 10 terms per call, matching the admin UI. Deleting a term unlinks every variant using it.', 'fluent-cart-elementor-blocks'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'action'   => ['type' => 'string', 'enum' => ['create', 'update', 'delete']],
                        'group_id' => ['type' => 'integer', 'description' => 'The group that owns the terms.'],
                        'term_id'  => ['type' => 'integer', 'description' => 'Required for update and delete.'],
                        'title'    => ['type' => 'string', 'description' => 'For update.'],
                        'color'    => ['type' => 'string', 'description' => 'Hex value for a colour term, e.g. #B5A897.'],
                        'image'    => ['type' => 'string', 'description' => 'Image URL for an image term.'],
                        'terms'    => [
                            'type'        => 'array',
                            'description' => 'For create: up to 10 rows of {title, color?, image?}.',
                            'items'       => [
                                'type'       => 'object',
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'color' => ['type' => 'string'],
                                    'image' => ['type' => 'string'],
                                ],
                            ],
                        ],
                        'confirm' => ['type' => 'boolean', 'description' => 'Required for delete.'],
                        'dry_run' => ['type' => 'boolean'],
                    ],
                    'required' => ['action', 'group_id'],
                ],
                'execute_callback'    => [self::class, 'manageTerms'],
                'permission_callback' => function () {
                    return PermissionGate::canAny(self::WRITE_CAPS);
                },
                'annotations' => ['destructive' => true],
            ],

            'fluent-cart/get-product-variations' => [
                'label'       => __('Get Product Variations', 'fluent-cart-elementor-blocks'),
                'description' => __('How one product is varied: its variation_type, the attribute groups and terms it offers, and every variant with its price, SKU, stock, image and the terms it maps to. Call this before manage-product-variations, and to diagnose a swatch row that renders wrong — the usual causes are visible here as variants with no terms, or an advanced product with an empty attribute_config.', 'fluent-cart-elementor-blocks'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'product_id' => ['type' => 'integer'],
                    ],
                    'required' => ['product_id'],
                ],
                'execute_callback'    => [self::class, 'getVariations'],
                'permission_callback' => function () {
                    return PermissionGate::canAny(PermissionGate::readRoleCaps());
                },
                'annotations' => ['readonly' => true],
            ],

            'fluent-cart/manage-product-variations' => [
                'label'       => __('Manage Product Variations', 'fluent-cart-elementor-blocks'),
                'description' => __('Set how a product is varied, and configure its variants. This is what turns a plain product into one with colour swatches: set variation_type to advanced_variations, declare which attribute groups and terms it offers, then give each variant its terms, price, SKU and image. A variant with no variation_id is created. Amounts are in cents. Setting media_id is what makes the gallery change when a swatch is clicked. Run with dry_run first — this rewrites the variant set.', 'fluent-cart-elementor-blocks'),
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'product_id'       => ['type' => 'integer'],
                        'variation_type'   => ['type' => 'string', 'enum' => self::VARIATION_TYPES],
                        'attribute_groups' => [
                            'type'        => 'array',
                            'description' => 'Which groups and terms this product offers. Replaces the product\'s attribute_config.',
                            'items'       => [
                                'type'       => 'object',
                                'properties' => [
                                    'group_id' => ['type' => 'integer'],
                                    'term_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                                ],
                            ],
                        ],
                        'variants' => [
                            'type'        => 'array',
                            'description' => 'Variants to create or update. Omit variation_id to create.',
                            'items'       => [
                                'type'       => 'object',
                                'properties' => [
                                    'variation_id' => ['type' => 'integer'],
                                    'title'        => ['type' => 'string'],
                                    'price_cents'  => ['type' => 'integer', 'description' => 'Amount in cents.'],
                                    'compare_price_cents' => ['type' => 'integer', 'description' => 'Was-price in cents, for a sale badge.'],
                                    'sku'          => ['type' => 'string'],
                                    'media_id'     => ['type' => 'integer', 'description' => 'Attachment id. Swapped into the gallery when this variant is chosen.'],
                                    'stock'        => ['type' => 'integer', 'description' => 'Available stock. Implies manage_stock.'],
                                    'terms'        => [
                                        'type'        => 'array',
                                        'description' => 'One term per attribute group, as {group_id, term_id}.',
                                        'items'       => [
                                            'type'       => 'object',
                                            'properties' => [
                                                'group_id' => ['type' => 'integer'],
                                                'term_id'  => ['type' => 'integer'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'remove_missing_variants' => ['type' => 'boolean', 'description' => 'Delete variants not listed in variants. Default false.'],
                        'dry_run'                 => ['type' => 'boolean'],
                    ],
                    'required' => ['product_id'],
                ],
                'execute_callback'    => [self::class, 'manageVariations'],
                'permission_callback' => function () {
                    return PermissionGate::canAny(self::WRITE_CAPS);
                },
                'annotations' => ['destructive' => true],
            ],
        ];
    }

    // ---------------------------------------------------------------- groups

    public static function listGroups($params = [])
    {
        $query = AttributeGroup::query()->orderBy('serial', 'ASC')->orderBy('id', 'ASC');

        if ($groupId = (int) Arr::get($params, 'group_id', 0)) {
            $query->where('id', $groupId);
        }

        if ($search = trim((string) Arr::get($params, 'search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', '%' . $search . '%')
                  ->orWhere('slug', 'LIKE', '%' . $search . '%');
            });
        }

        $groups        = $query->get();
        $includeTerms  = Arr::get($params, 'include_terms', true);
        $termsByGroup  = [];

        if ($includeTerms && count($groups)) {
            $ids = [];
            foreach ($groups as $group) {
                $ids[] = $group->id;
            }

            foreach (AttributeTerm::query()->whereIn('group_id', $ids)->orderBy('serial', 'ASC')->get() as $term) {
                $termsByGroup[$term->group_id][] = self::termRow($term);
            }
        }

        $rows = [];

        foreach ($groups as $group) {
            $row = self::groupRow($group);

            if ($includeTerms) {
                $row['terms']      = isset($termsByGroup[$group->id]) ? $termsByGroup[$group->id] : [];
                $row['term_count'] = count($row['terms']);
            }

            $rows[] = $row;
        }

        return MCPHelper::envelope(
            sprintf(
                /* translators: %d: group count */
                _n('%d attribute group.', '%d attribute groups.', count($rows), 'fluent-cart-elementor-blocks'),
                count($rows)
            ),
            ['groups' => $rows]
        );
    }

    public static function manageGroup($params = [])
    {
        $action = (string) Arr::get($params, 'action', '');
        $dryRun = (bool) Arr::get($params, 'dry_run', false);

        if ($denied = self::requireAction($action)) {
            return $denied;
        }

        if ($action === 'create') {
            $title = trim((string) Arr::get($params, 'title', ''));

            if ($title === '') {
                return MCPHelper::error('title_required', __('A title is required to create a group.', 'fluent-cart-elementor-blocks'));
            }

            $type    = self::validType(Arr::get($params, 'type', 'options'));
            $styling = self::validStyling(Arr::get($params, 'styling', 'swatch'));

            if (is_wp_error($type)) {
                return $type;
            }
            if (is_wp_error($styling)) {
                return $styling;
            }

            $slug = sanitize_title((string) Arr::get($params, 'slug', $title));
            $slug = self::uniqueGroupSlug($slug);

            if ($dryRun) {
                return MCPHelper::envelope(
                    __('Dry run — would create the attribute group.', 'fluent-cart-elementor-blocks'),
                    ['dry_run' => true, 'title' => $title, 'slug' => $slug, 'type' => $type, 'styling' => $styling]
                );
            }

            $group = AttributeGroup::create([
                'title'    => $title,
                'slug'     => $slug,
                'serial'   => (int) AttributeGroup::query()->max('serial') + 1,
                'settings' => self::groupSettings($type, $styling),
            ]);

            return MCPHelper::envelope(
                sprintf(/* translators: %s: group title */ __('Created the "%s" attribute group.', 'fluent-cart-elementor-blocks'), $title),
                ['group' => self::groupRow($group)]
            );
        }

        $groupId = (int) Arr::get($params, 'group_id', 0);
        $group   = $groupId ? AttributeGroup::query()->find($groupId) : null;

        if (!$group) {
            return MCPHelper::error('group_not_found', __('No attribute group with that id. Call list-attribute-groups.', 'fluent-cart-elementor-blocks'));
        }

        if ($action === 'update') {
            $settings = is_array($group->settings) ? $group->settings : [];
            $changes  = [];

            if (($title = trim((string) Arr::get($params, 'title', ''))) !== '') {
                $group->title = $title;
                $changes[]    = 'title';
            }

            if (Arr::get($params, 'type') !== null) {
                $type = self::validType(Arr::get($params, 'type'));

                if (is_wp_error($type)) {
                    return $type;
                }

                $settings['type'] = $type;
                $changes[]        = 'type';
            }

            if (Arr::get($params, 'styling') !== null) {
                $styling = self::validStyling(Arr::get($params, 'styling'));

                if (is_wp_error($styling)) {
                    return $styling;
                }

                $settings['styling'] = $styling === 'swatch' ? '' : $styling;
                $changes[]           = 'styling';
            }

            if (!$changes) {
                return MCPHelper::error('nothing_to_update', __('Pass title, type or styling.', 'fluent-cart-elementor-blocks'));
            }

            if ($dryRun) {
                return MCPHelper::envelope(
                    __('Dry run — would update the group.', 'fluent-cart-elementor-blocks'),
                    ['dry_run' => true, 'group_id' => $group->id, 'would_change' => $changes]
                );
            }

            $group->settings = $settings;
            $group->save();

            // A colour group whose terms carry no hex renders empty dots, and
            // the group is the only place that type is known — so say which
            // terms the type change has just invalidated.
            $incomplete = self::termsMissingSettings($group);

            return MCPHelper::envelope(
                sprintf(/* translators: %s: group title */ __('Updated the "%s" attribute group.', 'fluent-cart-elementor-blocks'), $group->title),
                [
                    'group'            => self::groupRow($group),
                    'changed'          => $changes,
                    'terms_incomplete' => $incomplete,
                ]
            );
        }

        if ($action === 'delete') {
            if ($group->is_system) {
                return MCPHelper::error(
                    'system_group',
                    sprintf(
                        /* translators: %s: group title */
                        __('"%s" is a built-in group and cannot be deleted. Create your own group instead.', 'fluent-cart-elementor-blocks'),
                        $group->title
                    )
                );
            }

            $termIds = AttributeTerm::query()->where('group_id', $group->id)->get()->pluck('id')->toArray();
            $links   = $termIds
                ? AttributeRelation::query()->whereIn('term_id', $termIds)->count()
                : 0;

            if (!Arr::get($params, 'confirm') || $dryRun) {
                return MCPHelper::envelope(
                    __('Confirm required — deleting a group removes its terms and unlinks every variant using them.', 'fluent-cart-elementor-blocks'),
                    [
                        'dry_run'          => true,
                        'group_id'         => $group->id,
                        'terms'            => count($termIds),
                        'variant_links'    => $links,
                        'pass_confirm'     => true,
                    ]
                );
            }

            if ($termIds) {
                AttributeRelation::query()->whereIn('term_id', $termIds)->delete();
                AttributeTerm::query()->whereIn('id', $termIds)->delete();
            }

            $title = $group->title;
            $group->delete();

            return MCPHelper::envelope(
                sprintf(/* translators: %s: group title */ __('Deleted the "%s" group.', 'fluent-cart-elementor-blocks'), $title),
                ['deleted_terms' => count($termIds), 'unlinked_variants' => $links]
            );
        }

        return MCPHelper::error('unknown_action', __('action must be create, update or delete.', 'fluent-cart-elementor-blocks'));
    }

    // ----------------------------------------------------------------- terms

    public static function manageTerms($params = [])
    {
        $groupId = (int) Arr::get($params, 'group_id', 0);
        $group   = AttributeGroup::query()->find($groupId);

        if (!$group) {
            return MCPHelper::error('group_not_found', __('No attribute group with that id. Call list-attribute-groups.', 'fluent-cart-elementor-blocks'));
        }

        $action = (string) Arr::get($params, 'action', '');
        $dryRun = (bool) Arr::get($params, 'dry_run', false);
        $type   = Arr::get(is_array($group->settings) ? $group->settings : [], 'type', 'options');

        if ($denied = self::requireAction($action)) {
            return $denied;
        }

        if ($action === 'create') {
            $rows = Arr::get($params, 'terms', []);

            if (!is_array($rows) || !$rows) {
                return MCPHelper::error('terms_required', __('Pass terms as an array of {title, color?, image?}.', 'fluent-cart-elementor-blocks'));
            }

            // The admin UI caps a create at ten; matching it keeps a runaway
            // agent from writing a hundred rows in one unreviewable call.
            if (count($rows) > 10) {
                return MCPHelper::error('too_many_terms', __('Create at most 10 terms per call.', 'fluent-cart-elementor-blocks'));
            }

            $prepared = [];

            foreach ($rows as $index => $row) {
                $prepared[] = self::prepareTerm($row, $type, $group, $index);
            }

            foreach ($prepared as $item) {
                if (is_wp_error($item)) {
                    return $item;
                }
            }

            if ($dryRun) {
                return MCPHelper::envelope(
                    __('Dry run — would create the terms.', 'fluent-cart-elementor-blocks'),
                    ['dry_run' => true, 'group' => $group->title, 'terms' => $prepared]
                );
            }

            $serial  = (int) AttributeTerm::query()->where('group_id', $group->id)->max('serial');
            $created = [];

            foreach ($prepared as $item) {
                $term = AttributeTerm::create([
                    'group_id' => $group->id,
                    'serial'   => ++$serial,
                    'title'    => $item['title'],
                    'slug'     => $item['slug'],
                    'settings' => $item['settings'],
                ]);

                $created[] = self::termRow($term);
            }

            return MCPHelper::envelope(
                sprintf(
                    /* translators: 1: term count, 2: group title */
                    __('Created %1$d term(s) in "%2$s".', 'fluent-cart-elementor-blocks'),
                    count($created),
                    $group->title
                ),
                ['group_id' => $group->id, 'terms' => $created]
            );
        }

        $termId = (int) Arr::get($params, 'term_id', 0);
        $term   = $termId ? AttributeTerm::query()->where('group_id', $group->id)->find($termId) : null;

        if (!$term) {
            return MCPHelper::error('term_not_found', __('No term with that id in this group.', 'fluent-cart-elementor-blocks'));
        }

        if ($action === 'update') {
            $settings = is_array($term->settings) ? $term->settings : [];
            $changes  = [];

            if (($title = trim((string) Arr::get($params, 'title', ''))) !== '') {
                $term->title = $title;
                $changes[]   = 'title';
            }

            if (($color = (string) Arr::get($params, 'color', '')) !== '') {
                $hex = sanitize_hex_color($color);

                if (!$hex) {
                    return MCPHelper::error('invalid_color', __('color must be a hex value such as #B5A897.', 'fluent-cart-elementor-blocks'));
                }

                $settings['color'] = $hex;
                $changes[]         = 'color';
            }

            if (($image = (string) Arr::get($params, 'image', '')) !== '') {
                if (!filter_var($image, FILTER_VALIDATE_URL)) {
                    return MCPHelper::error('invalid_image', __('image must be a URL.', 'fluent-cart-elementor-blocks'));
                }

                $settings['image'] = esc_url_raw($image);
                $changes[]         = 'image';
            }

            if (!$changes) {
                return MCPHelper::error('nothing_to_update', __('Pass title, color or image.', 'fluent-cart-elementor-blocks'));
            }

            if ($dryRun) {
                return MCPHelper::envelope(
                    __('Dry run — would update the term.', 'fluent-cart-elementor-blocks'),
                    ['dry_run' => true, 'term_id' => $term->id, 'would_change' => $changes]
                );
            }

            $term->settings = $settings;
            $term->save();

            return MCPHelper::envelope(
                sprintf(/* translators: %s: term title */ __('Updated the "%s" term.', 'fluent-cart-elementor-blocks'), $term->title),
                ['term' => self::termRow($term), 'changed' => $changes]
            );
        }

        if ($action === 'delete') {
            $links = AttributeRelation::query()->where('term_id', $term->id)->count();

            if (!Arr::get($params, 'confirm') || $dryRun) {
                return MCPHelper::envelope(
                    __('Confirm required — deleting a term unlinks every variant using it.', 'fluent-cart-elementor-blocks'),
                    ['dry_run' => true, 'term_id' => $term->id, 'variant_links' => $links, 'pass_confirm' => true]
                );
            }

            AttributeRelation::query()->where('term_id', $term->id)->delete();
            $title = $term->title;
            $term->delete();

            return MCPHelper::envelope(
                sprintf(/* translators: %s: term title */ __('Deleted the "%s" term.', 'fluent-cart-elementor-blocks'), $title),
                ['unlinked_variants' => $links]
            );
        }

        return MCPHelper::error('unknown_action', __('action must be create, update or delete.', 'fluent-cart-elementor-blocks'));
    }

    // ------------------------------------------------------------ variations

    public static function getVariations($params = [])
    {
        $productId = (int) Arr::get($params, 'product_id', 0);
        $product   = Product::query()->find($productId);

        if (!$product) {
            return MCPHelper::error('product_not_found', __('No product with that id. Call list-products.', 'fluent-cart-elementor-blocks'));
        }

        $detail        = ProductDetail::query()->where('post_id', $productId)->first();
        $otherInfo     = self::otherInfo($detail);
        $config        = Arr::get($otherInfo, 'attribute_config', []);
        $variationType = $detail ? $detail->variation_type : null;

        $variants  = ProductVariation::query()->where('post_id', $productId)->orderBy('serial_index', 'ASC')->get();
        $variantIds = [];
        foreach ($variants as $variant) {
            $variantIds[] = $variant->id;
        }

        $relations = $variantIds
            ? AttributeRelation::query()->whereIn('object_id', $variantIds)->get()
            : [];

        $termsByVariant = [];
        foreach ($relations as $relation) {
            $termsByVariant[$relation->object_id][] = [
                'group_id' => (int) $relation->group_id,
                'term_id'  => (int) $relation->term_id,
            ];
        }

        $rows = [];
        foreach ($variants as $variant) {
            $rows[] = [
                'variation_id'  => (int) $variant->id,
                'title'         => $variant->variation_title,
                'sku'           => $variant->sku,
                'price_cents'   => (int) $variant->item_price,
                'price'         => MCPHelper::money($variant->item_price),
                'compare_price_cents' => $variant->compare_price === null ? null : (int) $variant->compare_price,
                'media_id'      => $variant->media_id ? (int) $variant->media_id : null,
                'media_url'     => $variant->media_id ? wp_get_attachment_url($variant->media_id) : null,
                'stock_status'  => $variant->stock_status,
                'available'     => $variant->available === null ? null : (int) $variant->available,
                'terms'         => isset($termsByVariant[$variant->id]) ? $termsByVariant[$variant->id] : [],
            ];
        }

        // The three ways a swatch row silently renders wrong, stated rather
        // than left for the agent to infer from an empty selector.
        $findings = [];

        if ($variationType === 'advanced_variations' && !$config) {
            $findings[] = __('variation_type is advanced_variations but attribute_config is empty — no selector will render. Set attribute_groups.', 'fluent-cart-elementor-blocks');
        }

        if ($config && $variationType !== 'advanced_variations') {
            $findings[] = __('attribute_config is set but variation_type is not advanced_variations, so the swatches are ignored.', 'fluent-cart-elementor-blocks');
        }

        $withoutTerms = 0;
        foreach ($rows as $row) {
            if (!$row['terms']) {
                $withoutTerms++;
            }
        }

        if ($variationType === 'advanced_variations' && $withoutTerms) {
            $findings[] = sprintf(
                /* translators: %d: variant count */
                __('%d variant(s) map to no attribute terms, so no swatch combination selects them.', 'fluent-cart-elementor-blocks'),
                $withoutTerms
            );
        }

        $withoutMedia = 0;
        foreach ($rows as $row) {
            if (!$row['media_id']) {
                $withoutMedia++;
            }
        }

        if ($withoutMedia === count($rows) && count($rows)) {
            $findings[] = __('No variant has a media_id, so the gallery will not change when a swatch is chosen. Set media_id per variant.', 'fluent-cart-elementor-blocks');
        }

        // The gallery only swaps between slides it already holds, so a variant
        // image missing from it silently does nothing.
        $gallery = get_post_meta($productId, 'fluent-products-gallery-image', true);
        $inGallery = [];

        foreach ((is_array($gallery) ? $gallery : []) as $item) {
            if (is_array($item) && isset($item['id'])) {
                $inGallery[] = (int) $item['id'];
            }
        }

        $orphanMedia = [];

        foreach ($rows as $row) {
            if ($row['media_id'] && !in_array((int) $row['media_id'], $inGallery, true)) {
                $orphanMedia[$row['media_id']] = true;
            }
        }

        if ($orphanMedia) {
            $findings[] = sprintf(
                /* translators: %s: attachment ids */
                __('Variant image(s) %s are not in the product gallery, so selecting those variants will not change the picture. Re-run manage-product-variations, which adds them.', 'fluent-cart-elementor-blocks'),
                implode(', ', array_keys($orphanMedia))
            );
        }

        return MCPHelper::envelope(
            sprintf(
                /* translators: 1: variant count, 2: product title */
                __('%1$d variant(s) on "%2$s".', 'fluent-cart-elementor-blocks'),
                count($rows),
                $product->post_title
            ),
            [
                'product_id'       => $productId,
                'title'            => $product->post_title,
                'variation_type'   => $variationType,
                'attribute_config' => $config,
                'variants'         => $rows,
                'findings'         => $findings,
            ]
        );
    }

    public static function manageVariations($params = [])
    {
        $productId = (int) Arr::get($params, 'product_id', 0);
        $product   = Product::query()->find($productId);

        if (!$product) {
            return MCPHelper::error('product_not_found', __('No product with that id. Call list-products.', 'fluent-cart-elementor-blocks'));
        }

        $detail = ProductDetail::query()->where('post_id', $productId)->first();

        if (!$detail) {
            return MCPHelper::error('product_detail_missing', __('This product has no detail row; it cannot be varied.', 'fluent-cart-elementor-blocks'));
        }

        if (!PermissionGate::can('products/edit')) {
            return MCPHelper::error('forbidden', __('This action needs the products/edit capability.', 'fluent-cart-elementor-blocks'));
        }

        if (Arr::get($params, 'remove_missing_variants') && !PermissionGate::can('products/delete')) {
            return MCPHelper::error('forbidden', __('remove_missing_variants needs the products/delete capability.', 'fluent-cart-elementor-blocks'));
        }

        $dryRun        = (bool) Arr::get($params, 'dry_run', false);
        $variationType = Arr::get($params, 'variation_type');
        $groups        = Arr::get($params, 'attribute_groups');
        $variants      = Arr::get($params, 'variants', []);
        $removeMissing = (bool) Arr::get($params, 'remove_missing_variants', false);

        if ($variationType !== null && !in_array($variationType, self::VARIATION_TYPES, true)) {
            return MCPHelper::error('invalid_variation_type', __('variation_type must be simple_variations or advanced_variations.', 'fluent-cart-elementor-blocks'));
        }

        // Validate the declared groups and terms before writing anything: a
        // half-applied config leaves a selector referencing terms that do not
        // exist, which renders as a silently missing group.
        $config = null;

        if (is_array($groups)) {
            $config = [];

            foreach ($groups as $entry) {
                $groupId = (int) Arr::get($entry, 'group_id', 0);
                $termIds = array_map('intval', (array) Arr::get($entry, 'term_ids', []));

                if (!AttributeGroup::query()->find($groupId)) {
                    return MCPHelper::error('group_not_found', sprintf(
                        /* translators: %d: group id */
                        __('Attribute group %d does not exist.', 'fluent-cart-elementor-blocks'),
                        $groupId
                    ));
                }

                $known = AttributeTerm::query()
                    ->where('group_id', $groupId)
                    ->whereIn('id', $termIds ?: [0])
                    ->get()->pluck('id')->toArray();

                $missing = array_values(array_diff($termIds, array_map('intval', $known)));

                if ($missing) {
                    return MCPHelper::error('term_not_in_group', sprintf(
                        /* translators: 1: term ids, 2: group id */
                        __('Term(s) %1$s are not in group %2$d.', 'fluent-cart-elementor-blocks'),
                        implode(', ', $missing),
                        $groupId
                    ));
                }

                $config[] = ['group_id' => $groupId, 'variants' => $termIds];
            }
        }

        $existing = ProductVariation::query()->where('post_id', $productId)->get();
        $byId     = [];
        foreach ($existing as $variant) {
            $byId[(int) $variant->id] = $variant;
        }

        $plan = ['create' => 0, 'update' => 0, 'delete' => 0];
        $seen = [];

        foreach ((array) $variants as $row) {
            $id = (int) Arr::get($row, 'variation_id', 0);

            if ($id && !isset($byId[$id])) {
                return MCPHelper::error('variant_not_found', sprintf(
                    /* translators: %d: variation id */
                    __('Variant %d does not belong to this product.', 'fluent-cart-elementor-blocks'),
                    $id
                ));
            }

            if ($id) {
                $seen[] = $id;
                $plan['update']++;
            } else {
                $plan['create']++;
            }
        }

        $toDelete = [];

        if ($removeMissing) {
            foreach ($byId as $id => $variant) {
                if (!in_array($id, $seen, true)) {
                    $toDelete[] = $id;
                }
            }

            $plan['delete'] = count($toDelete);
        }

        if ($dryRun) {
            return MCPHelper::envelope(
                __('Dry run — nothing was written.', 'fluent-cart-elementor-blocks'),
                [
                    'dry_run'          => true,
                    'product_id'       => $productId,
                    'variation_type'   => $variationType ?: $detail->variation_type,
                    'attribute_config' => $config === null ? 'unchanged' : $config,
                    'variants'         => $plan,
                    'would_delete'     => $toDelete,
                ],
                ['currency' => MCPHelper::currencyCode()]
            );
        }

        if ($variationType !== null) {
            $detail->variation_type = $variationType;
        }

        if ($config !== null) {
            $otherInfo = self::otherInfo($detail);
            $otherInfo['attribute_config'] = $config;
            $detail->other_info = $otherInfo;
        }

        $detail->save();

        // Delete before inserting, not after: variants carry unique columns (SKU),
        // so replacing a set with one that reuses the same codes collides on the
        // insert while the old rows are still there. The dry run already
        // describes this as a rewrite, so the order has to match that promise.
        if ($toDelete) {
            AttributeRelation::query()->whereIn('object_id', $toDelete)->delete();
            ProductMeta::query()->whereIn('object_id', $toDelete)->where('meta_key', 'product_thumbnail')->delete();
            ProductVariation::query()->whereIn('id', $toDelete)->delete();
        }

        $serial  = (int) ProductVariation::query()->where('post_id', $productId)->max('serial_index');
        $results = [];

        foreach ((array) $variants as $row) {
            $id      = (int) Arr::get($row, 'variation_id', 0);
            $variant = $id ? $byId[$id] : new ProductVariation();

            if (!$id) {
                $variant->post_id      = $productId;
                $variant->serial_index = ++$serial;
                // A new variant with no price is free, which is never intended
                // and is invisible until checkout.
                $variant->item_price   = 0;
                $variant->payment_type = 'onetime';
                $variant->item_status  = 'active';
            }

            if (($title = Arr::get($row, 'title')) !== null) {
                $variant->variation_title = sanitize_text_field((string) $title);
            }

            if (($price = Arr::get($row, 'price_cents')) !== null) {
                $variant->item_price = (int) $price;
            }

            if (($compare = Arr::get($row, 'compare_price_cents')) !== null) {
                $variant->compare_price = (int) $compare;
            }

            if (($sku = Arr::get($row, 'sku')) !== null) {
                $variant->sku = sanitize_text_field((string) $sku);
            }

            if (($mediaId = Arr::get($row, 'media_id')) !== null) {
                $variant->media_id = (int) $mediaId ?: null;
            }

            if (($stock = Arr::get($row, 'stock')) !== null) {
                $variant->manage_stock = 1;
                $variant->total_stock  = (int) $stock;
                $variant->available    = (int) $stock;
                $variant->stock_status = (int) $stock > 0 ? 'in-stock' : 'out-of-stock';
            }

            $variant->save();

            $terms = Arr::get($row, 'terms');

            if (is_array($terms)) {
                // Replace rather than merge: a variant is exactly one term per
                // group, so leaving the old rows behind would make it match two
                // colours at once.
                AttributeRelation::query()->where('object_id', $variant->id)->delete();

                foreach ($terms as $term) {
                    $groupId = (int) Arr::get($term, 'group_id', 0);
                    $termId  = (int) Arr::get($term, 'term_id', 0);

                    if (!$groupId || !$termId) {
                        continue;
                    }

                    AttributeRelation::create([
                        'group_id'  => $groupId,
                        'term_id'   => $termId,
                        'object_id' => $variant->id,
                    ]);
                }

                // AdvancedVariationHandler builds variant_term_map by splitting
                // variation_identifier on "_", not by reading the relations. A
                // variant without it is invisible to the gallery swap even when
                // its relation rows are perfect.
                $termIds = [];

                foreach ($terms as $term) {
                    $termId = (int) Arr::get($term, 'term_id', 0);

                    if ($termId) {
                        $termIds[] = $termId;
                    }
                }

                sort($termIds);
                $variant->variation_identifier = implode('_', $termIds);
                $variant->save();
            }

            // The gallery reads the variant's FIRST image from a
            // `product_thumbnail` meta row, not from the media_id column, so
            // setting media_id alone changes the stored id and nothing visible.
            if (($mediaId = Arr::get($row, 'media_id')) !== null) {
                self::syncVariantThumbnail($variant, (int) $mediaId);
            }

            $results[] = ['variation_id' => (int) $variant->id, 'title' => $variant->variation_title];
        }

        self::refreshPriceRange($detail, $productId);
        $galleryAdded = self::syncGalleryWithVariantMedia($productId);

        return MCPHelper::envelope(
            sprintf(
                /* translators: 1: variant count, 2: product title */
                __('Configured %1$d variant(s) on "%2$s".', 'fluent-cart-elementor-blocks'),
                count($results),
                $product->post_title
            ),
            [
                'product_id'       => $productId,
                'variation_type'   => $detail->variation_type,
                'attribute_config' => $config === null ? Arr::get(self::otherInfo($detail), 'attribute_config', []) : $config,
                'variants'         => $results,
                'gallery_images_added' => $galleryAdded,
                'deleted'          => $toDelete,
                'next_step'        => __('Read it back with get-product-variations; its findings report anything still unconfigured.', 'fluent-cart-elementor-blocks'),
            ],
            ['currency' => MCPHelper::currencyCode()]
        );
    }

    // --------------------------------------------------------------- helpers

    /**
     * Mirror a variant's image into the `product_thumbnail` meta row.
     *
     * Two stores of the same fact again: `variations.media_id` is what the
     * admin edits, while the front-end gallery swap reads
     * `$variant->media->meta_value[0]`, a JSON array on a product-meta row.
     * Writing only the column leaves the swap with nothing to show.
     */
    private static function syncVariantThumbnail(ProductVariation $variant, $mediaId)
    {
        $existing = ProductMeta::query()
            ->where('object_id', $variant->id)
            ->where('meta_key', 'product_thumbnail')
            ->first();

        if (!$mediaId) {
            if ($existing) {
                $existing->delete();
            }

            return;
        }

        $url = wp_get_attachment_url($mediaId);

        if (!$url) {
            return;
        }

        $value = [[
            'id'    => $mediaId,
            'title' => $variant->variation_title,
            'url'   => $url,
        ]];

        if ($existing) {
            $existing->meta_value = $value;
            $existing->save();

            return;
        }

        ProductMeta::create([
            'object_id'   => $variant->id,
            'object_type' => 'product_variant_info',
            'meta_key'    => 'product_thumbnail',
            'meta_value'  => $value,
        ]);
    }

    /**
     * Make sure every variant image is also in the product gallery.
     *
     * Setting media_id alone is not enough: the gallery swaps between slides it
     * already holds, so a variant pointing at an image the gallery has never
     * heard of selects nothing and the picture simply does not change. That
     * reads as "swatches are broken" when in fact two separate stores of the
     * same fact disagree — the variant row and the gallery meta.
     *
     * Returns the attachment ids it had to add.
     */
    private static function syncGalleryWithVariantMedia($productId)
    {
        $mediaIds = ProductVariation::query()
            ->where('post_id', $productId)
            ->whereNotNull('media_id')
            ->get()->pluck('media_id')->toArray();

        $mediaIds = array_values(array_unique(array_filter(array_map('intval', $mediaIds))));

        if (!$mediaIds) {
            return [];
        }

        $gallery = get_post_meta($productId, 'fluent-products-gallery-image', true);
        $gallery = is_array($gallery) ? $gallery : [];

        $present = [];
        foreach ($gallery as $item) {
            if (is_array($item) && isset($item['id'])) {
                $present[] = (int) $item['id'];
            }
        }

        $added = [];

        foreach ($mediaIds as $mediaId) {
            if (in_array($mediaId, $present, true)) {
                continue;
            }

            $url = wp_get_attachment_url($mediaId);

            if (!$url) {
                continue;
            }

            $gallery[] = [
                'id'    => $mediaId,
                'title' => get_the_title($mediaId),
                'url'   => $url,
            ];

            $added[] = $mediaId;
        }

        if ($added) {
            update_post_meta($productId, 'fluent-products-gallery-image', $gallery);
        }

        return $added;
    }

    /**
     * Keep the cached price range in step with the variants.
     *
     * Product cards and the shop grid read min_price/max_price from the detail
     * row, not from the variants — change a price without this and the card
     * keeps advertising the old one.
     */
    private static function refreshPriceRange(ProductDetail $detail, $productId)
    {
        $prices = ProductVariation::query()
            ->where('post_id', $productId)
            ->where('item_status', '!=', 'archived')
            ->get()->pluck('item_price')->toArray();

        $prices = array_map('intval', array_filter($prices, function ($value) {
            return $value !== null;
        }));

        if (!$prices) {
            return;
        }

        $detail->min_price = min($prices);
        $detail->max_price = max($prices);
        $detail->save();
    }

    private static function otherInfo($detail)
    {
        if (!$detail) {
            return [];
        }

        $info = $detail->other_info;

        if (is_string($info)) {
            $info = json_decode($info, true);
        }

        return is_array($info) ? $info : [];
    }

    private static function groupSettings($type, $styling)
    {
        $settings = ['type' => $type];

        // The renderer treats anything other than the literal "dropdown" as
        // swatches, so an empty styling is the honest representation of the
        // default rather than a made-up value.
        if ($styling === 'dropdown') {
            $settings['styling'] = 'dropdown';
        }

        return $settings;
    }

    private static function groupRow(AttributeGroup $group)
    {
        $settings = is_array($group->settings) ? $group->settings : [];
        $styling  = Arr::get($settings, 'styling', '');

        return [
            'group_id'  => (int) $group->id,
            'title'     => $group->title,
            'slug'      => $group->slug,
            'type'      => Arr::get($settings, 'type', 'options'),
            'styling'   => $styling === 'dropdown' ? 'dropdown' : 'swatch',
            'is_system' => (bool) $group->is_system,
        ];
    }

    private static function termRow(AttributeTerm $term)
    {
        $settings = is_array($term->settings) ? $term->settings : [];

        return [
            'term_id' => (int) $term->id,
            'title'   => $term->title,
            'slug'    => $term->slug,
            'color'   => Arr::get($settings, 'color'),
            'image'   => Arr::get($settings, 'image'),
        ];
    }

    private static function validType($type)
    {
        $type = (string) $type;

        if (!in_array($type, self::GROUP_TYPES, true)) {
            return MCPHelper::error('invalid_type', sprintf(
                /* translators: %s: allowed values */
                __('type must be one of: %s.', 'fluent-cart-elementor-blocks'),
                implode(', ', self::GROUP_TYPES)
            ));
        }

        return $type;
    }

    private static function validStyling($styling)
    {
        $styling = (string) $styling;

        if (!in_array($styling, self::GROUP_STYLINGS, true)) {
            return MCPHelper::error('invalid_styling', sprintf(
                /* translators: %s: allowed values */
                __('styling must be one of: %s.', 'fluent-cart-elementor-blocks'),
                implode(', ', self::GROUP_STYLINGS)
            ));
        }

        return $styling;
    }

    private static function uniqueGroupSlug($slug)
    {
        $slug = $slug ?: 'group';
        $base = $slug;
        $i    = 1;

        while (AttributeGroup::query()->where('slug', $slug)->first()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    /**
     * One term row ready to insert, or a WP_Error naming what it is missing.
     *
     * The type rules mirror AttrTermRequest: a colour group whose term has no
     * hex draws an empty dot, and an image group whose term has no URL draws an
     * empty square — both look like a rendering bug rather than missing data.
     */
    private static function prepareTerm($row, $type, AttributeGroup $group, $index)
    {
        $title = trim((string) Arr::get($row, 'title', ''));

        if ($title === '') {
            return MCPHelper::error('term_title_required', sprintf(
                /* translators: %d: row number */
                __('Term %d has no title.', 'fluent-cart-elementor-blocks'),
                $index + 1
            ));
        }

        if (mb_strlen($title) > 50) {
            return MCPHelper::error('term_title_too_long', __('Term titles are limited to 50 characters.', 'fluent-cart-elementor-blocks'));
        }

        $settings = [];

        if ($type === 'color') {
            $hex = sanitize_hex_color((string) Arr::get($row, 'color', ''));

            if (!$hex) {
                return MCPHelper::error('term_color_required', sprintf(
                    /* translators: 1: term title, 2: group title */
                    __('"%1$s" needs a hex colour because "%2$s" is a colour group.', 'fluent-cart-elementor-blocks'),
                    $title,
                    $group->title
                ));
            }

            $settings['color'] = $hex;
        } elseif ($type === 'image') {
            $image = (string) Arr::get($row, 'image', '');

            if (!filter_var($image, FILTER_VALIDATE_URL)) {
                return MCPHelper::error('term_image_required', sprintf(
                    /* translators: 1: term title, 2: group title */
                    __('"%1$s" needs an image URL because "%2$s" is an image group.', 'fluent-cart-elementor-blocks'),
                    $title,
                    $group->title
                ));
            }

            $settings['image'] = esc_url_raw($image);
        }

        return [
            'title'    => sanitize_text_field($title),
            'slug'     => self::uniqueTermSlug(sanitize_title($title), $group->id),
            'settings' => $settings,
        ];
    }

    private static function uniqueTermSlug($slug, $groupId)
    {
        $slug = $slug ?: 'term';
        $base = $slug;
        $i    = 1;

        while (AttributeTerm::query()->where('group_id', $groupId)->where('slug', $slug)->first()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    /** Terms that cannot render under their group's current type. */
    private static function termsMissingSettings(AttributeGroup $group)
    {
        $settings = is_array($group->settings) ? $group->settings : [];
        $type     = Arr::get($settings, 'type', 'options');

        if (!in_array($type, ['color', 'image'], true)) {
            return [];
        }

        $key     = $type === 'color' ? 'color' : 'image';
        $missing = [];

        foreach (AttributeTerm::query()->where('group_id', $group->id)->get() as $term) {
            $termSettings = is_array($term->settings) ? $term->settings : [];

            if (!Arr::get($termSettings, $key)) {
                $missing[] = ['term_id' => (int) $term->id, 'title' => $term->title, 'missing' => $key];
            }
        }

        return $missing;
    }
}
