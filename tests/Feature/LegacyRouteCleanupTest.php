<?php

namespace Tests\Feature;

use App\Models\Customers;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Tailor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LegacyRouteCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_and_blank_legacy_routes_are_not_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertNull($routes->getByName('admin.payment-received'));
        $this->assertNull($routes->getByName('admin.tailor-rates.edit'));
    }

    public function test_order_print_redirects_with_an_urdu_message_when_shop_setup_is_inactive(): void
    {
        [$owner, $order] = $this->orderWithoutActiveSetting();

        $this->actingAs($owner)->from(route('admin.tailor-jobs.index'))
            ->get(route('admin.order-print', $order))
            ->assertRedirect(route('admin.tailor-jobs.index'))
            ->assertSessionHas('error', 'پرنٹ کرنے سے پہلے دکان کی فعال ترتیب منتخب کریں۔');
    }

    public function test_sale_print_redirects_with_an_urdu_message_when_shop_setup_is_inactive(): void
    {
        [$owner] = $this->orderWithoutActiveSetting();
        $sale = Sale::create(['user_id' => $owner->id, 'customer_name' => 'Walk-in customer']);

        $this->actingAs($owner)->from(route('admin.sale.index'))
            ->get(route('admin.sale-print', $sale))
            ->assertRedirect(route('admin.sale.index'))
            ->assertSessionHas('error', 'پرنٹ کرنے سے پہلے دکان کی فعال ترتیب منتخب کریں۔');
    }

    private function orderWithoutActiveSetting(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['tailoring_access' => true]);
        $owner->assignRole($role);
        $customer = Customers::create([
            'name' => 'Test customer',
            'phone_number1' => '03001234567',
            'user_id' => $owner->id,
        ]);
        $tailor = Tailor::create([
            'name' => 'Test tailor',
            'phone_number1' => '03007654321',
            'user_id' => $owner->id,
        ]);
        $order = Order::create([
            'customerId' => $customer->id,
            'sub_customer' => $customer->id,
            'suitQuantity' => 1,
            'totalPayment' => 1000,
            'userId' => $owner->id,
            'tailorId' => $tailor->id,
        ]);

        return [$owner, $order];
    }
}
