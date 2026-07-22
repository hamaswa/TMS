<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('tailoring_enabled')->default(false);
            $table->boolean('clothing_enabled')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained('businesses')->nullOnDelete();
            $table->boolean('is_business_owner')->default(false)->after('business_id');
            $table->boolean('employee_active')->default(true)->after('is_business_owner');
            $table->string('job_title')->nullable()->after('employee_active');
            $table->string('preferred_workspace', 20)->nullable()->after('job_title');
        });

        if (Schema::hasTable('roles') && Schema::hasTable('model_has_roles')) {
            $owners = DB::table('users')
                ->join('model_has_roles', function ($join) {
                    $join->on('model_has_roles.model_id', '=', 'users.id')
                        ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
                })
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', 'shop_owner')
                ->select('users.id', 'users.name', 'users.tailoring_access', 'users.clothing_access')
                ->get();

            foreach ($owners as $owner) {
                $businessId = DB::table('businesses')->insertGetId([
                    'name' => $owner->name,
                    'owner_user_id' => $owner->id,
                    'tailoring_enabled' => $owner->tailoring_access,
                    'clothing_enabled' => $owner->clothing_access,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('users')->where('id', $owner->id)->update([
                    'business_id' => $businessId,
                    'is_business_owner' => true,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_id');
            $table->dropColumn(['is_business_owner', 'employee_active', 'job_title', 'preferred_workspace']);
        });

        Schema::dropIfExists('businesses');
    }
};
