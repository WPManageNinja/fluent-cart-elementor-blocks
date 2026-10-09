<?php

namespace FluentCartElementorBlocks\App\Modules\MCP\Support;

/**
 * Finds custom CSS that silently overrides a FluentCart layout control.
 *
 * An agent can set card_elements to one order and then write kit CSS that
 * puts the children in another. Both calls report success, the page is wrong,
 * and nothing anywhere says the two disagree — which is exactly what happened
 * on the first site built this way: the cards rendered title and price above
 * the image for three rounds of misdiagnosis before the kit CSS was found.
 *
 * A layout control an agent can silently override is only half a control, so
 * the conflict is reported rather than left to be discovered by eye.
 */
class StyleConflicts
{
    /**
     * Properties that re-position an element independently of DOM order, and
     * therefore defeat a layout repeater. `flex-direction` earns its place:
     * `column-reverse` inverts a whole section list in one declaration.
     */
    const POSITIONING = ['order', 'grid-area', 'grid-row', 'grid-column', 'flex-direction'];

    /** Only FluentCart's own markup matters; a theme styling its own classes does not. */
    const TARGET_PREFIX = 'fct-';

    /**
     * Elementor's kit holds the site-wide custom CSS. Page-level CSS can
     * conflict too, but the kit is where an agent writes a design system and
     * where the damage is global.
     */
    public static function kitCss()
    {
        if (!class_exists('\Elementor\Plugin')) {
            return '';
        }

        $kitId = (int) get_option('elementor_active_kit');

        if (!$kitId) {
            return '';
        }

        $settings = get_post_meta($kitId, '_elementor_page_settings', true);

        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }

        return is_array($settings) && isset($settings['custom_css'])
            ? (string) $settings['custom_css']
            : '';
    }

    /**
     * Rules whose selector touches FluentCart markup and whose body sets a
     * positioning property.
     *
     * Deliberately a lightweight scan, not a CSS parser: a false positive
     * costs the agent one read, while a parser dependency costs the plugin
     * forever. Comments are stripped first so a commented-out rule is not
     * reported.
     */
    public static function find($css = null)
    {
        $css = $css === null ? self::kitCss() : (string) $css;

        if (!$css) {
            return [];
        }

        $css = preg_replace('#/\*.*?\*/#s', '', $css);

        if (!preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $found = [];

        foreach ($matches as $rule) {
            $selector = trim(preg_replace('/\s+/', ' ', $rule[1]));
            $body     = $rule[2];

            if (strpos($selector, self::TARGET_PREFIX) === false) {
                continue;
            }

            $properties = [];

            foreach (self::POSITIONING as $property) {
                // Word boundary on the left so `grid-row` does not match
                // `grid-row-gap`, and a colon on the right so a property name
                // inside a value is not counted.
                if (preg_match('/(^|[;{\s])' . preg_quote($property, '/') . '\s*:/i', $body)) {
                    $properties[] = $property;
                }
            }

            if (!$properties) {
                continue;
            }

            $found[] = [
                'selector'   => $selector,
                'properties' => $properties,
            ];
        }

        return $found;
    }

    /**
     * The conflicts grouped into one finding per property, so a kit that pins
     * four card children reads as one problem rather than four.
     */
    public static function summarise($css = null)
    {
        $conflicts = self::find($css);

        if (!$conflicts) {
            return [];
        }

        $byProperty = [];

        foreach ($conflicts as $conflict) {
            foreach ($conflict['properties'] as $property) {
                if (!isset($byProperty[$property])) {
                    $byProperty[$property] = [];
                }

                $byProperty[$property][] = $conflict['selector'];
            }
        }

        $findings = [];

        foreach ($byProperty as $property => $selectors) {
            $findings[] = [
                'property'  => $property,
                'selectors' => array_values(array_unique($selectors)),
                'count'     => count(array_unique($selectors)),
            ];
        }

        return $findings;
    }
}
