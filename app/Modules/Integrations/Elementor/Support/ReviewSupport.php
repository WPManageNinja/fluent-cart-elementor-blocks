<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Whether this site can render product reviews, and why not when it cannot.
 *
 * The review feature arrived in FluentCart after this addon shipped, so a
 * store can be running a core old enough to have no review classes at all.
 * Every review widget asks here before it registers a control or renders a
 * line, and the answer is three separate questions that fail differently:
 *
 *  - isSupportedByCore() — does this core have the feature? A class check,
 *    not a version_compare: the class either exists to be called or it does
 *    not, and that stays true across backports, betas and forks, where a
 *    version string does not. Widgets are hidden from the panel entirely
 *    when this is false, because nothing about them could work.
 *  - isModuleActive() — has the store switched Product Reviews on? The
 *    classes exist, so the widget is offered and explains itself in the
 *    editor rather than vanishing from a panel the merchant is looking at.
 *  - isVisibleFor() — does THIS product show reviews? Core's own policy,
 *    asked per product.
 *
 * Deliberately no version constant here. Adding one would mean editing this
 * file on a core release that changes nothing about what we call.
 */
class ReviewSupport
{
    /**
     * The core API the review widgets render through: every class, and on each
     * of them every method one of the six widgets actually calls.
     *
     * Classes alone are not the contract. A fork or a partial backport can
     * carry ProductReviewRenderer without renderWriteReviewCta(), or an
     * AssetLoader without the review form's own bundle, and a class-only gate
     * would wave that through and fatal on first render — which is the exact
     * failure this gate exists to prevent.
     *
     * Keep this in step with the widgets: a new core call belongs here.
     */
    const REQUIRED_API = [
        'FluentCart\\Api\\ModuleSettings' => [
            'isActive',
        ],
        'FluentCart\\App\\Services\\ProductReviewService' => [
            'getProductRatingSummary',
            'getReviewSettings',
        ],
        'FluentCart\\App\\Services\\Renderer\\ProductReviewRenderer' => [
            'render',
            'renderForm',
            'renderSummarySection',
            'renderWriteReviewCta',
        ],
        'FluentCart\\App\\Services\\Renderer\\ProductCardRender' => [
            'renderStarRatingBlock',
        ],
        'FluentCart\\App\\Modules\\Templating\\AssetLoader' => [
            'loadSingleProductAssets',
            'loadReviewSubmissionFormAssets',
        ],
    ];

    /**
     * Does the installed FluentCart carry the review feature at all?
     *
     * Memoised per request: every widget calls this from show_in_panel(),
     * which Elementor runs for each widget on every editor load.
     */
    public static function isSupportedByCore(): bool
    {
        static $supported = null;

        if ($supported !== null) {
            return $supported;
        }

        foreach (self::REQUIRED_API as $class => $methods) {
            if (!class_exists($class)) {
                return $supported = false;
            }

            foreach ($methods as $method) {
                if (!method_exists($class, $method)) {
                    return $supported = false;
                }
            }
        }

        return $supported = true;
    }

    /**
     * Has the store switched the Product Reviews module on? False whenever
     * core does not have the feature, so callers can ask this alone.
     */
    public static function isModuleActive(): bool
    {
        if (!self::isSupportedByCore()) {
            return false;
        }

        return \FluentCart\Api\ModuleSettings::isActive('reviews');
    }

    /**
     * Does this product show its reviews on this page? Core's own policy —
     * the store's Enable Product Reviews switch, the product's own toggle,
     * and on a product page the show-reviews setting with its filter.
     *
     * Asking core rather than re-deriving it is the point: the widget, the
     * storefront list and the JSON-LD then agree by construction, and a
     * merchant who hides reviews for one product hides them everywhere.
     *
     * @param int $postId
     */
    public static function isVisibleFor($postId): bool
    {
        if (!self::isModuleActive()) {
            return false;
        }

        // Added alongside the schema work; a core that has the module but not
        // this helper falls back to the module switch it already answered.
        if (!method_exists('FluentCart\\App\\Services\\Renderer\\ProductReviewRenderer', 'isVisibleFor')) {
            return true;
        }

        return \FluentCart\App\Services\Renderer\ProductReviewRenderer::isVisibleFor((int) $postId);
    }

    /**
     * Why reviews are not rendering, for the editor canvas. Empty string when
     * nothing is wrong, so a caller can treat it as the whole check.
     *
     * @param int $postId 0 to skip the per-product question.
     */
    public static function unavailableReason($postId = 0): string
    {
        if (!self::isSupportedByCore()) {
            return esc_html__(
                'Product reviews need a newer version of FluentCart. Update FluentCart to use this widget.',
                'fluent-cart-elementor-blocks'
            );
        }

        if (!self::isModuleActive()) {
            return esc_html__(
                'The Product Reviews module is switched off. Enable it in FluentCart → Settings → Modules.',
                'fluent-cart-elementor-blocks'
            );
        }

        if ($postId && !self::isVisibleFor($postId)) {
            return esc_html__(
                'Reviews are turned off for this product.',
                'fluent-cart-elementor-blocks'
            );
        }

        return '';
    }
}
