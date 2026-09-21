<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support;

if (!defined('ABSPATH')) {
    exit;
}

/** Product Reviews module and product visibility checks for Elementor widgets. */
class ReviewSupport
{
    /** Has the store switched the Product Reviews module on? */
    public static function isModuleActive(): bool
    {
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
