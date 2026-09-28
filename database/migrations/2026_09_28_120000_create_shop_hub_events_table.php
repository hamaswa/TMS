<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_hub_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->unsignedBigInteger('business_owner_user_id')->index();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('device_id', 100)->nullable();
            $table->string('event_type', 80);
            $table->string('aggregate_type', 50);
            $table->uuid('aggregate_uuid');
            $table->unsignedInteger('base_revision')->nullable();
            $table->unsignedInteger('resulting_revision')->nullable();
            $table->json('payload');
            $table->string('status', 30)->default('pending');
            $table->timestamp('occurred_at');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(
                ['business_owner_user_id', 'status', 'id'],
                'shop_hub_events_pending_index'
            );
            $table->index(
                ['aggregate_type', 'aggregate_uuid', 'resulting_revision'],
                'shop_hub_events_aggregate_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_hub_events');
    }
};
