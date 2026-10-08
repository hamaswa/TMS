<?php

namespace App\Services;

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

            return 'ORD-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
        });
    }

    public function createItem(CounterOrder $order, array $attributes): CounterOrderItem
    {
        return DB::transaction(function () use ($order, $attributes): CounterOrderItem {
            $lockedOrder = CounterOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $sequence = max(1, (int) $lockedOrder->next_item_sequence);
            $lockedOrder->forceFill(['next_item_sequence' => $sequence + 1])->save();

            return $lockedOrder->items()->create(array_merge($attributes, [
                'item_sequence' => $sequence,
                'item_serial' => $lockedOrder->displayNumber().'-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
            ]));
        });
    }
}
