<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderRevision extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'sales_order_id',
        'revision_number',
        'revision_reason',
        'revised_by_user_id',
        'revised_by_name',
        'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'revision_number' => 'integer',
        'created_at' => 'datetime',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function revisedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revised_by_user_id');
    }
}
