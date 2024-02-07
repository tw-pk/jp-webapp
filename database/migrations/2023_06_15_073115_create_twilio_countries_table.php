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
        Schema::create('twilio_countries', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 3);
            $table->string('country', 50);
            $table->text('uri')->nullable();
            $table->string('beta', 10)->nullable();
            $table->text('subresource_uris')->nullable();
            $table->string('subresource_type', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('twilio_countries');
    }
};
