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
            ->assertSeeText('1,800.00 روپے');
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
        $this->assertSame(StorefrontInquiry::VERIFICATION_PENDING, $inquiry->payment_verification_status);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_client_can_verify_or_reject_easypaisa_inquiry_reference_with_an_audit_actor(): void
    {
        [$owner, , $storefront] = $this->business();
        $inquiry = $storefront->inquiries()->create([
            'customer_name' => 'Payment Verification Customer',
            'phone' => '03002223333',
            'payment_method' => StorefrontInquiry::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03002223333',
            'payment_reference' => 'EP-INQUIRY-1001',
            'payment_verification_status' => StorefrontInquiry::VERIFICATION_PENDING,
            'status' => StorefrontInquiry::STATUS_NEW,
        ]);

        $this->actingAs($owner)->patch(route('admin.storefront.inquiries.payment-verification', $inquiry), [
            'decision' => StorefrontInquiry::VERIFICATION_REJECTED,
        ])->assertSessionHasErrors('payment_verification_notes');
        $this->actingAs($owner)->patch(route('admin.storefront.inquiries.payment-verification', $inquiry), [
            'decision' => StorefrontInquiry::VERIFICATION_VERIFIED,
            'payment_verification_notes' => 'Matched in merchant account',
        ])->assertRedirect(route('admin.storefront.inquiries.index'));

        $inquiry->refresh();
        $this->assertSame(StorefrontInquiry::VERIFICATION_VERIFIED, $inquiry->payment_verification_status);
        $this->assertSame($owner->id, $inquiry->payment_verified_by_user_id);
        $this->assertNotNull($inquiry->payment_verified_at);
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

    public function test_tailoring_inquiry_supports_client_enabled_raast_without_sender_mobile(): void
    {
        [, , $storefront] = $this->business();
        $storefront->update([
            'easypaisa_enabled' => false,
            'jazzcash_enabled' => false,
            'bank_transfer_enabled' => false,
            'raast_enabled' => true,
            'raast_account_title' => 'Tailoring Marketplace',
            'raast_id' => '03001234567',
        ]);

        $this->get(route('storefront.tailoring.index', $storefront))
            ->assertOk()
            ->assertSeeText('راست')
            ->assertSeeText('Tailoring Marketplace')
            ->assertSeeText('03001234567')
            ->assertDontSeeText('جاز کیش');
        $this->post(route('storefront.inquiries.store', $storefront), [
            'customer_name' => 'عائشہ خان',
            'phone' => '03121112222',
            'payment_method' => StorefrontInquiry::PAYMENT_RAAST,
            'payment_reference' => 'RAAST-20260724-7788',
        ])->assertRedirect(route('storefront.tailoring.index', $storefront))
            ->assertSessionHas('inquiry_success');

        $inquiry = StorefrontInquiry::firstOrFail();
        $this->assertSame(StorefrontInquiry::PAYMENT_RAAST, $inquiry->payment_method);
        $this->assertNull($inquiry->payment_sender_phone);
        $this->assertSame(StorefrontInquiry::VERIFICATION_PENDING, $inquiry->payment_verification_status);
    }

    public function test_tailoring_inquiry_rejects_a_manual_method_disabled_by_client(): void
    {
        [, , $storefront] = $this->business();
        $storefront->update(['bank_transfer_enabled' => false]);

        $this->post(route('storefront.inquiries.store', $storefront), [
            'customer_name' => 'Bilal Ahmed',
            'phone' => '03001110000',
            'payment_method' => StorefrontInquiry::PAYMENT_BANK_TRANSFER,
            'payment_reference' => 'BANK-DISABLED-1',
        ])->assertSessionHasErrors('payment_method');

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
