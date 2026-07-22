<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('business_roles')->orderBy('id')->each(function ($role) {
            $permissions = json_decode($role->permissions, true) ?: [];
            if (! in_array('clothing.access', $permissions, true)) {
                return;
            }

            $permissions = array_values(array_unique(array_merge($permissions, [
                'clothing.sales',
                'clothing.inventory',
                'clothing.purchases',
                'clothing.suppliers',
            ])));
            DB::table('business_roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('business_roles')->orderBy('id')->each(function ($role) {
            $permissions = array_values(array_diff(json_decode($role->permissions, true) ?: [], [
                'clothing.sales',
                'clothing.inventory',
                'clothing.purchases',
                'clothing.suppliers',
            ]));
            DB::table('business_roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
            ]);
        });
    }
};
