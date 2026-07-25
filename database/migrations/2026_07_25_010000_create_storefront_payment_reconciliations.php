<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_payment_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_id')->constrained()->cascadeOnDelete();
            $table->date('settlement_date');
            $table->string('payment_method', 30);
            $table->unsignedInteger('expected_count')->default(0);
            $table->decimal('expected_amount', 14, 2)->default(0);
            $table->decimal('actual_amount', 14, 2)->default(0);
            $table->decimal('variance_amount', 14, 2)->default(0);
            $table->string('external_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('reconciled_by_user_id')
                ->nullable()
                ->constrained('users', indexName: 'payment_reconciliation_user_fk')
                ->nullOnDelete();
            $table->timestamp('reconciled_at');
            $table->timestamps();

            $table->unique(
                ['storefront_id', 'settlement_date', 'payment_method'],
                'storefront_payment_reconciliation_daily_unique'
            );
            $table->index(
                ['storefront_id', 'settlement_date'],
                'payment_reconciliation_storefront_date_idx'
            );
        });

        Schema::create('storefront_payment_reconciliation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')
                ->constrained('storefront_payment_reconciliations', indexName: 'payment_reconciliation_event_parent_fk')
                ->cascadeOnDelete();
            $table->unsignedInteger('expected_count')->default(0);
            $table->decimal('expected_amount', 14, 2)->default(0);
            $table->decimal('actual_amount', 14, 2)->default(0);
            $table->decimal('variance_amount', 14, 2)->default(0);
            $table->string('external_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('reconciled_by_user_id')
                ->nullable()
                ->constrained('users', indexName: 'payment_reconciliation_event_user_fk')
                ->nullOnDelete();
            $table->timestamp('reconciled_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_payment_reconciliation_events');
        Schema::dropIfExists('storefront_payment_reconciliations');
    }
};
