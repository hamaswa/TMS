<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customers;
use App\Models\Storefront;
use App\Models\StorefrontInquiry;
use App\Models\StorefrontTailoringService;
use App\Models\User;
use App\Notifications\NewStorefrontTailoringBookingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontTailoringBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_booking_is_trackable_and_creates_workshop_records_only_after_confirmation(): void
    {
        Notification::fake();
        [$owner, $storefront, $service] = $this->shop();

        $response = $this->post(route('storefront.tailoring.bookings.store', $storefront), [
            'tailoring_service_id' => $service->id,
            'customer_name' => 'Online Tailoring Customer',
            'phone' => '03001234567',
            'city' => 'Rawalpindi',
            'preferred_date' => now()->addDays(8)->toDateString(),
            'measurement_method' => StorefrontTailoringService::MEASUREMENT_SHOP_VISIT,
            'suit_quantity' => 2,
            'booking_pin' => '482913',
            'booking_pin_confirmation' => '482913',
            'payment_method' => StorefrontInquiry::PAYMENT_UNPAID,
            'message' => 'Two suits with standard collar.',
        ]);

        $booking = StorefrontInquiry::firstOrFail();
        $response->assertRedirect(route('storefront.tailoring.bookings.show', [$storefront, $booking->reference]));
        $this->assertTrue($booking->isBooking());
        $this->assertSame('3600.00', $booking->estimated_price);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('transactions', 0);
        Notification::assertSentTo($owner, NewStorefrontTailoringBookingNotification::class);

        $this->app['session']->flush();
        $this->get(route('storefront.tailoring.bookings.show', [$storefront, $booking->reference]))
            ->assertOk()->assertSeeText($booking->reference)->assertDontSeeText('Two suits with standard collar.');
        $this->post(route('storefront.tailoring.bookings.authenticate', [$storefront, $booking->reference]), [
            'phone' => '03001234567', 'pin' => '111111',
        ])->assertSessionHasErrors('phone');
        $this->post(route('storefront.tailoring.bookings.authenticate', [$storefront, $booking->reference]), [
            'phone' => '03001234567', 'pin' => '482913',
        ])->assertRedirect(route('storefront.tailoring.bookings.show', [$storefront, $booking->reference]));

        $this->actingAs($owner)->patch(route('admin.storefront.inquiries.confirm', $booking), [
            'final_price' => 4000,
            'suit_quantity' => 2,
            'promised_date' => now()->addDays(10)->toDateString(),
            'admin_notes' => 'Confirm cloth at shop visit.',
        ])->assertRedirect(route('admin.storefront.inquiries.index'));

        $booking->refresh();
        $this->assertSame(StorefrontInquiry::STATUS_CONFIRMED, $booking->status);
        $this->assertNotNull($booking->customer_id);
        $this->assertNotNull($booking->order_id);
        $this->assertTrue(Hash::check('482913', $booking->customer->mobile_pin));
        $this->assertDatabaseHas('orders', [
            'id' => $booking->order_id,
            'userId' => $owner->id,
            'status' => 'unassigned',
            'suitQuantity' => '2',
            'totalPayment' => '4000',
        ]);
        $this->assertDatabaseHas('transactions', [
            'orderId' => $booking->order_id,
            'customerId' => $booking->customer_id,
            'remainingBalance' => '4000',
            'Order_type' => 'Tailor',
        ]);
        $this->actingAs($owner)->get(route('admin.storefront.inquiries.job-sheet', $booking))
            ->assertOk()->assertSeeText($booking->reference)->assertSeeText('TAILORING JOB SHEET');
    }

    public function test_existing_customer_must_use_their_pin_and_confirmation_is_idempotent(): void
    {
        [$owner, $storefront, $service] = $this->shop();
        $customer = Customers::create([
            'user_id' => $owner->id,
            'name' => 'Existing Customer',
            'phone_number1' => '03009998888',
            'mobile_pin' => Hash::make('654321'),
        ]);
        $payload = [
            'tailoring_service_id' => $service->id,
            'customer_name' => 'Existing Customer',
            'phone' => '03009998888',
            'measurement_method' => StorefrontTailoringService::MEASUREMENT_SHOP_VISIT,
            'suit_quantity' => 1,
            'booking_pin' => '123456',
            'booking_pin_confirmation' => '123456',
            'payment_method' => StorefrontInquiry::PAYMENT_UNPAID,
        ];
        $this->post(route('storefront.tailoring.bookings.store', $storefront), $payload)
            ->assertSessionHasErrors('booking_pin');
        $this->assertDatabaseCount('storefront_inquiries', 0);

        $payload['booking_pin'] = $payload['booking_pin_confirmation'] = '654321';
        $this->post(route('storefront.tailoring.bookings.store', $storefront), $payload)->assertRedirect();
        $booking = StorefrontInquiry::firstOrFail();
        $this->assertSame($customer->id, $booking->customer_id);
        $confirmation = [
            'final_price' => 1900,
            'suit_quantity' => 1,
            'promised_date' => now()->addWeek()->toDateString(),
        ];
        $this->actingAs($owner)->patch(route('admin.storefront.inquiries.confirm', $booking), $confirmation)
            ->assertRedirect(route('admin.storefront.inquiries.index'));
        $this->actingAs($owner)->patch(route('admin.storefront.inquiries.confirm', $booking), $confirmation)
            ->assertSessionHasErrors('booking');
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_manual_payment_must_be_verified_before_shop_can_confirm_booking(): void
    {
        [$owner, $storefront, $service] = $this->shop();
        $storefront->update(['easypaisa_enabled' => true, 'easypaisa_number' => '03001112222']);
        $this->post(route('storefront.tailoring.bookings.store', $storefront), [
            'tailoring_service_id' => $service->id,
            'customer_name' => 'Advance Customer',
            'phone' => '03112223333',
            'measurement_method' => StorefrontTailoringService::MEASUREMENT_SHOP_VISIT,
            'suit_quantity' => 1,
            'booking_pin' => '482913',
            'booking_pin_confirmation' => '482913',
            'payment_method' => StorefrontInquiry::PAYMENT_EASYPAISA,
            'payment_sender_phone' => '03112223333',
            'payment_reference' => 'EP-BOOK-1',
            'payment_claimed_amount' => 500,
        ])->assertRedirect();
        $booking = StorefrontInquiry::firstOrFail();
        $confirmation = ['final_price' => 1800, 'suit_quantity' => 1, 'promised_date' => now()->addWeek()->toDateString()];
        $this->actingAs($owner)->patch(route('admin.storefront.inquiries.confirm', $booking), $confirmation)
            ->assertSessionHasErrors('booking');
        $this->actingAs($owner)->patch(route('admin.storefront.inquiries.payment-verification', $booking), [
            'decision' => StorefrontInquiry::VERIFICATION_VERIFIED,
        ])->assertRedirect();
        $this->actingAs($owner)->patch(route('admin.storefront.inquiries.confirm', $booking), $confirmation)
            ->assertRedirect(route('admin.storefront.inquiries.index'));
        $this->assertDatabaseHas('transactions', ['orderId' => $booking->fresh()->order_id, 'recivedPayment' => '500']);
    }

    private function shop(): array
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);
        $owner = User::factory()->create(['is_business_owner' => true, 'tailoring_access' => true, 'clothing_access' => true]);
        $owner->assignRole($role);
        $business = Business::create([
            'name' => 'Booking Tailor', 'owner_user_id' => $owner->id,
            'tailoring_enabled' => true, 'clothing_enabled' => false,
            'status' => Business::STATUS_ACTIVE, 'approved_at' => now(),
        ]);
        $owner->update(['business_id' => $business->id]);
        $storefront = Storefront::create([
            'business_id' => $business->id, 'slug' => 'booking-tailor-'.$business->id,
            'display_name' => 'Booking Tailor', 'show_tailoring' => true,
            'inquiries_enabled' => true, 'is_published' => true, 'published_at' => now(),
        ]);
        $service = $storefront->tailoringServices()->create([
            'name' => 'Suit Stitching', 'price_from' => 1800, 'price_unit' => 'فی سوٹ',
            'estimated_days' => 7,
            'measurement_methods' => [StorefrontTailoringService::MEASUREMENT_SHOP_VISIT],
            'is_published' => true, 'is_available' => true, 'accepts_inquiries' => true,
        ]);

        return [$owner->fresh(), $storefront, $service];
    }
}
