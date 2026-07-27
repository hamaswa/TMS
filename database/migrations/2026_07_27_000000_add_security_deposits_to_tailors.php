<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tailors', function (Blueprint $table) {
            $table->decimal('security_deposit', 14, 2)->default(0)->after('advance');
        });

        Schema::create('tailor_security_deposit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tailor_id')->constrained('tailors')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('transaction_type', 20);
            $table->decimal('amount', 14, 2);
            $table->date('transaction_date');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['tailor_id', 'transaction_date'], 'tailor_security_date_idx');
            $table->index(['user_id', 'transaction_type'], 'tailor_security_owner_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tailor_security_deposit_transactions');

        Schema::table('tailors', function (Blueprint $table) {
            $table->dropColumn('security_deposit');
        });
    }
};
