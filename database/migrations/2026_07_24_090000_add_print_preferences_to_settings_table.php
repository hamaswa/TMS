<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('print_paper_size', 20)
                ->default('receipt_80')
                ->after('shop_slug');
            $table->boolean('print_show_qr')
                ->default(true)
                ->after('print_paper_size');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['print_paper_size', 'print_show_qr']);
        });
    }
};
