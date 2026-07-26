<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Storefront;
use App\Models\StorefrontInquiry;
use App\Models\StorefrontTailoringService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'measurement_method' => StorefrontTailoringService::MEASUREMENT_SHOP_VISIT,
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

    public function test_tailoring_payment_evidence_is_private_and_tenant_scoped(): void
    {
        Storage::fake('local');
        [$owner, , $storefront] = $this->business();

        $this->post(route('storefront.inquiries.store', $storefront), [
            'customer_name' => 'مریم اقبال',
            'phone' => '03001112222',
            'payment_method' => StorefrontInquiry::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03001112222',
            'payment_reference' => 'EP-TAILOR-PRIVATE-1',
            'payment_evidence' => UploadedFile::fake()->image('tailoring-receipt.png'),
        ])->assertRedirect(route('storefront.tailoring.index', $storefront));

        $inquiry = StorefrontInquiry::firstOrFail();
        Storage::disk('local')->assertExists($inquiry->payment_evidence_path);
        $this->assertSame('tailoring-receipt.png', $inquiry->payment_evidence_original_name);
        $this->actingAs($owner)
            ->get(route('admin.storefront.inquiries.payment-evidence', $inquiry))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        [$otherOwner] = $this->business('other-private-tailor');
        $this->actingAs($otherOwner)
            ->get(route('admin.storefront.inquiries.payment-evidence', $inquiry))
            ->assertNotFound();
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

    public function test_client_can_pause_one_service_without_hiding_its_public_details_or_history(): void
    {
        [$owner, , $storefront] = $this->business();
        $service = $storefront->tailoringServices()->create([
            'name' => 'Premium Waistcoat',
            'price_unit' => 'فی لباس',
            'is_published' => true,
            'is_available' => true,
            'accepts_inquiries' => true,
        ]);
        $inquiry = $storefront->inquiries()->create([
            'tailoring_service_id' => $service->id,
            'customer_name' => 'Existing Customer',
            'phone' => '03001234567',
            'status' => StorefrontInquiry::STATUS_NEW,
        ]);

        $this->actingAs($owner)->put(route('admin.storefront.tailoring.update', $service), [
            'service_controls_present' => '1',
            'name' => 'Premium Waistcoat',
            'price_unit' => 'فی لباس',
            'is_published' => '1',
            'measurement_methods' => [StorefrontTailoringService::MEASUREMENT_SHOP_VISIT],
        ])->assertRedirect(route('admin.storefront.tailoring.services'));

        $service->refresh();
        $this->assertFalse($service->is_available);
        $this->assertFalse($service->accepts_inquiries);
        $this->get(route('storefront.tailoring.show', [$storefront, $service]))
            ->assertOk()
            ->assertSeeText('عارضی طور پر دستیاب نہیں')
            ->assertDontSeeText('اس خدمت کے لیے درخواست بھیجیں');
        $this->post(route('storefront.inquiries.store', $storefront), [
            'tailoring_service_id' => $service->id,
            'customer_name' => 'New Customer',
            'phone' => '03009999999',
            'measurement_method' => StorefrontTailoringService::MEASUREMENT_SHOP_VISIT,
            'payment_method' => StorefrontInquiry::PAYMENT_UNPAID,
        ])->assertSessionHasErrors('tailoring_service_id');
        $this->assertDatabaseHas('storefront_inquiries', ['id' => $inquiry->id]);
    }

    public function test_service_deposit_and_measurement_policy_are_snapshotted_on_inquiry(): void
    {
        [, , $storefront] = $this->business();
        $service = $storefront->tailoringServices()->create([
            'name' => 'Wedding Sherwani',
            'price_from' => 25000,
            'price_unit' => 'فی لباس',
            'deposit_type' => StorefrontTailoringService::DEPOSIT_FIXED,
            'deposit_value' => 5000,
            'measurement_methods' => [StorefrontTailoringService::MEASUREMENT_HOME_VISIT],
            'is_published' => true,
            'is_available' => true,
            'accepts_inquiries' => true,
        ]);

        $this->post(route('storefront.inquiries.store', $storefront), [
            'tailoring_service_id' => $service->id,
            'customer_name' => 'Usman Tariq',
            'phone' => '03005556666',
            'measurement_method' => StorefrontTailoringService::MEASUREMENT_HOME_VISIT,
            'payment_method' => StorefrontInquiry::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03005556666',
            'payment_reference' => 'EP-SHERWANI-1',
        ])->assertRedirect(route('storefront.tailoring.index', $storefront));

        $inquiry = StorefrontInquiry::firstOrFail();
        $this->assertSame(StorefrontTailoringService::MEASUREMENT_HOME_VISIT, $inquiry->measurement_method);
        $this->assertSame(StorefrontTailoringService::DEPOSIT_FIXED, $inquiry->service_deposit_type);
        $this->assertSame('5000.00', $inquiry->service_deposit_value);
        $this->assertSame('5000.00', $inquiry->service_deposit_amount);

        $service->update(['deposit_value' => 7000]);
        $this->assertSame('5000.00', $inquiry->fresh()->service_deposit_amount);
    }

    public function test_weekly_service_capacity_rejects_overbooking_without_closing_existing_records(): void
    {
        [, , $storefront] = $this->business();
        $preferredDate = now()->addWeek()->startOfWeek()->addDay();
        $service = $storefront->tailoringServices()->create([
            'name' => 'Express Stitching',
            'price_unit' => 'فی سوٹ',
            'measurement_methods' => [StorefrontTailoringService::MEASUREMENT_SHOP_VISIT],
            'weekly_booking_limit' => 1,
            'is_published' => true,
            'is_available' => true,
            'accepts_inquiries' => true,
        ]);
        $storefront->inquiries()->create([
            'tailoring_service_id' => $service->id,
            'customer_name' => 'First Booking',
            'phone' => '03001112222',
            'preferred_date' => $preferredDate,
            'measurement_method' => StorefrontTailoringService::MEASUREMENT_SHOP_VISIT,
            'payment_method' => StorefrontInquiry::PAYMENT_UNPAID,
            'status' => StorefrontInquiry::STATUS_NEW,
        ]);

        $this->post(route('storefront.inquiries.store', $storefront), [
            'tailoring_service_id' => $service->id,
            'customer_name' => 'Second Booking',
            'phone' => '03003334444',
            'preferred_date' => $preferredDate->copy()->addDay()->toDateString(),
            'measurement_method' => StorefrontTailoringService::MEASUREMENT_SHOP_VISIT,
            'payment_method' => StorefrontInquiry::PAYMENT_UNPAID,
        ])->assertSessionHasErrors('preferred_date');

        $this->assertDatabaseCount('storefront_inquiries', 1);
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
