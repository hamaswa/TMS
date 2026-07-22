<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\Order;
use App\Models\Tailor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_owner_cannot_edit_another_shops_cloth_brand(): void
    {
        $owner = $this->userWithRole('shop_owner');
        $otherOwner = $this->userWithRole('shop_owner');
        $brand = ClothBrand::create([
            'name' => 'Other Brand',
            'brand_logo' => 'BrandImages/example.png',
            'brand_slug' => 'other-brand',
            'user_id' => $otherOwner->id,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.clothbrand.edit', $brand->id))
            ->assertNotFound();
    }

    public function test_new_tailor_password_is_hashed_and_not_rendered_in_list(): void
    {
        $owner = $this->userWithRole('shop_owner');

        $this->actingAs($owner)->post(route('admin.Tailor.store'), [
            'name' => 'Secure Tailor',
            'contact' => '03001234567',
            'password' => 'secret123',
        ])->assertRedirect('admin/Tailor');

        $tailor = Tailor::where('user_id', $owner->id)->firstOrFail();
        $this->assertNotSame('secret123', $tailor->password);
        $this->assertTrue(Hash::check('secret123', $tailor->password));

        $this->actingAs($owner)
            ->get(route('admin.Tailor.index'))
            ->assertOk()
            ->assertDontSee($tailor->password);
    }

    public function test_direct_payment_rejects_another_shops_customer(): void
    {
        $owner = $this->userWithRole('shop_owner');
        $otherOwner = $this->userWithRole('shop_owner');
        $customer = Customers::create([
            'name' => 'Other Customer',
            'phone_number1' => '03009999999',
            'user_id' => $otherOwner->id,
        ]);

        $this->actingAs($owner)->post(route('admin.DirectPayment'), [
            'customer_id' => $customer->id,
            'DirectPayment' => 100,
            'comment' => 'test',
        ])->assertNotFound();
    }

    public function test_removing_cart_item_restores_stock_and_is_owner_scoped(): void
    {
        $customerUser = $this->userWithRole('user');
        $otherCustomer = $this->userWithRole('user');
        $shopOwner = $this->userWithRole('shop_owner');
        $type = ClothType::create(['name' => 'Cotton', 'user_id' => $shopOwner->id]);
        $brand = ClothBrand::create(['name' => 'Brand', 'user_id' => $shopOwner->id]);
        $cloth = Cloth::create([
            'cloth_type_id' => $type->id,
            'cloth_brand_id' => $brand->id,
            'price' => 500,
            'sale_price' => 700,
            'user_id' => $shopOwner->id,
        ]);
        $color = ClothColor::create([
            'cloth_id' => $cloth->id,
            'color' => 'Blue',
            'length' => 8,
            'user_id' => $shopOwner->id,
        ]);
        $cart = Cart::create([
            'user_id' => $customerUser->id,
            'cloth_id' => $cloth->id,
            'length' => 2,
            'price' => 700,
            'color' => 'Blue',
            'shop_name' => 'Test Shop',
        ]);

        $this->actingAs($otherCustomer)
            ->delete(route('user.cart.delete', ['slug' => 'test-shop', 'id' => $cart->id]))
            ->assertNotFound();
        $this->assertDatabaseHas('carts', ['id' => $cart->id]);

        $this->actingAs($customerUser)
            ->delete(route('user.cart.delete', ['slug' => 'test-shop', 'id' => $cart->id]))
            ->assertRedirect(route('user.cart.show', ['slug' => 'test-shop']));
        $this->assertEquals(10.0, (float) $color->fresh()->length);
        $this->assertDatabaseHas('inventory_movements', [
            'cloth_color_id' => $color->id,
            'movement_type' => 'cart_release',
            'quantity' => 2,
            'balance_after' => 10,
        ]);
    }

    public function test_customer_api_rejects_unauthenticated_data_access(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized();
        $this->getJson('/api/transactions')->assertUnauthorized();
        $this->getJson('/api/notifications')->assertUnauthorized();
        $this->postJson('/api/mark-read')->assertUnauthorized();
    }

    public function test_customer_api_only_returns_the_authenticated_customers_orders(): void
    {
        $shopOwner = $this->userWithRole('shop_owner');
        $customer = Customers::create([
            'name' => 'API Customer',
            'phone_number1' => '03001111111',
            'user_id' => $shopOwner->id,
        ]);
        $otherCustomer = Customers::create([
            'name' => 'Other API Customer',
            'phone_number1' => '03002222222',
            'user_id' => $shopOwner->id,
        ]);
        $ownOrder = Order::create([
            'customerId' => $customer->id,
            'sub_customer' => $customer->id,
            'suitQuantity' => 1,
            'totalPayment' => 1000,
            'userId' => $shopOwner->id,
        ]);
        Order::create([
            'customerId' => $otherCustomer->id,
            'sub_customer' => $otherCustomer->id,
            'suitQuantity' => 1,
            'totalPayment' => 2000,
            'userId' => $shopOwner->id,
        ]);

        Sanctum::actingAs($customer);

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.id', $ownOrder->id);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
