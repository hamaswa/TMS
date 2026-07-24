<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Storefront;
use App\Models\StorefrontInquiry;
use App\Models\StorefrontTailoringService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontTailoringInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_publish_tailoring_service_and_receive_safe_inquiry(): void
    {
        [$owner, , $storefront] = $this->business();

        $this->actingAs($owner)->post(route('admin.storefront.tailoring.store'), [
            'name' => 'پریمیم مردانہ شلوار قمیض سلائی',
            'description' => 'ماہرانہ کٹنگ، نفیس سلائی اور فٹنگ ٹرائل شامل ہے۔',
            'price_from' => 1800,
            'price_unit' => 'فی سوٹ',
            'estimated_days' => 7,
            'is_featured' => '1',
            'is_published' => '1',
        ])->assertRedirect(route('admin.storefront.tailoring.services'));

        $service = StorefrontTailoringService::firstOrFail();
        $this->get(route('storefront.tailoring.index', $storefront))
            ->assertOk()
            ->assertSeeText('پریمیم مردانہ شلوار قمیض سلائی')
            ->assertSeeText('Rs 1,800.00');
        $this->get(route('storefront.tailoring.show', [$storefront, $service]))
            ->assertOk()
            ->assertSeeText('ماہرانہ کٹنگ، نفیس سلائی اور فٹنگ ٹرائل شامل ہے۔');

        $this->post(route('storefront.inquiries.store', $storefront), [
            'tailoring_service_id' => $service->id,
            'customer_name' => 'محمد حمزہ',
            'phone' => '03001234567',
            'email' => 'hamza@example.test',
            'city' => 'راولپنڈی',
            'preferred_date' => now()->addDays(10)->toDateString(),
            'message' => 'عید سے پہلے دو سوٹ تیار کروانے ہیں۔',
            'payment_method' => StorefrontInquiry::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03001234567',
            'payment_reference' => 'EP-TAILOR-1001',
        ])->assertRedirect(route('storefront.tailoring.index', $storefront))
            ->assertSessionHas('inquiry_success');

        $inquiry = StorefrontInquiry::firstOrFail();
        $this->assertSame(StorefrontInquiry::STATUS_NEW, $inquiry->status);
        $this->assertSame($service->id, $inquiry->tailoring_service_id);
        $this->assertSame(StorefrontInquiry::PAYMENT_EASYPAISA, $inquiry->payment_method);
        $this->assertSame('EP-TAILOR-1001', $inquiry->payment_reference);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_easypaisa_tailoring_preference_requires_manual_reference_details(): void
    {
        [, , $storefront] = $this->business();

        $this->post(route('storefront.inquiries.store', $storefront), [
            'customer_name' => 'حمزہ سعید',
            'phone' => '03005551111',
            'payment_method' => StorefrontInquiry::PAYMENT_EASYPAISA,
        ])->assertSessionHasErrors(['payment_sender_phone', 'payment_reference']);

        $this->assertDatabaseCount('storefront_inquiries', 0);
    }

    public function test_inquiries_can_be_disabled_and_private_services_stay_hidden(): void
    {
        [, , $storefront] = $this->business();
        $storefront->update(['inquiries_enabled' => false]);
        $service = $storefront->tailoringServices()->create([
            'name' => 'مسودہ خدمت',
            'price_unit' => 'فی سوٹ',
            'is_published' => false,
        ]);

        $this->get(route('storefront.tailoring.index', $storefront))->assertDontSeeText('مسودہ خدمت');
        $this->get(route('storefront.tailoring.show', [$storefront, $service]))->assertNotFound();
        $this->post(route('storefront.inquiries.store', $storefront), [
            'customer_name' => 'علی',
            'phone' => '03001111111',
        ])->assertNotFound();
        $this->assertDatabaseCount('storefront_inquiries', 0);
    }

    public function test_client_can_manage_only_own_inquiry_and_status_timestamps(): void
    {
        [$owner, , $storefront] = $this->business();
        [$otherOwner, , $otherStorefront] = $this->business('other-tailor');
        $inquiry = $storefront->inquiries()->create([
            'customer_name' => 'احمد',
            'phone' => '03002222222',
            'status' => StorefrontInquiry::STATUS_NEW,
        ]);

        $this->actingAs($otherOwner)->patch(route('admin.storefront.inquiries.update', $inquiry), [
            'status' => StorefrontInquiry::STATUS_CONTACTED,
        ])->assertNotFound();

        $this->actingAs($owner)->patch(route('admin.storefront.inquiries.update', $inquiry), [
            'status' => StorefrontInquiry::STATUS_CONTACTED,
            'admin_notes' => 'فون پر قیمت سمجھا دی گئی۔',
        ])->assertRedirect(route('admin.storefront.inquiries.index'));
        $inquiry->refresh();
        $this->assertNotNull($inquiry->contacted_at);
        $this->assertNull($inquiry->closed_at);

        $this->actingAs($owner)->patch(route('admin.storefront.inquiries.update', $inquiry), [
            'status' => StorefrontInquiry::STATUS_CLOSED,
        ])->assertRedirect(route('admin.storefront.inquiries.index'));
        $this->assertNotNull($inquiry->fresh()->closed_at);
        $this->assertDatabaseCount('storefront_inquiries', 1);
        $this->assertDatabaseMissing('storefront_inquiries', ['storefront_id' => $otherStorefront->id]);
    }

    private function business(string $slug = 'tailor-market'): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => true,
            'clothing_access' => true,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Tailoring Marketplace',
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
            'display_name' => 'عوامی ٹیلرنگ دکان',
            'show_clothing' => true,
            'show_tailoring' => true,
            'inquiries_enabled' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);

        return [$owner->fresh(), $business, $storefront];
    }
}
