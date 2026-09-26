<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'sales_order_id',
        'product_id',
        'order_qty',
        'rate',
        'order_value',
        'shipped_qty',
        'balance_qty',
        'status',
    ];

    protected $casts = [
        'order_qty' => 'decimal:2',
        'rate' => 'decimal:2',
        'order_value' => 'decimal:2',
        'shipped_qty' => 'decimal:2',
        'balance_qty' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function shipmentItems(): HasMany
    {
        return $this->hasMany(CommercialShipmentItem::class, 'sales_order_item_id');
    }
}
