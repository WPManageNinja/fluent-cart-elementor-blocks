<?php

namespace FluentCartElementorBlocks\App\Modules\MCP;

/**
 * Bootstrap for this addon's MCP builder tools.
 *
 * These abilities let an AI agent discover, configure and place FluentCart's
 * Elementor widgets — the gap Elementor's own MCP leaves, since it refuses to
 * configure classic (V3) elements. They live here rather than in FluentCart
 * core so a store without the Elementor addon never loads them and the agent
 * never sees builder tools it cannot use.
 *
 * Wired through the same seam FluentCart Pro uses:
 *   - `fluent_cart/mcp_loaded`        → register the abilities
 *   - `fluent_cart/mcp_ability_names` → expose them on the `fluent-cart` server
 *
 * Both hooks only fire when core's MCP module is enabled, so this costs
 * nothing when MCP is off.
 */
class MCPInit
{
    public function init()
    {
        // Elementor is required for every one of these tools. Without it the
        // widget catalog is empty and the document writer has nothing to
        // write, so skip registration entirely rather than publish tools that
        // can only fail.
        if (!defined('ELEMENTOR_VERSION')) {
            return;
        }

        add_action('fluent_cart/mcp_loaded', function () {
            AbilitiesRegistrar::register();
        });

        add_filter('fluent_cart/mcp_ability_names', function ($names) {
            return array_merge((array) $names, array_keys(AbilitiesRegistrar::getDefinitions()));
        });
    }
}
