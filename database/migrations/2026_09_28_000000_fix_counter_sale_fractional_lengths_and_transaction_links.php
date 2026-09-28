<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_stocks', function (Blueprint $table) {
            $table->decimal('length', 12, 2)->change();
        });

        if (! Schema::hasColumn('transactions', 'counter_sale_receipt_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreignId('counter_sale_receipt_id')->nullable()->after('sale_id')
                    ->constrained('counter_sale_receipts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'counter_sale_receipt_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('counter_sale_receipt_id');
            });
        }

        Schema::table('sale_stocks', function (Blueprint $table) {
            $table->integer('length')->change();
        });
    }
};
