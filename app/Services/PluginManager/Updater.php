<?php

namespace FluentCartElementorBlocks\App\Services\PluginManager;

if (!defined('ABSPATH')) {
    exit;
}

class Updater
{
    private $store_url = '';
    private $name = '';
    private $slug = '';
    private $version = '';
    private $addon_slug = '';
    private $parent_product_id = '';
    private $license_key = '';
    private $activation_hash = '';
    private $plugin_title = '';
    private $is_free = false;

    private $response_transient_key;
    private $license_notice_transient_key;

    /**
     * @param string $_store_url   The FluentCart store URL.
     * @param string $_plugin_file Path to the plugin file.
     * @param array  $_config      Configuration:
     *   - version           (string) Current addon version
     *   - addon_slug        (string) Must match the slug in Addon Assets settings on the store
     *   - parent_product_id (int)    FluentCart product ID on the store
     *   - license_key       (string) Optional — auto-detected from FluentCart Pro if empty
     *   - activation_hash   (string) Optional — auto-detected from FluentCart Pro if empty
     *   - plugin_title      (string) Display name for update notices
     *   - is_free           (bool)   When true, the updater never reads or sends a
     *                                FluentCart Pro license and never shows a license
     *                                activation notice. Updates are served by the store's
     *                                free (verify_license = false) addon asset flow.
     */
    function __construct($_store_url, $_plugin_file, $_config = [])
    {
        $this->store_url = rtrim($_store_url, '/');
        $this->name = plugin_basename($_plugin_file);
        $this->slug = basename($_plugin_file, '.php');

        $this->response_transient_key = md5(sanitize_key($this->name) . 'response_transient');
        $this->license_notice_transient_key = md5(sanitize_key($this->name) . 'license_notice_transient');

        $this->version = $_config['version'] ?? '1.0.0';
        $this->addon_slug = $_config['addon_slug'] ?? '';
        $this->parent_product_id = $_config['parent_product_id'] ?? '';
        $this->license_key = $_config['license_key'] ?? '';
        $this->activation_hash = $_config['activation_hash'] ?? '';
        $this->plugin_title = $_config['plugin_title'] ?? '';
        $this->is_free = !empty($_config['is_free']);

        $this->init();
    }

    public function init()
    {
        $this->maybe_delete_transients();

        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'), 51);
        add_action('delete_site_transient_update_plugins', [$this, 'delete_transients']);
        add_filter('plugins_api', array($this, 'plugins_api_filter'), 10, 3);

        remove_action('after_plugin_row_' . $this->name, 'wp_plugin_update_row');
        add_action('after_plugin_row_' . $this->name, [$this, 'show_update_notification'], 10, 2);

        // Free addons update without a license, so the license activation notice
        // is never registered.
        if (!$this->is_free) {
            add_action('admin_notices', [$this, 'show_license_activation_notice']);
        }
    }

    function check_update($_transient_data)
    {
        global $pagenow;

        if (!is_object($_transient_data)) {
            $_transient_data = new \stdClass();
        }

        if ('plugins.php' === $pagenow && is_multisite()) {
            return $_transient_data;
        }

        return $this->check_transient_data($_transient_data);
    }

    private function check_transient_data($_transient_data)
    {
        if (!is_object($_transient_data)) {
            $_transient_data = new \stdClass();
        }

        if (empty($_transient_data->checked)) {
            return $_transient_data;
        }

        $version_info = $this->get_transient($this->response_transient_key);

        if (false === $version_info) {
            $version_info = $this->api_request();
            if (is_wp_error($version_info)) {
                $version_info = new \stdClass();
                $version_info->error = true;
            }
            $this->set_transient($this->response_transient_key, $version_info);
        }

        if (!empty($version_info->error) || !$version_info) {
            // Ensure stale update payload is removed when API response is invalid.
            unset($_transient_data->response[$this->name]);
            unset($_transient_data->no_update[$this->name]);
            return $_transient_data;
        }

        if (is_object($version_info) && isset($version_info->new_version)) {
            $hasValidPackage = !empty($version_info->package) && wp_http_validate_url($version_info->package);

            if (version_compare($this->version, $version_info->new_version, '<') && $hasValidPackage) {
                $_transient_data->response[$this->name] = $version_info;
            } else {
                unset($_transient_data->response[$this->name]);
            }
            $_transient_data->last_checked = time();
            $_transient_data->checked[$this->name] = $this->version;
        }

        return $_transient_data;
    }

    public function show_update_notification($file, $plugin)
    {
        if (is_network_admin() || !current_user_can('update_plugins')) {
            return;
        }

        if ($this->name !== $file) {
            return;
        }

        remove_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);

        $update_cache = get_site_transient('update_plugins');
        $update_cache = $this->check_transient_data($update_cache);
        set_site_transient('update_plugins', $update_cache);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
    }

    function plugins_api_filter($_data, $_action = '', $_args = null)
    {
        if ('plugin_information' !== $_action) {
            return $_data;
        }

        if (!isset($_args->slug) || $_args->slug !== $this->slug) {
            return $_data;
        }

        $cache_key = $this->slug . '_api_request_' . substr(md5(serialize($this->slug)), 0, 15);

        global $pagenow;
        $api_request_transient = ('plugin-install.php' === $pagenow) ? false : get_site_transient($cache_key);

        if (empty($api_request_transient)) {
            $api_request_transient = $this->api_request();

            if ($api_request_transient && !is_wp_error($api_request_transient)) {
                set_site_transient($cache_key, $api_request_transient, DAY_IN_SECONDS * 2);
            }
        }

        if ($api_request_transient && !is_wp_error($api_request_transient)) {
            $_data = $api_request_transient;
        } else {
            $_data = $this->get_fallback_plugin_info();
        }

        return $_data;
    }

    private function get_fallback_plugin_info()
    {
        $plugin_page_url = $this->store_url ?: 'https://fluentcart.com';
        $plugin_name     = $this->plugin_title ?: $this->slug;

        $info           = new \stdClass();
        $info->name     = $plugin_name;
        $info->slug     = $this->slug;
        $info->version  = $this->version;
        $info->homepage = $plugin_page_url;
        $info->author   = '<a href="' . esc_url($plugin_page_url) . '">' . esc_html($plugin_name) . '</a>';
        $info->sections = [
            'description' => sprintf(
                '<p>%1$s</p><p><a href="%2$s" target="_blank" rel="noopener noreferrer" class="button button-primary">%3$s</a></p>',
                esc_html__('Full version details are available on the plugin page.', 'fluent-cart-elementor-blocks'),
                esc_url($plugin_page_url),
                esc_html__('View Plugin Page &rarr;', 'fluent-cart-elementor-blocks')
            ),
        ];

        return $info;
    }

    private function api_request()
    {
        if ($this->store_url === home_url()) {
            return false;
        }

        $siteUrl = is_multisite() ? network_site_url() : home_url();

        $licenseKey = '';
        $activationHash = '';

        // Free addons never depend on a FluentCart Pro license. Only resolve and
        // send license data for paid addons.
        if (!$this->is_free) {
            $licenseKey = $this->license_key;
            $activationHash = $this->activation_hash;

            if (!$licenseKey && !$activationHash) {
                $stored = $this->getParentLicenseInfo();
                $licenseKey = $stored['license_key'];
                $activationHash = $stored['activation_hash'];
            }
        }

        $url = add_query_arg(['fluent-cart' => 'get_license_version'], $this->store_url);

        $body = [
            'item_id'         => $this->parent_product_id,
            'addon_slug'      => $this->addon_slug,
            'license_key'     => $licenseKey,
            'activation_hash' => $activationHash,
            'site_url'        => $siteUrl,
            'current_version' => $this->version,
        ];

        $request = wp_remote_post($url, [
            'timeout'   => 15,
            'sslverify' => true,
            'body'      => $body,
        ]);

        if (is_wp_error($request)) {
            return $request;
        }

        $request = json_decode(wp_remote_retrieve_body($request));

        // A free addon never surfaces a license activation notice.
        if (!$this->is_free) {
            if ($request && isset($request->license_status) && $request->license_status !== 'valid') {
                $this->set_transient($this->license_notice_transient_key, [
                    'status'  => sanitize_text_field($request->license_status),
                    'message' => sanitize_text_field($request->license_message ?? ''),
                ]);
            } else {
                $this->delete_transient($this->license_notice_transient_key);
            }
        }

        if ($request && isset($request->sections)) {
            // Verify the response is for this addon, not the parent product.
            // If the store doesn't have the addon-assets plugin, it returns
            if (isset($request->slug) && $request->slug !== $this->addon_slug) {
                return false;
            }

            $sections = maybe_unserialize($request->sections);

            if (is_object($sections)) {
                $sections = (array) $sections;
            }

            if (!is_array($sections)) {
                $sections = [];
            }

            if (empty($sections['description'])) {
                $sections['description'] = sprintf(
                    '<p>%1$s</p>',
                    esc_html__('Full version details are available on the plugin page.', 'fluent-cart-elementor-blocks')
                );
            }

            if (empty($sections['changelog'])) {
                $sections['changelog'] = $sections['description'];
            }

            $request->sections = $sections;
            $request->slug = $this->slug;
            $request->plugin = $this->name;
        } else {
            $request = false;
        }

        return $request;
    }

    public function show_license_activation_notice()
    {
        // Free addons do not require a license, so no activation notice is shown.
        if ($this->is_free) {
            return;
        }

        global $pagenow;

        if (!in_array($pagenow, ['plugins.php', 'update-core.php'], true)) {
            return;
        }

        if (!current_user_can('update_plugins')) {
            return;
        }

        $notice = $this->get_transient($this->license_notice_transient_key);
        if (!$notice || (($notice['status'] ?? '') === 'valid')) {
            return;
        }

        $activateUrl = admin_url('admin.php?page=fluent-cart#/settings/licensing');
        $pluginTitle = $this->plugin_title ?: $this->slug;

        /* translators: %1$s: plugin title */
        $message = sprintf(
            __('%1$s updates require an active FluentCart Pro license. Please activate your FluentCart Pro license to receive updates.', 'fluent-cart-elementor-blocks'),
            esc_html($pluginTitle)
        );

        printf(
            '<div class="notice notice-warning"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
            wp_kses_post($message),
            esc_url($activateUrl),
            esc_html__('Activate License', 'fluent-cart-elementor-blocks')
        );
    }

    private function getParentLicenseInfo()
    {
        $settingsKey = '__fluent-cart-pro_sl_info';
        $licenseInfo = get_option($settingsKey, []);

        if (!empty($licenseInfo['license_key'])) {
            return [
                'license_key'     => $licenseInfo['license_key'] ?? '',
                'activation_hash' => $licenseInfo['activation_hash'] ?? '',
            ];
        }

        return ['license_key' => '', 'activation_hash' => ''];
    }

    private function maybe_delete_transients()
    {
        global $pagenow;

        if ('update-core.php' === $pagenow && isset($_GET['force-check'])) {
            $this->delete_transients();
        }

        if (isset($_GET['fluent-cart-elementor-blocks-check-update'])) {
            if (current_user_can('update_plugins')) {
                // A GET that performs an action: without this, an admin's
                // browser can be made to trigger the check from anywhere.
                // The capability check above is not enough on its own — it
                // says who may act, not that they meant to. The matching
                // wp_nonce_url() is on the Check Update link in boot/app.php;
                // the two have to move together or the link stops working.
                check_admin_referer('fluent-cart-elementor-blocks-check-update');

                $this->delete_transients();

                remove_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);

                $update_cache = get_site_transient('update_plugins');
                if ($update_cache && is_object($update_cache)) {
                    if (!empty($update_cache->response[$this->name])) {
                        unset($update_cache->response[$this->name]);
                    }
                    if (!empty($update_cache->no_update[$this->name])) {
                        unset($update_cache->no_update[$this->name]);
                    }
                }

                $update_cache = $this->check_transient_data($update_cache);
                set_site_transient('update_plugins', $update_cache);

                add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);

                wp_redirect(admin_url('plugins.php?s=fluent-cart-elementor-blocks&plugin_status=all'));
                exit();
            }
        }
    }

    public function delete_transients()
    {
        $this->delete_transient($this->response_transient_key);
        $this->delete_transient($this->license_notice_transient_key);
    }

    protected function delete_transient($cache_key)
    {
        delete_option($cache_key);
    }

    protected function get_transient($cache_key)
    {
        $cache_data = get_option($cache_key);

        if (empty($cache_data['timeout']) || current_time('timestamp') > $cache_data['timeout']) {
            return false;
        }

        return $cache_data['value'];
    }

    protected function set_transient($cache_key, $value, $expiration = 0)
    {
        if (empty($expiration)) {
            $expiration = strtotime('+12 hours', current_time('timestamp'));
        }

        update_option($cache_key, ['timeout' => $expiration, 'value' => $value], 'no');
    }
}
