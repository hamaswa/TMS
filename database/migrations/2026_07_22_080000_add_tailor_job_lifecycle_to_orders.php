<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'tailor_price')) {
                $table->decimal('tailor_price', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('orders', 'rack_no')) {
                $table->string('rack_no')->nullable();
            }
            if (! Schema::hasColumn('orders', 'status')) {
                $table->string('status')->default('assigned');
            }
            if (! Schema::hasColumn('orders', 'status_changed_at')) {
                $table->timestamp('status_changed_at')->nullable();
            }
            if (! Schema::hasColumn('orders', 'started_at')) {
                $table->timestamp('started_at')->nullable();
            }
            if (! Schema::hasColumn('orders', 'ready_at')) {
                $table->timestamp('ready_at')->nullable();
            }
            if (! Schema::hasColumn('orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable();
            }
            if (! Schema::hasColumn('orders', 'tailor_paid_amount')) {
                $table->decimal('tailor_paid_amount', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('orders', 'tailor_payment_status')) {
                $table->string('tailor_payment_status')->default('unpaid');
            }
        });

        DB::table('orders')->where('status', 'new')->update(['status' => 'assigned']);
        DB::table('orders')->where('status', 'start')->update(['status' => 'stitching']);
        DB::table('orders')->where('status', 'complete')->update(['status' => 'ready']);
        DB::table('orders')->whereNull('status')->update(['status' => 'assigned']);

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tailor_id')->nullable()->constrained('tailors')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('changed_by_type');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
