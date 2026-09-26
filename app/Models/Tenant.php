<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'logo_url',
        'industry',
        'currency_code',
        'currency_symbol',
        'tax_id_label',
        'tax_id_number',
        'pan_number',
        'iec_code',
        'lut_arn',
        'fssai_number',
        'email',
        'phone',
        'website',
        'address_line',
        'city',
        'state',
        'pincode',
        'country',
        'bank_name',
        'bank_account_number',
        'bank_ifsc_code',
        'bank_swift_code',
        'bank_branch',
        'invoice_prefix',
        'quotation_prefix',
        'po_prefix',
        'shipment_prefix',
        'plan',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot('role', 'is_default')
            ->withTimestamps();
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(CommercialShipment::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function isExportCompany(): bool
    {
        return !empty($this->iec_code) || in_array($this->currency_code, ['USD', 'EUR', 'GBP', 'AED']);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([$this->address_line, $this->city, $this->state, $this->pincode, $this->country]);
        return implode(', ', $parts);
    }
}