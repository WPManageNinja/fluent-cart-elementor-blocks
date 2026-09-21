<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ReviewStyleControls;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits\ReviewWidgetTrait;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Product Review List — the reviews themselves, with the row composed field by
 * field.
 *
 * In Gutenberg this is one block holding sixteen more: a count, filter chips
 * and a sort control above, a Review Item carrying eleven per-review fields,
 * and a pager below. Those sixteen are composition primitives, not features —
 * they exist because Gutenberg repeats a row by nesting blocks fed from block
 * context. Elementor has no block context, and its only repeat-a-design
 * mechanism is Pro's Loop Builder, bound to WP_Query and unable to iterate a
 * custom table. Sixteen panel widgets that only work when dragged inside
 * another widget would be broken by design, so here they are controls.
 *
 * The rendering is still theirs. Rather than reimplement eleven field
 * renderers that would drift from core the moment one changed, this widget
 * builds the parsed block tree those controls describe and hands it to
 * render_block(). That is the same move OrderReviewRenderer makes for the
 * Write a Review block, and for the same reason: going through the block
 * pipeline means attribute validation, wrapper attributes, supports and every
 * render_block filter an add-on registered all still apply — and Pro's votes,
 * photos and reply threads arrive with no code here at all.
 */
class ProductReviewListWidget extends Widget_Base
{
    use ReviewWidgetTrait;

    const VIEW_MODES = ['list', 'grid', 'slider'];
    const SORT_COLUMNS = ['created_at', 'rating'];
    const SORT_ORDERS = ['DESC', 'ASC'];
    const PAGINATION_TYPES = ['numbers', 'fraction', 'bullets'];
    const ARROW_SIZES = ['sm', 'md', 'lg'];
    const DEFAULT_STAR_COLOR = '#f59e0b';

    /**
     * The per-review fields, in the order a row shows them by default — the
     * same order ReviewListRenderer draws its own fixed row in. Key is the
     * repeater value; value is the block each one becomes.
     */
    const FIELD_BLOCKS = [
        'avatar'          => 'fluent-cart/review-item-avatar',
        'author_name'     => 'fluent-cart/review-item-author-name',
        'verified_badge'  => 'fluent-cart/review-item-verified-badge',
        'variation_title' => 'fluent-cart/review-item-variation-title',
        'rating'          => 'fluent-cart/review-item-rating',
        'date'            => 'fluent-cart/review-item-date',
        'title'           => 'fluent-cart/review-item-title',
        'content'         => 'fluent-cart/review-item-content',
        'photos'          => 'fluent-cart/review-item-photos',
        'votes'           => 'fluent-cart/review-item-votes',
        'reply'           => 'fluent-cart/review-item-reply',
    ];

    public function get_name()
    {
        return 'fluentcart_product_review_list';
    }

    public function get_title()
    {
        return esc_html__('Product Review List', 'fluent-cart-elementor-blocks');
    }

    public function get_icon()
    {
        return 'eicon-post-list fluent-cart-widget-icon';
    }

    public function get_categories()
    {
        return ['fluent-cart'];
    }

    public function get_keywords()
    {
        return ['review', 'reviews', 'list', 'rating', 'testimonial', 'fluent'];
    }

    public function get_style_depends()
    {
        AssetLoader::loadSingleProductAssets();

        return [];
    }

    protected function fieldLabels(): array
    {
        return [
            'avatar'          => esc_html__('Avatar', 'fluent-cart-elementor-blocks'),
            'author_name'     => esc_html__('Reviewer Name', 'fluent-cart-elementor-blocks'),
            'verified_badge'  => esc_html__('Verified Purchase Badge', 'fluent-cart-elementor-blocks'),
            'variation_title' => esc_html__('Variation Reviewed', 'fluent-cart-elementor-blocks'),
            'rating'          => esc_html__('Star Rating', 'fluent-cart-elementor-blocks'),
            'date'            => esc_html__('Date', 'fluent-cart-elementor-blocks'),
            'title'           => esc_html__('Review Title', 'fluent-cart-elementor-blocks'),
            'content'         => esc_html__('Review Text', 'fluent-cart-elementor-blocks'),
            'photos'          => esc_html__('Photos', 'fluent-cart-elementor-blocks'),
            'votes'           => esc_html__('Helpful Votes', 'fluent-cart-elementor-blocks'),
            'reply'           => esc_html__('Store Reply', 'fluent-cart-elementor-blocks'),
        ];
    }

    protected function register_controls()
    {
        // ── Content ───────────────────────────────────────
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->registerProductSourceControls();

        $this->add_control(
            'per_page',
            [
                'label'       => esc_html__('Reviews per Page', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('0 uses the store setting.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'max'         => 100,
                'default'     => 0,
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'min_rating',
            [
                'label'       => esc_html__('Minimum Rating', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Show only reviews at or above this rating. 0 shows them all.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'max'         => 5,
                'default'     => 0,
            ]
        );

        $this->end_controls_section();

        // ── Layout ────────────────────────────────────────
        $this->start_controls_section(
            'layout_section',
            [
                'label' => esc_html__('Layout', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'view_mode',
            [
                'label'   => esc_html__('View', 'fluent-cart-elementor-blocks'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'list',
                'options' => [
                    'list'   => esc_html__('List', 'fluent-cart-elementor-blocks'),
                    'grid'   => esc_html__('Grid', 'fluent-cart-elementor-blocks'),
                    'slider' => esc_html__('Slider', 'fluent-cart-elementor-blocks'),
                ],
            ]
        );

        $this->add_control(
            'grid_columns',
            [
                'label'     => esc_html__('Columns', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::NUMBER,
                'min'       => 1,
                'max'       => 6,
                'default'   => 2,
                'condition' => [
                    'view_mode' => ['grid', 'slider'],
                ],
            ]
        );

        $this->add_control(
            'slider_arrows',
            [
                'label'        => esc_html__('Arrows', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => ['view_mode' => 'slider'],
            ]
        );

        $this->add_control(
            'slider_arrows_size',
            [
                'label'     => esc_html__('Arrow Size', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'md',
                'options'   => [
                    'sm' => esc_html__('Small', 'fluent-cart-elementor-blocks'),
                    'md' => esc_html__('Medium', 'fluent-cart-elementor-blocks'),
                    'lg' => esc_html__('Large', 'fluent-cart-elementor-blocks'),
                ],
                'condition' => [
                    'view_mode'      => 'slider',
                    'slider_arrows'  => 'yes',
                ],
            ]
        );

        $this->add_control(
            'slider_autoplay',
            [
                'label'        => esc_html__('Autoplay', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => ['view_mode' => 'slider'],
            ]
        );

        $this->add_control(
            'slider_autoplay_delay',
            [
                'label'     => esc_html__('Autoplay Delay (ms)', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::NUMBER,
                'min'       => 1000,
                'max'       => 30000,
                'step'      => 500,
                'default'   => 3000,
                'condition' => [
                    'view_mode'       => 'slider',
                    'slider_autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'slider_infinite',
            [
                'label'        => esc_html__('Loop', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => ['view_mode' => 'slider'],
            ]
        );

        $this->end_controls_section();

        // ── Header ────────────────────────────────────────
        $this->start_controls_section(
            'header_section',
            [
                'label' => esc_html__('Header', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_count',
            [
                'label'        => esc_html__('Review Count', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_filter',
            [
                'label'        => esc_html__('Star Filter Chips', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'show_sorting',
            [
                'label'        => esc_html__('Sort Control', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'default_sort_by',
            [
                'label'   => esc_html__('Sort By', 'fluent-cart-elementor-blocks'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'created_at',
                'options' => [
                    'created_at' => esc_html__('Date', 'fluent-cart-elementor-blocks'),
                    'rating'     => esc_html__('Rating', 'fluent-cart-elementor-blocks'),
                ],
            ]
        );

        $this->add_control(
            'default_sort_order',
            [
                'label'   => esc_html__('Order', 'fluent-cart-elementor-blocks'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'DESC' => esc_html__('Newest / Highest first', 'fluent-cart-elementor-blocks'),
                    'ASC'  => esc_html__('Oldest / Lowest first', 'fluent-cart-elementor-blocks'),
                ],
            ]
        );

        $this->end_controls_section();

        // ── Review row ────────────────────────────────────
        $this->start_controls_section(
            'row_section',
            [
                'label' => esc_html__('Review Row', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'row_layout',
            [
                'label'       => esc_html__('Review Layout', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'standard',
                'options'     => [
                    'standard' => esc_html__('Standard', 'fluent-cart-elementor-blocks'),
                    'custom'   => esc_html__('Choose fields', 'fluent-cart-elementor-blocks'),
                ],
                'description' => esc_html__('Standard draws the row FluentCart draws everywhere else — avatar, name, stars and badge on one line. Choose fields lets you pick and order them, and stacks each on its own line.', 'fluent-cart-elementor-blocks'),
            ]
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'field',
            [
                'label'   => esc_html__('Field', 'fluent-cart-elementor-blocks'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'author_name',
                'options' => $this->fieldLabels(),
            ]
        );

        $this->add_control(
            'row_fields',
            [
                'label'       => esc_html__('Fields', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => $this->defaultRowFields(),
                'title_field' => '{{{ field }}}',
                'description' => esc_html__('Each row of the list shows these, in this order. Remove one to hide it.', 'fluent-cart-elementor-blocks'),
                'condition'   => ['row_layout' => 'custom'],
            ]
        );

        $this->add_control(
            'star_color',
            [
                'label'     => esc_html__('Star Color', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::COLOR,
                'default'   => self::DEFAULT_STAR_COLOR,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'content_max_words',
            [
                'label'       => esc_html__('Trim Review Text', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Cut the review text to this many words. 0 shows all of it.', 'fluent-cart-elementor-blocks'),
                'type'        => Controls_Manager::NUMBER,
                'min'         => 0,
                'max'         => 500,
                'default'     => 0,
                'condition'   => ['row_layout' => 'custom'],
            ]
        );

        $this->add_control(
            'standard_fields_heading',
            [
                'label'     => esc_html__('Show in each review', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => ['row_layout' => 'standard'],
            ]
        );

        foreach ([
            'show_reviewer_name' => esc_html__('Reviewer Name', 'fluent-cart-elementor-blocks'),
            'show_review_date'   => esc_html__('Date', 'fluent-cart-elementor-blocks'),
            'show_verified'      => esc_html__('Verified Purchase Badge', 'fluent-cart-elementor-blocks'),
            'show_view_reply'    => esc_html__('Store Reply', 'fluent-cart-elementor-blocks'),
        ] as $key => $label) {
            $this->add_control(
                $key,
                [
                    'label'        => $label,
                    'type'         => Controls_Manager::SWITCHER,
                    'return_value' => 'yes',
                    'default'      => 'yes',
                    'condition'    => ['row_layout' => 'standard'],
                ]
            );
        }

        $this->end_controls_section();

        // ── Pagination ────────────────────────────────────
        $this->start_controls_section(
            'pagination_section',
            [
                'label' => esc_html__('Pagination', 'fluent-cart-elementor-blocks'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_pagination',
            [
                'label'        => esc_html__('Show Pagination', 'fluent-cart-elementor-blocks'),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'pagination_type',
            [
                'label'     => esc_html__('Style', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'numbers',
                'options'   => [
                    'numbers'  => esc_html__('Numbers', 'fluent-cart-elementor-blocks'),
                    'fraction' => esc_html__('Page X of Y', 'fluent-cart-elementor-blocks'),
                    'bullets'  => esc_html__('Bullets', 'fluent-cart-elementor-blocks'),
                ],
                'condition' => ['show_pagination' => 'yes'],
            ]
        );

        $this->add_control(
            'pagination_justify',
            [
                'label'     => esc_html__('Alignment', 'fluent-cart-elementor-blocks'),
                'type'      => Controls_Manager::CHOOSE,
                'default'   => '',
                'options'   => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'fluent-cart-elementor-blocks'),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center'     => [
                        'title' => esc_html__('Center', 'fluent-cart-elementor-blocks'),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'flex-end'   => [
                        'title' => esc_html__('Right', 'fluent-cart-elementor-blocks'),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'condition' => ['show_pagination' => 'yes'],
            ]
        );

        $this->end_controls_section();

        // Style — every section for the list, shared with the all-in-one
        // widget so the two cannot drift.
        ReviewStyleControls::register($this);
    }

    /**
     * The row a freshly dropped widget shows: every field, in the order
     * ReviewListRenderer draws its own fixed row, so the widget's first
     * render looks like the storefront default rather than an empty row the
     * user has to assemble.
     */
    protected function defaultRowFields(): array
    {
        $defaults = [];

        foreach (array_keys(self::FIELD_BLOCKS) as $field) {
            $defaults[] = ['field' => $field];
        }

        return $defaults;
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        if ($this->renderReviewsUnavailable()) {
            return;
        }

        $product = $this->getProduct($settings);

        if (!$product && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $product = $this->getPreviewProduct();
        }

        if (!$product) {
            $this->renderPlaceholder(
                esc_html__('Please select a product or use this widget inside a product template.', 'fluent-cart-elementor-blocks')
            );
            return;
        }

        if ($this->renderReviewsUnavailable($product->ID)) {
            return;
        }

        AssetLoader::loadSingleProductAssets();

        // Standard layout renders through core's own row, which needs options
        // the block has no attributes for — the block deliberately carries
        // nothing per field, because in Gutenberg a field is turned off by
        // deleting its block. The renderer takes them through its own filter,
        // the same route OrderReviewRenderer uses to hand it an order grant.
        $injected = $this->rendererOptions($settings);

        if ($injected) {
            $inject = function ($options) use ($injected) {
                return array_merge($options, $injected);
            };
            add_filter('fluent_cart/review/renderer_options', $inject, 20);
        }

        $content = render_block($this->buildListBlock($product->ID, $settings));

        if ($injected) {
            remove_filter('fluent_cart/review/renderer_options', $inject, 20);
        }

        if (trim((string) $content) === '') {
            $this->renderPlaceholder(
                esc_html__('This product has no approved reviews yet.', 'fluent-cart-elementor-blocks')
            );
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block pipeline output, escaped at source
        echo '<div class="fluentcart-product-review-list">' . $content . '</div>';
    }

    /**
     * The whole tree: the list, its header pieces, the row and its fields,
     * and the pager.
     *
     * @param int $productId
     * @param array $settings
     */
    protected function buildListBlock($productId, array $settings): array
    {
        $attributes = $this->listAttributes($productId, $settings);

        // Core's own header — count, chips and sort in one row — renders only
        // for a list nobody composed. Placing a single header block here would
        // make this a composed list and take that header away, so the standard
        // layout leaves the tree empty and steers core through its options
        // instead. An empty Review List is exactly what core draws its own
        // storefront row for.
        if (!$this->isCustomRow($settings)) {
            return $this->block('fluent-cart/product-review-list', $attributes, []);
        }

        $sortBy = $attributes['defaultSortBy'];
        $sortOrder = $attributes['defaultSortOrder'];
        $perPage = $attributes['perPage'];

        $children = [];

        if ($this->isOn($settings, 'show_count')) {
            $children[] = $this->block('fluent-cart/review-list-count');
        }

        if ($this->isOn($settings, 'show_filter')) {
            $children[] = $this->block('fluent-cart/review-list-filter');
        }

        if ($this->isOn($settings, 'show_sorting')) {
            $children[] = $this->block('fluent-cart/review-list-sorting', [
                'defaultSort' => $sortBy . '-' . $sortOrder,
            ]);
        }

        $children[] = $this->buildRowBlock($settings);

        if ($this->isOn($settings, 'show_pagination')) {
            $children[] = $this->block('fluent-cart/review-list-pagination', [
                'paginationType' => $this->pick($settings, 'pagination_type', self::PAGINATION_TYPES, 'numbers'),
                'justify'        => in_array($settings['pagination_justify'] ?? '', ['flex-start', 'center', 'flex-end'], true)
                    ? $settings['pagination_justify']
                    : '',
                'perPage'        => $perPage,
            ]);
        }

        return $this->block('fluent-cart/product-review-list', $attributes, $children);
    }

    /**
     * The list block's own attributes, shared by both layouts.
     *
     * @param int $productId
     * @param array $settings
     */
    protected function listAttributes($productId, array $settings): array
    {
        return [
            // The widget has already resolved the product, including the
            // editor's preview fallback, so the block is told which one
            // rather than asked to resolve it again from a context the
            // canvas may not have.
            'query_type'       => 'custom',
            'product_id'       => (int) $productId,
            'viewMode'         => $this->pick($settings, 'view_mode', self::VIEW_MODES, 'list'),
            'gridColumns'      => max(1, min(6, absint($settings['grid_columns'] ?? 2))),
            'sliderSettings'   => $this->sliderSettings($settings),
            'showSortControls' => $this->isOn($settings, 'show_sorting'),
            'defaultSortBy'    => $this->pick($settings, 'default_sort_by', self::SORT_COLUMNS, 'created_at'),
            'defaultSortOrder' => $this->pick($settings, 'default_sort_order', self::SORT_ORDERS, 'DESC'),
            'perPage'          => max(0, min(100, absint($settings['per_page'] ?? 0))),
        ];
    }

    /**
     * Whether the editor chose to compose the row field by field.
     */
    protected function isCustomRow(array $settings): bool
    {
        return ($settings['row_layout'] ?? 'standard') === 'custom';
    }

    /**
     * What the standard row needs that the block cannot say: the per-field
     * switches, the star colour, the chips, and the rating floor. Empty in the
     * composed layout, where each field block speaks for itself.
     *
     * @param array $settings
     */
    protected function rendererOptions(array $settings): array
    {
        if ($this->isCustomRow($settings)) {
            return [];
        }

        return [
            'showReviewerName'  => $this->isOn($settings, 'show_reviewer_name'),
            'showReviewDate'    => $this->isOn($settings, 'show_review_date'),
            'showVerifiedBadge' => $this->isOn($settings, 'show_verified'),
            'showViewReply'     => $this->isOn($settings, 'show_view_reply'),
            'showFilterChips'   => $this->isOn($settings, 'show_filter'),
            'starColor'         => sanitize_hex_color((string) ($settings['star_color'] ?? '')) ?: self::DEFAULT_STAR_COLOR,
            'minRating'         => max(0, min(5, absint($settings['min_rating'] ?? 0))),
            'paginationType'    => $this->isOn($settings, 'show_pagination')
                ? $this->pick($settings, 'pagination_type', self::PAGINATION_TYPES, 'numbers')
                : 'numbers',
        ];
    }

    /**
     * The repeating row. Its wp_client_id is what lets core remember the
     * composition and hand the same row back to the reviews endpoint, so a
     * sort, a filter or a page draws what the first render drew instead of
     * falling back to the fixed row. Elementor's element id is the natural
     * source: stable for the life of the widget, and distinct per instance.
     */
    protected function buildRowBlock(array $settings): array
    {
        $starColor = sanitize_hex_color((string) ($settings['star_color'] ?? '')) ?: self::DEFAULT_STAR_COLOR;
        $maxWords = max(0, min(500, absint($settings['content_max_words'] ?? 0)));

        $fields = [];
        $seen = [];

        foreach ((array) ($settings['row_fields'] ?? []) as $item) {
            $field = is_array($item) ? (string) ($item['field'] ?? '') : '';

            if (!isset(self::FIELD_BLOCKS[$field]) || isset($seen[$field])) {
                continue;
            }

            // A field twice in one row would render twice. The repeater lets
            // it happen; the row should not.
            $seen[$field] = true;

            $attrs = [];

            if ($field === 'rating') {
                $attrs['starColor'] = $starColor;
            }

            if ($field === 'content' && $maxWords > 0) {
                $attrs['maxWords'] = $maxWords;
            }

            $fields[] = $this->block(self::FIELD_BLOCKS[$field], $attrs);
        }

        return $this->block(
            'fluent-cart/review-item',
            [
                'wp_client_id' => 'elementor-' . $this->get_id(),
                'minRating'    => max(0, min(5, absint($settings['min_rating'] ?? 0))),
            ],
            $fields
        );
    }

    /**
     * The slider attribute core expects, in its own shape. Read whatever the
     * view mode, so switching to slider and back does not lose the settings.
     */
    protected function sliderSettings(array $settings): array
    {
        return [
            'autoplay'      => $this->isOn($settings, 'slider_autoplay') ? 'yes' : 'no',
            'autoplayDelay' => max(1000, min(30000, absint($settings['slider_autoplay_delay'] ?? 3000))),
            'arrows'        => $this->isOn($settings, 'slider_arrows') ? 'yes' : 'no',
            'arrowsSize'    => $this->pick($settings, 'slider_arrows_size', self::ARROW_SIZES, 'md'),
            'infinite'      => $this->isOn($settings, 'slider_infinite') ? 'yes' : 'no',
        ];
    }

    /**
     * One parsed block, in the shape WP_Block wants.
     *
     * innerContent is one null per child: that is how the parser represents
     * "a block whose content is entirely its children", and serialize_block()
     * needs it to round-trip the row when core stores the composition.
     */
    protected function block(string $name, array $attrs = [], array $innerBlocks = []): array
    {
        return [
            'blockName'    => $name,
            'attrs'        => $attrs,
            'innerBlocks'  => $innerBlocks,
            'innerHTML'    => '',
            'innerContent' => array_fill(0, count($innerBlocks), null),
        ];
    }

    /**
     * Elementor switchers are enums, not truthy values.
     */
    protected function isOn(array $settings, string $key): bool
    {
        return ($settings[$key] ?? '') === 'yes';
    }

    protected function pick(array $settings, string $key, array $allowed, string $fallback): string
    {
        $value = (string) ($settings[$key] ?? $fallback);

        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
