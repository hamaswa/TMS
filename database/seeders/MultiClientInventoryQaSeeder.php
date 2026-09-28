<?php

namespace Database\Seeders;

use App\Models\Cloth;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothType;
use App\Models\Storefront;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MultiClientInventoryQaSeeder extends Seeder
{
    private const CATALOG = [
        ['J.', 'واش اینڈ ویئر', 920, 1390, ['نیوی بلیو', 'سلور گرے']],
        ['Gul Ahmed', 'کاٹن', 860, 1290, ['سفید', 'آسمانی']],
        ['Alkaram Studio', 'لینن', 1040, 1590, ['زیتونی', 'کریم']],
        ['Khaadi', 'کھدر', 1160, 1790, ['میرون', 'چارکول']],
        ['Sapphire', 'لان', 790, 1240, ['گلابی', 'فیروزی']],
        ['Nishat Linen', 'کیمبرک', 980, 1490, ['مسٹرڈ', 'جامنی']],
        ['Bonanza Satrangi', 'کرنڈی', 1210, 1890, ['بوتل گرین', 'بیج']],
        ['Grace', 'بوسکی', 1420, 2190, ['آف وائٹ', 'سنہری']],
    ];

    public function run(InventoryService $inventory): void
    {
        $owners = User::query()
            ->where('is_business_owner', true)
            ->whereHas('business', fn ($query) => $query->where('clothing_enabled', true))
            ->with('business')
            ->orderBy('id')
            ->get();

        foreach ($owners as $ownerIndex => $owner) {
            $storefront = Storefront::where('business_id', $owner->business_id)->first();

            foreach (self::CATALOG as $itemIndex => [$brandName, $typeName, $baseCost, $basePrice, $colors]) {
                $cost = $baseCost + ($ownerIndex * 35);
                $price = $basePrice + ($ownerIndex * 50);
                $brand = ClothBrand::firstOrCreate(
                    ['user_id' => $owner->id, 'name' => $brandName],
                    ['brand_slug' => Str::slug($brandName)]
                );
                $type = ClothType::firstOrCreate([
                    'user_id' => $owner->id,
                    'name' => $typeName,
                ]);
                $cloth = Cloth::firstOrCreate(
                    [
                        'user_id' => $owner->id,
                        'cloth_brand_id' => $brand->id,
                        'cloth_type_id' => $type->id,
                    ],
                    ['price' => $cost, 'sale_price' => $price]
                );
                $cloth->update(['price' => $cost, 'sale_price' => $price]);

                foreach ($colors as $colorIndex => $colorName) {
                    $color = ClothColor::firstOrCreate(
                        [
                            'cloth_id' => $cloth->id,
                            'user_id' => $owner->id,
                            'color' => $colorName,
                        ],
                        ['length' => 0, 'average_unit_cost' => $cost]
                    );
                    $targetLength = 28 + ($itemIndex * 3) + ($colorIndex * 4) + ($ownerIndex * 2);
                    $missingLength = max(0, $targetLength - (float) $color->length);
                    if ($missingLength > 0) {
                        $inventory->receive(
                            $color,
                            $missingLength,
                            $cost,
                            'qa_catalog_seed',
                            $cloth,
                            'Fresh multi-client QA catalogue'
                        );
                    }
                }

                if ($storefront) {
                    $storefront->clothingListings()->updateOrCreate(
                        ['cloth_id' => $cloth->id],
                        [
                            'public_name' => $brandName.' — '.$typeName,
                            'description' => 'QR-ready shop inventory with current colour-level stock and counter-sale pricing.',
                            'is_featured' => $itemIndex < 3,
                            'is_published' => true,
                            'is_available' => true,
                            'online_order_enabled' => true,
                            'minimum_order_quantity' => 1,
                            'maximum_order_quantity' => 20,
                            'order_increment' => 1,
                            'sort_order' => ($itemIndex + 1) * 10,
                        ]
                    );
                }
            }
        }
    }
}
