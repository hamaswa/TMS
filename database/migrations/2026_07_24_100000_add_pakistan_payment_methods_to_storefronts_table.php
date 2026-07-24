<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->boolean('jazzcash_enabled')->default(false)->after('easypaisa_enabled');
            $table->boolean('bank_transfer_enabled')->default(false)->after('jazzcash_enabled');
            $table->boolean('raast_enabled')->default(false)->after('bank_transfer_enabled');
            $table->string('easypaisa_account_title', 150)->nullable()->after('raast_enabled');
            $table->string('easypaisa_account_number', 50)->nullable()->after('easypaisa_account_title');
            $table->string('jazzcash_account_title', 150)->nullable()->after('easypaisa_account_number');
            $table->string('jazzcash_account_number', 50)->nullable()->after('jazzcash_account_title');
            $table->string('bank_name', 150)->nullable()->after('jazzcash_account_number');
            $table->string('bank_account_title', 150)->nullable()->after('bank_name');
            $table->string('bank_account_number', 100)->nullable()->after('bank_account_title');
            $table->string('bank_iban', 34)->nullable()->after('bank_account_number');
            $table->string('raast_account_title', 150)->nullable()->after('bank_iban');
            $table->string('raast_id', 100)->nullable()->after('raast_account_title');
            $table->string('raast_qr_path')->nullable()->after('raast_id');
        });
    }

    public function down(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->dropColumn([
                'jazzcash_enabled',
                'bank_transfer_enabled',
                'raast_enabled',
                'easypaisa_account_title',
                'easypaisa_account_number',
                'jazzcash_account_title',
                'jazzcash_account_number',
                'bank_name',
                'bank_account_title',
                'bank_account_number',
                'bank_iban',
                'raast_account_title',
                'raast_id',
                'raast_qr_path',
            ]);
        });
    }
};
