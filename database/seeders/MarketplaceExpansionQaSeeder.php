<?php

namespace Database\Seeders;

use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Customers;
use App\Models\BusinessRole;
use App\Models\Storefront;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class MarketplaceExpansionQaSeeder extends Seeder
{
    private const OWNER_EMAIL = 'kamran.siddiqui.workspace.20260723@tms.test';

    public function run(): void
    {
        DB::transaction(function () {
            $owner = User::where('email', self::OWNER_EMAIL)->first();
            if (! $owner) {
                throw new RuntimeException('The Siddiqui storefront QA client does not exist.');
            }

            $storefront = Storefront::where('business_id', $owner->business_id)->first();
            if (! $storefront) {
                throw new RuntimeException('The Siddiqui public storefront does not exist.');
            }

            $this->catalog($owner, $storefront);
            $this->services($storefront);
            $this->customers($owner);
            $this->qaManager($owner);
        });
    }

    private function catalog(User $owner, Storefront $storefront): void
    {
        $items = [
            [
                'brand' => 'Gul Ahmed',
                'type' => 'واش اینڈ ویئر',
                'name' => 'گل احمد پریمیم واش اینڈ ویئر',
                'description' => 'روزمرہ اور دفتر کے لیے نرم، کم شکن اور آرام دہ مردانہ کپڑا۔',
                'cost' => 980,
                'price' => 1450,
                'featured' => true,
                'colors' => ['نیوی بلیو', 'سلور گرے'],
            ],
            [
                'brand' => 'J.',
                'type' => 'کاٹن',
                'name' => 'جے۔ خالص کاٹن کلیکشن',
                'description' => 'گرمیوں کے لیے سانس لینے والا خالص کاٹن، شلوار قمیض کے لیے موزوں۔',
                'cost' => 1050,
                'price' => 1580,
                'featured' => true,
                'colors' => ['سفید', 'آسمانی'],
            ],
            [
                'brand' => 'Alkaram Studio',
                'type' => 'لینن',
                'name' => 'الکرم سٹوڈیو سافٹ لینن',
                'description' => 'درمیانے موسم کے لیے ہلکا اور خوبصورت لینن، رسمی اور روزمرہ استعمال کے لیے۔',
                'cost' => 1120,
                'price' => 1690,
                'featured' => false,
                'colors' => ['زیتونی', 'کریم'],
            ],
            [
                'brand' => 'Khaadi',
                'type' => 'کھدر',
                'name' => 'کھاڈی ونٹر کھدر',
                'description' => 'سردیوں کے لیے گرم، مضبوط اور روایتی بناوٹ والا کھدر۔',
                'cost' => 1250,
                'price' => 1850,
                'featured' => true,
                'colors' => ['میرون', 'چارکول'],
            ],
            [
                'brand' => 'Sapphire',
                'type' => 'لان',
                'name' => 'سیفائر پرنٹڈ لان',
                'description' => 'خواتین کے روزمرہ ملبوسات کے لیے نرم پرنٹڈ لان، رنگ پائیدار اور آرام دہ۔',
                'cost' => 900,
                'price' => 1390,
                'featured' => false,
                'colors' => ['گلابی', 'فیروزی'],
            ],
            [
                'brand' => 'Nishat Linen',
                'type' => 'کیمبرک',
                'name' => 'نشاط لینن کیمبرک',
                'description' => 'موسم بدلنے کے دنوں کے لیے نفیس کیمبرک، سلائی اور کٹنگ میں آسان۔',
                'cost' => 1080,
                'price' => 1620,
                'featured' => false,
                'colors' => ['مسٹرڈ', 'جامنی'],
            ],
            [
                'brand' => 'Bonanza Satrangi',
                'type' => 'کرنڈی',
                'name' => 'بونانزا سترنگی کرنڈی',
                'description' => 'سرد موسم کے لیے نفیس کرنڈی، تقریبات اور روزمرہ دونوں کے لیے۔',
                'cost' => 1320,
                'price' => 1980,
                'featured' => false,
                'colors' => ['بوتل گرین', 'بیج'],
            ],
            [
                'brand' => 'Siddiqui Select',
                'type' => 'بوسکی',
                'name' => 'صدیقی سلیکٹ بوسکی',
                'description' => 'تقریبات کے لیے چمکدار اور نفیس بوسکی، پریمیم فنش کے ساتھ۔',
                'cost' => 1550,
                'price' => 2350,
                'featured' => true,
                'colors' => ['آف وائٹ', 'سنہری'],
            ],
        ];

        foreach ($items as $sort => $item) {
            $brand = ClothBrand::firstOrCreate(
                ['user_id' => $owner->id, 'name' => $item['brand']],
                ['brand_slug' => Str::slug($item['brand'])]
            );
            $type = ClothType::firstOrCreate([
                'user_id' => $owner->id,
                'name' => $item['type'],
            ]);
            $cloth = Cloth::firstOrCreate(
                [
                    'user_id' => $owner->id,
                    'cloth_brand_id' => $brand->id,
                    'cloth_type_id' => $type->id,
                ],
                [
                    'price' => $item['cost'],
                    'sale_price' => $item['price'],
                ]
            );
            $cloth->update([
                'price' => $item['cost'],
                'sale_price' => $item['price'],
            ]);

            foreach ($item['colors'] as $colorName) {
                $color = ClothColor::firstOrCreate(
                    [
                        'cloth_id' => $cloth->id,
                        'user_id' => $owner->id,
                        'color' => $colorName,
                    ],
                    [
                        'length' => 0,
                        'average_unit_cost' => $item['cost'],
                    ]
                );
                $targetStock = 35 + ($sort * 2);
                $missing = max(0, $targetStock - (float) $color->length);
                if ($missing > 0) {
                    app(InventoryService::class)->receive(
                        $color,
                        $missing,
                        $item['cost'],
                        'qa_catalog_seed',
                        $cloth,
                        'Marketplace QA catalog expansion'
                    );
                }
            }

            $storefront->clothingListings()->updateOrCreate(
                ['cloth_id' => $cloth->id],
                [
                    'public_name' => $item['name'],
                    'description' => $item['description'],
                    'is_featured' => $item['featured'],
                    'is_published' => true,
                    'sort_order' => ($sort + 1) * 10,
                ]
            );
        }
    }

    private function services(Storefront $storefront): void
    {
        $services = [
            ['پریمیم مردانہ شلوار قمیض سلائی', 'ماہرانہ کٹنگ، نفیس سلائی، بٹن اور ایک فٹنگ ٹرائل شامل ہے۔', 1800, 'فی سوٹ', 7, true],
            ['مردانہ ویسٹ کوٹ سلائی', 'روایتی اور جدید ڈیزائن، اندرونی استر اور مکمل فٹنگ کے ساتھ۔', 2500, 'فی لباس', 10, true],
            ['بچوں کی شلوار قمیض سلائی', 'آرام دہ کٹنگ، مضبوط سلائی اور بچوں کے سائز کے مطابق فٹنگ۔', 1200, 'فی سوٹ', 5, false],
            ['خواتین کا سادہ سوٹ', 'سادہ قمیض، شلوار اور بنیادی ڈیزائن کی سلائی؛ پیچیدہ کام الگ شمار ہوگا۔', 2200, 'فی سوٹ', 8, false],
            ['الٹریشن اور فٹنگ', 'آستین، لمبائی، کمر اور بنیادی فٹنگ کی درستگی۔', 500, 'فی کام', 2, false],
        ];

        foreach ($services as $sort => [$name, $description, $price, $unit, $days, $featured]) {
            $storefront->tailoringServices()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'price_from' => $price,
                    'price_unit' => $unit,
                    'estimated_days' => $days,
                    'is_featured' => $featured,
                    'is_published' => true,
                    'sort_order' => ($sort + 1) * 10,
                ]
            );
        }
    }

    private function customers(User $owner): void
    {
        $customers = [
            ['عائشہ خان', '03005550101', '415263', 'سیٹلائٹ ٹاؤن، راولپنڈی'],
            ['عثمان علی', '03005550102', '526374', 'صدر، راولپنڈی'],
            ['مریم نور', '03005550103', '637485', 'بحریہ ٹاؤن، راولپنڈی'],
            ['فہد اقبال', '03005550104', '748596', 'چکلالہ سکیم 3، راولپنڈی'],
        ];

        foreach ($customers as [$name, $phone, $pin, $address]) {
            Customers::firstOrCreate(
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

    private function qaManager(User $owner): void
    {
        $role = BusinessRole::updateOrCreate(
            ['business_id' => $owner->business_id, 'name' => 'آن لائن دکان منیجر'],
            ['permissions' => array_keys(BusinessRole::PERMISSIONS)]
        );
        $employee = User::updateOrCreate(
            ['username' => 'zara.marketplace.qa'],
            [
                'name' => 'زارا احمد',
                'email' => 'zara.marketplace.qa@tms.test',
                'phone' => '03005550999',
                'address' => 'راولپنڈی، پنجاب',
                'password' => Hash::make('QaMarketplace@2026'),
                'business_id' => $owner->business_id,
                'business_role_id' => $role->id,
                'job_title' => 'آن لائن دکان منیجر',
                'preferred_workspace' => 'shop',
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
