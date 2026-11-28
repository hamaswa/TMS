<?php

namespace App\Services;

use App\Models\Business;
use App\Models\CounterOrder;
use App\Models\CounterOrderItem;
use Illuminate\Support\Facades\DB;

class CounterOrderNumberService
{
    public function nextOrderSerial(int $ownerId): string
    {
        return DB::transaction(function () use ($ownerId): string {
            DB::table('counter_order_serial_sequences')->insertOrIgnore([
                'user_id' => $ownerId,
                'next_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('counter_order_serial_sequences')
                ->where('user_id', $ownerId)
                ->lockForUpdate()
                ->first();
            $number = (int) $sequence->next_number;

            DB::table('counter_order_serial_sequences')
                ->where('user_id', $ownerId)
                ->update([
                    'next_number' => $number + 1,
                    'updated_at' => now(),
                ]);

            // The shop picks the style in Settings; both styles share one running number, so switching
            // carries on from the last order (ORD-000108, then 109).
            $simple = Business::where('owner_user_id', $ownerId)->value('order_serial_style') === Business::SERIAL_STYLE_SIMPLE;

            return $simple ? (string) $number : 'ORD-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
        });
    }

    public function createItem(CounterOrder $order, array $attributes): CounterOrderItem
    {
        return DB::transaction(function () use ($order, $attributes): CounterOrderItem {
            $lockedOrder = CounterOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $sequence = max(1, (int) $lockedOrder->next_item_sequence);
            $lockedOrder->forceFill(['next_item_sequence' => $sequence + 1])->save();

            // A suit follows its order's own number style: 108-1 for a plain-number order, ORD-000108-01 otherwise,
            // so orders made before a style change keep matching suit numbers.
            $orderNumber = (string) $lockedOrder->displayNumber();
            $simple = ctype_digit($orderNumber);
            $serial = $orderNumber.'-'.($simple ? (string) $sequence : str_pad((string) $sequence, 2, '0', STR_PAD_LEFT));
            // Simple numbers: the order's first stitching job is the plain order number (109) for the tailor's
            // slip and tag; later jobs and cloth items keep 109-N, so no two items share a number.
            if ($simple && ($attributes['type'] ?? null) === CounterOrderItem::TYPE_TAILORING
                && ! $lockedOrder->items()->where('item_serial', $orderNumber)->exists()) {
                $serial = $orderNumber;
            }

            return $lockedOrder->items()->create(array_merge($attributes, [
                'item_sequence' => $sequence,
                'item_serial' => $serial,
            ]));
        });
    }
}
