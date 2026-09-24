<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineOperation extends Model
{
    public const STATUS_APPLIED = 'applied';

    public const STATUS_SUPERSEDED = 'superseded';

    public const STATUS_CONFLICT = 'conflict';

    protected $fillable = [
        'operation_uuid',
        'owner_user_id',
        'actor_type',
        'actor_id',
        'action',
        'aggregate_type',
        'aggregate_id',
        'base_version',
        'payload',
        'status',
        'result',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'processed_at' => 'datetime',
    ];
}
