<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('option_types', function (Blueprint $table) {
            if (!Schema::hasColumn('option_types', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
        });

        if (!Schema::hasTable('designs')) {
            Schema::create('designs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('design_name');
                $table->decimal('design_price', 12, 2)->default(0);
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('designs', 'user_id')) {
            Schema::table('designs', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('designs') && Schema::hasColumn('designs', 'user_id')) {
            Schema::table('designs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        if (Schema::hasColumn('option_types', 'user_id')) {
            Schema::table('option_types', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
