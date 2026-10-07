<?php

namespace App\Services;

use App\Models\Cloth;
use App\Models\CounterOrder;
use App\Models\CounterOrderItem;
use App\Models\Customers;
use App\Models\MeasurementTemplate;
use App\Models\Order;
use App\Models\SaleSession;
use App\Models\Tailor;
use App\Models\Tailorsalary;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\SaleSessionCompletedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CounterOrderService
{
    public function __construct(
        private readonly CounterSaleService $counterSales,
        private readonly MeasurementService $measurements,
        private readonly ProductionWorkforceService $workforce,
    ) {}

    public function create(User $actor, Customers $customer, ?string $note = null): CounterOrder
    {
        return CounterOrder::create([
            'reference' => $this->reference(),
            'user_id' => $actor->businessOwnerId(),
            'customer_id' => $customer->id,
            'created_by_user_id' => $actor->id,
            'status' => CounterOrder::STATUS_DRAFT,
            'note' => $note,
        ]);
    }

    public function fromSaleSession(SaleSession $session, User $actor): CounterOrder
    {
        return DB::transaction(function () use ($session, $actor) {
            $session = SaleSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $existing = CounterOrder::where('sale_session_id', $session->id)->first();
            if ($existing) {
                return $existing;
            }

            $customer = $this->customerForSession($session);
            $order = CounterOrder::create([
                'reference' => $this->reference(),
                'user_id' => $session->user_id,
                'customer_id' => $customer->id,
                'sale_session_id' => $session->id,
                'created_by_user_id' => $session->agent_user_id,
                'claimed_by_user_id' => $actor->id,
                'status' => CounterOrder::STATUS_CLAIMED,
                'note' => $session->note,
            ]);

            foreach ($session->items ?? [] as $line) {
                $cloth = Cloth::where('user_id', $session->user_id)
                    ->when(data_get($line, 'setCode'), fn ($query, $code) => $query->where('set_code', $code))
                    ->when(! data_get($line, 'setCode') && data_get($line, 'brandId'), fn ($query) => $query
                        ->where('cloth_brand_id', data_get($line, 'brandId'))
                        ->where('cloth_type_id', data_get($line, 'clothTypeId')))
                    ->first();
                if (! $cloth) {
                    continue;
                }

                $length = (float) (data_get($line, 'length') ?: data_get($line, 'quantity') ?: 1);
                $quantity = max(1, (float) (data_get($line, 'quantity') ?: 1));
                $unitPrice = (float) (data_get($line, 'unitPrice') ?: 0);
                $lineTotal = ($line['salePriceBasis'] ?? null) === Cloth::SALE_PRICE_PER_SUIT
                    ? round($unitPrice * $quantity, 2)
                    : round($unitPrice * $length, 2);
                $order->items()->create([
                    'type' => CounterOrderItem::TYPE_CLOTH,
                    'status' => CounterOrderItem::STATUS_DRAFT,
                    'cloth_id' => $cloth->id,
                    'quantity' => $quantity,
                    'length' => $length,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'details' => [
                        'color' => data_get($line, 'color'),
                        'sale_price_basis' => data_get($line, 'salePriceBasis', $cloth->sale_price_basis),
                        'mobile_local_id' => data_get($line, 'localId'),
                    ],
                ]);
            }

            $order->recalculate();

            return $order->fresh('items');
        });
    }

    public function confirm(CounterOrder $order, User $actor, float $payment, string $method, ?string $reference): CounterOrder
    {
        return DB::transaction(function () use ($order, $actor, $payment, $method, $reference) {
            $order = CounterOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->isClosed()) {
                throw ValidationException::withMessages(['order' => 'بند آرڈر میں نئی تصدیق نہیں ہو سکتی۔']);
            }

            $draftItems = $order->items()->where('status', CounterOrderItem::STATUS_DRAFT)
                ->with(['cloth.brand', 'cloth.type', 'measurementProfile', 'measurementTemplate', 'tailor'])
                ->lockForUpdate()->get();
            if ($draftItems->isEmpty()) {
                throw ValidationException::withMessages(['order' => 'تصدیق کے لیے کوئی نیا آئٹم موجود نہیں ہے۔']);
            }
            $draftTotal = round((float) $draftItems->sum('line_total'), 2);
            if ($payment < 0 || $payment > $draftTotal) {
                throw ValidationException::withMessages(['payment' => 'وصول رقم نئے آئٹمز کی کل رقم سے زیادہ نہیں ہو سکتی۔']);
            }

            $remainingPayment = round($payment, 2);
            $clothItems = $draftItems->where('type', CounterOrderItem::TYPE_CLOTH)->values();
            if ($clothItems->isNotEmpty()) {
                $clothTotal = round((float) $clothItems->sum('line_total'), 2);
                $clothPayment = min($remainingPayment, $clothTotal);
                $result = $this->counterSales->complete($actor, [
                    'brand_name' => $clothItems->map(fn ($item) => $item->cloth->cloth_brand_id)->all(),
                    'cloth_id' => $clothItems->pluck('cloth_id')->all(),
                    'cloth_type' => $clothItems->map(fn ($item) => $item->cloth->cloth_type_id)->all(),
                    'color' => $clothItems->map(fn ($item) => (string) data_get($item->details, 'color', ''))->all(),
                    'item_total' => $clothItems->pluck('line_total')->map(fn ($value) => (float) $value)->all(),
                    'clothes_rack' => $clothItems->map(fn () => null)->all(),
                    'length' => $clothItems->map(fn ($item) =>
                        data_get($item->details, 'sale_price_basis') === Cloth::SALE_PRICE_PER_SUIT
                            ? (float) $item->length * (float) $item->quantity
                            : (float) $item->length
                    )->all(),
                    'customer_mode' => 'regular',
                    'existing_customer_id' => $order->customer_id,
                    'payment' => $clothPayment,
                    'payment_method' => $method,
                    'payment_reference' => $reference,
                    'paid_on' => now()->toDateString(),
                ]);
                $clothItems->each->update([
                    'status' => CounterOrderItem::STATUS_CONFIRMED,
                    'source_record_type' => 'counter_sale_receipt',
                    'source_record_id' => $result['receipt']->id,
                ]);
                $remainingPayment = round($remainingPayment - $clothPayment, 2);
            }

            foreach ($draftItems->where('type', CounterOrderItem::TYPE_TAILORING) as $item) {
                $paid = min($remainingPayment, (float) $item->line_total);
                $legacyOrder = $this->createTailoringOrder($order, $item, $actor, $paid, $method, $reference);
                $item->update([
                    'status' => CounterOrderItem::STATUS_CONFIRMED,
                    'source_record_type' => 'tailoring_order',
                    'source_record_id' => $legacyOrder->id,
                ]);
                $remainingPayment = round($remainingPayment - $paid, 2);
            }

            $order->forceFill([
                'status' => CounterOrder::STATUS_CONFIRMED,
                'confirmed_at' => $order->confirmed_at ?: now(),
                'paid_amount' => round((float) $order->paid_amount + $payment, 2),
            ])->save();
            $order->recalculate();

            if ($order->saleSession && ! $order->saleSession->isTerminal()) {
                $receiptId = $order->items()->where('source_record_type', 'counter_sale_receipt')->value('source_record_id');
                $order->saleSession->update([
                    'status' => SaleSession::STATUS_COMPLETED,
                    'completed_by_user_id' => $actor->id,
                    'counter_sale_receipt_id' => $receiptId,
                    'completed_at' => now(),
                    'revision' => $order->saleSession->revision + 1,
                ]);
                $order->saleSession->agent?->notify(new SaleSessionCompletedNotification(
                    $order->saleSession->fresh(['agent', 'completedBy', 'receipt'])
                ));
            }

            return $order->fresh(['items.cloth.brand', 'items.cloth.type', 'items.measurementProfile', 'customer']);
        });
    }

    private function createTailoringOrder(
        CounterOrder $counterOrder,
        CounterOrderItem $item,
        User $actor,
        float $paid,
        string $method,
        ?string $reference,
    ): Order {
        $ownerId = $actor->businessOwnerId();
        $profile = Customers::where('user_id', $ownerId)->findOrFail($item->measurement_profile_id);
        $primaryId = $profile->parent_id ?: $profile->id;
        if ((int) $primaryId !== (int) $counterOrder->customer_id) {
            throw ValidationException::withMessages(['measurement_profile_id' => 'منتخب ناپ اس گاہک کے اکاؤنٹ سے متعلق نہیں ہے۔']);
        }
        $template = MeasurementTemplate::where('user_id', $ownerId)->where('is_active', true)
            ->findOrFail($item->measurement_template_id);
        $measurementSnapshot = collect(data_get($item->details, 'measurement_snapshot', []));
        $missing = $measurementSnapshot->isEmpty()
            ? $this->measurements->missingRequiredMeasurements($profile, $ownerId, $template)
            : collect();
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'measurement_profile_id' => 'محفوظ ناپ نامکمل ہے: '.$missing->join('، '),
            ]);
        }

        $tailor = $item->tailor_id ? Tailor::where('user_id', $ownerId)->findOrFail($item->tailor_id) : null;
        $rate = null;
        if ($tailor && $item->rate_id) {
            $rate = Tailorsalary::where('tailor_id', $tailor->id)->findOrFail($item->rate_id);
        }
        $quantity = max(1, (int) $item->quantity);
        $order = Order::create([
            'customerId' => $counterOrder->customer_id,
            'sub_customer' => $profile->id,
            'measurement_template_id' => $template->id,
            'suitQuantity' => $quantity,
            'totalPayment' => $item->line_total,
            'returnDate' => $item->due_date,
            'design' => 0,
            'tailorId' => $tailor?->id,
            'rateId' => $rate?->id,
            'userId' => $ownerId,
            'remarks' => $item->note,
            'tailor_price' => $rate?->price ?? 0,
            'suitNum' => (string) ($profile->serial_number ?? $profile->id),
            'designPrice' => 0,
            'status' => $tailor ? 'assigned' : 'unassigned',
            'status_changed_at' => now(),
            'tailor_paid_amount' => 0,
            'tailor_payment_status' => 'unpaid',
        ]);
        Transaction::create([
            'recivedPayment' => $paid,
            'remainingBalance' => round((float) $item->line_total - $paid, 2),
            'orderId' => $order->id,
            'customerId' => $counterOrder->customer_id,
            'userId' => $ownerId,
            'Order_type' => 'Tailor',
            'payment_method' => $method,
            'payment_reference' => $reference,
            'paid_on' => now()->toDateString(),
        ]);
        if ($measurementSnapshot->isNotEmpty()) {
            $order->measurementValues()->createMany($measurementSnapshot->all());
        } else {
            $this->measurements->snapshotOrder($order, $profile, $template);
        }
        $this->workforce->syncOrder($order);

        return $order;
    }

    private function customerForSession(SaleSession $session): Customers
    {
        if ($session->customer_id) {
            return Customers::where('user_id', $session->user_id)->findOrFail($session->customer_id);
        }
        $name = trim((string) data_get($session->customer_data, 'name'));
        $phone = trim((string) data_get($session->customer_data, 'phone'));
        if ($phone !== '' && ($existing = Customers::findByPhoneForOwner($session->user_id, $phone))) {
            return $existing;
        }
        if ($session->customer_mode === 'new' && $name !== '') {
            return Customers::create([
                'name' => $name,
                'phone_number1' => $phone,
                'user_id' => $session->user_id,
                'acquisition_source' => 'sales_agent',
            ]);
        }

        return Customers::where('user_id', $session->user_id)->whereNull('parent_id')->where('is_walk_in', true)->first()
            ?? Customers::create([
                'name' => 'Walk-in Customer', 'phone_number1' => '', 'user_id' => $session->user_id,
                'is_walk_in' => true, 'acquisition_source' => 'walk_in',
            ]);
    }

    private function reference(): string
    {
        do {
            $reference = 'ORD-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (CounterOrder::where('reference', $reference)->exists());

        return $reference;
    }
}
