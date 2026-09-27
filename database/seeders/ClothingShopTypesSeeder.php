<?php

namespace Database\Seeders;

use App\Services\ClothingTypeDefaultsService;
use Illuminate\Database\Seeder;

class ClothingShopTypesSeeder extends Seeder
{
    public function run(ClothingTypeDefaultsService $defaults): void
    {
        $defaults->seedForAllClothingOwners();
    }
}
