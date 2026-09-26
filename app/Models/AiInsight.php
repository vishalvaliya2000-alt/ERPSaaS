<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInsight extends Model
{
    protected $fillable = [
        'customer_id',
        'insight_type',
        'priority',
        'score',
        'summary',
        'rationale',
        'recommended_action',
        'action_url',
        'is_dismissed',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'is_dismissed' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
