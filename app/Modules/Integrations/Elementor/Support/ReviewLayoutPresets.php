<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support;

use FluentCart\App\App;

/**
 * Starting arrangements for the review widgets.
 *
 * The block editor builds its layouts out of blocks: a preset there composes a
 * Review Item from the same field blocks a merchant could have assembled by
 * hand. This widget has no blocks — it maps panel controls onto
 * ProductReviewRenderer, whose row markup is fixed — so a preset here is a
 * bundle of control values instead.
 *
 * That difference is why this list is shorter than the block editor's. Four of
 * its layouts (Testimonials, Photo Strip, Photo Wall, Masonry) change the card
 * itself rather than the settings around it, and a fifth, Summary on Top,
 * cannot be told from Classic here: the renderer's showSummary is a boolean and
 * the summary's position is fixed by its markup. Offering those names against
 * arrangements the widget cannot produce would be worse than leaving them out.
 *
 * Applied in the panel, not at render. The preset writes the controls and then
 * has nothing more to do with it — every value stays editable afterwards, and
 * the panel never claims an arrangement the page will not draw. A preset is a
 * starting point, never a rule.
 */
class ReviewLayoutPresets
{
    /**
     * Every value a preset sets, so applying one leaves nothing behind from the
     * arrangement before it.
     *
     * Without this list a preset would be a patch rather than a layout: pick
     * Card Grid after Carousel and the slider's arrows would still be on,
     * because nothing turned them off. Each preset names the whole set.
     */
    public static function keys(): array
    {
        return [
            'view_mode',
            'grid_columns',
            'show_summary',
            'show_filter',
            'show_sorting',
            'show_review_date',
            'show_view_reply',
            'content_max_words',
            'pagination_type',
            'per_page',
            'slider_arrows',
            'slider_arrows_position',
            'slider_pagination',
            'slider_pagination_type',
            'slider_autoplay',
        ];
    }

    /**
     * The arrangements themselves.
     *
     * Grid and slider are Pro, so the two presets that use them are marked: the
     * panel offers them either way, because a layout that vanishes reads as one
     * the plugin does not have, and core refuses the view mode on render
     * regardless of what is saved here.
     */
    public static function all(): array
    {
        return [
            'classic' => [
                'label'    => esc_html__('Classic', 'fluent-cart-elementor-blocks'),
                'pro'      => false,
                'settings' => [
                    'view_mode'        => 'list',
                    'show_summary'     => 'yes',
                    'show_filter'      => 'yes',
                    'show_sorting'     => 'yes',
                    'show_review_date' => 'yes',
                    'show_view_reply'  => 'yes',
                    'pagination_type'  => 'numbers',
                    'per_page'         => 10,
                ],
            ],
            'minimal' => [
                'label'    => esc_html__('Minimal List', 'fluent-cart-elementor-blocks'),
                'pro'      => false,
                'settings' => [
                    'view_mode'        => 'list',
                    'show_summary'     => '',
                    'show_filter'      => '',
                    'show_sorting'     => '',
                    'show_review_date' => 'yes',
                    'show_view_reply'  => '',
                    'pagination_type'  => 'numbers',
                    'per_page'         => 10,
                ],
            ],
            'compact' => [
                'label'    => esc_html__('Compact', 'fluent-cart-elementor-blocks'),
                'pro'      => false,
                'settings' => [
                    'view_mode'         => 'list',
                    'show_summary'      => '',
                    'show_filter'       => '',
                    'show_sorting'      => '',
                    'show_review_date'  => '',
                    'show_view_reply'   => '',
                    // Enough to say what they thought, not enough to read as a
                    // paragraph — the whole point of a dense list.
                    'content_max_words' => 20,
                    'pagination_type'   => 'numbers',
                    'per_page'          => 10,
                ],
            ],
            'grid' => [
                'label'    => esc_html__('Card Grid', 'fluent-cart-elementor-blocks'),
                'pro'      => true,
                'settings' => [
                    'view_mode'        => 'grid',
                    'grid_columns'     => 2,
                    'show_summary'     => '',
                    'show_filter'      => 'yes',
                    'show_sorting'     => '',
                    'show_review_date' => 'yes',
                    'pagination_type'  => 'fraction',
                    // Whole rows: a page of five against two to a row leaves a
                    // row permanently one short.
                    'per_page'         => 6,
                ],
            ],
            'carousel' => [
                'label'    => esc_html__('Carousel', 'fluent-cart-elementor-blocks'),
                'pro'      => true,
                'settings' => [
                    'view_mode'              => 'slider',
                    'grid_columns'           => 2,
                    'show_summary'           => '',
                    'show_filter'            => '',
                    'show_sorting'           => '',
                    'show_review_date'       => 'yes',
                    'slider_arrows'          => 'yes',
                    'slider_arrows_position' => 'overlap',
                    'slider_pagination'      => 'yes',
                    'slider_pagination_type' => 'bullets',
                    'slider_autoplay'        => 'no',
                    // The slider is its own pager; a page length would fight it.
                    'per_page'               => 0,
                ],
            ],
        ];
    }

    /**
     * The panel's dropdown. 'custom' first and selected by default: a widget
     * that has never had a preset applied has not chosen one, and naming a
     * layout it did not build would be a claim about settings nobody set.
     */
    public static function options(): array
    {
        $pro = App::isProActive();
        $options = ['' => esc_html__('Custom', 'fluent-cart-elementor-blocks')];

        foreach (static::all() as $id => $preset) {
            $options[$id] = ($preset['pro'] && !$pro)
                /* translators: %s - layout name */
                ? sprintf(esc_html__('%s (Pro)', 'fluent-cart-elementor-blocks'), $preset['label'])
                : $preset['label'];
        }

        return $options;
    }

    /**
     * What the editor script needs: the settings each preset writes, and the
     * full key list so it can clear what the previous one set.
     */
    public static function forEditor(): array
    {
        $presets = [];

        foreach (static::all() as $id => $preset) {
            $presets[$id] = $preset['settings'];
        }

        return [
            'keys'    => static::keys(),
            'presets' => $presets,
        ];
    }
}
