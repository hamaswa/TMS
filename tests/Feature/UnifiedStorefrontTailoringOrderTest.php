<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\MeasurementTemplate;
use App\Models\Storefront;
use App\Models\StorefrontClothingListing;
use App\Models\StorefrontOrder;
use App\Models\StorefrontTailoringService;
use App\Models\StandardMeasurementProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnifiedStorefrontTailoringOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_continue_directly_to_stitching_for_the_reserved_fabric(): void
    {
        [$owner, $storefront] = $this->shop();
        [$listing, $color] = $this->fabric($owner, $storefront);

        $this->get(route('storefront.clothing.show', [$storefront, $listing]))
            ->assertOk()
            ->assertSeeText('صرف اَن سلا کپڑا')
            ->assertSeeText('کپڑا سلائی کے ساتھ');

        $response = $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $color->id,
            'quantity' => 4,
            'purchase_mode' => 'fabric_and_stitching',
        ]);

        $item = $storefront->carts()->firstOrFail()->items()->firstOrFail();
        $response->assertRedirect(route('storefront.tailoring.index', [
            $storefront,
            'cloth_item' => $item->id,
        ]));
        $this->assertSame('4.00', $item->quantity);
        $this->assertSame(10.0, (float) $color->fresh()->length);
    }

    public function test_standard_size_selector_displays_its_saved_measurement_details(): void
    {
        [, $storefront, $service] = $this->shop();

        $this->get(route('storefront.tailoring.show', [$storefront, $service]))
            ->assertOk()
            ->assertSeeText('Large B')
            ->assertSeeText('محفوظ پیمائش کی تفصیل')
            ->assertSeeText('لمبائی')
            ->assertSeeText('43 inch')
            ->assertSeeText('بازو')
            ->assertSeeText('25 inch');
    }

    public function test_stitching_only_uses_the_same_cart_customer_and_order(): void
    {
        [$owner, $storefront, $service, $template] = $this->shop();
        $standardProfile = $template->standardProfiles()->where('name', 'Large B')->firstOrFail();
        $customer = Customers::create([
            'user_id' => $owner->id,
            'name' => 'Unified Tailoring Customer',
            'phone_number1' => '03005550101',
            'mobile_pin' => Hash::make('482913'),
        ]);

        $this->get(route('storefront.tailoring.index', $storefront))
            ->assertOk()
            ->assertSeeText('کپڑا اور سلائی ایک ہی آرڈر میں');
        $this->post(route('storefront.tailoring.cart.store', [$storefront, $service]), [
            'measurement_method' => StorefrontTailoringService::MEASUREMENT_STANDARD_SIZE,
            'standard_measurement_profile_id' => $standardProfile->id,
            'quantity' => 2,
            'preferred_date' => now()->addWeek()->toDateString(),
            'notes' => 'Keep the collar simple.',
        ])->assertRedirect(route('storefront.cart.show', $storefront));
        $this->post(route('storefront.cart.customer.link', $storefront), [
            'phone' => $customer->phone_number1,
            'pin' => '482913',
        ])->assertSessionHasNoErrors();
        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_UNPAID,
        ])->assertRedirect();

        $order = StorefrontOrder::with('tailoringItems')->firstOrFail();
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertCount(0, $order->items);
        $this->assertCount(1, $order->tailoringItems);
        $this->assertSame('Large B', $order->tailoringItems->first()->standard_size);
        $this->assertSame($standardProfile->id, $order->tailoringItems->first()->standard_measurement_profile_id);
        $this->assertSame(['system.length', 'system.arms'], collect($order->tailoringItems->first()->measurement_values)->pluck('source_key')->all());
        $this->assertSame('5000.00', $order->subtotal);
        $this->assertDatabaseCount('storefront_inquiries', 0);
        $this->actingAs($owner)->get(route('admin.storefront.orders.index'))
            ->assertOk()->assertSeeText('Suit Stitching')->assertSeeText('Keep the collar simple.');
    }

    public function test_fabric_and_custom_tailoring_are_linked_in_one_order(): void
    {
        [$owner, $storefront, $service, $template] = $this->shop();
        [$listing, $color] = $this->fabric($owner, $storefront);
        $customer = Customers::create([
            'user_id' => $owner->id,
            'name' => 'Fabric Plus Stitching Customer',
            'phone_number1' => '03005550102',
            'mobile_pin' => Hash::make('482913'),
        ]);
        $this->post(route('storefront.cart.store', [$storefront, $listing]), [
            'cloth_color_id' => $color->id,
            'quantity' => 4,
        ])->assertSessionHasNoErrors();
        $clothCartItemId = $storefront->carts()->firstOrFail()->items()->value('id');
        $this->post(route('storefront.tailoring.cart.store', [$storefront, $service]), [
            'measurement_method' => StorefrontTailoringService::MEASUREMENT_CUSTOM,
            'quantity' => 1,
            'clothing_cart_item_id' => $clothCartItemId,
            'measurements' => ['system' => ['length' => '42', 'arms' => '24']],
        ])->assertRedirect(route('storefront.cart.show', $storefront));
        $this->post(route('storefront.cart.customer.link', $storefront), [
            'phone' => $customer->phone_number1,
            'pin' => '482913',
        ]);
        $this->post(route('storefront.checkout.store', $storefront), [
            'fulfillment_method' => 'pickup',
            'payment_method' => StorefrontOrder::PAYMENT_UNPAID,
        ])->assertRedirect();

        $order = StorefrontOrder::with(['items', 'tailoringItems.clothingItem'])->firstOrFail();
        $tailoring = $order->tailoringItems->first();
        $this->assertCount(1, $order->items);
        $this->assertSame($order->items->first()->id, $tailoring->clothing_order_item_id);
        $this->assertSame($template->id, $tailoring->measurement_template_id);
        $this->assertSame(['system.length', 'system.arms'], collect($tailoring->measurement_values)->pluck('source_key')->all());
        $this->assertSame('8300.00', $order->subtotal);
        $this->assertSame(6.0, (float) $color->fresh()->length);
    }

    private function shop(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => true,
            'clothing_access' => true,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Unified Shop',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'slug' => 'unified-shop-'.$business->id,
            'display_name' => 'Unified Shop',
            'show_clothing' => true,
            'show_tailoring' => true,
            'inquiries_enabled' => true,
            'pickup_enabled' => true,
            'unpaid_orders_enabled' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);
        $template = MeasurementTemplate::create([
            'user_id' => $owner->id,
            'name' => 'Shalwar Kameez',
            'system_fields' => ['length', 'arms'],
            'custom_field_ids' => [],
            'is_default' => true,
            'is_active' => true,
        ]);
        StandardMeasurementProfile::create([
            'user_id' => $owner->id,
            'measurement_template_id' => $template->id,
            'name' => 'Large B',
            'measurement_values' => [
                ['source_key' => 'system.length', 'label' => 'لمبائی', 'value' => '43', 'unit' => 'inch'],
                ['source_key' => 'system.arms', 'label' => 'بازو', 'value' => '25', 'unit' => 'inch'],
            ],
            'is_active' => true,
        ]);
        $service = $storefront->tailoringServices()->create([
            'name' => 'Suit Stitching',
            'price_from' => 2500,
            'price_unit' => 'per suit',
            'estimated_days' => 7,
            'measurement_template_id' => $template->id,
            'measurement_methods' => [
                StorefrontTailoringService::MEASUREMENT_STANDARD_SIZE,
                StorefrontTailoringService::MEASUREMENT_CUSTOM,
            ],
            'is_published' => true,
            'is_available' => true,
            'accepts_inquiries' => true,
        ]);

        return [$owner->fresh(), $storefront, $service, $template];
    }

    private function fabric(User $owner, Storefront $storefront): array
    {
        $brand = ClothBrand::create(['name' => 'Unified Brand', 'user_id' => $owner->id]);
        $type = ClothType::create(['name' => 'Wash and Wear', 'user_id' => $owner->id]);
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
            'color' => 'Navy',
            'length' => 10,
            'average_unit_cost' => 1000,
        ]);
        $listing = StorefrontClothingListing::create([
            'storefront_id' => $storefront->id,
            'cloth_id' => $cloth->id,
            'public_name' => 'Premium Navy',
            'is_published' => true,
        ]);

        return [$listing, $color];
    }
}
