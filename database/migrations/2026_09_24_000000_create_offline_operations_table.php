<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('operation_uuid')->unique();
            $table->unsignedBigInteger('owner_user_id')->index();
            $table->string('actor_type', 30);
            $table->unsignedBigInteger('actor_id');
            $table->string('action', 80);
            $table->string('aggregate_type', 50);
            $table->unsignedBigInteger('aggregate_id');
            $table->string('base_version', 100)->nullable();
            $table->json('payload');
            $table->string('status', 30);
            $table->json('result')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['owner_user_id', 'aggregate_type', 'aggregate_id'], 'offline_operations_aggregate_index');
            $table->index(['actor_type', 'actor_id', 'status'], 'offline_operations_actor_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_operations');
    }
};
