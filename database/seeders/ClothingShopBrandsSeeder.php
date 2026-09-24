<?php

namespace Database\Seeders;

use App\Services\ClothingBrandDefaultsService;
use Illuminate\Database\Seeder;

class ClothingShopBrandsSeeder extends Seeder
{
    public function run(ClothingBrandDefaultsService $defaults): void
    {
        $defaults->seedForAllClothingOwners();
    }
}
