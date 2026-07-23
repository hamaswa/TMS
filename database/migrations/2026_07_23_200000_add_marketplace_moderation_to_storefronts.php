<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->string('moderation_status', 30)->default('active')->after('is_published')->index();
            $table->text('moderation_reason')->nullable()->after('moderation_status');
            $table->foreignId('moderated_by_user_id')->nullable()->after('moderation_reason')->constrained('users')->nullOnDelete();
            $table->dateTime('moderated_at')->nullable()->after('moderated_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('storefronts', function (Blueprint $table) {
            $table->dropForeign(['moderated_by_user_id']);
            $table->dropColumn([
                'moderation_status',
                'moderation_reason',
                'moderated_by_user_id',
                'moderated_at',
            ]);
        });
    }
};
