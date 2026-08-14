<?php

namespace FluentCartElementorBlocks\App\Http\Policies;

use FluentCart\App\Services\Permission\PermissionManager;
use FluentCart\Framework\Request\Request;
use FluentCart\Framework\Foundation\Policy;

/**
 * Authorizes the product-picker REST routes (products/*).
 *
 * These routes back the editor's product/variant picker control. Access is
 * gated by FluentCart's own `products/view` permission — the permission the
 * route already declares in its meta, and the same one core's product
 * endpoints use. WP administrators hold it automatically; FluentCart store
 * managers hold it via their assigned role; everyone else is denied. Gating
 * on the FluentCart permission (not a bare WP capability) keeps this
 * consistent with core and lets store managers use the picker without full
 * admin rights. Self-contained: extends the framework base policy and calls
 * core's public permission API rather than extending core's Policy.
 */
class ProductPolicy extends Policy
{
    public function verifyRequest(Request $request): bool
    {
        return PermissionManager::hasPermission('products/view');
    }
}
