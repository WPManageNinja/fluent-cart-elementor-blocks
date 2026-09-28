<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits;

use FluentCart\App\Services\ProductReviewService;
use FluentCart\App\Services\Renderer\ProductReviewRenderer;
use FluentCart\App\Services\Renderer\ReviewThreadMarkup;
use FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support\ReviewLayoutPresets;
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
                'description' => esc_html__('Builds the section from a ready-made layout. The controls below stay yours — anything you set there overrides the layout. Choose Custom to start from nothing.', 'fluent-cart-elementor-blocks'),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'default'     => '',
                'options'     => ReviewLayoutPresets::options(),
            ]
        );

        $this->addReviewLayoutPresetProNotice();
    }

    /**
     * The warning under Layout Preset, shown only once a locked layout is
     * picked -- the same way the View Mode notice works, and for the same
     * reason: standing small print becomes furniture.
     *
     * It says what happens now and what happens later, because both surprise
     * people. Now, the layout is not applied at all and the section is
     * whatever the controls below say. After Pro is activated, the layout
     * supplies what those controls have not been told.
     */
    protected function addReviewLayoutPresetProNotice(): void
    {
        if (App::isProActive()) {
            return;
        }

        $locked = [];

        foreach (ReviewLayoutPresets::inertPresets() as $preset) {
            if ($preset !== '') {
                $locked[] = $preset;
            }
        }

        if (!$locked) {
            return;
        }

        $this->add_control(
            'layout_preset_pro_notice',
            [
                'type'            => \Elementor\Controls_Manager::RAW_HTML,
                'raw'             => esc_html__('This layout needs FluentCart Pro. Until then the section is whatever the controls below say. After Pro is activated, the layout supplies what you have not set yourself.', 'fluent-cart-elementor-blocks'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
                'condition'       => ['layout_preset' => $locked],
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
     * The warning under View Mode, shown only once a locked mode is picked.
     *
     * Conditional rather than standing: a line of small print that is always
     * there is read once and then becomes furniture, while one that appears on
     * the choice it concerns is read every time.
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
                'condition'       => ['view_mode' => ['grid', 'slider']],
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
            ]
        );

        $this->addReviewMediaStyleProNotice();
    }

    /**
     * The warning under them, on the same terms as the View Mode one: only
     * once a locked style is actually chosen.
     */
    protected function addReviewMediaStyleProNotice(): void
    {
        if (App::isProActive()) {
            return;
        }

        $notice = esc_html__('This needs FluentCart Pro. Without it the attachment stays inside the card at its own size.', 'fluent-cart-elementor-blocks');

        $this->add_control(
            'media_backdrop_pro_notice',
            [
                'type'            => \Elementor\Controls_Manager::RAW_HTML,
                'raw'             => $notice,
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
                'condition'       => ['media_backdrop' => 'yes'],
            ]
        );

        $this->add_control(
            'media_flush_pro_notice',
            [
                'type'            => \Elementor\Controls_Manager::RAW_HTML,
                'raw'             => $notice,
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
                'condition'       => ['media_flush' => 'yes', 'media_backdrop!' => 'yes'],
            ]
        );
    }

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
