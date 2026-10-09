<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\MeasurementTemplate;
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
    public const MEASUREMENT_SOURCE = 'performance_qa';

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

            $templates = $this->seedMeasurementTemplates($owner);
            $this->assignCustomerTemplates($owner, $profiles, $templates);
            $this->seedMeasurementHistories($owner, $roots, $profiles, $templates);
            $this->seedOrders($owner, $roots, $profiles, $templates);
            $this->normalizeOrderTimelineAndTemplates($owner, $templates);
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

    private function seedMeasurementTemplates(User $owner): array
    {
        $definitions = [
            'shalwar_kameez' => [
                'name' => 'QA Shalwar Kameez',
                'description' => 'Performance QA adult shalwar kameez measurements.',
                'system_fields' => ['length', 'arms', 'shalwar', 'pancha'],
                'is_default' => true,
            ],
            'waistcoat' => [
                'name' => 'QA Waistcoat',
                'description' => 'Performance QA waistcoat measurements.',
                'system_fields' => ['length', 'teraa', 'senaChorai'],
                'is_default' => false,
            ],
            'school_uniform' => [
                'name' => 'QA School Uniform',
                'description' => 'Performance QA child school uniform measurements.',
                'system_fields' => ['length', 'arms', 'teraa'],
                'is_default' => false,
            ],
        ];

        $templates = [];
        foreach ($definitions as $key => $definition) {
            $templates[$key] = MeasurementTemplate::updateOrCreate(
                ['user_id' => $owner->id, 'name' => $definition['name']],
                $definition + ['custom_field_ids' => [], 'is_active' => true]
            );
        }

        return $templates;
    }

    private function assignCustomerTemplates(User $owner, $profiles, array $templates): void
    {
        DB::table('customers')
            ->where('user_id', $owner->id)
            ->where('acquisition_source', 'performance_qa')
            ->update(['measurement_template_id' => $templates['shalwar_kameez']->id]);

        $schoolProfileIds = $profiles->filter(fn ($profile) => $profile->serial_number % 2 === 0)->pluck('id');
        $shalwarProfileIds = $profiles->filter(fn ($profile) => $profile->serial_number % 2 !== 0)->pluck('id');
        if ($schoolProfileIds->isNotEmpty()) {
            DB::table('customers')->whereIn('id', $schoolProfileIds)
                ->update(['measurement_template_id' => $templates['school_uniform']->id]);
        }
        if ($shalwarProfileIds->isNotEmpty()) {
            DB::table('customers')->whereIn('id', $shalwarProfileIds)
                ->update(['measurement_template_id' => $templates['shalwar_kameez']->id]);
        }
    }

    private function seedMeasurementHistories(User $owner, $roots, $profiles, array $templates): void
    {
        $expectedHistories = ($roots->count() + $profiles->count()) * 2;
        $expectedValues = ($roots->count() * 7) + ($profiles->count() * 7);
        $existingHistories = DB::table('customer_measurement_histories')
            ->where('user_id', $owner->id)
            ->where('source', self::MEASUREMENT_SOURCE)
            ->count();
        $existingValues = DB::table('customer_measurement_history_values as values')
            ->join('customer_measurement_histories as histories', 'histories.id', '=', 'values.customer_measurement_history_id')
            ->where('histories.user_id', $owner->id)
            ->where('histories.source', self::MEASUREMENT_SOURCE)
            ->count();

        if ($existingHistories === $expectedHistories && $existingValues === $expectedValues) {
            return;
        }
        if ($existingHistories !== 0 || $existingValues !== 0) {
            throw new RuntimeException("Partial performance measurement set found for owner {$owner->id}; no records were added.");
        }

        $now = now()->format('Y-m-d H:i:s');
        $historyRows = [];
        foreach ($roots as $customer) {
            foreach (['shalwar_kameez', 'waistcoat'] as $templateKey) {
                $historyRows[] = $this->measurementHistoryRow($owner, $customer->id, $templates[$templateKey]->id, $now);
            }
        }
        foreach ($profiles as $customer) {
            foreach (['shalwar_kameez', 'school_uniform'] as $templateKey) {
                $historyRows[] = $this->measurementHistoryRow($owner, $customer->id, $templates[$templateKey]->id, $now);
            }
        }
        foreach (array_chunk($historyRows, 1000) as $chunk) {
            DB::table('customer_measurement_histories')->insert($chunk);
        }

        $customers = $roots->concat($profiles)->keyBy(fn ($customer) => (int) $customer->id);
        $templateFields = [
            $templates['shalwar_kameez']->id => ['length', 'arms', 'shalwar', 'pancha'],
            $templates['waistcoat']->id => ['length', 'teraa', 'senaChorai'],
            $templates['school_uniform']->id => ['length', 'arms', 'teraa'],
        ];
        $valueRows = [];
        $histories = DB::table('customer_measurement_histories')
            ->where('user_id', $owner->id)
            ->where('source', self::MEASUREMENT_SOURCE)
            ->get(['id', 'customer_id', 'measurement_template_id']);
        foreach ($histories as $history) {
            $customer = $customers->get((int) $history->customer_id);
            foreach ($templateFields[(int) $history->measurement_template_id] as $sortOrder => $field) {
                $valueRows[] = [
                    'customer_measurement_history_id' => $history->id,
                    'measurement_field_id' => null,
                    'source_key' => 'system.'.$field,
                    'label' => $this->measurementLabel($field),
                    'value' => $this->measurementValue($field, (int) $customer->serial_number),
                    'unit' => 'inch',
                    'sort_order' => $sortOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($valueRows, 1000) as $chunk) {
            DB::table('customer_measurement_history_values')->insert($chunk);
        }
    }

    private function measurementHistoryRow(User $owner, int $customerId, int $templateId, string $now): array
    {
        return [
            'user_id' => $owner->id,
            'customer_id' => $customerId,
            'measurement_template_id' => $templateId,
            'recorded_by_user_id' => $owner->id,
            'source' => self::MEASUREMENT_SOURCE,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function measurementLabel(string $field): string
    {
        return [
            'length' => 'لمبائی',
            'arms' => 'بازو',
            'teraa' => 'تیرا',
            'senaChorai' => 'سینہ چوڑائی',
            'shalwar' => 'شلوار',
            'pancha' => 'پائنچہ',
        ][$field];
    }

    private function measurementValue(string $field, int $serial): string
    {
        return (string) ([
            'length' => 32 + ($serial % 13),
            'arms' => 18 + ($serial % 8),
            'teraa' => 14 + ($serial % 7),
            'senaChorai' => 18 + ($serial % 8),
            'shalwar' => 30 + ($serial % 13),
            'pancha' => 6 + ($serial % 5),
        ][$field]);
    }

    private function seedOrders(User $owner, $roots, $profiles, array $templates): void
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
                    $sequence === 1 ? 'delivered' : 'assigned',
                    $sequence === 1 ? now()->subDays(30)->toDateString() : now()->addDays(10)->toDateString(),
                    $sequence === 1 ? $templates['shalwar_kameez']->id : $templates['waistcoat']->id,
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
                'stitching',
                now()->addDays(14)->toDateString(),
                $templates['school_uniform']->id,
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
        string $status,
        string $returnDate,
        int $measurementTemplateId,
    ): array {
        return [
            'sub_customer' => (string) $profileId,
            'measurement_template_id' => $measurementTemplateId,
            'customerId' => (string) $customerId,
            'suitQuantity' => '1',
            'totalPayment' => (string) $total,
            'designPrice' => (string) $total,
            'suitNum' => json_encode([$reference]),
            'design' => 'Performance QA standard suit',
            'returnDate' => $returnDate,
            'userId' => (string) $ownerId,
            'remarks' => $reference,
            'status' => $status,
            'status_changed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function normalizeOrderTimelineAndTemplates(User $owner, array $templates): void
    {
        $base = DB::table('orders')
            ->where('userId', (string) $owner->id)
            ->where('remarks', 'like', 'PERF-QA|%');

        (clone $base)->where('remarks', 'like', 'PERF-QA|R%|1')->update([
            'measurement_template_id' => $templates['shalwar_kameez']->id,
            'status' => 'delivered',
            'returnDate' => now()->subDays(30)->toDateString(),
            'status_changed_at' => now()->subDays(25),
            'created_at' => now()->subDays(60),
            'updated_at' => now()->subDays(25),
        ]);
        (clone $base)->where('remarks', 'like', 'PERF-QA|R%|2')->update([
            'measurement_template_id' => $templates['waistcoat']->id,
            'status' => 'assigned',
            'returnDate' => now()->addDays(10)->toDateString(),
            'status_changed_at' => now()->subDays(2),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(2),
        ]);
        (clone $base)->where('remarks', 'like', 'PERF-QA|F%|1')->update([
            'measurement_template_id' => $templates['school_uniform']->id,
            'status' => 'stitching',
            'returnDate' => now()->addDays(14)->toDateString(),
            'status_changed_at' => now()->subDay(),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDay(),
        ]);
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
