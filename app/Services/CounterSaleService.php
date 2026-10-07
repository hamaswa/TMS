<?php

namespace App\Services;

use App\Models\Cloth;
use App\Models\CounterSaleReceipt;
use App\Models\Customers;
use App\Models\SaleStock;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PaymentMethods;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CounterSaleService
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function validate(array $input): array
    {
        $validated = Validator::make($input, [
            'brand_name' => ['required', 'array', 'min:1'],
            'brand_name.*' => ['required', 'integer'],
            'cloth_id' => ['nullable', 'array'],
            'cloth_id.*' => ['nullable', 'integer'],
            'cloth_type' => ['required', 'array'],
            'cloth_type.*' => ['required', 'integer'],
            'color' => ['required', 'array'],
            'color.*' => ['nullable', 'string', 'max:100'],
            'item_total' => ['nullable', 'array'],
            'item_total.*' => ['required', 'numeric', 'min:0'],
            'per_meter' => ['nullable', 'array'],
            'per_meter.*' => ['required', 'numeric', 'min:0'],
            'clothes_rack' => ['required', 'array'],
            'clothes_rack.*' => ['nullable', 'string', 'max:100'],
            'length' => ['required', 'array'],
            'length.*' => ['required', 'numeric', 'gt:0'],
            'customer_mode' => ['nullable', Rule::in(['regular', 'new', 'walk_in', 'random'])],
            'existing_customer_id' => ['nullable', 'integer'],
            'random_customer_name' => ['nullable', 'string', 'max:255'],
            'random_customer_phone' => ['nullable', 'string', 'max:30'],
            'c_name' => ['nullable', 'string', 'regex:/^.+\|\d+$/'],
            'payment' => ['required', 'numeric', 'min:0'],
            'remain' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(array_keys(PaymentMethods::LABELS))],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'paid_on' => ['nullable', 'date'],
        ])->validate();

        if (PaymentMethods::requiresReference($validated['payment_method'] ?? null)
            && blank($validated['payment_reference'] ?? null)) {
            throw ValidationException::withMessages([
                'payment_reference' => 'Payment reference is required for this payment method.',
            ]);
        }

        return $validated;
    }

    /** @return array{first_sale: SaleStock, customer: Customers, receipt: CounterSaleReceipt} */
    public function complete(User $actor, array $validated): array
    {
        $itemCount = count($validated['brand_name']);
        foreach (['cloth_type', 'color', 'clothes_rack', 'length'] as $field) {
            if (count($validated[$field]) !== $itemCount) {
                throw ValidationException::withMessages([$field => 'Sale item fields are incomplete.']);
            }
        }

        $usesItemTotals = isset($validated['item_total']) && count($validated['item_total']) === $itemCount;
        $usesLegacyRates = isset($validated['per_meter']) && count($validated['per_meter']) === $itemCount;
        if (! $usesItemTotals && ! $usesLegacyRates) {
            throw ValidationException::withMessages(['item_total' => 'ہر کپڑے کی کل قیمت درج کریں۔']);
        }

        $itemTotals = [];
        $perMeters = [];
        foreach ($validated['length'] as $index => $length) {
            $length = (float) $length;
            if ($usesItemTotals) {
                $itemTotals[$index] = round((float) $validated['item_total'][$index], 2);
                $perMeters[$index] = round($itemTotals[$index] / $length, 2);
            } else {
                $perMeters[$index] = round((float) $validated['per_meter'][$index], 2);
                $itemTotals[$index] = round($length * $perMeters[$index], 2);
            }
        }

        $saleTotal = round(array_sum($itemTotals), 2);
        if ((float) $validated['payment'] > $saleTotal) {
            throw ValidationException::withMessages(['payment' => 'موصول شدہ رقم کل فروخت سے زیادہ نہیں ہو سکتی۔']);
        }

        $ownerId = $actor->businessOwnerId();
        $customerMode = ($validated['customer_mode'] ?? 'regular') === 'random'
            ? 'new'
            : ($validated['customer_mode'] ?? 'regular');
        $existingCustomerId = $validated['existing_customer_id'] ?? null;
        if (! $existingCustomerId && ! empty($validated['c_name'])) {
            [, $existingCustomerId] = explode('|', $validated['c_name'], 2);
        }

        if ($customerMode === 'new' && blank($validated['random_customer_name'] ?? null)) {
            throw ValidationException::withMessages(['random_customer_name' => 'نئے گاہک کا نام ضرور لکھیں۔']);
        }
        if ($customerMode === 'regular' && ! $existingCustomerId) {
            throw ValidationException::withMessages(['existing_customer_id' => 'فہرست سے گاہک منتخب کریں۔']);
        }

        $existingCustomer = $customerMode === 'regular'
            ? Customers::where('user_id', $ownerId)->findOrFail($existingCustomerId)
            : null;
        $remainingBalance = round($saleTotal - (float) $validated['payment'], 2);
        if ($customerMode === 'walk_in' && $remainingBalance > 0) {
            throw ValidationException::withMessages(['payment' => 'واک اِن فروخت مکمل کرنے کے لیے پوری رقم وصول کریں، یا گاہک کو نیا/موجودہ گاہک منتخب کریں۔']);
        }
        if ($customerMode === 'new' && filled($validated['random_customer_phone'] ?? null)
            && Customers::findByPhoneForOwner($ownerId, (string) $validated['random_customer_phone'])) {
            throw ValidationException::withMessages(['random_customer_phone' => 'یہ فون نمبر پہلے سے موجود ہے۔ موجودہ گاہک منتخب کریں۔']);
        }

        return DB::transaction(function () use ($validated, $existingCustomer, $customerMode, $itemCount, $remainingBalance, $ownerId, $perMeters) {
            $firstSale = null;
            $soldAt = now();
            $customer = $existingCustomer;
            if ($customerMode === 'new') {
                $customer = Customers::create([
                    'name' => trim($validated['random_customer_name']),
                    'phone_number1' => trim($validated['random_customer_phone'] ?? ''),
                    'user_id' => $ownerId,
                    'first_sale_at' => $soldAt,
                    'acquisition_source' => 'counter_sale',
                ]);
            } elseif ($customerMode === 'walk_in') {
                $customer = Customers::where('user_id', $ownerId)->whereNull('parent_id')->where('is_walk_in', true)->first()
                    ?? Customers::create([
                        'name' => 'Walk-in Customer',
                        'phone_number1' => '',
                        'user_id' => $ownerId,
                        'is_walk_in' => true,
                        'acquisition_source' => 'walk_in',
                    ]);
            } elseif (! $customer->first_sale_at) {
                $customer->forceFill([
                    'first_sale_at' => $soldAt,
                    'acquisition_source' => $customer->acquisition_source ?: 'counter_sale',
                ])->save();
            }

            $receipt = CounterSaleReceipt::create([
                'receipt_number' => 'SALE-'.Str::upper(Str::random(6)),
                'user_id' => $ownerId,
                'customer_id' => $customer->id,
                'status' => 'completed',
                'created_at' => $soldAt,
                'updated_at' => $soldAt,
            ]);

            for ($i = 0; $i < $itemCount; $i++) {
                $cloth = Cloth::where('user_id', $ownerId)
                    ->when(data_get($validated, "cloth_id.{$i}"), fn ($query, $clothId) => $query->whereKey($clothId))
                    ->where('cloth_type_id', $validated['cloth_type'][$i])
                    ->where('cloth_brand_id', $validated['brand_name'][$i])
                    ->first();
                if (! $cloth) {
                    throw ValidationException::withMessages(['cloth_type.'.$i => 'منتخب برانڈ اور کپڑے کی قسم آپس میں درست نہیں۔']);
                }

                $requestedColor = trim((string) ($validated['color'][$i] ?? ''));
                if ($cloth->hasSelectableColors() && $requestedColor === '') {
                    throw ValidationException::withMessages(['color.'.$i => 'اس سیٹ کے لیے رنگ منتخب کریں۔']);
                }
                if ($cloth->usesDisplayOnlyColors()
                    && ! in_array($requestedColor, $cloth->selectableColorNames(), true)) {
                    throw ValidationException::withMessages(['color.'.$i => 'منتخب رنگ اس سیٹ کے دستیاب رنگوں میں شامل نہیں ہے۔']);
                }
                $usesExplicitColor = $cloth->hasSelectableColors() && $requestedColor !== '';
                $colorQuery = $cloth->colors()->lockForUpdate();
                $clothColor = $cloth->tracksColors()
                    ? $colorQuery->where('color', $requestedColor)->first()
                    : $colorQuery->first();
                if (! $clothColor) {
                    throw ValidationException::withMessages(['color.'.$i => $requestedColor !== ''
                        ? 'منتخب رنگ اس برانڈ اور کپڑے کی قسم میں دستیاب نہیں۔'
                        : 'اس کپڑے کا کوئی رنگ اسٹاک میں دستیاب نہیں ہے۔']);
                }
                if ((float) $clothColor->length < (float) $validated['length'][$i]) {
                    throw ValidationException::withMessages(['length.'.$i => 'منتخب کپڑے کا مطلوبہ اسٹاک دستیاب نہیں ہے۔']);
                }

                $costPrice = (float) $clothColor->average_unit_cost ?: (float) $cloth->price;
                $salePrice = $perMeters[$i];
                $sale = SaleStock::create([
                    'counter_sale_receipt_id' => $receipt->id,
                    'cloth_type_id' => $validated['cloth_type'][$i],
                    'cloth_brand_id' => $validated['brand_name'][$i],
                    'color' => $usesExplicitColor ? $requestedColor : null,
                    'c_name' => $customer->name,
                    'c_id' => $customer->id,
                    'phone' => $customer->phone_number1,
                    'length' => $validated['length'][$i],
                    'sellDate' => $soldAt,
                    'clothes_rack' => $validated['clothes_rack'][$i] ?? null,
                    'selling_price' => $salePrice,
                    'profit' => max(0, $salePrice - $costPrice),
                    'loss' => max(0, $costPrice - $salePrice),
                    'user_id' => $ownerId,
                    'cloth_id' => $cloth->id,
                    'cloth_color_id' => $clothColor->id,
                    'cost_per_meter' => $costPrice,
                    'cost_total' => round($costPrice * (float) $validated['length'][$i], 2),
                    'created_at' => $soldAt,
                    'updated_at' => $soldAt,
                ]);
                $this->inventory->issue($clothColor, (float) $validated['length'][$i], 'counter_sale', $sale, 'Counter sale #'.$sale->id, $costPrice);
                $firstSale ??= $sale;
            }

            $receipt->update(['first_sale_stock_id' => $firstSale->id]);
            Transaction::create([
                'remainingBalance' => $remainingBalance,
                'recivedPayment' => $validated['payment'],
                'customerId' => $customer->id,
                'Order_type' => 'Sale',
                'sale_id' => $firstSale->id,
                'counter_sale_receipt_id' => $receipt->id,
                'userId' => $ownerId,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'payment_reference' => $validated['payment_reference'] ?? null,
                'paid_on' => $validated['paid_on'] ?? now()->toDateString(),
            ]);

            return ['first_sale' => $firstSale, 'customer' => $customer, 'receipt' => $receipt];
        });
    }
}
