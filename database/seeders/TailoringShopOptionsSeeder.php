<?php

namespace Database\Seeders;

use App\Services\TailoringOptionDefaultsService;
use Illuminate\Database\Seeder;

class TailoringShopOptionsSeeder extends Seeder
{
    public function run(TailoringOptionDefaultsService $defaults): void
    {
        $defaults->seedForAllTailoringOwners();
    }
}
