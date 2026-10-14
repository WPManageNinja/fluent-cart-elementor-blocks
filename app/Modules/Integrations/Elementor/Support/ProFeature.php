<?php

namespace FluentCartElementorBlocks\App\Modules\Integrations\Elementor\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * How a widget marks a FluentCart Pro feature while Pro is inactive, in the look of the
 * admin's Pro screens: an amber crown on its panel tile, and a canvas notice with the
 * crown, a note and a dark Upgrade to Pro button (styles in css/elementor.css).
 */
class ProFeature
{
    const UPGRADE_URL = 'https://fluentcart.com/pricing/';

    /** The admin's Pro crown (core's Bits/Components/Icons/Crown.vue), outlined. */
    const CROWN_SVG = '<svg viewBox="0 0 14 16" fill="none" aria-hidden="true" focusable="false"><path d="M1.35 6.87C1.09 6.15.96 5.8 1.01 5.57c.06-.25.24-.45.47-.51.21-.06.53.08 1.18.37.57.26.86.38 1.13.38.3-.01.58-.13.81-.34.21-.19.34-.5.62-1.11l.61-1.34C6.34 1.89 6.6 1.33 7 1.33s.66.56 1.17 1.68l.61 1.34c.28.61.41.92.62 1.11.23.21.51.33.81.34.27 0 .56-.12 1.13-.38.65-.29.97-.43 1.18-.37.23.06.41.26.47.51.05.23-.08.58-.34 1.3l-1.11 3.08c-.48 1.31-.71 1.97-1.21 2.35-.5.37-1.14.37-2.43.37H6.1c-1.29 0-1.93 0-2.43-.37-.5-.38-.73-1.04-1.21-2.35z" stroke="currentColor" stroke-width="1.5"/><path d="M7 9.33h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M3.67 14.67h6.66" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';

    /**
     * The canvas notice shown in place of a Pro widget's output.
     *
     * @param string $note Plain text saying what the feature needs.
     */
    public static function notice(string $note): string
    {
        $button = sprintf(
            '<a class="fct-elementor-pro-button" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s%3$s</a>',
            esc_url(self::UPGRADE_URL),
            self::CROWN_SVG,
            esc_html__('Upgrade to Pro', 'fluent-cart-elementor-blocks')
        );

        return sprintf(
            '<div class="fct-elementor-pro-notice"><span class="fct-elementor-pro-icon">%1$s</span><p class="fct-elementor-pro-text">%2$s</p>%3$s</div>',
            self::CROWN_SVG,
            esc_html($note),
            $button
        );
    }

    /**
     * Panel CSS putting the amber crown top-left on these widgets' tiles (the FluentCart
     * badge has top-right). "Pro" is its alt text.
     *
     * @param string[] $widgetNames Elementor widget names (get_name()).
     */
    public static function tileCrownCss(array $widgetNames): string
    {
        if (!$widgetNames) {
            return '';
        }

        $selectors = array_map(function ($widgetName) {
            return '.elementor-element[data-library-element-type="' . esc_attr($widgetName) . '"]::after';
        }, $widgetNames);

        // An SVG used as an image needs its namespace
        $crownMask = 'url("data:image/svg+xml;base64,' . base64_encode(str_replace(['currentColor', '<svg '], ['black', '<svg xmlns="http://www.w3.org/2000/svg" '], self::CROWN_SVG)) . '") center / contain no-repeat';

        return implode(',', $selectors) . ' {
            content: "" / "' . esc_js(__('Pro', 'fluent-cart-elementor-blocks')) . '";
            position: absolute;
            top: 4px;
            left: 4px;
            width: 14px;
            height: 14px;
            background-color: #F6B51E;
            mask: ' . $crownMask . ';
            -webkit-mask: ' . $crownMask . ';
        }';
    }
}
