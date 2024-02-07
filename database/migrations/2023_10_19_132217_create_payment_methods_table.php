<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('pmId');
            $table->boolean('default')->default(false);
            $table->string('card_brand')->nullable();
            $table->string('card_name')->nullable();
            $table->string('card_email')->nullable();
            $table->string('card_number')->nullable();
            $table->string('expiry_month')->nullable();
            $table->string('expiry_year')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
