<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->string('payment_evidence_path')->nullable()->after('payment_reference');
            $table->string('payment_evidence_original_name')->nullable()->after('payment_evidence_path');
            $table->string('payment_evidence_mime_type', 100)->nullable()->after('payment_evidence_original_name');
            $table->unsignedBigInteger('payment_evidence_size')->nullable()->after('payment_evidence_mime_type');
            $table->timestamp('payment_evidence_submitted_at')->nullable()->after('payment_evidence_size');
        });

        Schema::table('storefront_inquiries', function (Blueprint $table) {
            $table->string('payment_evidence_path')->nullable()->after('payment_reference');
            $table->string('payment_evidence_original_name')->nullable()->after('payment_evidence_path');
            $table->string('payment_evidence_mime_type', 100)->nullable()->after('payment_evidence_original_name');
            $table->unsignedBigInteger('payment_evidence_size')->nullable()->after('payment_evidence_mime_type');
            $table->timestamp('payment_evidence_submitted_at')->nullable()->after('payment_evidence_size');
        });
    }

    public function down(): void
    {
        Schema::table('storefront_orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_evidence_path',
                'payment_evidence_original_name',
                'payment_evidence_mime_type',
                'payment_evidence_size',
                'payment_evidence_submitted_at',
            ]);
        });

        Schema::table('storefront_inquiries', function (Blueprint $table) {
            $table->dropColumn([
                'payment_evidence_path',
                'payment_evidence_original_name',
                'payment_evidence_mime_type',
                'payment_evidence_size',
                'payment_evidence_submitted_at',
            ]);
        });
    }
};
