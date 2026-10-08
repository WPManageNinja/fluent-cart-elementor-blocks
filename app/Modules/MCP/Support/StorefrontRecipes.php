<?php

namespace FluentCartElementorBlocks\App\Modules\MCP\Support;

use FluentCart\Api\StoreSettings;

/**
 * The pages a working FluentCart store needs, and what goes on each.
 *
 * A storefront is not one page: cart, checkout and receipt only function
 * when FluentCart's own settings point at them, so building the layout is
 * only half the job. Each recipe therefore carries both the widget to place
 * and the `*_page_id` setting to assign, and the assignment is the part an
 * agent would otherwise miss.
 *
 * Setting keys verified against api/StoreSettings.php and the page-type map
 * in app/Services/TemplateService.php.
 */
class StorefrontRecipes
{
    /**
     * Each role names all three ways its content can already be on the page.
     * FluentCart's own installer uses blocks for some pages and shortcodes for
     * others, so a validator that only looked for the Elementor widget would
     * report a perfectly good store as broken.
     */
    public static function all()
    {
        return [
            'shop' => [
                'title'       => __('Shop', 'fluent-cart-elementor-blocks'),
                'widget'      => 'fluent_cart_shop_app',
                'block'       => 'fluent-cart/products',
                'shortcode'   => 'fluent_cart_products',
                'setting_key' => 'shop_page_id',
                'purpose'     => __('Product listing / catalogue.', 'fluent-cart-elementor-blocks'),
            ],
            'cart' => [
                'title'       => __('Cart', 'fluent-cart-elementor-blocks'),
                'widget'      => 'fluent_cart_cart',
                'block'       => 'fluent-cart/cart',
                'shortcode'   => 'fluent_cart_cart',
                'setting_key' => 'cart_page_id',
                'purpose'     => __('The shopping cart.', 'fluent-cart-elementor-blocks'),
            ],
            'checkout' => [
                'title'       => __('Checkout', 'fluent-cart-elementor-blocks'),
                'widget'      => 'fluent_cart_checkout',
                'block'       => 'fluent-cart/checkout',
                'shortcode'   => 'fluent_cart_checkout',
                'setting_key' => 'checkout_page_id',
                'purpose'     => __('Payment and order placement.', 'fluent-cart-elementor-blocks'),
            ],
            'receipt' => [
                'title'       => __('Receipt', 'fluent-cart-elementor-blocks'),
                'widget'      => 'fluent_cart_receipt',
                'block'       => null,
                'shortcode'   => 'fluent_cart_receipt',
                'setting_key' => 'receipt_page_id',
                'purpose'     => __('Order confirmation shown after payment.', 'fluent-cart-elementor-blocks'),
            ],
            'customer_dashboard' => [
                'title'       => __('Account', 'fluent-cart-elementor-blocks'),
                'widget'      => 'fluent_cart_customer_dashboard',
                'block'       => 'fluent-cart/customer-profile',
                'shortcode'   => 'fluent_cart_customer_profile',
                'setting_key' => 'customer_profile_page_id',
                'purpose'     => __('Customer account area — orders, subscriptions, downloads.', 'fluent-cart-elementor-blocks'),
            ],
        ];
    }

    /**
     * Is this page already serving its role, by any of the three routes?
     */
    public static function pageCarriesContent($pageId, array $recipe)
    {
        $tree  = ElementorDocument::read($pageId);
        $found = false;

        if (!is_wp_error($tree)) {
            ElementorDocument::walk($tree, function ($node) use (&$found, $recipe) {
                if (!empty($node['widgetType']) && $node['widgetType'] === $recipe['widget']) {
                    $found = true;
                }
            });
        }

        if ($found) {
            return true;
        }

        $content = (string) get_post_field('post_content', $pageId);

        if (!$content) {
            return false;
        }

        if (!empty($recipe['block']) && has_block($recipe['block'], $content)) {
            return true;
        }

        return !empty($recipe['shortcode']) && has_shortcode($content, $recipe['shortcode']);
    }

    public static function get($name)
    {
        $all = self::all();

        return isset($all[$name]) ? $all[$name] : null;
    }

    public static function names()
    {
        return array_keys(self::all());
    }

    /** Current page assignment for every recipe, with the page's status. */
    public static function assignments()
    {
        $settings = new StoreSettings();
        $rows     = [];

        foreach (self::all() as $name => $recipe) {
            $pageId = (int) $settings->get($recipe['setting_key']);
            $post   = $pageId ? get_post($pageId) : null;

            $rows[] = [
                'page'        => $name,
                'setting_key' => $recipe['setting_key'],
                'page_id'     => $pageId ?: null,
                'page_title'  => $post ? $post->post_title : null,
                'page_status' => $post ? $post->post_status : null,
                'assigned'    => (bool) $post,
                'url'         => $post ? get_permalink($post) : null,
            ];
        }

        return $rows;
    }

    public static function assign($settingKey, $pageId)
    {
        $settings = new StoreSettings();
        $settings->set($settingKey, (string) (int) $pageId);

        return true;
    }
}
