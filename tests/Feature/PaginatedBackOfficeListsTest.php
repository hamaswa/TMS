<?php

namespace Tests\Feature;

use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tailor;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaginatedBackOfficeListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tailor_job_board_searches_and_paginates_inside_the_shop(): void
    {
        [$owner, $customer, $tailor] = $this->shop();
        for ($i = 1; $i <= 20; $i++) {
            Order::create([
                'customerId' => $customer->id, 'sub_customer' => $customer->id,
                'suitNum' => 'SUIT-' . $i, 'suitQuantity' => 1, 'totalPayment' => 1000,
                'userId' => $owner->id, 'tailorId' => $tailor->id,
                'returnDate' => now()->addDays($i)->toDateString(), 'status' => 'assigned',
            ]);
        }

        $this->actingAs($owner)->get(route('admin.tailor-jobs.index', ['per_page' => 15]))
            ->assertOk()->assertViewHas('orders', fn ($orders) => $orders instanceof LengthAwarePaginator
                && $orders->perPage() === 15 && $orders->total() === 20);

        $this->actingAs($owner)->get(route('admin.tailor-jobs.index', ['q' => 'SUIT-20']))
            ->assertOk()->assertViewHas('orders', fn ($orders) => $orders->total() === 1
                && $orders->first()->suitNum === 'SUIT-20');
    }

    public function test_purchase_list_filters_by_search_date_and_page_size(): void
    {
        [$owner] = $this->shop();
        $supplier = Supplier::create(['user_id' => $owner->id, 'name' => 'Filtered Supplier']);
        for ($i = 1; $i <= 18; $i++) {
            Purchase::create([
                'user_id' => $owner->id, 'supplier_id' => $supplier->id,
                'purchase_number' => 'PO-PAGE-' . $i, 'purchase_date' => now()->subDays($i),
                'status' => $i === 18 ? 'received' : 'draft',
            ]);
        }

        $this->actingAs($owner)->get(route('admin.purchases.index', ['per_page' => 15]))
            ->assertOk()->assertViewHas('purchases', fn ($rows) => $rows->count() === 15 && $rows->total() === 18);
        $this->actingAs($owner)->get(route('admin.purchases.index', ['q' => 'PO-PAGE-18', 'status' => 'received']))
            ->assertOk()->assertViewHas('purchases', fn ($rows) => $rows->total() === 1);
    }

    public function test_inventory_ledger_filters_movement_type_and_paginates(): void
    {
        [$owner, , , $color] = $this->shop();
        for ($i = 1; $i <= 30; $i++) {
            InventoryMovement::create([
                'user_id' => $owner->id, 'cloth_id' => $color->cloth_id,
                'cloth_color_id' => $color->id,
                'movement_type' => $i <= 20 ? 'purchase_receipt' : 'counter_sale',
                'quantity' => 1, 'balance_after' => $i, 'occurred_at' => now()->subMinutes($i),
                'reference_type' => ClothColor::class, 'reference_id' => $color->id,
            ]);
        }

        $this->actingAs($owner)->get(route('admin.inventory-ledger.index', [
            'movement_type' => 'purchase_receipt', 'per_page' => 15,
        ]))->assertOk()->assertViewHas('movements', fn ($rows) => $rows->count() === 15 && $rows->total() === 20);

        $this->actingAs($owner)->get(route('admin.inventory-valuation.index', ['q' => 'Blue', 'per_page' => 15]))
            ->assertOk()->assertViewHas('colors', fn ($rows) => $rows->total() === 1);
    }

    public function test_financial_receivables_are_database_paginated_and_searchable(): void
    {
        [$owner] = $this->shop();
        for ($i = 1; $i <= 12; $i++) {
            $customer = Customers::create([
                'name' => $i === 12 ? 'Unique Receivable' : 'Customer ' . $i,
                'phone_number1' => '0300' . str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'user_id' => $owner->id,
            ]);
            Transaction::create([
                'customerId' => $customer->id, 'userId' => $owner->id,
                'Order_type' => 'Tailor', 'recivedPayment' => 0, 'remainingBalance' => 100,
            ]);
        }

        $this->actingAs($owner)->get(route('admin.financial-reports.index', ['per_page' => 10]))
            ->assertOk()->assertViewHas('report', fn ($report) => $report['receivables'] instanceof LengthAwarePaginator
                && $report['receivables']->count() === 10 && $report['receivables']->total() === 12);
        $this->actingAs($owner)->get(route('admin.financial-reports.index', ['receivables_q' => 'Unique']))
            ->assertOk()->assertViewHas('report', fn ($report) => $report['receivables']->total() === 1);
    }

    private function shop(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create();
        $owner->assignRole($role);
        $customer = Customers::create(['name' => 'List Customer', 'phone_number1' => fake()->unique()->numerify('03#########'), 'user_id' => $owner->id]);
        $tailor = Tailor::create(['name' => 'List Tailor', 'phone_number1' => fake()->unique()->numerify('03#########'), 'password' => bcrypt('password'), 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => fake()->unique()->word(), 'user_id' => $owner->id]);
        $brand = ClothBrand::create(['name' => fake()->unique()->company(), 'user_id' => $owner->id]);
        $cloth = Cloth::create(['cloth_type_id' => $type->id, 'cloth_brand_id' => $brand->id, 'price' => 100, 'sale_price' => 150, 'user_id' => $owner->id]);
        $color = ClothColor::create(['cloth_id' => $cloth->id, 'color' => 'Blue', 'length' => 10, 'average_unit_cost' => 100, 'user_id' => $owner->id]);

        return [$owner, $customer, $tailor, $color];
    }
}
