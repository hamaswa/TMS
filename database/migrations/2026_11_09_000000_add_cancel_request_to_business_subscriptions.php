<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_subscriptions', function (Blueprint $table) {
            // The shop asked to stop: the plan runs to its end date and is not renewed.
            $table->timestamp('cancel_requested_at')->nullable();
            $table->unsignedBigInteger('cancel_requested_by_user_id')->nullable();
            $table->text('cancel_request_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('business_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['cancel_requested_at', 'cancel_requested_by_user_id', 'cancel_request_reason']);
        });
    }
};
