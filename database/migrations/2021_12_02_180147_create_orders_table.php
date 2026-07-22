<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('sub_customer')->nullable();
            $table->string('customerId')->nullable();
            $table->string('suitQuantity')->nullable();
            $table->string('totalPayment')->nullable();
            $table->string('designPrice')->nullable();
            $table->string('tailorId')->nullable();
            $table->string('rateId')->nullable();
            $table->json('suitNum')->nullable();
            $table->string('design')->nullable();
            $table->string('returnDate')->nullable();
            $table->string('userId')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
