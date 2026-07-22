<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cloth_brands')) {
            Schema::create('cloth_brands', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->string('name');
            $table->string('brand_logo')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('cloth_types')) {
            Schema::create('cloth_types', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->string('name');
            $table->foreignId('user_id');
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('cloths')) {
            Schema::create('cloths', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();

            // relations
            $table->foreignId('cloth_type_id')
                ->nullable()
                ->constrained('cloth_types')
                ->nullOnDelete();

            $table->foreignId('cloth_brand_id')
                ->nullable()
                ->constrained('cloth_brands')
                ->nullOnDelete();

            // data
            $table->string('price');
            $table->string('sale_price')->nullable();
            $table->foreignId('user_id')->nullable();

            $table->softDeletes();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cloth_brands');
        Schema::dropIfExists('cloth_types');
        Schema::dropIfExists('cloths');
    }
};
