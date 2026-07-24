<?php

use App\Support\PakistanPhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone_number1_normalized', 13)->nullable()->after('phone_number1');
            $table->boolean('phone_normalization_conflict')->default(false)
                ->after('phone_number1_normalized');
        });

        DB::table('customers')
            ->select(['id', 'phone_number1'])
            ->orderBy('id')
            ->chunkById(500, function ($customers) {
                foreach ($customers as $customer) {
                    DB::table('customers')->where('id', $customer->id)->update([
                        'phone_number1_normalized' => PakistanPhoneNumber::normalize($customer->phone_number1),
                    ]);
                }
            });

        $conflicts = DB::table('customers')
            ->select(['user_id', 'phone_number1_normalized'])
            ->whereNotNull('phone_number1_normalized')
            ->groupBy('user_id', 'phone_number1_normalized')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($conflicts as $conflict) {
            $ids = DB::table('customers')
                ->where('user_id', $conflict->user_id)
                ->where('phone_number1_normalized', $conflict->phone_number1_normalized)
                ->orderBy('id')
                ->pluck('id');

            DB::table('customers')->whereIn('id', $ids)->update([
                'phone_normalization_conflict' => true,
            ]);
            DB::table('customers')->whereIn('id', $ids->skip(1))->update([
                'phone_number1_normalized' => null,
            ]);
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'phone_number1_normalized'],
                'customers_owner_phone_normalized_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_owner_phone_normalized_unique');
            $table->dropColumn(['phone_number1_normalized', 'phone_normalization_conflict']);
        });
    }
};
