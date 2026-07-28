<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('payment_method', 40)->nullable()->after('comment');
            $table->string('payment_reference', 255)->nullable()->after('payment_method');
            $table->date('paid_on')->nullable()->after('payment_reference');
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->string('payment_method', 40)->nullable()->after('amount');
        });

        Schema::table('worker_ledger_entries', function (Blueprint $table) {
            $table->string('payment_method', 40)->nullable()->after('entry_date');
            $table->string('payment_reference', 255)->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_reference', 'paid_on']);
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });

        Schema::table('worker_ledger_entries', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_reference']);
        });
    }
};
