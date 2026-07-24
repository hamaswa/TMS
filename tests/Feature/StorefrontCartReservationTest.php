<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\Storefront;
use App\Models\StorefrontCart;
use App\Models\StorefrontCartItem;
use App\Models\StorefrontClothingListing;
use App\Models\StorefrontOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontCartReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_reserves_without_changing_physical_stock_or_ledger(): void
    {
        [, $storefront, $listing, $color] = $this->catalog();

        $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $color->id,
            'quantity' => 3,
        ])->assertRedirect(route('storefront.cart.show', $storefront));

        $item = StorefrontCartItem::firstOrFail();
        $this->assertSame('3.00', $item->quantity);
        $this->assertSame(10.0, (float) $color->fresh()->length);
        $this->assertSame(7.0, $color->fresh()->reservableLength());
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('online_orders', 0);
        $this->get(route('storefront.cart.show', $storefront))
            ->assertOk()
            ->assertSeeText('4,350.00 روپے')
            ->assertSeeText('30 منٹ');
    }

    public function test_separate_cart_cannot_reserve_more_than_remaining_stock(): void
    {
        [, $storefront, $listing, $color] = $this->catalog();
        $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $color->id,
            'quantity' => 6,
        ])->assertSessionHasNoErrors();
        $this->app['session']->flush();

        $this->from(route('storefront.clothing.show', [$storefront, $listing]))
            ->post(route('storefront.cart.store', [$storefront, $listing]), [
                'cloth_color_id' => $color->id,
                'quantity' => 5,
            ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('storefront_cart_items', 1);
        $this->assertSame(4.0, $color->fresh()->reservableLength());
    }

    public function test_expired_and_removed_items_release_reservable_quantity(): void
    {
        [, $storefront, $listing, $color] = $this->catalog();
        $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $color->id,
            'quantity' => 4,
        ]);
        $item = StorefrontCartItem::firstOrFail();
        $this->assertSame(6.0, $color->fresh()->reservableLength());

        $item->cart->update(['expires_at' => now()->subMinute()]);
        $this->assertSame(10.0, $color->fresh()->reservableLength());
        $item->cart->update(['expires_at' => now()->addHour()]);
        $item->update(['reserved_until' => now()->subMinute()]);
        $this->assertSame(10.0, $color->fresh()->reservableLength());
        $this->get(route('storefront.cart.show', $storefront))->assertOk();
        $this->assertDatabaseCount('storefront_cart_items', 0);

        $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $color->id,
            'quantity' => 2,
        ]);
        $item = StorefrontCartItem::latest('id')->firstOrFail();
        $this->delete(route('storefront.cart.destroy', [$storefront, $item->id]))->assertRedirect();
        $this->assertSame(10.0, $color->fresh()->reservableLength());
    }

    public function test_existing_customer_links_by_shop_phone_and_pin_without_creating_order(): void
    {
        [$owner, $storefront, $listing, $color] = $this->catalog();
        $customer = Customers::create([
            'user_id' => $owner->id,
            'name' => 'محمد اسلم',
            'phone_number1' => '03007771111',
            'mobile_pin' => Hash::make('482913'),
        ]);
        $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $color->id,
            'quantity' => 2,
        ]);
        $expectedExpiry = StorefrontCart::firstOrFail()->expires_at->toDateTimeString();
        $this->post(route('storefront.cart.customer.link', $storefront), [
            'phone' => '+92 300 7771111',
            'pin' => '482913',
        ])->assertRedirect(route('storefront.cart.show', $storefront));

        $this->assertSame($customer->id, StorefrontCart::firstOrFail()->customer_id);
        $this->assertSame($expectedExpiry, StorefrontCart::firstOrFail()->expires_at->toDateTimeString());
        $this->assertTrue(StorefrontCart::firstOrFail()->expires_at->isFuture());
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('online_orders', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->get(route('storefront.cart.show', $storefront))
            ->assertSeeText('محمد اسلم')
            ->assertSeeText('موجودہ متحد گاہک ریکارڈ منسلک ہے');
    }

    public function test_customer_from_another_shop_cannot_link_to_cart(): void
    {
        [, $storefront, $listing, $color] = $this->catalog();
        [$otherOwner] = $this->catalog('other-catalog');
        Customers::create([
            'user_id' => $otherOwner->id,
            'name' => 'دوسری دکان کا گاہک',
            'phone_number1' => '03009999999',
            'mobile_pin' => Hash::make('123456'),
        ]);
        $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $color->id,
            'quantity' => 1,
        ]);
        $this->post(route('storefront.cart.customer.link', $storefront), [
            'phone' => '03009999999',
            'pin' => '123456',
        ])->assertSessionHasErrors('phone');

        $this->assertNull(StorefrontCart::where('storefront_id', $storefront->id)->firstOrFail()->customer_id);
    }

    public function test_new_customer_can_securely_register_and_link_the_unified_record(): void
    {
        [$owner, $storefront, $listing, $color] = $this->catalog();
        $this->withServerVariables(['REMOTE_ADDR' => '10.50.0.11'])
            ->post(route('storefront.cart.store', [$storefront, $listing]), [
                'cloth_color_id' => $color->id,
                'quantity' => 2,
            ])->assertSessionHasNoErrors();

        $this->withServerVariables(['REMOTE_ADDR' => '10.50.0.11'])
            ->post(route('storefront.cart.customer.register', $storefront), [
                'name' => 'Ayesha Siddiqua',
                'phone' => '+92 300 555 1122',
                'pin' => '482913',
                'pin_confirmation' => '482913',
            ])->assertRedirect(route('storefront.cart.show', $storefront));

        $customer = Customers::where('user_id', $owner->id)->firstOrFail();
        $this->assertSame('Ayesha Siddiqua', $customer->name);
        $this->assertSame('+923005551122', $customer->phone_number1_normalized);
        $this->assertTrue(Hash::check('482913', $customer->mobile_pin));
        $this->assertNotNull($customer->self_registered_at);
        $this->assertNull($customer->phone_verified_at);
        $this->assertSame($customer->id, StorefrontCart::firstOrFail()->customer_id);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('storefront_orders', 0);
        $this->assertDatabaseCount('transactions', 0);
        $this->get(route('storefront.cart.show', $storefront))
            ->assertOk()
            ->assertSeeText('Ayesha Siddiqua');
    }

    public function test_registration_cannot_duplicate_an_existing_phone_or_use_a_weak_pin(): void
    {
        [$owner, $storefront, $listing, $color] = $this->catalog();
        Customers::create([
            'user_id' => $owner->id,
            'name' => 'Existing Customer',
            'phone_number1' => '03005551122',
            'mobile_pin' => Hash::make('482913'),
        ]);
        $this->withServerVariables(['REMOTE_ADDR' => '10.50.0.12'])
            ->post(route('storefront.cart.store', [$storefront, $listing]), [
                'cloth_color_id' => $color->id,
                'quantity' => 1,
            ]);
        $this->get(route('public.locale.update', [
            'locale' => 'en',
            'redirect' => route('storefront.cart.show', $storefront, false),
        ]))->assertRedirect();

        $this->withServerVariables(['REMOTE_ADDR' => '10.50.0.12'])
            ->post(route('storefront.cart.customer.register', $storefront), [
                'name' => 'Duplicate Customer',
                'phone' => '+92 300 555 1122',
                'pin' => '482913',
                'pin_confirmation' => '482913',
            ])->assertSessionHasErrors([
                'phone' => 'A customer record already exists for this phone. Use the existing-customer form or contact the shop.',
            ]);
        $this->withServerVariables(['REMOTE_ADDR' => '10.50.0.12'])
            ->post(route('storefront.cart.customer.register', $storefront), [
                'name' => 'Weak PIN Customer',
                'phone' => '03005552233',
                'pin' => '123456',
                'pin_confirmation' => '123456',
            ])->assertSessionHasErrors('pin');

        $this->assertDatabaseCount('customers', 1);
        $this->assertNull(StorefrontCart::firstOrFail()->customer_id);
    }

    public function test_self_registered_customer_can_checkout_to_the_unified_balance(): void
    {
        [$owner, $storefront, $listing, $color] = $this->catalog();
        $this->withServerVariables(['REMOTE_ADDR' => '10.50.0.14'])
            ->post(route('storefront.cart.store', [$storefront, $listing]), [
                'cloth_color_id' => $color->id,
                'quantity' => 2,
            ]);
        $this->withServerVariables(['REMOTE_ADDR' => '10.50.0.14'])
            ->post(route('storefront.cart.customer.register', $storefront), [
                'name' => 'Bilal Ahmed',
                'phone' => '03006667788',
                'pin' => '739281',
                'pin_confirmation' => '739281',
            ])->assertSessionHasNoErrors();

        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.50.0.14'])
            ->post(route('storefront.checkout.store', $storefront), [
                'fulfillment_method' => 'pickup',
                'payment_method' => 'unpaid',
            ]);

        $order = StorefrontOrder::firstOrFail();
        $customer = Customers::firstOrFail();
        $response->assertRedirect(route('storefront.orders.show', [$storefront, $order->reference]));
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame(8.0, (float) $color->fresh()->length);
        $this->assertDatabaseHas('transactions', [
            'id' => $order->transaction_id,
            'customerId' => $customer->id,
            'userId' => $owner->id,
            'Order_type' => 'Sale',
            'remainingBalance' => 2900,
            'recivedPayment' => 0,
        ]);
    }

    public function test_public_registration_is_rate_limited(): void
    {
        [, $storefront, $listing, $color] = $this->catalog();
        $this->withServerVariables(['REMOTE_ADDR' => '10.50.0.99'])
            ->post(route('storefront.cart.store', [$storefront, $listing]), [
                'cloth_color_id' => $color->id,
                'quantity' => 1,
            ]);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.50.0.13'])
                ->post(route('storefront.cart.customer.register', $storefront), [])
                ->assertSessionHasErrors();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.50.0.13'])
            ->post(route('storefront.cart.customer.register', $storefront), [])
            ->assertTooManyRequests();
    }

    private function catalog(string $slug = 'reservation-shop'): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => true,
            'clothing_access' => true,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Reservation Business',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'slug' => $slug.'-'.$business->id,
            'display_name' => 'ریزرویشن دکان',
            'show_clothing' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);
        $brand = ClothBrand::create(['name' => 'صدیقی فیبرکس', 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'واش اینڈ ویئر', 'user_id' => $owner->id]);
        $cloth = Cloth::create([
            'cloth_brand_id' => $brand->id,
            'cloth_type_id' => $type->id,
            'user_id' => $owner->id,
            'price' => 1000,
            'sale_price' => 1450,
        ]);
        $color = ClothColor::create([
            'cloth_id' => $cloth->id,
            'user_id' => $owner->id,
            'color' => 'نیلا',
            'length' => 10,
            'average_unit_cost' => 1000,
        ]);
        $listing = StorefrontClothingListing::create([
            'storefront_id' => $storefront->id,
            'cloth_id' => $cloth->id,
            'public_name' => 'نیلا پریمیم کپڑا',
            'is_published' => true,
        ]);

        return [$owner->fresh(), $storefront, $listing, $color];
    }
}
