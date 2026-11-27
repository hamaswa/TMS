<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Line height and gap between rows on the tailoring slip, set by the shop to suit its printer. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('print_line_height', 3, 2)->default(1.7)->after('print_show_qr');
            $table->unsignedTinyInteger('print_row_gap')->default(6)->after('print_line_height');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['print_line_height', 'print_row_gap']);
        });
    }
};
