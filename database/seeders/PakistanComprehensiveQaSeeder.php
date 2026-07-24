<?php

namespace Database\Seeders;

use App\Models\BusinessRole;
use App\Models\Customers;
use App\Models\Storefront;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PakistanComprehensiveQaSeeder extends Seeder
{
    private const PASSWORD = 'PakistanQA@2026';

    public function run(): void
    {
        DB::transaction(function () {
            $this->combinedBusiness();
            $this->tailoringBusiness();
            $this->clothingBusiness();
        });
    }

    private function combinedBusiness(): void
    {
        $owner = User::where('email', 'ahmed.khan@tms.test')->first();
        if (! $owner?->business) {
            return;
        }

        $storefront = $this->storefront($owner, [
            'slug' => 'khan-tailors-fabrics-rawalpindi',
            'display_name' => 'خان ٹیلرز اینڈ فیبرکس',
            'tagline' => 'راولپنڈی میں کپڑا، کٹنگ اور سلائی ایک ہی جگہ',
            'description' => 'مردانہ کپڑے، کسٹم پیمائش، ماہر کٹنگ اور مکمل سلائی کی سہولت۔ دکان سے وصولی اور مقامی ڈیلیوری دستیاب ہے۔',
            'public_phone' => '03001234567',
            'public_email' => 'ahmed.khan@tms.test',
            'address' => 'مین بازار، راولپنڈی، پنجاب',
            'city' => 'راولپنڈی',
            'show_clothing' => true,
            'show_tailoring' => true,
            'inquiries_enabled' => true,
            'pickup_enabled' => true,
            'delivery_enabled' => true,
        ]);

        $this->publishOwnerCloths($owner, $storefront, 'معیاری کپڑا، واضح قیمت اور موجودہ اسٹاک کے ساتھ۔');
        $this->services($storefront, [
            ['مردانہ شلوار قمیض', 'روزمرہ اور تقریبات کے لیے مکمل کٹنگ اور سلائی۔', 1700, 'فی سوٹ', 7, true],
            ['ایگزیکٹو ویسٹ کوٹ', 'استر، فٹنگ ٹرائل اور نفیس فنش کے ساتھ۔', 2600, 'فی لباس', 10, true],
            ['فوری الٹریشن', 'آستین، لمبائی، کمر اور بنیادی فٹنگ کی درستگی۔', 600, 'فی کام', 2, false],
        ]);
        $this->customers($owner, [
            ['حسن رضا', '03117001001', '246801', 'کمرشل مارکیٹ، راولپنڈی'],
            ['فاطمہ زہرہ', '03117001002', '246802', 'بحریہ ٹاؤن، راولپنڈی'],
            ['عبداللہ نعیم', '03117001003', '246803', 'چکلالہ، راولپنڈی'],
        ]);
        $this->employee(
            $owner,
            'ahmed.manager.qa',
            'مریم اقبال',
            'برانچ منیجر',
            array_keys(BusinessRole::PERMISSIONS),
            null,
            '03117001999'
        );
    }

    private function tailoringBusiness(): void
    {
        $owner = User::where('email', 'bilal.ahmed@tms.test')->first();
        if (! $owner?->business) {
            return;
        }

        $storefront = $this->storefront($owner, [
            'slug' => 'bilal-tailors-peshawar',
            'display_name' => 'بلال ٹیلرز پشاور',
            'tagline' => 'روایتی مہارت کے ساتھ جدید فٹنگ',
            'description' => 'پشاور میں مردانہ اور بچوں کے ملبوسات کی پیمائش، کٹنگ، سلائی اور الٹریشن کی خدمات۔',
            'public_phone' => '03011234567',
            'public_email' => 'bilal.ahmed@tms.test',
            'address' => 'صدر بازار، پشاور، خیبر پختونخوا',
            'city' => 'پشاور',
            'show_clothing' => false,
            'show_tailoring' => true,
            'inquiries_enabled' => true,
            'pickup_enabled' => true,
            'delivery_enabled' => false,
        ]);

        $this->services($storefront, [
            ['مردانہ شلوار قمیض سلائی', 'ماہر کٹنگ، مضبوط سلائی اور ایک فٹنگ ٹرائل۔', 1600, 'فی سوٹ', 7, true],
            ['بچوں کی شلوار قمیض', 'آرام دہ فٹنگ اور مضبوط سلائی۔', 1100, 'فی سوٹ', 5, true],
            ['پشاوری ویسٹ کوٹ', 'روایتی ڈیزائن اور مکمل استر کے ساتھ۔', 2800, 'فی لباس', 12, false],
            ['کپڑوں کی مرمت', 'زپ، بٹن، سلائی اور بنیادی مرمت۔', 400, 'فی کام', 2, false],
        ]);
        $this->customers($owner, [
            ['شہریار خان', '03337002001', '357901', 'حیات آباد، پشاور'],
            ['وقاص احمد', '03337002002', '357902', 'صدر، پشاور'],
            ['حمزہ یوسف', '03337002003', '357903', 'یونیورسٹی ٹاؤن، پشاور'],
        ]);
        $this->employee(
            $owner,
            'bilal.orders.qa',
            'عمر فاروق',
            'آرڈر منیجر',
            BusinessRole::ROLE_PRESETS['order_manager']['permissions'],
            'tailoring',
            '03337002991'
        );
        $this->employee(
            $owner,
            'bilal.workshop.qa',
            'سلمان شاہ',
            'ورکشاپ کوآرڈینیٹر',
            [
                BusinessRole::TAILORING_ACCESS,
                BusinessRole::TAILORING_WORKSHOP,
                BusinessRole::TAILORING_TAILORS,
            ],
            'tailoring',
            '03337002992'
        );
    }

    private function clothingBusiness(): void
    {
        $owner = User::where('email', 'usman.ali@tms.test')->first();
        if (! $owner?->business) {
            return;
        }

        $storefront = $this->storefront($owner, [
            'slug' => 'ali-fabrics-faisalabad',
            'display_name' => 'علی فیبرکس فیصل آباد',
            'tagline' => 'فیصل آباد سے معیاری کپڑا مناسب قیمت پر',
            'description' => 'مردانہ اور خواتین کے موسمی کپڑے، واضح فی میٹر قیمت، رنگوں کا انتخاب اور گھر تک فراہمی۔',
            'public_phone' => '03021234567',
            'public_email' => 'usman.ali@tms.test',
            'address' => 'کچہری بازار، فیصل آباد، پنجاب',
            'city' => 'فیصل آباد',
            'show_clothing' => true,
            'show_tailoring' => false,
            'inquiries_enabled' => false,
            'pickup_enabled' => true,
            'delivery_enabled' => true,
        ]);

        $this->publishOwnerCloths($owner, $storefront, 'فیصل آباد کا معیاری کپڑا، فی میٹر قیمت اور دستیاب رنگوں کے ساتھ۔');
        $this->customers($owner, [
            ['علی حیدر', '03217003001', '468101', 'مدینہ ٹاؤن، فیصل آباد'],
            ['صبا جاوید', '03217003002', '468102', 'پیپلز کالونی، فیصل آباد'],
            ['نعمان اکرم', '03217003003', '468103', 'ڈی گراؤنڈ، فیصل آباد'],
        ]);
        $this->employee(
            $owner,
            'usman.sales.qa',
            'عائشہ صدیق',
            'سیلز پرسن',
            BusinessRole::ROLE_PRESETS['salesperson']['permissions'],
            'shop',
            '03217003991'
        );
        $this->employee(
            $owner,
            'usman.stock.qa',
            'دانش محمود',
            'اسٹاک منیجر',
            BusinessRole::ROLE_PRESETS['stock_keeper']['permissions'],
            'shop',
            '03217003992'
        );
    }

    private function storefront(User $owner, array $attributes): Storefront
    {
        return Storefront::updateOrCreate(
            ['business_id' => $owner->business_id],
            [
                ...$attributes,
                'is_published' => true,
                'published_at' => now(),
            ]
        );
    }

    private function publishOwnerCloths(User $owner, Storefront $storefront, string $description): void
    {
        $owner->loadMissing('business');
        $cloths = $owner->businessOwnerId() === $owner->id
            ? \App\Models\Cloth::where('user_id', $owner->id)->with(['brand', 'type'])->get()
            : collect();

        foreach ($cloths as $index => $cloth) {
            $name = collect([$cloth->brand?->name, $cloth->type?->name])->filter()->implode(' — ');
            $storefront->clothingListings()->updateOrCreate(
                ['cloth_id' => $cloth->id],
                [
                    'public_name' => $name ?: 'معیاری کپڑا',
                    'description' => $description,
                    'is_featured' => $index === 0,
                    'is_published' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }
    }

    private function services(Storefront $storefront, array $services): void
    {
        foreach ($services as $index => [$name, $description, $price, $unit, $days, $featured]) {
            $storefront->tailoringServices()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'price_from' => $price,
                    'price_unit' => $unit,
                    'estimated_days' => $days,
                    'is_featured' => $featured,
                    'is_published' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }
    }

    private function customers(User $owner, array $customers): void
    {
        foreach ($customers as [$name, $phone, $pin, $address]) {
            Customers::updateOrCreate(
                ['user_id' => $owner->id, 'phone_number1' => $phone],
                [
                    'name' => $name,
                    'note' => $address,
                    'mobile_pin' => Hash::make($pin),
                    'pin_changed_at' => now(),
                ]
            );
        }
    }

    private function employee(
        User $owner,
        string $username,
        string $name,
        string $jobTitle,
        array $permissions,
        ?string $workspace,
        string $phone,
    ): void {
        $role = BusinessRole::updateOrCreate(
            ['business_id' => $owner->business_id, 'name' => $jobTitle],
            ['permissions' => $permissions]
        );
        $employee = User::updateOrCreate(
            ['username' => $username],
            [
                'name' => $name,
                'email' => $username.'@tms.test',
                'phone' => $phone,
                'address' => $owner->address,
                'password' => Hash::make(self::PASSWORD),
                'business_id' => $owner->business_id,
                'business_role_id' => $role->id,
                'job_title' => $jobTitle,
                'preferred_workspace' => $workspace,
                'employee_active' => true,
                'must_change_password' => false,
                'password_changed_at' => now(),
                'is_business_owner' => false,
                'tailoring_access' => false,
                'clothing_access' => false,
            ]
        );

        $employee->syncRoles(Role::firstOrCreate(['name' => 'business_employee', 'guard_name' => 'web']));
    }
}
