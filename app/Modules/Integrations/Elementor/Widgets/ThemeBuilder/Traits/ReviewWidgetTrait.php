<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Widgets\ThemeBuilder\Traits;

use FluentCart\App\Services\ProductReviewService;
use FluentCart\App\Services\Renderer\ProductReviewRenderer;
use FluentCart\App\Services\Renderer\ReviewThreadMarkup;
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

    /**
     * Where the Verified Purchase switcher starts.
     *
     * The store has its own switch for the badge, and a widget control that
     * always started on would quietly overrule it: a store that had turned
     * badges off would find them back on every page carrying this widget.
     * Reading the store's answer as the default keeps the control honest and
     * still lets a single placement differ.
     */
    protected static function verifiedBadgeDefault(): string
    {
        $settings = (array) ProductReviewService::getReviewSettings();

        return (!isset($settings['show_verified_badge']) || $settings['show_verified_badge'] === 'yes') ? 'yes' : '';
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
     * Every call is guarded. This add-on ships separately from FluentCart, and
     * against a core that predates these helpers an unguarded call is a fatal
     * on every render — rather than a setting that quietly does nothing, which
     * is what an older core should give.
     *
     * @param array $settings
     * @param bool  $fullWidth already read by the widget, whose switcher
     *                         default differs from the other's.
     */
    protected function reviewMediaOptions(array $settings, bool $fullWidth): array
    {
        return [
            'mediaVisible'   => self::boundedMediaValue('mediaVisibleCount', $settings['media_visible'] ?? 0),
            'mediaWidth'     => self::boundedMediaValue('mediaTileSize', $settings['media_width'] ?? 0),
            'mediaHeight'    => self::boundedMediaValue('mediaTileSize', $settings['media_height'] ?? 0),
            'mediaFullWidth' => $fullWidth,
            'mediaMore'      => method_exists(ReviewThreadMarkup::class, 'moreTilePlacement')
                ? ReviewThreadMarkup::moreTilePlacement($settings['media_more'] ?? 'overlay')
                : 'overlay',
        ];
    }

    /**
     * 0 from an older core, which is "leave it to the stylesheet" for a size
     * and "show them all" for a limit — the same as the setting untouched.
     *
     * @param mixed $value
     */
    protected static function boundedMediaValue(string $method, $value): int
    {
        return method_exists(ProductReviewRenderer::class, $method)
            ? (int) ProductReviewRenderer::{$method}($value)
            : 0;
    }

    /**
     * The highest limit worth offering: what a review may actually hold.
     */
    protected static function maxAttachmentsShown(): int
    {
        return method_exists(ProductReviewRenderer::class, 'maxPhotosPerReview')
            ? ProductReviewRenderer::maxPhotosPerReview()
            : 5;
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
