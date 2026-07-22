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
        // -------------------------
        // 1. Create Admin User
        // -------------------------
        $admin = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin@1234'),
            ]
        );

        // -------------------------
        // 2. Create Itlinked User
        // -------------------------
        $itlinked = User::updateOrCreate(
            ['email' => 'itlinked@gmail.com'],
            [
                'name' => 'Itlinked',
                'password' => Hash::make('itlinked@1234'),
            ]
        );

        // -------------------------
        // 3. Assign Roles safely
        // -------------------------

        $adminRole = Role::find(1);
        $userRole  = Role::find(2);

        if ($adminRole) {
            $admin->syncRoles([$adminRole]);
        }

        if ($userRole) {
            $itlinked->syncRoles([$userRole]);
        }
    }
}
