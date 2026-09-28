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
            // Every preset that draws a footer draws the store's reply in it:
            // LayoutPresets::row() puts the reply beside the votes in the one
            // footer group it builds, so there is no preset where this is off
            // and a footer still appears. Stated here rather than left to the
            // widget: a preset answers for every flag it draws, and a merchant
            // who wants this one off says so on the control, which is laid
            // over this.
            'showViewReply' => true,
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
            // Every key the preset states, not just the ones that switch a
            // feature on. Arrow size and position and the autoplay delay were
            // left out, so a preset asking for small arrows clear of the track
            // got medium ones over the cards, and a testimonial set to turn
            // every five seconds turned every three.
            'sliderSettings' => [
                'arrows' => Arr::get($slider, 'arrows') ? 'yes' : 'no',
                'arrowsSize' => (string) Arr::get($slider, 'arrows_size', 'md'),
                'arrowsPosition' => (string) Arr::get($slider, 'arrows_position', 'overlap'),
                'pagination' => Arr::get($slider, 'pagination') ? 'yes' : 'no',
                'paginationType' => (string) Arr::get($slider, 'pagination_type', 'bullets'),
                'autoplay' => Arr::get($slider, 'autoplay') ? 'yes' : 'no',
                'autoplayDelay' => (int) Arr::get($slider, 'autoplay_delay', 3000),
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
     * Which layout a set of widget settings actually is, or '' for none.
     *
     * The same question the picker answers in the panel, answered the same
     * way and from the same data, because two answers would be two layouts.
     * Tuning is left out of it: four to a row is still Card Grid.
     *
     * @param array $settings
     * @return string
     */
    public static function detect(array $settings): string
    {
        foreach (array_keys(LayoutPresets::all()) as $id) {
            $id = (string) $id;

            if (!static::exists($id)) {
                continue;
            }

            $want = static::controlSettings($id);

            foreach (static::tuningControls() as $tuning) {
                unset($want[$tuning]);
            }

            $matches = true;

            foreach ($want as $key => $value) {
                if ((string) (isset($settings[$key]) ? $settings[$key] : '') !== (string) $value) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                return $id;
            }
        }

        return '';
    }

    /**
     * The settings the six tuning controls hold.
     *
     * The block editor keeps the same list and keeps it for the same reason:
     * how many to a row, how many to a page, which arrows and which order are
     * a merchant's tuning of a layout, not the layout itself. Choosing a new
     * one carries them across rather than putting them back to that layout's
     * idea of them, and a layout tuned this way is still that layout.
     *
     * @return array<int, string>
     */
    public static function tuningControls(): array
    {
        return [
            'grid_columns',
            'per_page',
            'pagination_type',
            'default_sort',
            'slider_autoplay',
            'slider_autoplay_delay',
            'slider_arrows',
            'slider_arrows_size',
            'slider_arrows_position',
            'slider_infinite',
            'slider_pagination',
            'slider_pagination_type',
        ];
    }

    /**
     * A layout, as a complete set of widget settings.
     *
     * Complete, not only what the layout mentions: choosing one has to put
     * back what the last one changed, the way the block editor rebuilds the
     * whole row from a template rather than patching the row it finds. A
     * layout that says nothing about the avatar means a card with an avatar,
     * not a card keeping whatever the previous layout did with it.
     *
     * @param string $preset
     * @return array<string, mixed>
     */
    public static function controlSettings(string $preset): array
    {
        $options = static::exists($preset) ? static::rendererOptions($preset) : [];

        $settings = [];

        foreach (static::controlMap() as $option => $control) {
            $value = array_key_exists($option, $options)
                ? $options[$option]
                : static::rendererDefault($option);

            $settings[$control] = is_bool($value) ? ($value ? 'yes' : '') : $value;
        }

        list($sortBy, $sortOrder) = [
            (string) (isset($options['defaultSortBy']) ? $options['defaultSortBy'] : 'created_at'),
            (string) (isset($options['defaultSortOrder']) ? $options['defaultSortOrder'] : 'DESC'),
        ];
        $settings['default_sort'] = $sortBy . '-' . $sortOrder;

        $slider = isset($options['sliderSettings']) ? (array) $options['sliderSettings'] : [];

        foreach (static::sliderControlMap() as $key => $control) {
            if (array_key_exists($key, $slider)) {
                $settings[$control] = $slider[$key];
            }
        }

        return $settings;
    }

    /**
     * Renderer option to widget control, for everything that is one of each.
     *
     * @return array<string, string>
     */
    protected static function controlMap(): array
    {
        return [
            'showSummary'       => 'show_summary',
            'summaryMode'       => 'summary_mode',
            'showCount'         => 'show_count',
            'showFilterChips'   => 'show_filter',
            'showSortControls'  => 'show_sorting',
            'showReviewerName'  => 'show_reviewer_name',
            'showReviewDate'    => 'show_review_date',
            'showVerifiedBadge' => 'show_verified',
            'showViewReply'     => 'show_view_reply',
            'showAvatar'        => 'show_avatar',
            'showTitle'         => 'show_title',
            'showContent'       => 'show_content',
            'showVariation'     => 'show_variation',
            'showPhotos'        => 'show_photos',
            'showFooter'        => 'show_footer',
            'showMeta'          => 'show_meta',
            'photosFirst'       => 'photos_first',
            'ratingFirst'       => 'rating_first',
            'badgeLast'         => 'badge_last',
            'itemClass'         => 'item_class',
            'viewMode'          => 'view_mode',
            'hasMedia'          => 'photos_only',
            'mediaVisible'      => 'media_visible',
            'mediaFullWidth'    => 'media_full_width',
            'mediaFlush'        => 'media_flush',
            'mediaBackdrop'     => 'media_backdrop',
            'gridColumns'       => 'grid_columns',
            'perPage'           => 'per_page',
            'paginationType'    => 'pagination_type',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function sliderControlMap(): array
    {
        return [
            'autoplay'       => 'slider_autoplay',
            'autoplayDelay'  => 'slider_autoplay_delay',
            'arrows'         => 'slider_arrows',
            'arrowsSize'     => 'slider_arrows_size',
            'arrowsPosition' => 'slider_arrows_position',
            'infinite'       => 'slider_infinite',
            'pagination'     => 'slider_pagination',
            'paginationType' => 'slider_pagination_type',
        ];
    }

    /**
     * What a section looks like when no layout has said otherwise.
     *
     * @param string $option
     * @return mixed
     */
    protected static function rendererDefault(string $option)
    {
        $defaults = [
            'showSummary' => true, 'summaryMode' => 'side', 'showCount' => true,
            'showFilterChips' => true, 'showSortControls' => true,
            'showReviewerName' => true, 'showReviewDate' => true,
            'showVerifiedBadge' => true, 'showViewReply' => true,
            'showAvatar' => true, 'showTitle' => true, 'showContent' => true,
            'showVariation' => true, 'showFooter' => true, 'showMeta' => true,
            'showPhotos' => true,
            'photosFirst' => false, 'ratingFirst' => false, 'badgeLast' => false,
            'itemClass' => '', 'viewMode' => 'list', 'hasMedia' => false,
            'mediaVisible' => 0, 'mediaFullWidth' => false,
            'mediaFlush' => false, 'mediaBackdrop' => false,
            'gridColumns' => 2, 'perPage' => 0, 'paginationType' => 'numbers',
        ];

        return array_key_exists($option, $defaults) ? $defaults[$option] : '';
    }

    /**
     * The values of the layout control that draw nothing of their own, so the
     * section is entirely what the widget's controls say.
     *
     * Custom is the obvious one. The rest are the Pro layouts on a site
     * without Pro: exists() refuses them at render, so nothing of the layout
     * reaches the page. The panel says so where a merchant would otherwise be
     * looking at a layout they picked and cannot see.
     *
     * Derived from exists() rather than from a second reading of the Pro flag.
     * The panel and the renderer then cannot disagree about which layouts are
     * real: whatever exists() refuses is, by construction, one of these.
     *
     * @return array<int, string>
     */
    public static function inertPresets(): array
    {
        $inert = [''];

        foreach (array_keys(LayoutPresets::all()) as $id) {
            if (!static::exists((string) $id)) {
                $inert[] = (string) $id;
            }
        }

        return $inert;
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
