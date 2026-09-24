<?php

namespace App\Services;

use App\Models\Customers;
use App\Models\MeasurementTemplate;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\StorefrontInquiry;
use App\Models\StorefrontTailoringService;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StorefrontTailoringBookingService
{
    public function __construct(private MeasurementService $measurements) {}

    public function confirm(
        StorefrontInquiry $booking,
        int $actorUserId,
        float $finalPrice,
        int $suitQuantity,
        string $promisedDate,
        ?string $adminNotes,
    ): StorefrontInquiry {
        return DB::transaction(function () use ($booking, $actorUserId, $finalPrice, $suitQuantity, $promisedDate, $adminNotes) {
            $locked = StorefrontInquiry::query()->lockForUpdate()
                ->with(['storefront.business', 'service'])->findOrFail($booking->id);
            if (! $locked->isBooking() || ! in_array($locked->status, [StorefrontInquiry::STATUS_NEW, StorefrontInquiry::STATUS_CONTACTED], true)) {
                throw ValidationException::withMessages(['booking' => 'صرف زیرِ جائزہ آن لائن بکنگ کی تصدیق کی جا سکتی ہے۔']);
            }
            if (StorefrontInquiry::requiresManualVerification($locked->payment_method)
                && $locked->payment_verification_status !== StorefrontInquiry::VERIFICATION_VERIFIED) {
                throw ValidationException::withMessages(['booking' => 'دستی ادائیگی کی تصدیق کے بعد بکنگ منظور کریں۔']);
            }
            $paidAmount = StorefrontInquiry::requiresManualVerification($locked->payment_method)
                ? (float) $locked->payment_claimed_amount : 0.0;
            if ($paidAmount > $finalPrice) {
                throw ValidationException::withMessages(['final_price' => 'حتمی قیمت تصدیق شدہ پیشگی ادائیگی سے کم نہیں ہو سکتی۔']);
            }

            $ownerId = (int) $locked->storefront->business->owner_user_id;
            if ($locked->customer_id) {
                $customer = Customers::where('user_id', $ownerId)->findOrFail($locked->customer_id);
            } else {
                // Do not silently attach a booking to a customer identity created after submission.
                if (Customers::findByPhoneForOwner($ownerId, $locked->phone)) {
                    throw ValidationException::withMessages([
                        'booking' => 'اس فون نمبر کا گاہک بکنگ کے بعد بن چکا ہے۔ پہلے گاہک کی شناخت کی تصدیق کریں۔',
                    ]);
                }
                $customer = Customers::create([
                    'user_id' => $ownerId,
                    'name' => $locked->customer_name,
                    'phone_number1' => $locked->phone,
                    'mobile_pin' => $locked->booking_pin_hash,
                    'note' => collect([$locked->city, $locked->email])->filter()->join(' · ') ?: null,
                ]);
            }
            $template = $customer->measurementTemplate
                ?: MeasurementTemplate::where('user_id', $ownerId)
                    ->where('is_active', true)->where('is_default', true)->first();
            $measurementLabel = $locked->measurement_method
                ? (StorefrontTailoringService::measurementMethodLabels()[$locked->measurement_method] ?? $locked->measurement_method)
                : null;
            $remarks = collect([
                'آن لائن بکنگ '.$locked->reference,
                $locked->service?->name,
                $measurementLabel,
                $locked->message,
                $adminNotes,
            ])->filter()->join(' · ');
            $order = Order::create([
                'customerId' => $customer->id,
                'sub_customer' => $customer->id,
                'measurement_template_id' => $template?->id,
                'suitQuantity' => $suitQuantity,
                'totalPayment' => round($finalPrice, 2),
                'returnDate' => $promisedDate,
                'design' => 0,
                'userId' => $ownerId,
                'remarks' => $remarks,
                'tailor_price' => 0,
                'suitNum' => (string) $customer->id,
                'designPrice' => 0,
                'status' => 'unassigned',
                'status_changed_at' => now(),
                'tailor_paid_amount' => 0,
                'tailor_payment_status' => 'unpaid',
            ]);
            Transaction::create([
                'recivedPayment' => $paidAmount,
                'remainingBalance' => round($finalPrice - $paidAmount, 2),
                'orderId' => $order->id,
                'customerId' => $customer->id,
                'userId' => $ownerId,
                'Order_type' => 'Tailor',
                'payment_method' => $locked->payment_method,
                'payment_reference' => $locked->payment_reference,
                'paid_on' => $paidAmount > 0 ? now()->toDateString() : null,
                'comment' => 'آن لائن ٹیلرنگ بکنگ '.$locked->reference,
            ]);
            $this->measurements->snapshotOrder($order, $customer, $template);
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => $actorUserId,
                'from_status' => null,
                'to_status' => 'unassigned',
                'changed_by_type' => 'shop_owner',
                'note' => 'آن لائن ٹیلرنگ بکنگ کی تصدیق سے آرڈر بنایا گیا۔',
            ]);
            $locked->update([
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'suit_quantity' => $suitQuantity,
                'final_price' => round($finalPrice, 2),
                'promised_date' => $promisedDate,
                'admin_notes' => $adminNotes,
                'status' => StorefrontInquiry::STATUS_CONFIRMED,
                'confirmed_by_user_id' => $actorUserId,
                'confirmed_at' => now(),
                'rejected_by_user_id' => null,
                'rejection_reason' => null,
                'rejected_at' => null,
                'closed_at' => now(),
            ]);

            return $locked->fresh(['customer', 'order', 'service', 'confirmedBy']);
        }, 3);
    }

    public function reject(StorefrontInquiry $booking, int $actorUserId, string $reason): StorefrontInquiry
    {
        return DB::transaction(function () use ($booking, $actorUserId, $reason) {
            $locked = StorefrontInquiry::query()->lockForUpdate()->findOrFail($booking->id);
            if (! $locked->isBooking() || ! in_array($locked->status, [StorefrontInquiry::STATUS_NEW, StorefrontInquiry::STATUS_CONTACTED], true)) {
                throw ValidationException::withMessages(['booking' => 'صرف زیرِ جائزہ آن لائن بکنگ مسترد کی جا سکتی ہے۔']);
            }
            $locked->update([
                'status' => StorefrontInquiry::STATUS_REJECTED,
                'rejected_by_user_id' => $actorUserId,
                'rejection_reason' => $reason,
                'rejected_at' => now(),
                'closed_at' => now(),
            ]);

            return $locked;
        }, 3);
    }
}
