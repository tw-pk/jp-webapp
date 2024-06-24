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
        if (!Schema::hasTable('twilio_countries_price_lists')) {
            Schema::create('twilio_countries_price_lists', function (Blueprint $table) {
                $table->id();
                $table->string('ISO', 2)->comment('ISO code for the country (2 characters)');
                $table->string('Country')->comment('Name of the country');
                $table->text('Description')->comment('Description of the rate');
                $table->decimal('Price', 8, 2)->comment('Price per minute');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('twilio_countries_price_lists');
    }
};
