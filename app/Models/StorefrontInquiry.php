<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontInquiry extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'storefront_id',
        'tailoring_service_id',
        'customer_name',
        'phone',
        'email',
        'city',
        'preferred_date',
        'message',
        'status',
        'admin_notes',
        'contacted_at',
        'closed_at',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'contacted_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW => 'نئی درخواست',
            self::STATUS_CONTACTED => 'رابطہ ہو گیا',
            self::STATUS_CLOSED => 'بند',
        ];
    }

    public function storefront()
    {
        return $this->belongsTo(Storefront::class);
    }

    public function service()
    {
        return $this->belongsTo(StorefrontTailoringService::class, 'tailoring_service_id');
    }

    public function getReferenceAttribute(): string
    {
        return 'TMSI-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
