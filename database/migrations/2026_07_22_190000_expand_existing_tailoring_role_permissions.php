<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        'tailoring.customers',
        'tailoring.orders',
        'tailoring.workshop',
        'tailoring.tailors',
        'tailoring.configuration',
    ];

    public function up(): void
    {
        DB::table('business_roles')->orderBy('id')->each(function ($role) {
            $permissions = json_decode($role->permissions, true) ?: [];
            if (! in_array('tailoring.access', $permissions, true)) {
                return;
            }

            DB::table('business_roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_unique(array_merge($permissions, $this->permissions))), JSON_UNESCAPED_UNICODE),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('business_roles')->orderBy('id')->each(function ($role) {
            $permissions = array_values(array_diff(json_decode($role->permissions, true) ?: [], $this->permissions));
            DB::table('business_roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
            ]);
        });
    }
};
