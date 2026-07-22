<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('c_name')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('profit', 8, 2)->default(0);
            $table->decimal('loss', 8, 2)->default(0);
            $table->integer('length');
            $table->timestamp('sellDate')->nullable();
            $table->string('clothes_rack')->nullable();
            $table->decimal('selling_price', 8, 2)->default(0);
            $table->timestamps();

            $table->foreign('stock_id')->references('id')->on('stocks');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sale_stocks');
    }
}
