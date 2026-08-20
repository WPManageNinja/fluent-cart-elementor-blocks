<?php

namespace FluentCartElementorBlocks\App\Services\Badges;

use FluentCart\App\Models\Product;
use FluentCart\App\Modules\Templating\AssetLoader;
use FluentCart\App\Vite as CoreVite;
use FluentCart\Framework\Support\Arr;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Turns badge state (BadgeState) into core's badge markup + stylesheet. One
 * code path for the "Show Sale Badge" / "Show Sold Out Badge" toggles embedded
 * in the image-bearing Elementor widgets (Product Card, Product Carousel,
 * Products, Related Products), so the span/CSS/enqueue logic is never
 * duplicated.
 *
 * Returns '' when the product isn't in the badge's state — the caller emits
 * nothing (no empty wrapper).
 */
class BadgeRenderer
{
    const STYLES = ['badge', 'ribbon', 'tag'];
    const POSITIONS = ['top-left', 'top-right', 'bottom-left', 'bottom-right'];

    /**
     * @param Product $product
     * @param array $opts badgeText, showPercentage(bool), percentageText,
     *                    priceSource, badgeStyle, badgePosition('' = inline)
     * @return string Badge HTML, or '' when not on sale.
     */
    public static function sale(Product $product, array $opts = [])
    {
        $priceSource = self::oneOf(Arr::get($opts, 'priceSource', 'default_variant'), ['default_variant', 'best_discount'], 'default_variant');
        $state = BadgeState::saleState($product, $priceSource);

        if (empty($state['is_on_sale'])) {
            return '';
        }

        $text = (string) Arr::get($opts, 'badgeText', __('Sale!', 'fluent-cart-elementor-blocks'));

        if (Arr::get($opts, 'showPercentage') && $state['discount_percent'] > 0) {
            $format = (string) Arr::get($opts, 'percentageText', '-{percent}%');
            $text = str_replace('{percent}', (string) $state['discount_percent'], $format);
        }

        return self::span('fct-sale-badge', $opts, $text);
    }

    /**
     * @param Product $product
     * @param array $opts badgeText, badgeStyle, badgePosition('' = inline)
     * @return string Badge HTML, or '' when in stock.
     */
    public static function soldOut(Product $product, array $opts = [])
    {
        if (!BadgeState::isOutOfStock($product)) {
            return '';
        }

        $text = (string) Arr::get($opts, 'badgeText', __('Sold Out', 'fluent-cart-elementor-blocks'));

        return self::span('fct-sold-out-badge', $opts, $text);
    }

    /**
     * Core badge markup + core badge stylesheet, so the Elementor badges are
     * pixel-identical to the Gutenberg blocks and core styling fixes flow
     * through. Same style handle as core — never double-enqueued.
     *
     * @param string $baseClass fct-sale-badge | fct-sold-out-badge
     * @param array $opts
     * @param string $text
     * @return string
     */
    private static function span($baseClass, array $opts, $text)
    {
        AssetLoader::markFrontendAssetsRequired();

        if ($baseClass === 'fct-sold-out-badge') {
            CoreVite::enqueueStyle(
                'fluent-cart-sold-out-badge',
                'admin/BlockEditor/SoldOutBadge/style/sold-out-badge-block-editor.scss'
            );
        } else {
            CoreVite::enqueueStyle(
                'fluent-cart-sale-badge',
                'admin/BlockEditor/SaleBadge/style/sale-badge-block-editor.scss'
            );
        }

        $style = self::oneOf(Arr::get($opts, 'badgeStyle', 'badge'), self::STYLES, 'badge');
        $position = self::oneOf(Arr::get($opts, 'badgePosition', ''), self::POSITIONS, '');

        $classes = [$baseClass, $baseClass . '--' . $style];
        $inline = '';
        if ($position) {
            $classes[] = $baseClass . '--' . $position;
            // Emit the corner offsets INLINE. Core's position classes resolve
            // their offsets through CSS anchor positioning (anchor()), which
            // can collapse to invalid in some contexts (e.g. the Elementor
            // editor preview iframe) — unsetting top/left and stacking every
            // position in one spot. Inline styles win over the class rule
            // everywhere, front end and canvas, with no anchor dependency. On
            // grid/card widgets the positioned ancestor is the
            // .fct-elementor-badge-imagewrap that cardBadgeClosures wraps around
            // the image, so the offsets overlay the IMAGE, not the whole card.
            $inline = ' style="' . esc_attr(self::positionCss($position)) . '"';
        }

        return sprintf(
            '<span class="%s"%s>%s</span>',
            esc_attr(implode(' ', $classes)),
            $inline,
            esc_html(sanitize_text_field($text))
        );
    }

    /**
     * Absolute corner offsets for a position, as an inline style string.
     *
     * @param string $position top-left|top-right|bottom-left|bottom-right
     * @return string
     */
    private static function positionCss($position)
    {
        $vertical = strpos($position, 'top-') === 0 ? 'top:8px;bottom:auto;' : 'bottom:8px;top:auto;';
        $horizontal = substr($position, -5) === '-left' ? 'left:8px;right:auto;' : 'right:8px;left:auto;';

        return 'position:absolute;z-index:2;' . $vertical . $horizontal;
    }

    /**
     * Overlay the Sale / Sold Out badges on the card IMAGE (not the whole
     * card) for the widgets whose card image is rendered by core
     * (ProductCardRender / ProductListRenderer). No core change: core fires
     * `before_image_block` and `after_image_block` around each card's image, so
     * we buffer the image between them and re-emit it wrapped in a
     * position:relative box with the badges inside — the badge's absolute
     * corner offsets then pin to the image bounds, not the card. Returns the
     * ['before' => closure, 'after' => closure] pair the caller registers on
     * core's before/after_image_block actions around its render (and removes
     * after), or null when both badges are off.
     *
     * The ONE Elementor adaptation vs the Divi port: this reads the widget's
     * Elementor control values straight off $settings
     * ($widget->get_settings_for_display()) instead of a Divi module's nested
     * attribute tree. The buffering/re-wrap logic and BadgeState detection are
     * unchanged.
     *
     * @param array $settings     Elementor get_settings_for_display() array.
     * @param bool $includeSoldOut Whether the widget offers a Sold Out badge
     *                             (only Products + Carousel do).
     * @return array|null ['before' => callable, 'after' => callable] or null.
     */
    public static function cardBadgeClosures(array $settings, $includeSoldOut = true)
    {
        $get = function ($name, $default = '') use ($settings) {
            return Arr::get($settings, $name, $default);
        };

        $showSale = $get('show_sale_badge', '') === 'yes';
        $showSoldOut = $includeSoldOut && $get('show_sold_out_badge', '') === 'yes';

        if (!$showSale && !$showSoldOut) {
            return null;
        }

        // Each badge has its own corner; Sold Out falls back to the Sale
        // position when its own is unset.
        $salePosition = $get('sale_badge_position', 'top-right');
        $soldOutPosition = $get('sold_out_badge_position', $salePosition);

        $saleOpts = $showSale ? [
            'badgeText'      => $get('sale_badge_text', __('Sale!', 'fluent-cart-elementor-blocks')),
            'showPercentage' => $get('show_percentage', '') === 'yes',
            'percentageText' => $get('sale_percentage_text', '-{percent}%'),
            'priceSource'    => $get('sale_price_source', 'default_variant'),
            'badgeStyle'     => $get('sale_badge_style', 'badge'),
            'badgePosition'  => $salePosition,
        ] : null;

        $soldOutOpts = $showSoldOut ? [
            'badgeText'     => $get('sold_out_badge_text', __('Sold Out', 'fluent-cart-elementor-blocks')),
            'badgeStyle'    => $get('sold_out_badge_style', 'badge'),
            'badgePosition' => $soldOutPosition,
        ] : null;

        // Shared flag keeps before/after symmetric: only buffer + wrap for the
        // catalog card image scope, so a mismatched after() never cleans a
        // buffer before() didn't open.
        $buffering = false;

        $before = function ($payload) use (&$buffering) {
            if (is_array($payload) && Arr::get($payload, 'scope') === 'product_card') {
                $buffering = true;
                ob_start();
            }
        };

        $after = function ($payload) use (&$buffering, $saleOpts, $soldOutOpts) {
            if (!$buffering) {
                return;
            }
            $buffering = false;

            $image = ob_get_clean();
            $product = is_array($payload) ? Arr::get($payload, 'product') : null;

            if (!$product instanceof \FluentCart\App\Models\Product) {
                echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup
                return;
            }

            $badges = '';
            if ($saleOpts) {
                $badges .= self::sale($product, $saleOpts);
            }
            if ($soldOutOpts) {
                $badges .= self::soldOut($product, $soldOutOpts);
            }

            if ($badges === '') {
                echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup
                return;
            }

            // Wrap ONLY the image (not the whole card) as the badge's
            // positioned ancestor, so the corner offsets overlay the image.
            // fct-product-image-wrap is core's own badge-wrapper class — the
            // same one core's Gutenberg badge markup uses. The shop app's
            // list view assigns grid placement to the card's direct children
            // by class (shop-app.scss mode-list rules, already loaded on
            // these pages); without it this wrapper is an unplaced grid
            // child and the list card layout collapses below the image.
            printf(
                '<div class="fct-elementor-badge-imagewrap fct-product-image-wrap" style="position:relative;display:block">%s%s</div>',
                $image, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup
                $badges // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in span()
            );
        };

        return ['before' => $before, 'after' => $after];
    }

    /**
     * Build the closures and register them on core's before/after image-block
     * actions in one call, returning the handle for removeCardBadgeHooks().
     *
     * @param array $settings      Elementor get_settings_for_display() array.
     * @param bool $includeSoldOut
     * @return array|null Handle for removeCardBadgeHooks(), or null.
     */
    public static function addCardBadgeHooks(array $settings, $includeSoldOut = true)
    {
        $closures = self::cardBadgeClosures($settings, $includeSoldOut);
        if ($closures) {
            add_action('fluent_cart/product/group/before_image_block', $closures['before'], 10, 1);
            add_action('fluent_cart/product/group/after_image_block', $closures['after'], 10, 1);
        }

        return $closures;
    }

    /**
     * Remove the before/after image-block hooks a caller registered from the
     * closures returned by cardBadgeClosures()/addCardBadgeHooks().
     *
     * @param array|null $handle
     */
    public static function removeCardBadgeHooks($handle)
    {
        if (!$handle) {
            return;
        }
        remove_action('fluent_cart/product/group/before_image_block', $handle['before'], 10);
        remove_action('fluent_cart/product/group/after_image_block', $handle['after'], 10);
    }

    /**
     * @param mixed $value
     * @param array $allowed
     * @param string $fallback
     * @return string
     */
    private static function oneOf($value, array $allowed, $fallback)
    {
        $value = is_string($value) ? $value : '';

        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
