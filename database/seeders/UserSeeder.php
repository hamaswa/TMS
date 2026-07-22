<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Keep the base seed predictable: demo businesses belong in QaDemoSeeder.
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin@1234'),
                'tailoring_access' => false,
                'clothing_access' => false,
            ]
        );

        $adminRole = Role::where('name', 'administrative')->first();

        if ($adminRole) {
            $admin->syncRoles([$adminRole]);
        }
    }
}
