<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class PerformanceQaSeeder extends Seeder
{
    public const PASSWORD = 'Performance@2026';
    public const SHOP_COUNT = 10;
    public const ROOT_CUSTOMERS_PER_SHOP = 1000;
    public const FAMILY_PROFILE_INTERVAL = 4;

    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'shop_owner', 'guard_name' => 'web']);

        for ($shopNumber = 1; $shopNumber <= self::SHOP_COUNT; $shopNumber++) {
            $owner = $this->owner($shopNumber, $role);
            $this->seedShop($owner, $shopNumber);
            $this->command?->info(sprintf(
                'Performance QA shop %02d ready: %d customer accounts.',
                $shopNumber,
                self::ROOT_CUSTOMERS_PER_SHOP
            ));
        }
    }

    private function owner(int $shopNumber, Role $role): User
    {
        $email = sprintf('performance.owner%02d@buynstitch.test', $shopNumber);
        $owner = User::updateOrCreate(['email' => $email], [
            'name' => sprintf('Performance Owner %02d', $shopNumber),
            'phone' => sprintf('0315%07d', $shopNumber),
            'address' => 'Local performance QA dataset',
            'password' => Hash::make(self::PASSWORD),
            'tailoring_access' => true,
            'clothing_access' => true,
            'is_business_owner' => true,
            'employee_active' => true,
        ]);
        $owner->forceFill(['email_verified_at' => now()])->saveQuietly();
        $owner->syncRoles([$role]);

        $business = Business::updateOrCreate(['owner_user_id' => $owner->id], [
            'name' => sprintf('Performance QA Shop %02d', $shopNumber),
            'tailoring_enabled' => true,
            'clothing_enabled' => true,
            'status' => Business::STATUS_ACTIVE,
            'is_demo' => true,
            'approved_at' => now(),
            'status_changed_at' => now(),
            'status_reason' => 'Local performance QA dataset.',
        ]);
        $owner->forceFill(['business_id' => $business->id])->saveQuietly();

        return $owner;
    }

    private function seedShop(User $owner, int $shopNumber): void
    {
        DB::transaction(function () use ($owner, $shopNumber) {
            $this->seedRootCustomers($owner, $shopNumber);
            $roots = DB::table('customers')
                ->where('user_id', $owner->id)
                ->where('acquisition_source', 'performance_qa')
                ->whereNull('parent_id')
                ->orderBy('serial_number')
                ->get(['id', 'serial_number', 'phone_number1']);

            if ($roots->count() !== self::ROOT_CUSTOMERS_PER_SHOP) {
                throw new RuntimeException("Performance QA customer set is incomplete for owner {$owner->id}.");
            }

            $this->seedFamilyProfiles($owner, $shopNumber, $roots);
            $profiles = DB::table('customers')
                ->where('user_id', $owner->id)
                ->where('acquisition_source', 'performance_qa_family')
                ->orderBy('serial_number')
                ->get(['id', 'serial_number', 'parent_id']);

            $this->seedOrders($owner, $roots, $profiles);
            $this->seedTransactions($owner);

            DB::table('customer_serial_sequences')->updateOrInsert(
                ['user_id' => $owner->id],
                [
                    'next_number' => self::ROOT_CUSTOMERS_PER_SHOP + $profiles->count() + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        });
    }

    private function seedRootCustomers(User $owner, int $shopNumber): void
    {
        $existing = DB::table('customers')
            ->where('user_id', $owner->id)
            ->where('acquisition_source', 'performance_qa')
            ->whereNull('parent_id')
            ->count();

        if ($existing === self::ROOT_CUSTOMERS_PER_SHOP) {
            return;
        }
        if ($existing !== 0) {
            throw new RuntimeException("Partial performance customer set found for owner {$owner->id}; no records were added.");
        }

        $now = now()->format('Y-m-d H:i:s');
        $rows = [];
        for ($number = 1; $number <= self::ROOT_CUSTOMERS_PER_SHOP; $number++) {
            $subscriber = (($shopNumber * 1000000) + $number) % 1000000000;
            $phone = '03'.str_pad((string) $subscriber, 9, '0', STR_PAD_LEFT);
            $rows[] = [
                'name' => sprintf('Performance Customer %02d-%04d', $shopNumber, $number),
                'phone_number1' => $phone,
                'phone_number1_normalized' => '+92'.substr($phone, 1),
                'phone_normalization_conflict' => false,
                'user_id' => $owner->id,
                'serial_number' => $number,
                'parent_id' => null,
                'is_walk_in' => false,
                'acquisition_source' => 'performance_qa',
                'length' => (string) (40 + ($number % 5)),
                'arms' => (string) (23 + ($number % 4)),
                'teraa' => (string) (17 + ($number % 3)),
                'senaChorai' => (string) (21 + ($number % 5)),
                'shalwar' => (string) (39 + ($number % 4)),
                'pancha' => (string) (7 + ($number % 3)),
                'note' => 'Local performance QA customer',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) === 500) {
                DB::table('customers')->insert($rows);
                $rows = [];
            }
        }
        if ($rows !== []) {
            DB::table('customers')->insert($rows);
        }
    }

    private function seedFamilyProfiles(User $owner, int $shopNumber, $roots): void
    {
        $target = intdiv(self::ROOT_CUSTOMERS_PER_SHOP, self::FAMILY_PROFILE_INTERVAL);
        $existing = DB::table('customers')
            ->where('user_id', $owner->id)
            ->where('acquisition_source', 'performance_qa_family')
            ->count();

        if ($existing === $target) {
            return;
        }
        if ($existing !== 0) {
            throw new RuntimeException("Partial performance family set found for owner {$owner->id}; no records were added.");
        }

        $now = now()->format('Y-m-d H:i:s');
        $rows = [];
        $familyNumber = 0;
        foreach ($roots as $index => $root) {
            if (($index + 1) % self::FAMILY_PROFILE_INTERVAL !== 0) {
                continue;
            }
            $familyNumber++;
            $rows[] = [
                'name' => sprintf('Family Member %02d-%04d', $shopNumber, $familyNumber),
                'phone_number1' => $root->phone_number1,
                'phone_number1_normalized' => null,
                'phone_normalization_conflict' => true,
                'user_id' => $owner->id,
                'serial_number' => self::ROOT_CUSTOMERS_PER_SHOP + $familyNumber,
                'parent_id' => (string) $root->id,
                'is_walk_in' => false,
                'acquisition_source' => 'performance_qa_family',
                'length' => (string) (32 + ($familyNumber % 8)),
                'arms' => (string) (18 + ($familyNumber % 6)),
                'note' => 'Local performance QA family profile',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('customers')->insert($chunk);
        }
    }

    private function seedOrders(User $owner, $roots, $profiles): void
    {
        $target = ($roots->count() * 2) + $profiles->count();
        $existing = DB::table('orders')
            ->where('userId', (string) $owner->id)
            ->where('remarks', 'like', 'PERF-QA|%')
            ->count();

        if ($existing === $target) {
            return;
        }
        if ($existing !== 0) {
            throw new RuntimeException("Partial performance order set found for owner {$owner->id}; no records were added.");
        }

        $now = now()->format('Y-m-d H:i:s');
        $rows = [];
        foreach ($roots as $root) {
            foreach ([1, 2] as $sequence) {
                $total = 1800 + (($root->serial_number + $sequence) % 18) * 175;
                $rows[] = $this->orderRow(
                    $owner->id,
                    $root->id,
                    $root->id,
                    $total,
                    "PERF-QA|R{$root->serial_number}|{$sequence}",
                    $now,
                    ($root->serial_number + $sequence) % 4
                );
            }
        }
        foreach ($profiles as $profile) {
            $total = 1500 + ($profile->serial_number % 12) * 150;
            $rows[] = $this->orderRow(
                $owner->id,
                (int) $profile->parent_id,
                $profile->id,
                $total,
                "PERF-QA|F{$profile->serial_number}|1",
                $now,
                $profile->serial_number % 4
            );
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('orders')->insert($chunk);
        }
    }

    private function orderRow(
        int $ownerId,
        int $customerId,
        int $profileId,
        int $total,
        string $reference,
        string $now,
        int $statusIndex,
    ): array {
        $statuses = ['assigned', 'stitching', 'ready', 'delivered'];

        return [
            'sub_customer' => (string) $profileId,
            'customerId' => (string) $customerId,
            'suitQuantity' => '1',
            'totalPayment' => (string) $total,
            'designPrice' => (string) $total,
            'suitNum' => json_encode([$reference]),
            'design' => 'Performance QA standard suit',
            'returnDate' => now()->addDays(7 + ($profileId % 14))->toDateString(),
            'userId' => (string) $ownerId,
            'remarks' => $reference,
            'status' => $statuses[$statusIndex],
            'status_changed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function seedTransactions(User $owner): void
    {
        $orders = DB::table('orders')
            ->where('userId', (string) $owner->id)
            ->where('remarks', 'like', 'PERF-QA|%')
            ->get(['id', 'customerId', 'totalPayment']);
        $existing = DB::table('transactions')
            ->where('userId', (string) $owner->id)
            ->where('comment', 'Performance QA order payment')
            ->count();

        if ($existing === $orders->count()) {
            return;
        }
        if ($existing !== 0) {
            throw new RuntimeException("Partial performance transaction set found for owner {$owner->id}; no records were added.");
        }

        $now = now()->format('Y-m-d H:i:s');
        $rows = [];
        foreach ($orders as $order) {
            $total = (float) $order->totalPayment;
            $received = round($total * 0.6, 2);
            $rows[] = [
                'remainingBalance' => (string) ($total - $received),
                'recivedPayment' => (string) $received,
                'customerId' => (string) $order->customerId,
                'userId' => (string) $owner->id,
                'orderId' => (string) $order->id,
                'comment' => 'Performance QA order payment',
                'payment_method' => 'cash',
                'paid_on' => now()->toDateString(),
                'Order_type' => 'Tailor',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('transactions')->insert($chunk);
        }
    }
}
