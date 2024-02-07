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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->string('sid');
            $table->string('to');
            $table->string('from');
            $table->string('status');
            $table->string('duration');
            $table->string('direction');
            $table->string('date_time')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
