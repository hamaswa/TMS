<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ClothType;
use App\Models\User;

class ClothingTypeDefaultsService
{
    public const TYPES = [
        'ریشم',
        'میرینو اون',
        'کاٹن',
        'سینیل',
        'سوتی',
        'مخمل',
    ];

    public function seedForOwner(int $ownerId): void
    {
        foreach (self::TYPES as $type) {
            ClothType::firstOrCreate([
                'user_id' => $ownerId,
                'name' => $type,
            ]);
        }
    }

    public function seedForAllClothingOwners(): void
    {
        $ownerIds = User::query()
            ->where('clothing_access', true)
            ->where(function ($query) {
                $query->where('is_business_owner', true)
                    ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'shop_owner'));
            })
            ->pluck('id')
            ->merge(Business::query()->where('clothing_enabled', true)->pluck('owner_user_id'))
            ->filter()
            ->unique();

        foreach ($ownerIds as $ownerId) {
            $this->seedForOwner((int) $ownerId);
        }
    }
}
