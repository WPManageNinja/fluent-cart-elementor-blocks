<?php

namespace FluentCartElementorBlocks\App\Services\PluginManager;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
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

    private $response_transient_key;

    /**
     * @param string $_store_url   The FluentCart store URL.
     * @param string $_plugin_file Path to the plugin file.
     * @param array  $_config      Configuration: version, addon_slug, parent_product_id, license_key, activation_hash, plugin_title.
     */
    function __construct($_store_url, $_plugin_file, $_config = [])
    {
        $this->store_url = rtrim($_store_url, '/');
        $this->name = plugin_basename($_plugin_file);
        $this->slug = basename($_plugin_file, '.php');

        $this->response_transient_key = md5(sanitize_key($this->name) . 'response_transient');

        $this->version = $_config['version'] ?? '1.0.0';
        $this->addon_slug = $_config['addon_slug'] ?? '';
        $this->parent_product_id = $_config['parent_product_id'] ?? '';
        $this->license_key = $_config['license_key'] ?? '';
        $this->activation_hash = $_config['activation_hash'] ?? '';
        $this->plugin_title = $_config['plugin_title'] ?? '';

        $this->init();
    }

    /**
     * Set up WordPress filters to hook into WP's update process.
     *
     * @uses add_filter()
     *
     * @return void
     */
    public function init()
    {
        $this->maybe_delete_transients();

        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'), 51);
        add_action( 'delete_site_transient_update_plugins', [ $this, 'delete_transients' ] );

        add_filter('plugins_api', array($this, 'plugins_api_filter'), 10, 3);
        remove_action( 'after_plugin_row_' . $this->name, 'wp_plugin_update_row' );

        add_action( 'after_plugin_row_' . $this->name, [ $this, 'show_update_notification' ], 10, 2 );

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
            return $_transient_data;
        }

        if (is_object($version_info) && isset($version_info->new_version)) {
            if (version_compare($this->version, $version_info->new_version, '<')) {
                $_transient_data->response[$this->name] = $version_info;
            }
            $_transient_data->last_checked = time();
            $_transient_data->checked[$this->name] = $this->version;
        }

        return $_transient_data;
    }

    /**
     * show update notification row -- needed for multisite subsites, because WP won't tell you otherwise!
     *
     * @param string $file
     * @param array $plugin
     */
    public function show_update_notification($file, $plugin)
    {
        if ( is_network_admin() ) {
            return;
        }

        if ( ! current_user_can( 'update_plugins' ) ) {
            return;
        }


        if ( $this->name !== $file ) {
            return;
        }


        // Remove our filter on the site transient
        remove_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );

        $update_cache = get_site_transient( 'update_plugins' );

        $update_cache = $this->check_transient_data( $update_cache );

        set_site_transient( 'update_plugins', $update_cache );

        // Restore our filter
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );

    }


    /**
     * Updates information on the "View version x.x details" page with custom data.
     *
     * @uses api_request()
     *
     * @param mixed $_data
     * @param string $_action
     * @param object $_args
     *
     * @return object $_data
     */
    function plugins_api_filter($_data, $_action = '', $_args = null)
    {
        if ( 'plugin_information' !== $_action ) {
            return $_data;
        }

        if(!isset($_args->slug)) {
            return $_data;
        }

        if($_args->slug !== $this->slug) {
            return $_data;
        }

        $cache_key = $this->slug.'_api_request_' . substr( md5( serialize( $this->slug ) ), 0, 15 );
        $api_request_transient = get_site_transient( $cache_key );

        if ( empty( $api_request_transient ) ) {
            $api_request_transient = $this->api_request();

            // Expires in 2 days
            set_site_transient( $cache_key, $api_request_transient, DAY_IN_SECONDS * 2 );
        }

        if (false !== $api_request_transient) {
            $_data = $api_request_transient;
        }

        return $_data;
    }


    /**
     * Call FluentCart license API with addon_slug support.
     *
     * @return false|object
     */
    private function api_request()
    {
        if ($this->store_url === home_url()) {
            return false;
        }

        $siteUrl = is_multisite() ? network_site_url() : home_url();

        $licenseKey = $this->license_key;
        $activationHash = $this->activation_hash;

        // Auto-detect license from FluentCart Pro stored settings
        if (!$licenseKey && !$activationHash) {
            $stored = $this->getParentLicenseInfo();
            $licenseKey = $stored['license_key'];
            $activationHash = $stored['activation_hash'];
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

        if ($request && isset($request->sections)) {
            $request->sections = maybe_unserialize($request->sections);
            $request->slug = $this->slug;
            $request->plugin = $this->name;
        } else {
            $request = false;
        }

        return $request;
    }

    /**
     * Try to get parent product's license info from FluentCart Pro stored settings.
     */
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

        if(isset($_GET['fluent-cart-elementor-blocks-check-update'])) {
            if ( current_user_can( 'update_plugins' ) ) {
                $this->delete_transients();

                // Remove our filter on the site transient
                remove_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );

                $update_cache = get_site_transient( 'update_plugins' );

                $update_cache = $this->check_transient_data( $update_cache );

                set_site_transient( 'update_plugins', $update_cache );

                // Restore our filter
                add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );

                wp_redirect(admin_url('plugins.php?s=fluent-cart-elementor-blocks&plugin_status=all'));
                exit();
            }
        }
    }

    public function delete_transients()
    {
        $this->delete_transient($this->response_transient_key);
    }

    protected function delete_transient($cache_key)
    {
        delete_option($cache_key);
    }

    protected function get_transient($cache_key)
    {
        $cache_data = get_option($cache_key);

        if (empty($cache_data['timeout']) || current_time('timestamp') > $cache_data['timeout']) {
            // Cache is expired.
            return false;
        }

        return $cache_data['value'];
    }

    protected function set_transient($cache_key, $value, $expiration = 0)
    {
        if (empty($expiration)) {
            $expiration = strtotime('+12 hours', current_time('timestamp'));
        }

        $data = [
            'timeout' => $expiration,
            'value'   => $value,
        ];

        update_option($cache_key, $data, 'no');
    }

}
