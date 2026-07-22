<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_number1');
            $table->string('phone_number2')->nullable();
            $table->string('ref_phone_number')->nullable();
            $table->string('shirtlength')->nullable();
            $table->string('sleeve')->nullable();
            $table->string('sleevetop')->nullable();
            $table->string('shoulder')->nullable();
            $table->string('chuta')->nullable();
            $table->string('senaChorai')->nullable();
            $table->string('shirtbottomwidth')->nullable();
            $table->string('shalwar')->nullable();
            $table->string('shalwarwidth')->nullable();
            $table->string('shalwarlength')->nullable();
            $table->string('teraa')->nullable();
            $table->string('jeab')->nullable();
            $table->string('length')->nullable();
            $table->string('damanchorai')->nullable();
            $table->string('button')->nullable();
            $table->string('shirtbutton')->nullable();
            $table->string('swingtype')->nullable();
            $table->string('arms')->nullable();
            $table->string('necktype')->nullable();
            $table->string('shalwarGheer')->nullable();
            $table->string('pancha')->nullable();
            $table->string('plate_type')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');

            // $table->foreignIdFor(Options::class,'shirtbottomtype');
            // $table->foreignIdFor(Options::class,'necktype');
            // $table->foreignIdFor(Options::class,'pockettype');
            // $table->foreignIdFor(Options::class,'buttontype');
            // $table->foreignIdFor(Options::class,'cufftype');
            // $table->foreignIdFor(Options::class,'chestplatetype');
            // $table->foreignIdFor(Options::class,'sewingtype');
            $table->foreignId('comments')->nullable();
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
        Schema::dropIfExists('customers');
    }
}
