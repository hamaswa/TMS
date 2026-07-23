<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_carts', function (Blueprint $table) {
            $table->dateTime('expires_at')->change();
            $table->dateTime('last_activity_at')->change();
        });
        Schema::table('storefront_cart_items', function (Blueprint $table) {
            $table->dateTime('reserved_until')->change();
        });
    }

    public function down(): void
    {
        // These deadline columns intentionally remain DATETIME. Reintroducing
        // MySQL's implicit TIMESTAMP update behavior would corrupt expiries.
    }
};
