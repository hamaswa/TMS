<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderIdToTailorRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tailor_records', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable();

            $table->foreign('order_id')->references('id')->on('orders');
        });
    }

    public function down()
    {
        Schema::table('tailor_records', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });
    }
}
