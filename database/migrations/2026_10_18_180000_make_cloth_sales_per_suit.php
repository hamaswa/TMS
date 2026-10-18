<?php

use App\Models\Cloth;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_stocks', function (Blueprint $table) {
            $table->string('sale_price_basis', 20)->default(Cloth::SALE_PRICE_PER_METER)->after('selling_price');
            $table->decimal('suit_quantity', 10, 2)->nullable()->after('sale_price_basis');
            $table->decimal('suit_unit_price', 12, 2)->nullable()->after('suit_quantity');
            $table->decimal('suit_length', 8, 2)->nullable()->after('suit_unit_price');
        });

        DB::table('cloths')
            ->leftJoin('cloth_types', 'cloth_types.id', '=', 'cloths.cloth_type_id')
            ->select('cloths.id', 'cloths.sale_price', 'cloths.suit_sale_price', 'cloth_types.name as type_name')
            ->orderBy('cloths.id')
            ->get()
            ->each(function ($cloth) {
                $type = mb_strtolower(trim((string) $cloth->type_name));
                $isCotton = str_contains($type, 'cotton')
                    || str_contains($type, 'کاٹن')
                    || str_contains($type, 'کپاس');
                $length = $isCotton ? 4.5 : 4.0;
                $suitPrice = (float) $cloth->suit_sale_price > 0
                    ? round((float) $cloth->suit_sale_price, 2)
                    : round((float) $cloth->sale_price * $length, 2);

                DB::table('cloths')->where('id', $cloth->id)->update([
                    'sale_price_basis' => Cloth::SALE_PRICE_PER_SUIT,
                    'default_sale_length' => $length,
                    'suit_sale_price' => $suitPrice,
                    // Kept as an internal effective meter rate for stock/report compatibility.
                    'sale_price' => round($suitPrice / $length, 2),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('sale_stocks', function (Blueprint $table) {
            $table->dropColumn(['sale_price_basis', 'suit_quantity', 'suit_unit_price', 'suit_length']);
        });
    }
};
