<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorefrontModerationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'storefront_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by_user_id',
    ];

    public function storefront()
    {
        return $this->belongsTo(Storefront::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
