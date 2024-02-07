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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('code_3', 3)->nullable();
            $table->string('code_2', 2)->nullable();
            $table->string('numeric_code', 10)->nullable();
            $table->string('phone_code', 100)->nullable();
            $table->string('capital', 50)->nullable();
            $table->string('currency', 50)->nullable();
            $table->string('currency_name', 50)->nullable();
            $table->string('currency_symbol', 50)->nullable();
            $table->string('tld', 10)->nullable();
            $table->string('native', 50)->nullable();
            $table->string('region', 50)->nullable();
            $table->string('subregion', 50)->nullable();
            $table->string('latitude', 100)->nullable();
            $table->string('longitude', 100)->nullable();
            $table->string('emoji', 100)->nullable();
            $table->string('emojiU', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
