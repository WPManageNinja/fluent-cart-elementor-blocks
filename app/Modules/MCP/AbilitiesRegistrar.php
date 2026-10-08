<?php

namespace FluentCartElementorBlocks\App\Modules\MCP;

use FluentCart\App\Modules\MCP\Support\MCPHelper;
use FluentCartElementorBlocks\App\Modules\MCP\Tools\BuilderTools;

/**
 * Registers this addon's builder abilities onto FluentCart's `fluent-cart` MCP
 * server, the same way FluentCart Pro registers its license tools: through the
 * `fluent_cart/mcp_loaded` action and the `fluent_cart/mcp_ability_names`
 * filter. Mirrors the free registrar's shape so the tools behave identically —
 * same execute-callback wrapping, same meta, same `fluent-cart` category.
 */
class AbilitiesRegistrar
{
    /** Tool classes that expose a static definitions() method. */
    private static function toolClasses()
    {
        return [
            BuilderTools::class,
        ];
    }

    public static function getDefinitions()
    {
        $defs = [];

        foreach (self::toolClasses() as $class) {
            if (class_exists($class) && method_exists($class, 'definitions')) {
                $defs = array_merge($defs, (array) $class::definitions());
            }
        }

        return $defs;
    }

    public static function register()
    {
        if (!function_exists('wp_register_ability')) {
            return;
        }

        foreach (self::getDefinitions() as $name => $definition) {
            try {
                self::registerAbility($name, $definition);
            } catch (\Throwable $e) {
                // Registration runs on wp_abilities_api_init, which the adapter
                // fires from inside create_server(). An uncaught throw here
                // would abort every later callback on that action — other
                // plugins' abilities included — and kill the FluentCart MCP
                // server itself. Skip the bad definition, keep the rest.
                if (function_exists('fluent_cart_error_log')) {
                    fluent_cart_error_log(
                        'Elementor MCP ability registration failed: ' . $name,
                        get_class($e) . ': ' . $e->getMessage()
                    );
                }
            }
        }
    }

    private static function registerAbility($name, $definition)
    {
        // Cast before array_keys: no-arg tools declare properties as stdClass
        // (so the schema serializes as {} not []), which array_keys() rejects
        // on PHP 8 with a TypeError.
        $declaredParams = isset($definition['input_schema']['properties'])
            ? array_keys((array) $definition['input_schema']['properties'])
            : [];

        $args = [
            'label'               => $definition['label'],
            'description'         => $definition['description'],
            'category'            => 'fluent-cart',
            'execute_callback'    => self::wrapExecuteCallback($name, $definition['execute_callback'], $declaredParams),
            'permission_callback' => $definition['permission_callback'],
            'meta'                => [
                'show_in_rest' => true,
                'mcp'          => ['public' => true],
            ],
        ];

        if (!empty($definition['input_schema'])) {
            $args['input_schema'] = $definition['input_schema'];
        }

        if (!empty($definition['output_schema'])) {
            $args['output_schema'] = $definition['output_schema'];
        }

        if (!empty($definition['annotations'])) {
            $mapped = self::mapAnnotations($definition['annotations']);

            if (!empty($mapped)) {
                $args['meta']['annotations'] = $mapped;
            }
        }

        wp_register_ability($name, $args);
    }

    /**
     * Reject undeclared params and turn exceptions into a readable error, so
     * the agent sees what went wrong instead of the adapter's generic
     * "Tool execution failed".
     */
    private static function wrapExecuteCallback($name, $callback, array $declaredParams)
    {
        return function ($params = []) use ($name, $callback, $declaredParams) {
            $params = is_array($params) ? $params : [];

            $unknown = array_diff(array_keys($params), $declaredParams);

            if ($unknown) {
                return MCPHelper::error(
                    'unknown_parameters',
                    sprintf(
                        /* translators: 1: rejected parameter names, 2: accepted parameter names */
                        __('Unknown parameters: %1$s. This tool accepts: %2$s.', 'fluent-cart-elementor-blocks'),
                        implode(', ', $unknown),
                        $declaredParams ? implode(', ', $declaredParams) : __('(none)', 'fluent-cart-elementor-blocks')
                    )
                );
            }

            try {
                return call_user_func($callback, $params);
            } catch (\Throwable $e) {
                if (function_exists('fluent_cart_error_log')) {
                    fluent_cart_error_log(
                        'Elementor MCP tool failed: ' . $name,
                        get_class($e) . ': ' . $e->getMessage() . ' at ' . basename($e->getFile()) . ':' . $e->getLine()
                    );
                }

                return MCPHelper::error(
                    'tool_failed',
                    sprintf(
                        /* translators: %s: tool name */
                        __('%s could not complete. The error has been logged.', 'fluent-cart-elementor-blocks'),
                        $name
                    )
                );
            }
        };
    }

    /** snake_case annotations onto the MCP meta shape. */
    private static function mapAnnotations(array $annotations)
    {
        $map = [
            'readonly'    => 'readOnlyHint',
            'destructive' => 'destructiveHint',
            'idempotent'  => 'idempotentHint',
            'open_world'  => 'openWorldHint',
        ];

        $mapped = [];

        foreach ($map as $from => $to) {
            if (isset($annotations[$from])) {
                $mapped[$to] = (bool) $annotations[$from];
            }
        }

        return $mapped;
    }
}
