<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('counter_order_complaints', function (Blueprint $table) {
            $table->string('category', 40)->default('stitching_defect')->after('reason');
            $table->text('inspection_notes')->nullable()->after('category');
            $table->string('responsible_party', 30)->nullable()->after('inspection_notes');
            $table->foreignId('responsible_tailor_id')->nullable()->after('responsible_party')->constrained('tailors')->nullOnDelete();
            $table->string('resolution', 30)->nullable()->after('status');
            $table->text('decision_notes')->nullable()->after('resolution');
            $table->date('rework_due_on')->nullable()->after('decision_notes');
            $table->timestamp('rework_completed_at')->nullable()->after('rework_due_on');
            $table->foreignId('approved_by_user_id')->nullable()->after('rework_completed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            $table->timestamp('closed_at')->nullable()->after('settled_at');
            $table->timestamp('customer_acknowledged_at')->nullable()->after('closed_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('status');
            $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
            $table->foreignId('cancelled_by_user_id')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('tailoring_complaint_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('counter_order_complaints')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::create('tailoring_recoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained('counter_order_complaints')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('tailor_id')->nullable()->constrained('tailors')->nullOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 30);
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('recorded');
            $table->text('note')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tailoring_recoveries');
        Schema::dropIfExists('tailoring_complaint_evidence');
        Schema::table('orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('cancelled_by_user_id'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['cancellation_reason', 'cancelled_at']));
        Schema::table('counter_order_complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('responsible_tailor_id');
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropColumn(['category', 'inspection_notes', 'responsible_party', 'resolution', 'decision_notes', 'rework_due_on', 'rework_completed_at', 'approved_at', 'closed_at', 'customer_acknowledged_at']);
        });
    }
};
