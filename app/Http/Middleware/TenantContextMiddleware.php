<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class TenantContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $previousTenant = TenantManager::getTenant();

        if ($user) {
            $tenant = null;
            if ($user->tenant_id) {
                // The authenticated user's tenant is the primary authority; never trust leftover state
                $tenant = Tenant::where('id', $user->tenant_id)->where('is_active', true)->first();
            }

            // Strictly establish the user's tenant (or null if missing/invalid/inactive).
            // Never fall back to TenantManager or previous tenant context.
            TenantManager::setTenant($tenant);

            if (! $tenant && ! $request->routeIs('logout')) {
                try {
                    abort(403, 'Unauthorized: No active organization or tenant context associated with this account.');
                } finally {
                    TenantManager::setTenant($previousTenant);
                }
            }
        } else {
            // Guest request: clear any stale tenant state so guests never inherit a previous tenant
            TenantManager::setTenant(null);
            $tenant = null;
        }

        View::share('currentTenant', $tenant);

        try {
            return $next($request);
        } finally {
            TenantManager::setTenant($previousTenant);
        }
    }

    public function terminate(Request $request, Response $response): void
    {
        if (TenantManager::getRunAsDepth() === 0) {
            TenantManager::clear();
        }
    }
}
