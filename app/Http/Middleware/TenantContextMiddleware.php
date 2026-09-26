<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TenantManager;
use Illuminate\Support\Facades\View;

class TenantContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantManager::getTenant();

        if ($tenant) {
            // Share active tenant globally across all Blade views
            View::share('currentTenant', $tenant);
        }

        return $next($request);
    }
}