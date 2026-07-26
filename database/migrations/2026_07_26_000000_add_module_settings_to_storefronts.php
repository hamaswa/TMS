<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->boolean('tailoring_inquiries_enabled')->nullable()->after('inquiries_enabled');
            $table->boolean('tailoring_unpaid_enabled')->nullable()->after('tailoring_inquiries_enabled');
            $table->boolean('tailoring_cod_enabled')->nullable()->after('tailoring_unpaid_enabled');
            $table->boolean('tailoring_easypaisa_enabled')->nullable()->after('tailoring_cod_enabled');
            $table->boolean('tailoring_jazzcash_enabled')->nullable()->after('tailoring_easypaisa_enabled');
            $table->boolean('tailoring_bank_transfer_enabled')->nullable()->after('tailoring_jazzcash_enabled');
            $table->boolean('tailoring_raast_enabled')->nullable()->after('tailoring_bank_transfer_enabled');
            $table->boolean('tailoring_pickup_enabled')->nullable()->after('tailoring_raast_enabled');
            $table->boolean('tailoring_delivery_enabled')->nullable()->after('tailoring_pickup_enabled');

            $table->boolean('clothing_online_ordering_enabled')->nullable()->after('online_ordering_enabled');
            $table->boolean('clothing_unpaid_enabled')->nullable()->after('clothing_online_ordering_enabled');
            $table->boolean('clothing_cod_enabled')->nullable()->after('clothing_unpaid_enabled');
            $table->boolean('clothing_easypaisa_enabled')->nullable()->after('clothing_cod_enabled');
            $table->boolean('clothing_jazzcash_enabled')->nullable()->after('clothing_easypaisa_enabled');
            $table->boolean('clothing_bank_transfer_enabled')->nullable()->after('clothing_jazzcash_enabled');
            $table->boolean('clothing_raast_enabled')->nullable()->after('clothing_bank_transfer_enabled');
            $table->boolean('clothing_pickup_enabled')->nullable()->after('clothing_raast_enabled');
            $table->boolean('clothing_delivery_enabled')->nullable()->after('clothing_pickup_enabled');
        });

        DB::table('storefronts')->update([
            'tailoring_inquiries_enabled' => DB::raw('inquiries_enabled'),
            'tailoring_unpaid_enabled' => DB::raw('unpaid_orders_enabled'),
            'tailoring_cod_enabled' => DB::raw('cod_enabled'),
            'tailoring_easypaisa_enabled' => DB::raw('easypaisa_enabled'),
            'tailoring_jazzcash_enabled' => DB::raw('jazzcash_enabled'),
            'tailoring_bank_transfer_enabled' => DB::raw('bank_transfer_enabled'),
            'tailoring_raast_enabled' => DB::raw('raast_enabled'),
            'tailoring_pickup_enabled' => DB::raw('pickup_enabled'),
            'tailoring_delivery_enabled' => DB::raw('delivery_enabled'),
            'clothing_online_ordering_enabled' => DB::raw('online_ordering_enabled'),
            'clothing_unpaid_enabled' => DB::raw('unpaid_orders_enabled'),
            'clothing_cod_enabled' => DB::raw('cod_enabled'),
            'clothing_easypaisa_enabled' => DB::raw('easypaisa_enabled'),
            'clothing_jazzcash_enabled' => DB::raw('jazzcash_enabled'),
            'clothing_bank_transfer_enabled' => DB::raw('bank_transfer_enabled'),
            'clothing_raast_enabled' => DB::raw('raast_enabled'),
            'clothing_pickup_enabled' => DB::raw('pickup_enabled'),
            'clothing_delivery_enabled' => DB::raw('delivery_enabled'),
        ]);
    }

    public function down(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->dropColumn([
                'tailoring_inquiries_enabled',
                'tailoring_unpaid_enabled',
                'tailoring_cod_enabled',
                'tailoring_easypaisa_enabled',
                'tailoring_jazzcash_enabled',
                'tailoring_bank_transfer_enabled',
                'tailoring_raast_enabled',
                'tailoring_pickup_enabled',
                'tailoring_delivery_enabled',
                'clothing_online_ordering_enabled',
                'clothing_unpaid_enabled',
                'clothing_cod_enabled',
                'clothing_easypaisa_enabled',
                'clothing_jazzcash_enabled',
                'clothing_bank_transfer_enabled',
                'clothing_raast_enabled',
                'clothing_pickup_enabled',
                'clothing_delivery_enabled',
            ]);
        });
    }
};
