<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('serial_number')->nullable()->after('user_id');
        });

        DB::table('customers')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($customers) {
                foreach ($customers as $customer) {
                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update(['serial_number' => $customer->id]);
                }
            });

        Schema::table('customers', function (Blueprint $table) {
            $table->unique(['user_id', 'serial_number'], 'customers_user_serial_unique');
        });

        Schema::create('customer_serial_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();
        });

        DB::table('customers')
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('MAX(serial_number) as max_serial'))
            ->groupBy('user_id')
            ->get()
            ->each(function ($row) {
                DB::table('customer_serial_sequences')->insert([
                    'user_id' => $row->user_id,
                    'next_number' => ((int) $row->max_serial) + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_serial_sequences');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_user_serial_unique');
            $table->dropColumn('serial_number');
        });
    }
};
