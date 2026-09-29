<?php

namespace App\Services;

use App\Models\Business;
use Database\Seeders\DemoShopSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DemoShopResetter
{
    public function reset(): void
    {
        $business = Business::where('is_demo', true)
            ->whereHas('owner', fn ($query) => $query->where('email', config('demo.email')))
            ->first();

        if ($business) {
            $this->remove($business);
        }

        app(DemoShopSeeder::class)->run();
    }

    private function remove(Business $business): void
    {
        if (! $business->isDemo()) {
            throw new RuntimeException('Refusing to reset a business that is not marked as demo.');
        }

        $ownerId = (int) $business->owner_user_id;
        $memberIds = DB::table('users')->where('business_id', $business->id)->pluck('id')
            ->push($ownerId)->unique()->values()->all();

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($business, $ownerId, $memberIds) {
                $this->deleteRelationshipChildren($ownerId);

                foreach (Schema::getTableListing() as $table) {
                    $table = str_contains($table, '.') ? last(explode('.', $table)) : $table;
                    if (in_array($table, ['users', 'businesses', 'migrations', 'roles', 'permissions'], true)) {
                        continue;
                    }

                    if (Schema::hasColumn($table, 'user_id')) {
                        DB::table($table)->where('user_id', $ownerId)->delete();
                    }
                    if (Schema::hasColumn($table, 'userId')) {
                        DB::table($table)->where('userId', (string) $ownerId)->delete();
                    }
                    if (Schema::hasColumn($table, 'business_owner_user_id')) {
                        DB::table($table)->where('business_owner_user_id', $ownerId)->delete();
                    }
                    if (Schema::hasColumn($table, 'business_id')) {
                        DB::table($table)->where('business_id', $business->id)->delete();
                    }
                }

                if (Schema::hasTable('personal_access_tokens')) {
                    DB::table('personal_access_tokens')->where('tokenable_type', 'App\\Models\\User')
                        ->whereIn('tokenable_id', $memberIds)->delete();
                }
                if (Schema::hasTable('model_has_roles')) {
                    DB::table('model_has_roles')->where('model_type', 'App\\Models\\User')
                        ->whereIn('model_id', $memberIds)->delete();
                }
                if (Schema::hasTable('model_has_permissions')) {
                    DB::table('model_has_permissions')->where('model_type', 'App\\Models\\User')
                        ->whereIn('model_id', $memberIds)->delete();
                }

                DB::table('businesses')->where('id', $business->id)->where('is_demo', true)->delete();
                DB::table('users')->whereIn('id', $memberIds)->delete();
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function deleteRelationshipChildren(int $ownerId): void
    {
        foreach (['inventory_movements', 'sale_stocks'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                DB::table($table)->where('user_id', $ownerId)->delete();
            }
        }

        $this->deleteChildren('customer_measurement_histories', 'user_id', $ownerId, 'customer_measurement_history_values', 'history_id');
        $this->deleteChildren('purchase_returns', 'user_id', $ownerId, 'purchase_return_items', 'purchase_return_id');
        $this->deleteChildren('purchases', 'user_id', $ownerId, 'purchase_items', 'purchase_id');
        $this->deleteChildren('sales', 'user_id', $ownerId, 'saledetails', 'sale_id');
        $this->deleteChildren('storefront_order_returns', 'user_id', $ownerId, 'storefront_order_return_items', 'return_id');
    }

    private function deleteChildren(string $parentTable, string $ownerColumn, int $ownerId, string $childTable, string $foreignKey): void
    {
        if (! Schema::hasTable($parentTable) || ! Schema::hasTable($childTable)
            || ! Schema::hasColumn($parentTable, $ownerColumn) || ! Schema::hasColumn($childTable, $foreignKey)) {
            return;
        }

        $ids = DB::table($parentTable)->where($ownerColumn, $ownerId)->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table($childTable)->whereIn($foreignKey, $ids)->delete();
        }
    }
}
