<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits;

use FluentCart\App\Services\ProductReviewService;
use FluentCart\App\Services\Renderer\ProductReviewRenderer;
use FluentCart\App\Services\Renderer\ReviewThreadMarkup;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Controls\ReviewLayoutPresetControl;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ReviewLayoutPresets;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ReviewLayoutThumbnails;
use FluentCart\App\Services\Reviews\LayoutPresets;
use FluentCart\Framework\Support\Arr;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ReviewSupport;
use FluentCartElementorBlocks\App\Utils\Enqueuer\Enqueue;
use FluentCart\App\App;
use FluentCart\App\Vite;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * What every review widget shares on top of ProductWidgetTrait: it explains
 * itself in the editor instead of rendering nothing when the store or the
 * product has reviews switched off.
 *
 * The store module remains visible in Elementor when disabled so the merchant
 * can enable it without silently losing an existing widget.
 */
trait ReviewWidgetTrait
{
    use ProductWidgetTrait {
        show_in_panel as productShowInPanel;
    }

    public function show_in_panel()
    {
        return $this->productShowInPanel();
    }

    /**
     * Core binds its review containers on DOM ready, which the editor is long
     * past by the time it replaces a widget's markup. This is what re-binds
     * the replacement, so a list switched to slider view actually becomes one.
     */
    public function get_script_depends()
    {
        static::registerReviewEditorScript();

        return ['fluentcart-product-reviews-elementor'];
    }

    protected static function registerReviewEditorScript(): void
    {
        static $registered = false;

        if ($registered) {
            return;
        }

        $registered = true;

        Enqueue::script(
            'fluentcart-product-reviews-elementor',
            'elementor/product-reviews-elementor.js',
            ['jquery'],
            FLUENTCART_ELEMENTOR_BLOCKS_VERSION,
            true
        );
    }

    protected static function registerReviewPresetStyles(): void
    {
        // Shared with the editor preview, which is a separate document and has
        // no widget to ask. ReviewSupport owns the single registration.
        ReviewSupport::enqueuePresetStyles();
    }

    /**
     * Where the Verified Purchase switcher starts.
     *
     * The store has its own switch for the badge, and a widget control that
     * always started on would quietly overrule it: a store that had turned
     * badges off would find them back on every page carrying this widget.
     * Reading the store's answer as the default keeps the control honest. The
     * renderer also treats the store setting as a hard upper bound, matching
     * Gutenberg when a saved widget setting tries to override it.
     */
    protected static function verifiedBadgeDefault(): string
    {
        $settings = (array) ProductReviewService::getReviewSettings();

        return (!isset($settings['show_verified_badge']) || $settings['show_verified_badge'] === 'yes') ? 'yes' : '';
    }

    /**
     * The store-wide switch is authoritative; a widget can only turn the
     * badge off when the store allows it, never turn it back on after the
     * merchant disabled it globally.
     */
    protected function showVerifiedBadge(array $settings): bool
    {
        return static::verifiedBadgeDefault() === 'yes'
            && $this->isOn($settings, 'show_verified');
    }

    /**
     * Swiper, ahead of any slider that might need it.
     *
     * Core loads it while drawing a slider, which is in time for a page but
     * not for the editor: a section drawn in list view never loads it, so
     * switching to slider view afterwards leaves the rows stacked with no
     * script to build them.
     *
     * @return string the script handle to depend on
     */
    protected static function registerSliderAssets(): string
    {
        static $registered = false;
        $handle = App::getInstance()->config->get('app.slug') . '-fluentcart-swiper-js';

        if ($registered) {
            return $handle;
        }

        $registered = true;

        Vite::enqueueStaticScript($handle, 'public/lib/swiper/swiper-bundle.min.js', []);
        Vite::enqueueStaticStyle(
            App::getInstance()->config->get('app.slug') . '-fluentcart-swiper-css',
            'public/lib/swiper/swiper-bundle.min.css'
        );

        return $handle;
    }

    /**
     * Render the editor notice for whatever is stopping reviews, and say
     * whether anything was. The front end stays silent: a shopper should see
     * an absent section, never a diagnostic.
     *
     * @param int $postId 0 to skip the per-product question.
     * @return bool true when a reason was found (the caller should return).
     */

    /**
     * The attachment settings, in the shape core's renderer reads them.
     *
     * Bounded by core rather than here: the same helpers bound a block
     * attribute and a shortcode attribute, so a widget cannot arrive at a
     * size, a limit or a placement the other two could not.
     *
     * @param array $settings
     * @param bool  $fullWidth already read by the widget, whose switcher
     *                         default differs from the other's.
     */
    /**
     * The View Mode choices, marked for what this site may actually draw.
     *
     * Grid and slider belong to Pro, and core decides that for real in
     * ProductReviewService::resolveViewMode() — every path this widget renders
     * through goes past it, so a locked mode already comes out as a list. What
     * was missing was the panel saying so: a merchant picked Grid, nothing
     * changed on the page, and nothing explained why.
     *
     * The options are marked rather than removed. A choice that disappears
     * looks like a feature the plugin does not have; one labelled Pro is an
     * invitation.
     */
    /**
     * The Layout Preset control.
     *
     * Choosing one builds the section from that layout's blocks — the same
     * blocks the block editor would build, so the two agree by construction
     * rather than by being kept in step.
     *
     * The controls below stay where they are, whichever layout is chosen. A
     * preset is where a section starts, not the whole of what it may be: it
     * supplies the settings a merchant has not spoken about, and anything
     * they do set is theirs and stays theirs. Hiding those controls behind
     * Custom meant that picking a layout to start from cost the merchant
     * every setting they had, and that the only way to change one thing
     * about a layout was to abandon it.
     */
    protected function addReviewLayoutPresetControl(): void
    {
        $this->add_control(
            'layout_preset',
            [
                'label'       => esc_html__('Layout Preset', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Builds the section from these settings. Anything you have set is replaced.', 'fluent-cart-elementor-blocks'),
                'type'        => ReviewLayoutPresetControl::TYPE,
                'default'     => '',
                'layouts'     => static::reviewLayoutCards(),
                'categories'  => static::reviewLayoutCategories(),
                'tuningKeys'  => ReviewLayoutPresets::tuningControls(),
                'tuningDefaults' => static::reviewTuningDefaults(),
            ]
        );

        $this->addReviewLayoutPresetProNotice();
    }

    /**
     * One card per layout, in the shape the picker's template reads.
     *
     * Everything but the drawing comes from core's declaration, so a layout
     * renamed or moved between free and Pro is right here without being
     * touched. `locked` is exists() asking the same question the renderer
     * asks, so a card that cannot be chosen is exactly a layout that would
     * not have drawn.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function reviewLayoutCards(): array
    {
        $cards = [[
            'value'    => '',
            'label'    => esc_html__('Custom layout', 'fluent-cart-elementor-blocks'),
            'help'     => esc_html__('Custom layout. Choose a preset to replace it with a starter layout.', 'fluent-cart-elementor-blocks'),
            'category' => 'custom',
            'locked'   => false,
            // No drawing: the picker gives Custom a line of its own above the
            // grid rather than a card, there being no shape to draw.
            'thumb'    => '',
            // Nothing to write and nothing to match: Custom is what the
            // picker says when no layout's settings are the ones in force.
            'settings' => [],
            'tuning'   => [],
        ]];

        foreach (LayoutPresets::all() as $id => $preset) {
            $id = (string) $id;

            $cards[] = [
                'value'    => $id,
                'label'    => (string) Arr::get($preset, 'label', $id),
                'help'     => (string) Arr::get($preset, 'help', ''),
                'category' => (string) Arr::get($preset, 'category', 'list'),
                'locked'   => !ReviewLayoutPresets::exists($id),
                'thumb'    => ReviewLayoutThumbnails::svg($id),
                // What this layout is, as settings. The picker writes these
                // when it is chosen and reads them back to work out which
                // layout the widget is currently in - the same set doing both
                // jobs, so the two can never disagree about what Card Grid
                // means. Tuning is left out of it on purpose: it travels with
                // the merchant, not with the layout.
                'settings' => static::layoutIdentitySettings($id),
                // What this layout would tune to, kept apart from what it is.
                // Choosing a layout applies these too - but only where the
                // merchant had left the outgoing layout's tuning alone. The
                // block editor draws the same distinction in tunedAttributes():
                // a value that still matches the layout being left is that
                // layout's opinion, not the merchant's, and has no claim on
                // the next one.
                'tuning'   => static::layoutTuningSettings($id),
            ];
        }

        return $cards;
    }

    /**
     * A layout's settings, without the tuning ones.
     *
     * The block editor draws the same line: its matcher ignores six
     * attributes, and applying a layout carries those six across rather than
     * resetting them. So a merchant who sets four to a row still has Card
     * Grid, and still has four to a row after switching to Masonry.
     *
     * @param string $preset
     * @return array<string, mixed>
     */
    protected static function layoutIdentitySettings(string $preset): array
    {
        $settings = ReviewLayoutPresets::controlSettings($preset);

        foreach (ReviewLayoutPresets::tuningControls() as $tuning) {
            unset($settings[$tuning]);
        }

        return $settings;
    }

    /**
     * What each tuning control holds when nobody has touched it.
     *
     * The picker needs these to tell a value someone chose from one that has
     * simply always been there. Leaving a layout, the layout's own tuning
     * answers that question; leaving Custom there is no layout to ask, and
     * without these every untouched default would travel to the next layout
     * as though it had been asked for - a merchant picking Photo Strip would
     * get numbered pages because the control had always said numbered.
     *
     * The block editor asks a block for its registered default at exactly
     * this point, in tunedAttributes(). Elementor cannot be asked the same
     * way here, the control being registered before the ones it names, so
     * the list is written out - and reviewTuningDefaultsMatchControls() in
     * the test below keeps it honest.
     *
     * @return array<string, mixed>
     */
    protected static function reviewTuningDefaults(): array
    {
        return [
            'grid_columns'           => 2,
            'per_page'               => 0,
            'pagination_type'        => 'numbers',
            'default_sort'           => ProductReviewListWidget::FALLBACK_SORT,
            'slider_autoplay'        => 'no',
            'slider_autoplay_delay'  => 3000,
            'slider_arrows'          => 'yes',
            'slider_arrows_size'     => 'md',
            'slider_arrows_position' => 'overlap',
            'slider_infinite'        => '',
            'slider_pagination'      => 'yes',
            'slider_pagination_type' => 'bullets',
        ];
    }

    /**
     * The tuning settings a layout would set, on their own.
     *
     * @param string $preset
     * @return array<string, mixed>
     */
    protected static function layoutTuningSettings(string $preset): array
    {
        $settings = ReviewLayoutPresets::controlSettings($preset);
        $tuning = [];

        foreach (ReviewLayoutPresets::tuningControls() as $key) {
            if (array_key_exists($key, $settings)) {
                $tuning[$key] = $settings[$key];
            }
        }

        return $tuning;
    }

    /**
     * The tabs above the grid: All first, then whichever categories the
     * layouts actually use, so removing the last photo layout removes the
     * Photo tab rather than leaving an empty one.
     *
     * @return array<int, array<string, string>>
     */
    protected static function reviewLayoutCategories(): array
    {
        $labels = [
            'list'     => esc_html__('List', 'fluent-cart-elementor-blocks'),
            'grid'     => esc_html__('Grid', 'fluent-cart-elementor-blocks'),
            'carousel' => esc_html__('Carousel', 'fluent-cart-elementor-blocks'),
            'photo'    => esc_html__('Photo', 'fluent-cart-elementor-blocks'),
        ];

        $categories = [[
            'value' => 'all',
            'label' => esc_html__('All layouts', 'fluent-cart-elementor-blocks'),
        ]];

        $seen = [];

        foreach (LayoutPresets::all() as $preset) {
            $category = (string) Arr::get($preset, 'category', 'list');

            if (isset($seen[$category]) || !isset($labels[$category])) {
                continue;
            }

            $seen[$category] = true;
            $categories[] = ['value' => $category, 'label' => $labels[$category]];
        }

        return $categories;
    }

    /**
     * The layout names inertPresets() refuses, without the empty one: Custom
     * is not a locked layout, it is the absence of one.
     *
     * @return array<int, string>
     */
    protected static function lockedReviewPresets(): array
    {
        $locked = [];

        foreach (ReviewLayoutPresets::inertPresets() as $preset) {
            if ($preset !== '') {
                $locked[] = $preset;
            }
        }

        return $locked;
    }

    /**
     * The note under Layout Preset.
     *
     * It used to wait for a locked layout to be picked. Those cannot be
     * picked now, so there is no such moment, and it stands with them as the
     * only thing saying why most of the list is greyed.
     *
     * It says what happens now and what happens later, because both surprise
     * people. Now, no layout is applied at all and the section is whatever
     * the controls below say. After Pro is activated, a layout supplies what
     * those controls have not been told.
     */
    protected function addReviewLayoutPresetProNotice(): void
    {
        if (App::isProActive() || !static::lockedReviewPresets()) {
            return;
        }

        $this->add_control(
            'layout_preset_pro_notice',
            [
                'type'            => \Elementor\Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('The greyed layouts need FluentCart Pro. Until then the section is whatever the controls below say. After Pro is activated, the layout you choose supplies what you have not set yourself.', 'fluent-cart-elementor-blocks'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
            ]
        );
    }

    protected function reviewViewModeOptions(): array
    {
        $pro = App::isProActive();

        return [
            'list'   => esc_html__('List', 'fluent-cart-elementor-blocks'),
            'grid'   => $pro
                ? esc_html__('Grid', 'fluent-cart-elementor-blocks')
                : esc_html__('Grid (Pro)', 'fluent-cart-elementor-blocks'),
            'slider' => $pro
                ? esc_html__('Slider', 'fluent-cart-elementor-blocks')
                : esc_html__('Slider (Pro)', 'fluent-cart-elementor-blocks'),
        ];
    }

    /**
     * The classes that tell the panel guard which options Pro draws.
     *
     * The value rides in the class name because Elementor's control arguments
     * offer no other way through: `classes` is the one string that reaches
     * the rendered control. The guard reads the values back off it and
     * disables those options, so a merchant cannot choose a view this site
     * will not draw - and does not choose one, watch the panel fill with
     * slider settings, and then read that none of it applies.
     *
     * Empty with Pro, which leaves every option as it was.
     */
    protected function reviewViewModeProClasses(): string
    {
        if (App::isProActive()) {
            return '';
        }

        return 'fct-control-pro-options fct-pro-option-grid fct-pro-option-slider';
    }

    /**
     * The warning under View Mode.
     *
     * It used to wait for a locked mode to be picked. Now that those options
     * are disabled there is no such moment, so it stands with them - it is
     * the only thing saying why two of the three cannot be chosen.
     */
    protected function addReviewViewModeProNotice(): void
    {
        if (App::isProActive()) {
            return;
        }

        $this->add_control(
            'view_mode_pro_notice',
            [
                'type'            => \Elementor\Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('Grid and Slider need FluentCart Pro. Without it this list renders as a single column.', 'fluent-cart-elementor-blocks'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
            ]
        );
    }

    /**
     * The two ways a full-width attachment can take over the card.
     *
     * Both belong under Full Width Attachments and neither means anything
     * without it: a tile at its own size has no card edge to reach and nothing
     * to sit behind. Backdrop and flush are alternatives rather than a pair,
     * so flush stands down while backdrop is on -- the same shape the block
     * editor's Attachments panel has, with the same words, so a merchant who
     * has set this up in one builder recognises it in the other.
     *
     * Pro draws them. The gate is ReviewThreadMarkup's and stays there: these
     * travel as intent, so a section configured before Pro arrives draws as
     * configured the day it does, rather than needing to be set up twice.
     */
    protected function addReviewMediaStyleControls(): void
    {
        $pro = App::isProActive();

        $this->add_control(
            'media_backdrop',
            [
                'label'        => $pro
                    ? esc_html__('Attachment As Card Background', 'fluent-cart-elementor-blocks')
                    : esc_html__('Attachment As Card Background (Pro)', 'fluent-cart-elementor-blocks'),
                'description'  => esc_html__('The first attachment fills the card and the rest of the review sits over it. A review with no attachment is unaffected, so a grid can mix photo cards and plain ones.', 'fluent-cart-elementor-blocks'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => '',
                'condition'    => ['media_full_width' => 'yes'],
                'classes'      => $pro ? '' : 'fct-control-pro-only',
            ]
        );

        $this->add_control(
            'media_flush',
            [
                'label'        => $pro
                    ? esc_html__('Flush To Card Edges', 'fluent-cart-elementor-blocks')
                    : esc_html__('Flush To Card Edges (Pro)', 'fluent-cart-elementor-blocks'),
                'description'  => esc_html__('The attachment becomes the top of the card: no padding around it, and one height for every card in the row. Applies when the attachments are the first thing in the review.', 'fluent-cart-elementor-blocks'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Yes', 'fluent-cart-elementor-blocks'),
                'label_off'    => esc_html__('No', 'fluent-cart-elementor-blocks'),
                'return_value' => 'yes',
                'default'      => '',
                // Backdrop already gives the attachment the whole card. Flush
                // on top of it is a setting with nothing left to change.
                'condition'    => ['media_full_width' => 'yes', 'media_backdrop!' => 'yes'],
                'classes'      => $pro ? '' : 'fct-control-pro-only',
            ]
        );

        $this->addReviewMediaStyleProNotice();
    }

    /**
     * The note under them.
     *
     * The View Mode notice waits for the locked choice to be made, because it
     * can be. These cannot: the controls are shown switched off and left that
     * way, so a merchant would otherwise be looking at two settings that do
     * nothing, with nothing saying why. It appears with them rather than
     * standing on every panel -- Full Width is what brings all three out.
     */
    protected function addReviewMediaStyleProNotice(): void
    {
        if (App::isProActive()) {
            return;
        }

        $this->add_control(
            'media_style_pro_notice',
            [
                'type'            => \Elementor\Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('These two need FluentCart Pro. Without it an attachment stays inside the card at its own size.', 'fluent-cart-elementor-blocks'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
                'condition'       => ['media_full_width' => 'yes'],
            ]
        );
    }

    /**
     * The panel's attachment choices in the shape the renderer reads.
     *
     * @param array $settings the widget's settings
     * @param bool $fullWidth whether attachments span the whole review
     */
    protected function reviewMediaOptions(array $settings, bool $fullWidth): array
    {
        return [
            'mediaVisible'   => ProductReviewRenderer::mediaVisibleCount($settings['media_visible'] ?? 0),
            'mediaWidth'     => ProductReviewRenderer::mediaTileSize($settings['media_width'] ?? 0),
            'mediaHeight'    => ProductReviewRenderer::mediaTileSize($settings['media_height'] ?? 0),
            'mediaFullWidth' => $fullWidth,
            // Only at full width, and backdrop before flush, so the options
            // say what the panel shows rather than what an older save left
            // behind when the merchant changed their mind.
            'mediaBackdrop'  => $fullWidth && static::switchOn($settings, 'media_backdrop'),
            'mediaFlush'     => $fullWidth
                && !static::switchOn($settings, 'media_backdrop')
                && static::switchOn($settings, 'media_flush'),
            'mediaMore'      => ReviewThreadMarkup::moreTilePlacement($settings['media_more'] ?? 'overlay'),
        ];
    }

    /**
     * Whether a switcher is on.
     *
     * The trait's own, rather than the widgets' isOn(): those two do not agree
     * on what an untouched control means, and these two controls default to
     * off in both builders. Absent is off here, with no room for the question.
     */
    protected static function switchOn(array $settings, string $key): bool
    {
        return ($settings[$key] ?? '') === 'yes';
    }

    protected function renderReviewsUnavailable($postId = 0): bool
    {
        $reason = ReviewSupport::unavailableReason($postId);

        if ($reason === '') {
            return false;
        }

        $this->renderPlaceholder($reason);

        return true;
    }
}
