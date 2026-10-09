<?php

namespace Database\Seeders;

use App\Models\ClothColor;
use App\Models\CounterSaleReceipt;
use App\Models\Customers;
use App\Models\MeasurementTemplate;
use App\Models\Order;
use App\Models\Options;
use App\Models\OptionType;
use App\Models\ProductionWorker;
use App\Models\SaleStock;
use App\Models\Tailor;
use App\Models\Tailorsalary;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkType;
use App\Services\MeasurementService;
use App\Services\TailoringOptionDefaultsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ChinnarWorkflowQaSeeder extends Seeder
{
    public const OWNER_EMAIL = 'hkhan.swa@gmail.com';
    public const SOURCE = 'chinnar_workflow_qa';

    public function run(): void
    {
        $owner = User::where('email', self::OWNER_EMAIL)->first();
        if (! $owner) {
            throw new RuntimeException('Chinnar Fabrics owner was not found.');
        }

        DB::transaction(function () use ($owner) {
            $templates = $this->templates($owner);
            $profiles = $this->customers($owner, $templates);
            $this->measurementHistories($owner, $profiles, $templates);
            [$tailors, $rates] = $this->workforce($owner);
            $this->orders($owner, $profiles, $templates, $tailors, $rates);
            $this->activeClothSale($owner, $profiles['farhan']);
        });

        $this->command?->info('Chinnar Fabrics workflow QA data is ready.');
    }

    private function templates(User $owner): array
    {
        $definitions = [
            'shalwar_kameez' => [
                'name' => 'مردانہ شلوار قمیض',
                'description' => 'قمیض اور شلوار کی مکمل بنیادی پیمائش',
                'system_fields' => ['length', 'arms', 'teraa', 'senaChorai', 'damanchorai', 'shalwar', 'pancha', 'shoulder'],
                'is_default' => true,
            ],
            'waistcoat' => [
                'name' => 'ویسٹ کوٹ',
                'description' => 'ویسٹ کوٹ کے لیے الگ پیمائش',
                'system_fields' => ['length', 'teraa', 'senaChorai', 'shoulder'],
                'is_default' => false,
            ],
            'school_uniform' => [
                'name' => 'اسکول یونیفارم',
                'description' => 'بچوں کے اسکول یونیفارم کی پیمائش',
                'system_fields' => ['length', 'arms', 'teraa', 'senaChorai', 'shalwar', 'pancha'],
                'is_default' => false,
            ],
        ];

        $templates = [];
        foreach ($definitions as $key => $definition) {
            $templates[$key] = MeasurementTemplate::updateOrCreate(
                ['user_id' => $owner->id, 'name' => $definition['name']],
                $definition + [
                    'custom_field_ids' => [],
                    'layout_columns' => 2,
                    'is_active' => true,
                ]
            );
        }

        return $templates;
    }

    private function customers(User $owner, array $templates): array
    {
        $definitions = [
            'farhan' => ['Farhan Ali', '03009991001', null, 'shalwar_kameez', 42, 24, 18.5, 23, 41, 8.5],
            'ali' => ['Ali Farhan', '03009991001', 'farhan', 'school_uniform', 31, 18, 14, 17, 30, 6],
            'tariq' => ['Tariq Mehmood', '03009991002', null, 'shalwar_kameez', 43, 24.5, 19, 24, 42, 9],
            'hamza' => ['Hamza Tariq', '03009991002', 'tariq', 'school_uniform', 34, 20, 15, 18, 33, 6.5],
            'usman' => ['Usman Khan', '03009991003', null, 'shalwar_kameez', 41, 23.5, 18, 22, 40, 8],
            'saad' => ['Saad Usman', '03009991003', 'usman', 'school_uniform', 36, 21, 16, 19, 35, 7],
        ];

        $profiles = [];
        foreach ($definitions as $key => [$name, $phone, $parentKey, $templateKey, $length, $arms, $teraa, $chest, $shalwar, $pancha]) {
            $parentId = $parentKey ? $profiles[$parentKey]->id : null;
            $customer = Customers::withTrashed()->firstOrNew([
                'user_id' => $owner->id,
                'name' => $name,
                'acquisition_source' => self::SOURCE,
            ]);
            $customer->fill([
                'phone_number1' => $phone,
                'parent_id' => $parentId,
                'is_walk_in' => false,
                'measurement_template_id' => $templates[$templateKey]->id,
                'length' => $length,
                'arms' => $arms,
                'teraa' => $teraa,
                'senaChorai' => $chest,
                'damanchorai' => $chest + 2,
                'shalwar' => $shalwar,
                'pancha' => $pancha,
                'shoulder' => $teraa,
                'note' => 'Chinnar Fabrics local workflow QA profile',
                'mobile_pin' => Hash::make('123456'),
            ]);
            $customer->deleted_at = null;
            $customer->save();
            $profiles[$key] = $customer;
        }

        return $profiles;
    }

    private function measurementHistories(User $owner, array $profiles, array $templates): void
    {
        $service = app(MeasurementService::class);
        $profileTemplates = [
            'farhan' => ['shalwar_kameez', 'waistcoat'],
            'ali' => ['shalwar_kameez', 'school_uniform'],
            'tariq' => ['shalwar_kameez', 'waistcoat'],
            'hamza' => ['shalwar_kameez', 'school_uniform'],
            'usman' => ['shalwar_kameez', 'waistcoat'],
            'saad' => ['shalwar_kameez', 'school_uniform'],
        ];

        foreach ($profileTemplates as $profileKey => $templateKeys) {
            foreach ($templateKeys as $templateKey) {
                $exists = DB::table('customer_measurement_histories')
                    ->where('user_id', $owner->id)
                    ->where('customer_id', $profiles[$profileKey]->id)
                    ->where('measurement_template_id', $templates[$templateKey]->id)
                    ->where('source', self::SOURCE)
                    ->exists();
                if (! $exists) {
                    $service->recordHistory(
                        $profiles[$profileKey],
                        $owner->id,
                        $templates[$templateKey],
                        $owner->id,
                        self::SOURCE,
                    );
                }
            }
        }
    }

    private function workforce(User $owner): array
    {
        app(TailoringOptionDefaultsService::class)->seedForOwner($owner->id);
        $sewingTypeId = OptionType::where('slug', TailoringOptionDefaultsService::SEWING_TYPE_SLUG)->value('id');
        $sewingOption = Options::where('user_id', $owner->id)
            ->where('option_id', $sewingTypeId)
            ->where('Name', 'سادہ')
            ->first() ?? Options::where('user_id', $owner->id)->where('option_id', $sewingTypeId)->firstOrFail();

        $tailorDefinitions = [
            ['Akram Darzi', '03009992001', 'akram.darzi@chinnar.test', 950],
            ['Naveed Master', '03009992002', 'naveed.master@chinnar.test', 1100],
        ];
        $tailors = [];
        $rates = [];
        foreach ($tailorDefinitions as [$name, $phone, $email, $price]) {
            $tailor = Tailor::withTrashed()->updateOrCreate(
                ['user_id' => $owner->id, 'email' => $email],
                ['name' => $name, 'phone_number1' => $phone, 'password' => Hash::make('Tailor@2026'), 'deleted_at' => null]
            );
            $rate = Tailorsalary::updateOrCreate(
                ['tailor_id' => $tailor->id, 'options_id' => $sewingOption->id],
                ['type' => $sewingOption->Name, 'price' => $price]
            );
            $tailors[] = $tailor;
            $rates[] = $rate;
        }

        $workTypes = collect([
            'stitching' => 'سلائی',
            'cutting' => 'کٹائی',
            'embroidery' => 'کڑھائی',
            'finishing' => 'فنشنگ اور بٹن',
            'ironing' => 'استری',
            'quality_check' => 'معیار کی جانچ',
        ])->mapWithKeys(function (string $name, string $code) use ($owner) {
            $workType = WorkType::updateOrCreate(
                ['user_id' => $owner->id, 'code' => $code],
                ['name' => $name, 'category' => 'production', 'is_system' => true, 'active' => true]
            );

            return [$code => $workType];
        });

        foreach ($tailors as $index => $tailor) {
            $worker = ProductionWorker::updateOrCreate(
                ['legacy_tailor_id' => $tailor->id],
                [
                    'user_id' => $owner->id,
                    'name' => $tailor->name,
                    'phone' => $tailor->phone_number1,
                    'email' => $tailor->email,
                    'relationship_type' => 'contractor',
                    'active' => true,
                    'notes' => 'Chinnar Fabrics local workflow QA worker',
                ]
            );
            $worker->skills()->syncWithoutDetaching([
                $workTypes['stitching']->id,
                $index === 0 ? $workTypes['cutting']->id : $workTypes['finishing']->id,
            ]);
        }

        foreach ([
            ['Shahid Embroidery', '03009992003', 'embroidery'],
            ['Bilal Pressman', '03009992004', 'ironing'],
        ] as [$name, $phone, $skill]) {
            $worker = ProductionWorker::updateOrCreate(
                ['user_id' => $owner->id, 'phone' => $phone],
                [
                    'name' => $name,
                    'relationship_type' => 'employee',
                    'active' => true,
                    'notes' => 'Chinnar Fabrics local workflow QA worker',
                ]
            );
            $worker->skills()->syncWithoutDetaching([$workTypes[$skill]->id]);
        }

        return [$tailors, $rates];
    }

    private function orders(
        User $owner,
        array $profiles,
        array $templates,
        array $tailors,
        array $rates,
    ): void {
        $definitions = [
            ['farhan', 'shalwar_kameez', 'delivered', -35, 4800, 4800, 0, 'Past delivered adult shalwar kameez'],
            ['farhan', 'waistcoat', 'assigned', 12, 3600, 1500, 1, 'Future waistcoat awaiting production'],
            ['ali', 'school_uniform', 'delivered', -24, 3200, 3200, 0, 'Past delivered child school uniform'],
            ['ali', 'school_uniform', 'unassigned', 15, 3400, 1200, null, 'Future child school uniform waiting for tailor'],
            ['tariq', 'shalwar_kameez', 'delivered', -18, 5200, 5200, 1, 'Past delivered customer order'],
            ['tariq', 'waistcoat', 'stitching', 9, 3900, 2000, 0, 'Future waistcoat currently stitching'],
            ['hamza', 'school_uniform', 'cutting', 7, 3500, 1000, 1, 'Future family profile school uniform'],
            ['usman', 'shalwar_kameez', 'ready', 4, 5000, 2500, 0, 'Future order ready for handover'],
            ['saad', 'school_uniform', 'unassigned', 18, 3300, 800, null, 'Future family order waiting for assignment'],
        ];
        $measurementService = app(MeasurementService::class);

        foreach ($definitions as $index => [$profileKey, $templateKey, $status, $dayOffset, $total, $received, $tailorIndex, $label]) {
            $profile = $profiles[$profileKey];
            $primary = $profile->parent_id ? $profiles[array_search((int) $profile->parent_id, array_map(fn ($item) => $item->id, $profiles), true)] ?? null : $profile;
            if (! $primary) {
                $primary = Customers::findOrFail($profile->parent_id);
            }
            $remarks = self::SOURCE.'|'.$label;
            $tailor = $tailorIndex === null ? null : $tailors[$tailorIndex];
            $rate = $tailorIndex === null ? null : $rates[$tailorIndex];
            $order = Order::updateOrCreate(
                ['userId' => $owner->id, 'remarks' => $remarks],
                [
                    'sub_customer' => (string) $profile->id,
                    'customerId' => (string) $primary->id,
                    'measurement_template_id' => $templates[$templateKey]->id,
                    'suitQuantity' => 1,
                    'totalPayment' => $total,
                    'designPrice' => $total,
                    'tailorId' => $tailor?->id,
                    'rateId' => $rate?->id,
                    'suitNum' => json_encode(['QA suit '.($index + 1)]),
                    'design' => $templates[$templateKey]->name,
                    'returnDate' => today()->addDays($dayOffset)->toDateString(),
                    'tailor_price' => $rate?->price ?? 0,
                    'status' => $status,
                    'status_changed_at' => now(),
                    'started_at' => in_array($status, ['cutting', 'stitching', 'ready', 'delivered'], true) ? now()->subDays(3) : null,
                    'ready_at' => in_array($status, ['ready', 'delivered'], true) ? now()->subDay() : null,
                    'delivered_at' => $status === 'delivered' ? today()->addDays($dayOffset)->endOfDay() : null,
                    'tailor_payment_status' => 'unpaid',
                ]
            );
            if ($dayOffset < 0) {
                $order->forceFill(['created_at' => today()->addDays($dayOffset - 8)])->saveQuietly();
            }
            $measurementService->snapshotOrder($order, $profile, $templates[$templateKey]);
            Transaction::updateOrCreate(
                ['userId' => (string) $owner->id, 'orderId' => (string) $order->id],
                [
                    'remainingBalance' => (string) ($total - $received),
                    'recivedPayment' => (string) $received,
                    'customerId' => (string) $primary->id,
                    'tailorId' => $tailor?->id,
                    'comment' => self::SOURCE.' order payment',
                    'Order_type' => 'Tailor',
                    'paid_on' => $dayOffset < 0 ? today()->addDays($dayOffset - 8) : today(),
                    'payment_method' => 'cash',
                ]
            );
        }
    }

    private function activeClothSale(User $owner, Customers $customer): void
    {
        $receipt = CounterSaleReceipt::updateOrCreate(
            ['receipt_number' => 'QA-CHINNAR-ACTIVE-01'],
            [
                'user_id' => $owner->id,
                'customer_id' => $customer->id,
                'status' => 'completed',
                'cancellation_reason' => null,
                'cancelled_at' => null,
                'cancelled_by_user_id' => null,
            ]
        );
        if (SaleStock::where('counter_sale_receipt_id', $receipt->id)->exists()) {
            return;
        }

        $color = ClothColor::where('user_id', $owner->id)
            ->where('length', '>=', 3)
            ->with('cloth')
            ->orderByDesc('length')
            ->firstOrFail();
        $meters = 3.0;
        $salePrice = (float) ($color->cloth->sale_price ?: $color->cloth->price);
        $cost = (float) $color->average_unit_cost;
        $sale = SaleStock::create([
            'counter_sale_receipt_id' => $receipt->id,
            'cloth_type_id' => $color->cloth->cloth_type_id,
            'cloth_brand_id' => $color->cloth->cloth_brand_id,
            'color' => $color->color,
            'c_name' => $customer->name,
            'c_id' => $customer->id,
            'phone' => $customer->phone_number1,
            'user_id' => $owner->id,
            'profit' => max(0, ($salePrice - $cost) * $meters),
            'loss' => max(0, ($cost - $salePrice) * $meters),
            'length' => $meters,
            'sellDate' => now(),
            'selling_price' => $salePrice,
            'cloth_id' => $color->cloth_id,
            'cloth_color_id' => $color->id,
            'cost_per_meter' => $cost,
            'cost_total' => $cost * $meters,
        ]);
        $color->decrement('length', $meters);
        $receipt->update(['first_sale_stock_id' => $sale->id]);
        $customer->update(['first_sale_at' => now()]);
        Transaction::updateOrCreate(
            ['userId' => (string) $owner->id, 'counter_sale_receipt_id' => $receipt->id],
            [
                'remainingBalance' => '0',
                'recivedPayment' => (string) ($salePrice * $meters),
                'customerId' => (string) $customer->id,
                'comment' => self::SOURCE.' active cloth sale',
                'Order_type' => 'Cloth',
                'paid_on' => today(),
                'payment_method' => 'cash',
            ]
        );
    }
}
