<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 10);
            $table->string('route_name')->nullable()->index();
            $table->string('path', 1000);
            $table->json('route_parameters')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['business_id', 'created_at']);
            $table->index(['business_id', 'actor_user_id']);
        });

        DB::table('business_roles')->orderBy('id')->each(function ($role) {
            $permissions = json_decode($role->permissions, true) ?: [];
            if (! in_array('team.manage', $permissions, true)) {
                return;
            }

            $permissions[] = 'activity.view';
            DB::table('business_roles')->where('id', $role->id)->update([
                'permissions' => json_encode(array_values(array_unique($permissions)), JSON_UNESCAPED_UNICODE),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_activity_logs');
        DB::table('business_roles')->orderBy('id')->each(function ($role) {
            $permissions = array_values(array_diff(json_decode($role->permissions, true) ?: [], ['activity.view']));
            DB::table('business_roles')->where('id', $role->id)->update([
                'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
            ]);
        });
    }
};
