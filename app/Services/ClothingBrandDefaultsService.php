<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ClothBrand;
use App\Models\User;
use Illuminate\Support\Str;

class ClothingBrandDefaultsService
{
    public const BRANDS = [
        'J.',
        'Grace',
        'Nishat Linen',
        'Gul Ahmed',
        'Ideas',
        'Alkaram Studio',
        'Sapphire',
        'Khaadi',
        'Sana Safinaaz',
    ];

    public function seedForOwner(int $ownerId): void
    {
        foreach (self::BRANDS as $brand) {
            ClothBrand::firstOrCreate(
                [
                    'user_id' => $ownerId,
                    'name' => $brand,
                ],
                [
                    'brand_slug' => Str::slug($brand),
                    'brand_logo' => null,
                ]
            );
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
