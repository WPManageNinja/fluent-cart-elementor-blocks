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
     * rather than by being kept in step. The controls below it describe the
     * arrangement a merchant builds by hand, so they show only for Custom;
     * with a preset chosen they would be describing something they do not
     * decide.
     */
    protected function addReviewLayoutPresetControl(): void
    {
        $this->add_control(
            'layout_preset',
            [
                'label'       => esc_html__('Layout Preset', 'fluent-cart-elementor-blocks'),
                'description' => esc_html__('Builds the section from a ready-made layout. Choose Custom to arrange it with the controls below.', 'fluent-cart-elementor-blocks'),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'default'     => '',
                'options'     => ReviewLayoutPresets::options(),
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

    protected function reviewMediaOptions(array $settings, bool $fullWidth): array
    {
        return [
            'mediaVisible'   => ProductReviewRenderer::mediaVisibleCount($settings['media_visible'] ?? 0),
            'mediaWidth'     => ProductReviewRenderer::mediaTileSize($settings['media_width'] ?? 0),
            'mediaHeight'    => ProductReviewRenderer::mediaTileSize($settings['media_height'] ?? 0),
            'mediaFullWidth' => $fullWidth,
            'mediaMore'      => ReviewThreadMarkup::moreTilePlacement($settings['media_more'] ?? 'overlay'),
        ];
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
