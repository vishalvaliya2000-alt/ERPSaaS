<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierShipment extends Model
{
    protected $fillable = [
        'sample_id',
        'tracking_number',
        'provider',
        'shipment_date',
        'status',
        'last_status_update',
        'estimated_delivery',
        'actual_delivery',
        'history_json',
    ];

    protected $casts = [
        'shipment_date' => 'datetime',
        'last_status_update' => 'datetime',
        'estimated_delivery' => 'datetime',
        'actual_delivery' => 'datetime',
        'history_json' => 'array',
    ];

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class, 'sample_id');
    }
}
