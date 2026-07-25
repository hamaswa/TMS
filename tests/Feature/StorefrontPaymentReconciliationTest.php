<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customers;
use App\Models\Storefront;
use App\Models\StorefrontOrder;
use App\Models\StorefrontPaymentReconciliation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontPaymentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_report_groups_only_verified_manual_payments(): void
    {
        [$owner, $storefront, $customer] = $this->business('reconciliation-report');
        $date = now()->subDay()->toDateString();
        $this->payment($storefront, $customer, StorefrontOrder::PAYMENT_EASYPAISA, 1000, $date, 'EP-1');
        $this->payment($storefront, $customer, StorefrontOrder::PAYMENT_EASYPAISA, 1500, $date, 'EP-2');
        $this->payment($storefront, $customer, StorefrontOrder::PAYMENT_JAZZCASH, 2000, $date, 'JC-1');
        $this->payment($storefront, $customer, StorefrontOrder::PAYMENT_BANK_TRANSFER, 900, $date, 'BANK-PENDING', false);
        $this->payment($storefront, $customer, StorefrontOrder::PAYMENT_UNPAID, 600, $date, 'UNPAID');

        $this->actingAs($owner)->get(route('admin.payment-reconciliation.index', [
            'start_date' => $date,
            'end_date' => $date,
        ]))->assertOk()
            ->assertSeeText('روزانہ ادائیگی مصالحت')
            ->assertSeeText('ایزی پیسہ')
            ->assertSeeText('جاز کیش')
            ->assertSeeText('2 تصدیق شدہ ادائیگیاں')
            ->assertSeeText('2,500.00')
            ->assertDontSeeText('900.00');
    }

    public function test_reconciliation_is_locked_revisable_and_keeps_immutable_events(): void
    {
        [$owner, $storefront, $customer] = $this->business('reconciliation-events');
        $date = now()->subDay()->toDateString();
        $this->payment($storefront, $customer, StorefrontOrder::PAYMENT_RAAST, 3000, $date, 'RAAST-1');
        $this->payment($storefront, $customer, StorefrontOrder::PAYMENT_RAAST, 2000, $date, 'RAAST-2');

        $this->actingAs($owner)->post(route('admin.payment-reconciliation.store'), [
            'settlement_date' => $date,
            'payment_method' => StorefrontOrder::PAYMENT_RAAST,
            'actual_amount' => 5000,
            'external_reference' => 'SBP-SETTLEMENT-1',
        ])->assertRedirect();

        $record = StorefrontPaymentReconciliation::firstOrFail();
        $this->assertSame('5000.00', $record->expected_amount);
        $this->assertSame('5000.00', $record->actual_amount);
        $this->assertSame('0.00', $record->variance_amount);
        $this->assertSame(2, $record->expected_count);
        $this->assertSame($owner->id, $record->reconciled_by_user_id);
        $this->assertDatabaseCount('storefront_payment_reconciliation_events', 1);

        $this->actingAs($owner)->post(route('admin.payment-reconciliation.store'), [
            'settlement_date' => $date,
            'payment_method' => StorefrontOrder::PAYMENT_RAAST,
            'actual_amount' => 4900,
            'external_reference' => 'SBP-SETTLEMENT-CORRECTED',
            'notes' => 'Provider deducted one hundred rupees; investigating.',
        ])->assertRedirect();

        $record->refresh();
        $this->assertSame('4900.00', $record->actual_amount);
        $this->assertSame('-100.00', $record->variance_amount);
        $this->assertDatabaseCount('storefront_payment_reconciliations', 1);
        $this->assertDatabaseCount('storefront_payment_reconciliation_events', 2);
        $this->assertDatabaseHas('storefront_payment_reconciliation_events', [
            'reconciliation_id' => $record->id,
            'actual_amount' => 5000,
            'external_reference' => 'SBP-SETTLEMENT-1',
        ]);
        $this->actingAs($owner)->get(route('admin.payment-reconciliation.index', [
            'start_date' => $date,
            'end_date' => $date,
        ]))->assertOk()
            ->assertSeeText('تبدیلی کی مکمل تاریخ (2)')
            ->assertSeeText('SBP-SETTLEMENT-1')
            ->assertSeeText('SBP-SETTLEMENT-CORRECTED');
    }

    public function test_late_verified_payment_marks_previous_snapshot_for_recheck(): void
    {
        [$owner, $storefront, $customer] = $this->business('reconciliation-drift');
        $date = now()->subDay()->toDateString();
        $this->payment($storefront, $customer, StorefrontOrder::PAYMENT_JAZZCASH, 1000, $date, 'JC-DRIFT-1');
        $this->actingAs($owner)->post(route('admin.payment-reconciliation.store'), [
            'settlement_date' => $date,
            'payment_method' => StorefrontOrder::PAYMENT_JAZZCASH,
            'actual_amount' => 1000,
            'external_reference' => 'JC-DAILY-1',
        ])->assertRedirect();

        $this->payment($storefront, $customer, StorefrontOrder::PAYMENT_JAZZCASH, 500, $date, 'JC-DRIFT-2');

        $this->actingAs($owner)->get(route('admin.payment-reconciliation.index', [
            'start_date' => $date,
            'end_date' => $date,
        ]))->assertOk()
            ->assertSeeText('پچھلی مصالحت کے بعد تبدیل ہوئی ہیں')
            ->assertSeeText('1,500.00')
            ->assertSeeText('-500.00');
    }

    public function test_reconciliation_is_tenant_scoped_and_requires_verified_payments(): void
    {
        [$owner, $storefront] = $this->business('reconciliation-empty');
        [, $otherStorefront, $otherCustomer] = $this->business('reconciliation-other');
        $date = now()->subDay()->toDateString();
        $this->payment($otherStorefront, $otherCustomer, StorefrontOrder::PAYMENT_EASYPAISA, 2500, $date, 'OTHER-EP');

        $this->actingAs($owner)->post(route('admin.payment-reconciliation.store'), [
            'settlement_date' => $date,
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
            'actual_amount' => 2500,
            'external_reference' => 'SHOULD-NOT-MATCH',
        ])->assertSessionHasErrors('settlement_date');

        $this->assertDatabaseCount('storefront_payment_reconciliations', 0);
        $this->assertSame($storefront->id, $owner->business->storefront->id);
    }

    public function test_reconciliation_csv_is_tenant_scoped_and_formula_safe(): void
    {
        [$owner, $storefront, $customer] = $this->business('reconciliation-csv');
        $date = now()->subDay()->toDateString();
        $this->payment($storefront, $customer, StorefrontOrder::PAYMENT_BANK_TRANSFER, 1250, $date, 'BANK-1');
        $this->actingAs($owner)->post(route('admin.payment-reconciliation.store'), [
            'settlement_date' => $date,
            'payment_method' => StorefrontOrder::PAYMENT_BANK_TRANSFER,
            'actual_amount' => 1250,
            'external_reference' => '=HYPERLINK("https://invalid.test")',
        ])->assertRedirect();

        $response = $this->actingAs($owner)->get(route('admin.payment-reconciliation.export', [
            'start_date' => $date,
            'end_date' => $date,
        ]))->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
    }

    public function test_reconciliation_requires_provider_reference_or_zero_amount_explanation(): void
    {
        [$owner, $storefront, $customer] = $this->business('reconciliation-validation');
        $date = now()->subDay()->toDateString();
        $this->payment($storefront, $customer, StorefrontOrder::PAYMENT_EASYPAISA, 800, $date, 'EP-VALIDATE');

        $this->actingAs($owner)->post(route('admin.payment-reconciliation.store'), [
            'settlement_date' => $date,
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
            'actual_amount' => 800,
        ])->assertSessionHasErrors('external_reference');
        $this->actingAs($owner)->post(route('admin.payment-reconciliation.store'), [
            'settlement_date' => $date,
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
            'actual_amount' => 0,
        ])->assertSessionHasErrors('notes');
        $this->actingAs($owner)->post(route('admin.payment-reconciliation.store'), [
            'settlement_date' => now()->addDay()->toDateString(),
            'payment_method' => StorefrontOrder::PAYMENT_EASYPAISA,
            'actual_amount' => 800,
            'external_reference' => 'FUTURE',
        ])->assertSessionHasErrors('settlement_date');

        $this->assertDatabaseCount('storefront_payment_reconciliations', 0);
    }

    private function business(string $slug): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create([
            'is_business_owner' => true,
            'tailoring_access' => true,
            'clothing_access' => true,
        ]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Settlement Business '.$slug,
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
            'display_name' => 'Settlement Shop',
            'show_clothing' => true,
            'show_tailoring' => true,
        ]);
        $customer = Customers::create([
            'user_id' => $owner->id,
            'name' => 'Settlement Customer',
            'phone_number1' => '0300'.str_pad((string) $business->id, 7, '0', STR_PAD_LEFT),
        ]);

        return [$owner->fresh(), $storefront, $customer];
    }

    private function payment(
        Storefront $storefront,
        Customers $customer,
        string $method,
        float $amount,
        string $date,
        string $suffix,
        bool $verified = true
    ): StorefrontOrder {
        static $sequence = 0;
        $sequence++;

        return StorefrontOrder::create([
            'storefront_id' => $storefront->id,
            'customer_id' => $customer->id,
            'reference' => 'TMSO-SETTLEMENT-'.$sequence.'-'.$suffix,
            'tracking_token_hash' => hash('sha256', 'settlement-'.$sequence),
            'status' => StorefrontOrder::STATUS_PENDING,
            'fulfillment_method' => 'pickup',
            'payment_method' => $method,
            'payment_reference' => $suffix,
            'payment_verification_status' => $verified
                ? StorefrontOrder::VERIFICATION_VERIFIED
                : StorefrontOrder::VERIFICATION_PENDING,
            'subtotal' => $amount,
            'paid_amount' => $verified ? $amount : 0,
            'balance_amount' => $verified ? 0 : $amount,
            'placed_at' => $date.' 09:00:00',
            'payment_verified_at' => $verified ? $date.' 12:00:00' : null,
        ]);
    }
}
