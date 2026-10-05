<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cloths', function (Blueprint $table) {
            $table->decimal('suit_sale_price', 12, 2)->nullable()->after('sale_price');
            $table->decimal('default_sale_length', 8, 2)->nullable()->after('suit_sale_price');
            $table->string('sale_price_basis', 20)->default('per_meter')->after('default_sale_length');
        });
    }

    public function down(): void
    {
        Schema::table('cloths', function (Blueprint $table) {
            $table->dropColumn(['suit_sale_price', 'default_sale_length', 'sale_price_basis']);
        });
    }
};
