<?php

namespace App\Models\Traits;

use App\Models\Tenant;
use App\Scopes\TenantScope;
use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            $activeTenantId = TenantManager::getTenantId();

            if (! $model->tenant_id) {
                if ($activeTenantId) {
                    $model->tenant_id = $activeTenantId;
                } else {
                    throw new \InvalidArgumentException('Cannot create '.static::class.' without a valid tenant context.');
                }
            } elseif ($activeTenantId !== null && (int) $model->tenant_id !== (int) $activeTenantId) {
                throw new \InvalidArgumentException('Cannot create '.static::class.' for tenant ['.$model->tenant_id.'] when active tenant context is ['.$activeTenantId.'].');
            } elseif ($activeTenantId === null) {
                throw new \InvalidArgumentException('Cannot create '.static::class.' without a valid tenant context.');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
