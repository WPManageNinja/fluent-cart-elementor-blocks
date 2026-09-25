<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support;

use FluentCart\App\App;
use FluentCart\App\Services\Reviews\LayoutPresets;

/**
 * The review layouts, as this panel needs to describe them.
 *
 * The layouts themselves are core's, at FluentCart\App\Services\Reviews\
 * LayoutPresets — the same eleven the block editor builds, declared once. This
 * class only answers what the Elementor panel asks: what to call them, and
 * which are Pro.
 *
 * Nothing here describes a layout. An earlier attempt had this file carry its
 * own five, as bundles of control values, because the widget could not build
 * the other six: they change the review card itself, and a card is made of
 * blocks. Rendering the preset's blocks removed that limit and the copy with
 * it — a second description of a layout is the thing these presets exist to
 * avoid.
 */
class ReviewLayoutPresets
{
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
            $options[$id] = ($preset['pro'] && !$pro)
                /* translators: %s - layout name */
                ? sprintf(esc_html__('%s (Pro)', 'fluent-cart-elementor-blocks'), $preset['label'])
                : $preset['label'];
        }

        return $options;
    }

    /**
     * What the picker says about the layout in hand, for the panel's hint.
     */
    public static function help(string $preset): string
    {
        $all = LayoutPresets::all();

        return isset($all[$preset]) ? (string) $all[$preset]['help'] : '';
    }

    /**
     * Whether a preset by that name exists. A widget saved against a layout
     * that has since been renamed or removed falls back to its own controls
     * rather than rendering nothing.
     */
    public static function exists(string $preset): bool
    {
        return $preset !== '' && isset(LayoutPresets::all()[$preset]);
    }
}
