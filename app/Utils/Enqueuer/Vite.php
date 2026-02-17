<?php

namespace FluentCartElementorBlocks\App\Utils\Enqueuer;

use FluentCartElementorBlocks\App\Core\App;
use FluentCartElementorBlocks\Framework\Framework\Support\Arr;

class Vite extends Enqueuer
{

    protected static $env = 'PRODUCTION_MODE';

    /**
     * @method static enqueueScript(string $handle, string $src, array $dependency = [], string|null $version = null, bool|null $inFooter = false)
     * @method static enqueueStyle(string $handle, string $src, array $dependency = [], string|null $version = null)
     */

    private array $moduleScripts = [];
    private bool $isScriptFilterAdded = false;
    private static string $viteHostProtocol = 'http://';
    private static string $viteHost = 'localhost';
    private static string $vitePort = '4230';
    private static string $resourceDirectory = 'resources/';

    protected static $instance = null;
    protected static $lastJsHandel = null;

    private $manifestData = null;

    public static function __callStatic($method, $params)
    {
        if (static::$instance == null) {
            static::$instance = new static();
            if (!self::isOnDevMode()) {
                (static::$instance)->loadViteManifest();
            }
        }
        return call_user_func_array(array(static::$instance, $method), $params);
    }

    public static function getAssetUrl($path = ''): string
    {
        return esc_url(static::$instance->get_asset_url($path) ?? '');
    }

    private function get_asset_url($path = ''): string
    {

        if (!self::isOnDevMode()) {
            return FLUENTCART_ELEMENTOR_BLOCKS_URL . 'assets' . '/' . $path;
        } else {
            return $this->getVitePath() . $path;
        }
    }

    private function loadViteManifest()
    {

        if (!empty((static::$instance)->manifestData)) {
            return;
        }

        (static::$instance)->manifestData = App::getInstance('config')->get('vite_config');;
    }

    private function enqueueScript($handle, $src, $dependency = [], $version = null, $inFooter = false)
    {
        $version = $version ?? FLUENTCART_ELEMENTOR_BLOCKS_VERSION;
        if (in_array($handle, (static::$instance)->moduleScripts)) {
            if (self::isOnDevMode()) {
                $callerReference = (debug_backtrace()[2]);
                $fileName = explode('plugins', $callerReference['file'])[1];
                $line = $callerReference['line'];
                //throw new \Exception("This handel Has been used'. 'Filename: $fileName Line: $line");
            }
        }

        (static::$instance)->moduleScripts[] = $handle;

        static::$lastJsHandel = $handle;

        if (!(static::$instance)->isScriptFilterAdded) {
            add_filter('script_loader_tag', function ($tag, $handle, $src) {
                return (static::$instance)->addModuleToScript($tag, $handle, $src);
            }, 10, 3);
            (static::$instance)->isScriptFilterAdded = true;
        }


        if (!static::isOnDevMode()) {
            $assetFile = (static::$instance)->getFileFromManifest($src);
            $srcPath = static::getProductionFilePath($assetFile);
        } else {
            $srcPath = static::getVitePath() . $src;
        }

        wp_enqueue_script(
            $handle,
            $srcPath,
            $dependency,
            $version,
            $inFooter
        );
        return $this;
    }

    private function getFileFromManifest($src)
    {

        if (isset((static::$instance)->manifestData[static::$resourceDirectory . $src])) {
            return (static::$instance)->manifestData[static::$resourceDirectory . $src];
        }

        if (static::isOnDevMode()) {
            throw new \Exception(esc_html($src) . " file not found in vite manifest, Make sure it is in rollupOptions input and build again");
        }

        return '';
    }

    static function getProductionFilePath($file)
    {
        $assetPath = static::getAssetPath();
        if (isset($file['css']) && is_array($file['css'])) {
            foreach ($file['css'] as $key => $path) {
                wp_enqueue_style(
                    $file['file'] . '_' . $key . '_css',
                    $assetPath . $path,
                    [],
                    FLUENTCART_VERSION
                );
            }
        }
        return ($assetPath . $file['file']);
    }

    static function with($params)
    {
        if (!is_array($params) || !Arr::isAssoc($params) || empty(static::$lastJsHandel)) {
            static::$lastJsHandel = null;
            return;
        }

        foreach ($params as $key => $val) {
            wp_localize_script(static::$lastJsHandel, $key, $val);
        }
        static::$lastJsHandel = null;
    }

    private function enqueueStyle($handle, $src, $dependency = [], $version = null)
    {
        $version = $version ?? FLUENTCART_ELEMENTOR_BLOCKS_VERSION;
        if (!static::isOnDevMode()) {
            $assetFile = (static::$instance)->getFileFromManifest($src);
            $srcPath = static::getProductionFilePath($assetFile);
        } else {
            $srcPath = static::getVitePath() . $src;
        }

        wp_enqueue_style(
            $handle,
            $srcPath,
            $dependency,
            $version
        );
    }

    private function enqueueStaticScript($handle, $src, $dependency = [], $version = null, $inFooter = false)
    {
        $version = $version ?? FLUENTCART_ELEMENTOR_BLOCKS_VERSION;
        wp_enqueue_script(
            $handle,
            static::getEnqueuePath($src),
            $dependency,
            $version,
            $inFooter
        );
    }

    private function enqueueStaticStyle($handle, $src, $dependency = [], $version = null)
    {
        $version = $version ?? FLUENTCART_ELEMENTOR_BLOCKS_VERSION;
        wp_enqueue_style(
            $handle,
            static::getEnqueuePath($src),
            $dependency,
            $version
        );
    }


    static function isOnDevMode(): bool
    {
        // Don't change this line, it's a heck and required
        return static::$env !== 'PRODUCTION' . '_MODE';
    }

    static function getVitePath(): string
    {
        return static::$viteHostProtocol . static::$viteHost . ":" . (static::$vitePort) . '/' . (static::$resourceDirectory);
    }

    static function getEnqueuePath($path = ''): string
    {
        return (static::isOnDevMode() ? static::getVitePath() : static::getAssetPath()) . $path;
    }

    static function getAssetPath(): string
    {
        return App::getInstance()['url.assets'];
    }

    private function getStaticFilePath($path = ''): string
    {
        return static::getAssetPath() . $path;
    }

    private function addModuleToScript($tag, $handle, $src)
    {
        if (in_array($handle, (static::$instance)->moduleScripts)) {
            $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
        }
        return $tag;
    }
}
