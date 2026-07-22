<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('online_orders', 'cost_per_meter')) $table->decimal('cost_per_meter', 14, 4)->default(0);
            if (! Schema::hasColumn('online_orders', 'cost_total')) $table->decimal('cost_total', 14, 2)->default(0);
        });
    }

    public function down(): void
    {
        // Preserve historical cost snapshots.
    }
};
