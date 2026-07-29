<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counter_sale_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 60)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->unsignedBigInteger('first_sale_stock_id')->nullable();
            $table->string('status', 30)->default('completed')->index();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('sale_stocks', function (Blueprint $table) {
            $table->foreignId('counter_sale_receipt_id')->nullable()->after('id')
                ->constrained('counter_sale_receipts')->nullOnDelete();
        });

        $receiptIds = [];
        foreach (DB::table('sale_stocks')->orderBy('id')->get() as $sale) {
            $key = implode('|', [
                (string) ($sale->user_id ?? 0),
                (string) ($sale->c_id ?? 0),
                (string) ($sale->created_at ?? ''),
            ]);
            if (! isset($receiptIds[$key])) {
                $receiptIds[$key] = DB::table('counter_sale_receipts')->insertGetId([
                    'receipt_number' => 'TMSC-'.str_pad((string) $sale->id, 8, '0', STR_PAD_LEFT),
                    'user_id' => $sale->user_id,
                    'customer_id' => $sale->c_id,
                    'first_sale_stock_id' => $sale->id,
                    'status' => 'completed',
                    'created_at' => $sale->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('sale_stocks')->where('id', $sale->id)->update([
                'counter_sale_receipt_id' => $receiptIds[$key],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('sale_stocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('counter_sale_receipt_id');
        });
        Schema::dropIfExists('counter_sale_receipts');
    }
};
