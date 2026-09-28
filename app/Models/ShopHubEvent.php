<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopHubEvent extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SYNCED = 'synced';

    public const STATUS_CONFLICT = 'conflict';

    protected $fillable = [
        'event_uuid',
        'business_owner_user_id',
        'actor_user_id',
        'device_id',
        'event_type',
        'aggregate_type',
        'aggregate_uuid',
        'base_revision',
        'resulting_revision',
        'payload',
        'status',
        'occurred_at',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'base_revision' => 'integer',
            'resulting_revision' => 'integer',
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
