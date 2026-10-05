<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('standard_measurement_profiles')) {
            Schema::create('standard_measurement_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('measurement_template_id')->constrained()->cascadeOnDelete();
                $table->string('name', 100);
                $table->json('measurement_values');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['measurement_template_id', 'name'], 'standard_profile_template_name_unique');
                $table->index(['user_id', 'is_active', 'sort_order'], 'standard_profile_owner_active_sort');
            });
        }

        $this->addProfileReference('storefront_cart_tailoring_items', 'sf_cart_tailor_profile_fk');
        $this->addProfileReference('storefront_order_tailoring_items', 'sf_order_tailor_profile_fk');
    }

    public function down(): void
    {
        foreach ([
            'storefront_order_tailoring_items' => 'sf_order_tailor_profile_fk',
            'storefront_cart_tailoring_items' => 'sf_cart_tailor_profile_fk',
        ] as $tableName => $foreignName) {
            if (Schema::hasColumn($tableName, 'standard_measurement_profile_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($foreignName) {
                    $table->dropForeign($foreignName);
                    $table->dropColumn('standard_measurement_profile_id');
                });
            }
        }
        Schema::dropIfExists('standard_measurement_profiles');
    }

    private function addProfileReference(string $tableName, string $foreignName): void
    {
        if (! Schema::hasColumn($tableName, 'standard_measurement_profile_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('standard_measurement_profile_id')->nullable()->after('measurement_template_id');
            });
        }

        $hasForeign = collect(Schema::getForeignKeys($tableName))
            ->contains(fn (array $foreign) => ($foreign['name'] ?? null) === $foreignName);
        if (! $hasForeign) {
            Schema::table($tableName, function (Blueprint $table) use ($foreignName) {
                $table->foreign('standard_measurement_profile_id', $foreignName)
                    ->references('id')->on('standard_measurement_profiles')->nullOnDelete();
            });
        }
    }
};
