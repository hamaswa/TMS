<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_user_id',
        'tailoring_enabled',
        'clothing_enabled',
        'password_expiry_days',
        'password_policy_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'tailoring_enabled' => 'boolean',
            'clothing_enabled' => 'boolean',
            'password_expiry_days' => 'integer',
            'password_policy_updated_at' => 'datetime',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members()
    {
        return $this->hasMany(User::class);
    }

    public function roles()
    {
        return $this->hasMany(BusinessRole::class);
    }

    public function hasModule(string $module): bool
    {
        return match ($module) {
            User::MODULE_TAILORING => $this->tailoring_enabled,
            User::MODULE_CLOTHING => $this->clothing_enabled,
            default => false,
        };
    }
}
