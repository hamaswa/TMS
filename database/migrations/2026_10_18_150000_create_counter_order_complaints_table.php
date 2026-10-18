<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('counter_order_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counter_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counter_order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('created_by_user_id');
            $table->text('reason');
            $table->decimal('requested_tailoring_refund', 12, 2)->default(0);
            $table->decimal('requested_cloth_compensation', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('counter_order_complaints'); }
};
