<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class CustomerSerialNumberService
{
    public function nextFor(int $ownerId): int
    {
        return DB::transaction(function () use ($ownerId) {
            $nextFromCustomers = ((int) DB::table('customers')
                ->where('user_id', $ownerId)
                ->max('serial_number')) + 1;

            DB::table('customer_serial_sequences')->insertOrIgnore([
                'user_id' => $ownerId,
                'next_number' => $nextFromCustomers,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('customer_serial_sequences')
                ->where('user_id', $ownerId)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                throw new RuntimeException('Unable to allocate a customer serial number.');
            }

            $serialNumber = max((int) $sequence->next_number, $nextFromCustomers);

            DB::table('customer_serial_sequences')
                ->where('user_id', $ownerId)
                ->update([
                    'next_number' => $serialNumber + 1,
                    'updated_at' => now(),
                ]);

            return $serialNumber;
        }, 3);
    }
}
