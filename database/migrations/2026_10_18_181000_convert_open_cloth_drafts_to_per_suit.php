<?php

use App\Models\Cloth;
use App\Models\CounterOrderItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        CounterOrderItem::query()
            ->where('type', CounterOrderItem::TYPE_CLOTH)
            ->where('status', CounterOrderItem::STATUS_DRAFT)
            ->orderBy('id')
            ->get()
            ->each(function (CounterOrderItem $item) {
                $details = $item->details ?? [];
                if (data_get($details, 'sale_price_basis') === Cloth::SALE_PRICE_PER_SUIT) {
                    return;
                }

                $quantity = max(1, (float) ($item->quantity ?: 1));
                data_set($details, 'sale_price_basis', Cloth::SALE_PRICE_PER_SUIT);
                $item->forceFill([
                    'quantity' => $quantity,
                    'unit_price' => round((float) $item->line_total / $quantity, 2),
                    'details' => $details,
                ])->save();
            });
    }

    public function down(): void
    {
        // Historical draft pricing cannot be reconstructed safely.
    }
};
