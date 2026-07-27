<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TailorSecurityDepositTransaction extends Model
{
    use HasFactory;

    public const TYPE_RECEIVED = 'received';

    public const TYPE_REFUNDED = 'refunded';

    protected $fillable = [
        'tailor_id',
        'user_id',
        'transaction_type',
        'amount',
        'transaction_date',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function tailor()
    {
        return $this->belongsTo(Tailor::class);
    }
}
