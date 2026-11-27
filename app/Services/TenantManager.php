<?php

namespace App\Services;

use App\Models\Tenant;

class TenantManager
{
    protected static ?Tenant $currentTenant = null;

    protected static int $runAsDepth = 0;

    public static function getTenant(): ?Tenant
    {
        if (static::$currentTenant !== null) {
            return static::$currentTenant;
        }

        try {
            $user = auth()->user();
            if ($user && $user->tenant_id) {
                $tenant = Tenant::find($user->tenant_id);
                if ($tenant && $tenant->is_active) {
                    static::$currentTenant = $tenant;

                    return static::$currentTenant;
                }
            }

            // Fail-closed: Never silently fall back to Tenant 1; inactive or missing tenant returns null
            static::$currentTenant = null;
        } catch (\Throwable $e) {
            static::$currentTenant = null;
        }

        return static::$currentTenant;
    }

    public static function getTenantId(): ?int
    {
        if (static::$currentTenant !== null) {
            return (int) static::$currentTenant->id;
        }

        try {
            $tenant = static::getTenant();

            return $tenant ? (int) $tenant->id : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function setTenant(?Tenant $tenant): void
    {
        static::$currentTenant = $tenant;
    }

    public static function setTenantId(?int $id): void
    {
        if ($id === null) {
            static::$currentTenant = null;

            return;
        }

        $tenant = Tenant::find($id);
        static::$currentTenant = ($tenant && $tenant->is_active) ? $tenant : null;
    }

    public static function clear(): void
    {
        static::$currentTenant = null;
    }

    public static function getRunAsDepth(): int
    {
        return static::$runAsDepth;
    }

    /**
     * Safely execute a callable within an explicit tenant context and restore
     * the previous context on completion or exception.
     *
     * @template T
     *
     * @param  callable(Tenant|null): T  $callback
     * @return T
     */
    public static function runAsTenant(Tenant|int $tenant, callable $callback)
    {
        $previousTenant = static::$currentTenant;
        static::$runAsDepth++;

        try {
            if ($tenant instanceof Tenant) {
                if (! $tenant->exists || empty($tenant->id)) {
                    throw new \InvalidArgumentException('Tenant model must be persisted in the database.');
                }
                $tenantId = (int) $tenant->id;
            } else {
                $tenantId = (int) $tenant;
            }

            $resolvedTenant = Tenant::find($tenantId);
            if (! $resolvedTenant) {
                throw new \InvalidArgumentException("Tenant [{$tenantId}] does not exist.");
            }
            if (! $resolvedTenant->is_active) {
                throw new \InvalidArgumentException("Tenant [{$tenantId}] is not active.");
            }

            static::$currentTenant = $resolvedTenant;

            return $callback(static::$currentTenant);
        } finally {
            static::$currentTenant = $previousTenant;
            static::$runAsDepth--;
        }
    }
}
