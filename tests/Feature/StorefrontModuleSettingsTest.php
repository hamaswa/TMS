<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Storefront;
use App\Models\StorefrontInquiry;
use App\Models\StorefrontOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontModuleSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_sees_only_settings_for_authorized_modules(): void
    {
        [$owner, $business] = $this->business(true, false);
        Storefront::create([
            'business_id' => $business->id,
            'display_name' => 'Tailoring Only',
            'slug' => 'tailoring-only-settings',
            'show_tailoring' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.storefront.module-settings.edit', 'tailoring'))
            ->assertOk()
            ->assertSeeText('ٹیلرنگ کی ترتیب');

        $this->actingAs($owner)
            ->get(route('admin.storefront.module-settings.edit', 'clothing'))
            ->assertNotFound();
    }

    public function test_tailoring_and_clothing_operational_settings_are_independent(): void
    {
        [$owner, $business] = $this->business(true, true);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'display_name' => 'Combined Shop',
            'slug' => 'combined-module-settings',
            'show_tailoring' => true,
            'show_clothing' => true,
            'inquiries_enabled' => true,
            'online_ordering_enabled' => true,
            'unpaid_orders_enabled' => true,
            'pickup_enabled' => true,
        ]);

        $this->actingAs($owner)->put(
            route('admin.storefront.module-settings.update', 'tailoring'),
            [
                'payment_collection_mode' => 'none',
                'pickup_enabled' => '1',
            ]
        )->assertRedirect(route('admin.storefront.module-settings.edit', 'tailoring'));

        $storefront->refresh();
        $this->assertFalse($storefront->tailoringInquiriesEnabled());
        $this->assertTrue($storefront->clothingOrderingEnabled());
        $this->assertSame(
            [StorefrontInquiry::PAYMENT_UNPAID],
            array_keys($storefront->acceptedInquiryPaymentMethods())
        );
        $this->assertContains(
            StorefrontOrder::PAYMENT_UNPAID,
            array_keys($storefront->acceptedPaymentMethods())
        );

        $this->actingAs($owner)->put(
            route('admin.storefront.module-settings.update', 'clothing'),
            [
                'accepting_enabled' => '1',
                'payment_collection_mode' => 'methods',
                'cod_enabled' => '1',
                'delivery_enabled' => '1',
            ]
        )->assertRedirect(route('admin.storefront.module-settings.edit', 'clothing'));

        $storefront->refresh();
        $this->assertFalse($storefront->tailoringInquiriesEnabled());
        $this->assertTrue($storefront->clothingOrderingEnabled());
        $this->assertSame(
            [StorefrontOrder::PAYMENT_COD],
            array_keys($storefront->acceptedPaymentMethods())
        );
    }

    public function test_pausing_tailoring_requests_preserves_existing_records(): void
    {
        [$owner, $business] = $this->business(true, false);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'display_name' => 'Preserved Tailoring Shop',
            'slug' => 'preserved-tailoring-shop',
            'show_tailoring' => true,
            'is_published' => true,
            'published_at' => now(),
            'moderation_status' => Storefront::MODERATION_ACTIVE,
        ]);
        $service = $storefront->tailoringServices()->create([
            'name' => 'Shalwar Kameez',
            'price_unit' => 'فی سوٹ',
            'is_published' => true,
        ]);
        $inquiry = $storefront->inquiries()->create([
            'customer_name' => 'Existing Customer',
            'phone' => '03001234567',
            'message' => 'Existing inquiry',
            'status' => 'new',
            'payment_method' => StorefrontInquiry::PAYMENT_UNPAID,
        ]);

        $this->actingAs($owner)->put(
            route('admin.storefront.module-settings.update', 'tailoring'),
            [
                'payment_collection_mode' => 'none',
                'pickup_enabled' => '1',
            ]
        )->assertRedirect();

        $this->assertDatabaseHas('storefront_tailoring_services', ['id' => $service->id]);
        $this->assertDatabaseHas('storefront_inquiries', ['id' => $inquiry->id]);
        $this->post(route('storefront.inquiries.store', $storefront), [
            'customer_name' => 'New Customer',
            'phone' => '03009999999',
            'message' => 'New inquiry',
        ])->assertNotFound();
    }

    public function test_manual_wallet_details_are_shared_but_enablement_is_per_module(): void
    {
        [$owner, $business] = $this->business(true, true);
        $storefront = Storefront::create([
            'business_id' => $business->id,
            'display_name' => 'Shared Account Shop',
            'slug' => 'shared-account-shop',
            'show_tailoring' => true,
            'show_clothing' => true,
        ]);

        $this->actingAs($owner)->put(
            route('admin.storefront.module-settings.update', 'tailoring'),
            [
                'accepting_enabled' => '1',
                'payment_collection_mode' => 'methods',
                'easypaisa_enabled' => '1',
                'easypaisa_account_title' => 'Shared Account Shop',
                'easypaisa_account_number' => '03001234567',
                'pickup_enabled' => '1',
            ]
        )->assertRedirect();

        $storefront->refresh();
        $this->assertTrue($storefront->tailoring_easypaisa_enabled);
        $this->assertNull($storefront->clothing_easypaisa_enabled);
        $this->assertSame('03001234567', $storefront->easypaisa_account_number);
        $this->assertContains(
            StorefrontInquiry::PAYMENT_EASYPAISA,
            array_keys($storefront->acceptedInquiryPaymentMethods())
        );
    }

    private function business(bool $tailoring, bool $clothing): array
    {
        $ownerRole = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => $tailoring,
            'clothing_access' => $clothing,
        ]);
        $owner->assignRole($ownerRole);
        $business = Business::create([
            'name' => 'Module Settings Test Business',
            'owner_user_id' => $owner->id,
            'tailoring_enabled' => $tailoring,
            'clothing_enabled' => $clothing,
            'status' => Business::STATUS_ACTIVE,
            'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);

        return [$owner->fresh(), $business];
    }
}
