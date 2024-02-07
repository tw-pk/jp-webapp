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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->json('abilities')->nullable();
            $table->string('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->string('currency')->nullable();
            $table->string('timezone')->nullable();
            $table->string('language')->nullable();
            $table->string('country')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->string('organization')->nullable();
            $table->string('phone_number')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
