<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'category',
        'title',
        'message',
        'priority',
        'read',
        'entity_type',
        'entity_id',
    ];

    protected $casts = [
        'read' => 'boolean',
    ];
}
