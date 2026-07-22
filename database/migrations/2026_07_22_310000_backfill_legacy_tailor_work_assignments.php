<?php

use App\Models\Order;
use App\Models\TailorRecord;
use App\Services\ProductionWorkforceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_work_assignments')) {
            return;
        }

        $service = app(ProductionWorkforceService::class);
        Order::query()
            ->whereNotNull('tailorId')
            ->where('tailorId', '!=', '')
            ->with('tailor')
            ->chunkById(200, function ($orders) use ($service) {
                DB::transaction(function () use ($orders, $service) {
                    foreach ($orders as $order) {
                        if ($order->tailor) {
                            $service->syncOrder($order);
                        }
                    }
                });
            });

        TailorRecord::query()
            ->whereNotNull('order_id')
            ->where('amount', '>', 0)
            ->with('order')
            ->chunkById(200, function ($records) use ($service) {
                DB::transaction(function () use ($records, $service) {
                    foreach ($records as $record) {
                        if ($record->order) {
                            $service->recordTailorPayment($record->order, $record);
                        }
                    }
                });
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_work_assignments')) {
            return;
        }

        DB::table('worker_ledger_entries')
            ->where('legacy_key', 'like', 'tailor-order-earning:%')
            ->orWhere('legacy_key', 'like', 'tailor-record-payment:%')
            ->delete();
        DB::table('order_work_assignments')->where('legacy_key', 'like', 'tailor-order:%')->delete();
    }
};
