<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'serial_number')) {
            return;
        }

        DB::table('customers')
            ->whereNotNull('user_id')
            ->distinct()
            ->orderBy('user_id')
            ->pluck('user_id')
            ->each(function ($ownerId) {
                DB::transaction(function () use ($ownerId) {
                    $customers = DB::table('customers')
                        ->where('user_id', $ownerId)
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get(['id', 'serial_number']);

                    $previousSerial = 0;
                    $repairs = [];

                    foreach ($customers as $customer) {
                        $serial = (int) ($customer->serial_number ?? 0);
                        if ($serial <= $previousSerial) {
                            $serial = $previousSerial + 1;
                        }

                        if ((int) ($customer->serial_number ?? 0) !== $serial) {
                            $repairs[(int) $customer->id] = $serial;
                        }

                        $previousSerial = $serial;
                    }

                    if ($repairs !== []) {
                        // Free stale values first so the per-shop unique key cannot
                        // collide while restored rows are put back in sequence.
                        DB::table('customers')->whereIn('id', array_keys($repairs))->update(['serial_number' => null]);

                        foreach ($repairs as $customerId => $serial) {
                            DB::table('customers')->where('id', $customerId)->update(['serial_number' => $serial]);
                        }
                    }

                    if (Schema::hasTable('customer_serial_sequences')) {
                        DB::table('customer_serial_sequences')->insertOrIgnore([
                            'user_id' => $ownerId,
                            'next_number' => $previousSerial + 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        DB::table('customer_serial_sequences')->where('user_id', $ownerId)->update([
                            'next_number' => $previousSerial + 1,
                            'updated_at' => now(),
                        ]);
                    }
                });
            });
    }

    public function down(): void
    {
        // Data repair is intentionally irreversible.
    }
};
