<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support;

use FluentCart\App\App;
use FluentCart\App\Services\Reviews\LayoutPresets;
use FluentCart\Framework\Support\Arr;

/**
 * The review layouts, as this panel needs to describe them.
 *
 * The layouts themselves are core's, at FluentCart\App\Services\Reviews\
 * LayoutPresets — the same eleven the block editor uses, declared once. This
 * class adapts their semantic settings to Elementor and answers what the
 * panel asks: what to call them, and which are Pro.
 */
class ReviewLayoutPresets
{
    /**
     * Translate the shared semantic preset contract into core renderer options.
     *
     * Elementor owns the widget markup; it must not render Gutenberg parsed
     * blocks. The review data, pagination, Pro gates and JavaScript contract
     * remain in ProductReviewRenderer.
     */
    public static function rendererOptions(string $preset): array
    {
        $config = LayoutPresets::builderSettings($preset);
        $section = (array) Arr::get($config, 'section', []);
        $list = (array) Arr::get($config, 'list', []);
        $header = (array) Arr::get($config, 'header', []);
        $item = (array) Arr::get($config, 'item', []);
        $sort = (array) Arr::get($list, 'sort', []);
        $slider = (array) Arr::get($list, 'slider', []);
        $summary = Arr::get($section, 'summary', 'side');
        $pagination = Arr::get($list, 'pagination', 'numbers');

        $options = [
            'showSummary' => $summary !== 'none',
            'summaryMode' => in_array($summary, ['side', 'top', 'cta'], true)
                ? $summary
                : 'side',
            'showCount' => (bool) Arr::get($header, 'count', true),
            'showFilterChips' => (bool) Arr::get($header, 'filters', false),
            'showSortControls' => (bool) Arr::get($header, 'sorting', false),
            'showReviewDate' => (bool) Arr::get($item, 'show_date', true),
            'showReviewerName' => (bool) Arr::get($item, 'show_reviewer_name', true),
            // Gutenberg's row template includes the verified badge unless a
            // preset explicitly turns it off. Do not fall back to the store
            // setting here or the two builders can disagree.
            'showVerifiedBadge' => (bool) Arr::get($item, 'show_badge', true),
            'viewMode' => (string) Arr::get($list, 'view_mode', 'list'),
            // Preserve the shared contract for list presets, whose column
            // value is intentionally 1. ProductReviewRenderer applies its
            // own minimum only when a grid or slider actually uses it.
            'gridColumns' => max(1, min(6, absint(Arr::get($list, 'columns', 2)))),
            'perPage' => max(0, min(100, absint(Arr::get($list, 'per_page', 0)))),
            'paginationType' => (string) $pagination,
            'showPagination' => $pagination !== 'none',
            'hasMedia' => (bool) Arr::get($list, 'has_media', false),
            'defaultSortBy' => (string) Arr::get($sort, 'by', 'created_at'),
            'defaultSortOrder' => (string) Arr::get($sort, 'order', 'DESC'),
            'sliderSettings' => [
                'arrows' => Arr::get($slider, 'arrows') ? 'yes' : 'no',
                'pagination' => Arr::get($slider, 'pagination') ? 'yes' : 'no',
                'paginationType' => (string) Arr::get($slider, 'pagination_type', 'bullets'),
                'autoplay' => Arr::get($slider, 'autoplay') ? 'yes' : 'no',
                'infinite' => Arr::get($slider, 'infinite') ? 'yes' : 'no',
            ],
        ];

        $showAvatar = Arr::get($item, 'show_avatar', null);
        if ($showAvatar !== null) {
            $options['showAvatar'] = (bool) $showAvatar;
        }

        $showTitle = Arr::get($item, 'show_title', null);
        if ($showTitle !== null) {
            $options['showTitle'] = (bool) $showTitle;
        }

        $showContent = Arr::get($item, 'show_content', null);
        if ($showContent !== null) {
            $options['showContent'] = (bool) $showContent;
        }

        $showFooter = Arr::get($item, 'show_footer', null);
        if ($showFooter !== null) {
            $options['showFooter'] = (bool) $showFooter;
        }

        $showPhotos = Arr::get($item, 'show_photos', null);
        if ($showPhotos !== null) {
            $options['showPhotos'] = (bool) $showPhotos;
        }

        $showVariation = Arr::get($item, 'show_variation', null);
        if ($showVariation !== null) {
            $options['showVariation'] = (bool) $showVariation;
        }

        // How the row is arranged. Forwarded rather than interpreted: core's
        // ReviewListRenderer builds the same two arrangements the Gutenberg
        // template does, so the widget and the block agree by construction.
        foreach ([
            'photos_first' => 'photosFirst',
            'rating_first' => 'ratingFirst',
            'badge_last'   => 'badgeLast',
            'show_meta'    => 'showMeta',
        ] as $key => $option) {
            $value = Arr::get($item, $key, null);
            if ($value !== null) {
                $options[$option] = (bool) $value;
            }
        }

        // The card class the preset asks for. Core whitelists it on the way
        // in, so an unknown name becomes nothing rather than markup.
        $itemClass = Arr::get($item, 'class', '');
        if ($itemClass !== '') {
            $options['itemClass'] = (string) $itemClass;
        }

        $media = (array) Arr::get($item, 'media', []);
        $mediaVisible = Arr::get($media, 'visible', null);
        if ($mediaVisible !== null) {
            $options['mediaVisible'] = absint($mediaVisible);
        }

        if (Arr::get($media, 'full_width', false)) {
            $options['mediaFullWidth'] = true;
        }

        // Pro, and gated in core rather than here: ProductReviewService::
        // isPhotoStylingAllowed() refuses both on a site without Pro, so a
        // widget saved against a lapsed subscription flattens on its own.
        if (Arr::get($media, 'flush', false)) {
            $options['mediaFlush'] = true;
        }

        if (Arr::get($media, 'backdrop', false)) {
            $options['mediaBackdrop'] = true;
        }

        return $options;
    }

    /**
     * The panel's dropdown.
     *
     * 'Custom' first and selected by default: a widget that has never had a
     * preset applied has not chosen one, and naming a layout it did not build
     * would be a claim about settings nobody set. Custom is also where the
     * individual layout controls live — with a preset chosen they would be
     * describing an arrangement they do not decide.
     */
    public static function options(): array
    {
        $pro = App::isProActive();
        $options = ['' => esc_html__('Custom', 'fluent-cart-elementor-blocks')];

        foreach (LayoutPresets::all() as $id => $preset) {
            $label = (string) Arr::get($preset, 'label', $id);
            $options[$id] = (Arr::get($preset, 'pro', false) && !$pro)
                /* translators: %s - layout name */
                ? sprintf(esc_html__('%s (Pro)', 'fluent-cart-elementor-blocks'), $label)
                : $label;
        }

        return $options;
    }

    /**
     * What the picker says about the layout in hand, for the panel's hint.
     */
    public static function help(string $preset): string
    {
        $all = LayoutPresets::all();

        return isset($all[$preset]) ? (string) Arr::get($all[$preset], 'help', '') : '';
    }

    /**
     * Whether a preset by that name exists. A widget saved against a layout
     * that has since been renamed or removed falls back to its own controls
     * rather than rendering nothing.
     */
    public static function exists(string $preset): bool
    {
        if ($preset === '') {
            return false;
        }

        $all = LayoutPresets::all();
        if (!isset($all[$preset])) {
            return false;
        }

        // A saved widget can outlive a Pro subscription. Do not let its
        // preset CSS or photo layout continue rendering after Pro is gone;
        // the widget falls back to its ordinary free settings instead.
        return !Arr::get($all[$preset], 'pro', false) || App::isProActive();
    }
}
