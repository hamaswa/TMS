<?php

namespace Database\Seeders;

use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventorySetCatalogQaSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::find((int) env('TMS_CATALOG_OWNER_ID', 2))
            ?? User::where('is_business_owner', true)->where('clothing_access', true)->first();
        if (! $owner) {
            throw new RuntimeException('No clothing business owner was found for the inventory QA catalog.');
        }

        $catalog = [
            ['Pasha', 'کاٹن', 'Dilbar', 920, 1450, 4.5, ['سفید' => 52, 'کریم' => 44]],
            ['Pasha', 'کاٹن', 'Waqar', 1180, 1850, 4.5, ['سفید' => 40, 'آسمانی' => 36]],
            ['Gul Ahmed', 'کاٹن', 'Summer Classic', 870, 1325, 4.25, ['سفید' => 58, 'سرمئی' => 42]],
            ['Gul Ahmed', 'کاٹن', 'Premium Gold', 1260, 1990, 4.5, ['کریم' => 38, 'نیوی بلیو' => 34]],
            ['J.', 'واش اینڈ ویئر', 'Shahkar', 980, 1575, 4.5, ['سلور گرے' => 46, 'نیوی بلیو' => 39]],
            ['J.', 'واش اینڈ ویئر', 'Royal Touch', 1340, 2180, 4.5, ['چارکول' => 31, 'بوتل گرین' => 29]],
            ['Khaadi', 'کھدر', 'Aangan', 1050, 1690, 4.25, ['میرون' => 35, 'خاکی' => 37]],
            ['Khaadi', 'کھدر', 'Mehr', 1210, 1940, 4.25, ['بھورا' => 33, 'کریم' => 41]],
            ['Alkaram Studio', 'لینن', 'Mehran', 1120, 1760, 4.5, ['زیتونی' => 32, 'کریم' => 36]],
            ['Alkaram Studio', 'لینن', 'Naqsh', 1390, 2240, 4.5, ['سیاہ' => 30, 'بیج' => 34]],
            ['Sapphire', 'لان', 'Bahar', 760, 1190, 4.0, ['گلابی' => 48, 'فیروزی' => 43]],
            ['Sapphire', 'لان', 'Noor', 940, 1480, 4.0, ['آسمانی' => 47, 'سفید' => 51]],
            ['Nishat Linen', 'کیمبرک', 'Roshni', 990, 1580, 4.25, ['مسٹرڈ' => 34, 'جامنی' => 31]],
            ['Bonanza Satrangi', 'کرنڈی', 'Zewar', 1280, 2050, 4.5, ['بوتل گرین' => 37, 'بیج' => 40]],
            ['Grace', 'بوسکی', 'Sultan', 1540, 2490, 4.5, ['آف وائٹ' => 45, 'سنہری' => 28]],
            ['Ideas', 'ریشم', 'Nafees', 720, 1160, 4.0, ['عام' => 54]],
            ['Sana Safinaaz', 'لان', 'Gulnaar', 1080, 1720, 4.0, ['گلابی' => 39, 'کریم' => 44]],
            ['Pasha', 'واش اینڈ ویئر', 'Ahsan', 1100, 1740, 4.5, ['سفید' => 43, 'سلور گرے' => 38]],
        ];

        DB::transaction(function () use ($catalog, $owner) {
            $inventory = app(InventoryService::class);
            foreach ($catalog as [$brandName, $typeName, $setName, $cost, $salePrice, $defaultLength, $colors]) {
                $brand = ClothBrand::firstOrCreate(
                    ['user_id' => $owner->id, 'name' => $brandName],
                    ['brand_slug' => (string) str($brandName)->slug()]
                );
                $type = ClothType::firstOrCreate(
                    ['user_id' => $owner->id, 'name' => $typeName],
                    ['name_ur' => $typeName]
                );
                $cloth = Cloth::firstOrCreate([
                    'user_id' => $owner->id,
                    'cloth_brand_id' => $brand->id,
                    'cloth_type_id' => $type->id,
                    'name' => $setName,
                ], [
                    'price' => $cost,
                    'sale_price' => $salePrice,
                    'suit_sale_price' => round($salePrice * $defaultLength, 2),
                    'default_sale_length' => $defaultLength,
                    'sale_price_basis' => Cloth::SALE_PRICE_PER_METER,
                    'color_tracking_mode' => count($colors) > 1 ? Cloth::COLOR_TRACKING_PER_COLOR : Cloth::COLOR_TRACKING_NONE,
                ]);

                foreach ($colors as $colorName => $length) {
                    $color = ClothColor::firstOrCreate([
                        'cloth_id' => $cloth->id,
                        'color' => count($colors) > 1 ? $colorName : 'عام',
                    ], [
                        'length' => 0,
                        'average_unit_cost' => 0,
                        'user_id' => $owner->id,
                    ]);
                    if ($color->wasRecentlyCreated) {
                        $inventory->receive($color, $length, $cost, 'manual_adjustment_in', $cloth, 'Local QA catalog opening stock');
                    }
                }
            }

            $legacyNames = [
                ['Gul Ahmed', 'کپاس', 'Classic Cotton'],
                ['J.', 'واش اینڈ ویئر', 'Executive'],
                ['Gul Ahmed', 'کاٹن', 'Signature'],
                ['Alkaram Studio', 'لینن', 'Shehzada'],
                ['Khaadi', 'کھدر', 'Dastaan'],
                ['Sapphire', 'لان', 'Mehfil'],
                ['Nishat Linen', 'کیمبرک', 'Nazakat'],
                ['Bonanza Satrangi', 'کرنڈی', 'Riwayat'],
                ['Grace', 'بوسکی', 'Badshah'],
                ['Ideas', 'ریشم', 'Shaan'],
            ];
            foreach ($legacyNames as [$brandName, $typeName, $setName]) {
                Cloth::where('user_id', $owner->id)
                    ->where('name', 'like', 'سیٹ %')
                    ->whereHas('brand', fn ($query) => $query->where('name', $brandName))
                    ->whereHas('type', fn ($query) => $query->where('name', $typeName))
                    ->oldest('id')->first()?->update(['name' => $setName]);
            }
        });

        $this->command?->info('Inventory set catalog added for '.$owner->name.'.');
    }
}
