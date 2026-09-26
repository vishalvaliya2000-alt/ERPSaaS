<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'product_code',
        'product_name',
        'category_id',
        'uom',
        'standard_rate',
        'tax_rate_percent',
        'standard_cost',
        'hsn_code',
        'min_order_qty',
        'packaging',
        'specification_url',
        'description',
        'is_active',
    ];

    protected $casts = [
        'standard_rate' => 'decimal:2',
        'tax_rate_percent' => 'decimal:2',
        'standard_cost' => 'decimal:2',
        'min_order_qty' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Accessor / Mutator Aliases for smooth UI compatibility
    public function getCurrentRatePerKgAttribute(): float
    {
        return (float) ($this->standard_rate ?? 0);
    }

    public function setCurrentRatePerKgAttribute($value): void
    {
        $this->attributes['standard_rate'] = $value;
    }

    public function getDefaultPackagingAttribute(): ?string
    {
        return $this->packaging;
    }

    public function setDefaultPackagingAttribute($value): void
    {
        $this->attributes['packaging'] = $value;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'product_id');
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class, 'product_id');
    }
}