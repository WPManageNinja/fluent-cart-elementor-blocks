<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The drawing on a layout's card in the Elementor picker.
 *
 * A port of the block editor's PresetThumb.jsx, line for line and number for
 * number, so a layout looks the same whichever builder a merchant is in. The
 * original's reasoning holds here too: one drawing per layout rather than one
 * drawing generated from a layout, because the generated version read the
 * template and drew rails and bars from what it found - honest, and illegible,
 * with twelve layouts arriving as the same stack of lines.
 *
 * This is a second copy of those drawings and that is a real cost, chosen
 * deliberately: the alternative was moving them into FluentCart core, which
 * both builders would then read, and that is a change to another plugin. If
 * these two ever disagree, core's is the one that is right - it is the one a
 * merchant sees first, in the editor that ships with WordPress.
 *
 * A layout with no drawing here falls back to plain lines rather than to
 * someone else's picture.
 */
class ReviewLayoutThumbnails
{
    /**
     * Flat, low-contrast greys, so a drawing reads as a wireframe and never
     * competes with the name under it. Stars are the one warm accent and
     * photographs the one cool one - the two things worth spotting at a
     * glance.
     */
    const PANEL = '#eef1f7';
    const CARD = '#ffffff';
    const BAR = '#ccd1db';
    const SOFT = '#dfe3ea';
    const RULE = '#e6e9ef';
    const STAR = '#f2b134';
    const PHOTO = '#becffa';
    const ACCENT = '#3b5bdb';

    const VIEW_W = 120;
    const VIEW_H = 66;

    /**
     * The whole drawing for one layout, ready to print.
     *
     * @param string $preset
     * @return string
     */
    public static function svg(string $preset): string
    {
        $drawings = static::drawings();
        $drawing = isset($drawings[$preset]) ? $drawings[$preset] : static::fallback();

        return '<svg class="fct-review-preset-icon" viewBox="0 0 ' . static::VIEW_W . ' ' . static::VIEW_H . '"'
            . ' preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false">'
            . static::rect(0, 0, static::VIEW_W, static::VIEW_H, 4, static::PANEL)
            . $drawing
            . '</svg>';
    }

    /**
     * A number as SVG wants it: no trailing zeros, no locale decimal comma.
     *
     * @param float|int $value
     * @return string
     */
    protected static function n($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    protected static function rect($x, $y, $w, $h, $rx, string $fill): string
    {
        return '<rect x="' . static::n($x) . '" y="' . static::n($y) . '"'
            . ' width="' . static::n($w) . '" height="' . static::n($h) . '"'
            . ' rx="' . static::n($rx) . '" fill="' . $fill . '"/>';
    }

    /** A line of words. */
    protected static function bar($x, $y, $w, $h = 3, string $fill = self::SOFT): string
    {
        return static::rect($x, $y, $w, $h, $h / 2, $fill);
    }

    /** A face. */
    protected static function dot($cx, $cy, $r = 4, string $fill = self::BAR): string
    {
        return '<circle cx="' . static::n($cx) . '" cy="' . static::n($cy) . '"'
            . ' r="' . static::n($r) . '" fill="' . $fill . '"/>';
    }

    protected static function card($x, $y, $w, $h): string
    {
        return static::rect($x, $y, $w, $h, 3, static::CARD);
    }

    protected static function photo($x, $y, $w, $h, $rx = 2): string
    {
        return static::rect($x, $y, $w, $h, $rx, static::PHOTO);
    }

    /** A review row: face, name, stars, then what they wrote. */
    protected static function row($y, $width = 84): string
    {
        return static::dot(14, $y + 4, 5)
            . static::bar(23, $y + 2, 22, 4, static::BAR)
            . static::bar(49, $y + 2, 18, 4, static::STAR)
            . static::bar(71, $y + 2.5, 36)
            . static::bar(23, $y + 11, $width);
    }

    /** A card in a grid or a slider: stars, then a couple of lines of words. */
    protected static function wordCard($x, $y, $w, $h): string
    {
        $out = static::card($x, $y, $w, $h)
            . static::bar($x + 4, $y + 5, 16, 4, static::STAR)
            . static::bar($x + 4, $y + 13, $w - 8);

        if ($h > 20) {
            $out .= static::bar($x + 4, $y + 19, $w - 14);
        }

        return $out;
    }

    /**
     * A layout with no drawing of its own. Plain lines say "a layout" without
     * claiming which.
     */
    protected static function fallback(): string
    {
        return static::bar(9, 16, 102)
            . static::bar(9, 31, 84)
            . static::bar(9, 46, 62);
    }

    /**
     * @return array<string, string>
     */
    protected static function drawings(): array
    {
        $drawings = [];

        // Two full reviews, each with a face, a name, stars and a sentence.
        $drawings['classic'] = static::row(10) . static::row(38, 62);

        // No faces and no cards: names and sentences, ruled off from each other.
        $drawings['minimal'] = static::bar(9, 10, 38, 4, static::BAR)
            . static::bar(9, 18, 102)
            . static::rect(9, 24, 102, 1, 0, static::RULE)
            . static::bar(9, 29, 30, 4, static::BAR)
            . static::bar(9, 37, 92)
            . static::rect(9, 43, 102, 1, 0, static::RULE)
            . static::bar(9, 48, 34, 4, static::BAR)
            . static::bar(9, 56, 102);

        // Four reviews to the space Classic gives two, each on a single line.
        $compact = '';
        foreach ([13, 26, 39, 52] as $y) {
            $compact .= static::dot(13, $y, 3.5)
                . static::bar(21, $y - 2, 22, 4, static::BAR)
                . static::bar(48, $y - 1.5, 59);
        }
        $drawings['compact'] = $compact;

        // The average and the star breakdown in a card across the top.
        $drawings['summary-top'] = static::card(8, 6, 104, 30)
            . static::rect(13, 11, 14, 10, 2, static::BAR)
            . static::bar(32, 11, 67, 5, static::STAR)
            . static::bar(13, 25, 14, 4, static::STAR)
            . static::bar(32, 25, 30, 4)
            . static::bar(8, 42, 44, 4, static::BAR)
            . static::bar(8, 51, 104)
            . static::bar(8, 58, 78);

        // Three to a row, two rows, every card the same height.
        $grid = '';
        foreach ([[8, 8], [44, 8], [80, 8], [8, 36], [44, 36], [80, 36]] as $at) {
            $grid .= static::wordCard($at[0], $at[1], 32, 24);
        }
        $drawings['grid'] = $grid;

        // The same cards, but nothing lines up - that is the whole point of it.
        $drawings['masonry'] = static::wordCard(8, 8, 32, 26)
            . static::wordCard(8, 38, 32, 19)
            . static::wordCard(44, 8, 32, 19)
            . static::wordCard(44, 31, 32, 26)
            . static::wordCard(80, 8, 32, 30)
            . static::wordCard(80, 42, 32, 15);

        // Two at a time, with dots underneath saying there are more.
        $carousel = '';
        foreach ([8, 62] as $x) {
            $carousel .= static::card($x, 8, 50, 34)
                . static::dot($x + 10, 18, 4)
                . static::bar($x + 18, 15, 22, 5, static::STAR)
                . static::bar($x + 6, 27, 38)
                . static::bar($x + 6, 33, 28);
        }
        $carousel .= static::bar(48, 50, 9, 3.5, static::BAR)
            . static::dot(63, 51.5, 1.8, static::SOFT)
            . static::dot(70, 51.5, 1.8, static::SOFT);
        $drawings['carousel'] = $carousel;

        // The quotation mark, the headline, the quote, then who said it -
        // centred, which is the arrangement the layout actually builds.
        $drawings['testimonials'] = '<text x="60" y="26" text-anchor="middle" font-size="26"'
            . ' font-family="Georgia, serif" fill="' . static::ACCENT . '" opacity="0.3">&#8220;</text>'
            . static::bar(38, 24, 44, 4, static::BAR)
            . static::bar(22, 33, 76)
            . static::bar(32, 40, 56)
            . static::dot(46, 53, 4)
            . static::bar(54, 51, 20, 4, static::STAR);

        // Three to a row, the photograph edge to edge across the top of the
        // card. Drawn as the card's own top corners rather than as a picture
        // set inside it, because that is the whole difference flush makes.
        $photoGrid = '';
        foreach ([8, 44, 80] as $x) {
            $photoGrid .= static::card($x, 6, 32, 54)
                . '<path d="M' . static::n($x) . ' 9a3 3 0 0 1 3-3h26a3 3 0 0 1 3 3v22H' . static::n($x) . 'z"'
                . ' fill="' . static::PHOTO . '"/>'
                . static::bar($x + 4, 37, 16, 4, static::STAR)
                . static::bar($x + 4, 45, 24)
                . static::bar($x + 4, 52, 18);
        }
        $drawings['photo-grid'] = $photoGrid;

        // The photograph is the card; the stars and the name sit over its foot.
        $photoWall = '';
        foreach ([8, 44, 80] as $x) {
            $photoWall .= static::photo($x, 9, 32, 48, 3)
                . static::bar($x + 5, 38, 14, 4, static::STAR)
                . static::bar($x + 5, 46, 22, 3.5, static::CARD);
        }
        $drawings['photo-wall'] = $photoWall;

        // A band of customer photographs that slides, above a list rather than
        // instead of one.
        $photoStrip = '';
        foreach ([9.5, 30.5, 51.5, 72.5, 93.5] as $x) {
            $photoStrip .= static::photo($x, 10, 17, 46, 3);
        }
        $drawings['photo-strip'] = $photoStrip;

        return $drawings;
    }
}
