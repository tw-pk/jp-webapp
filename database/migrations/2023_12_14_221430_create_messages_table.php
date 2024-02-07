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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->string('smsSId')->nullable();
            $table->string('smsMessageSId')->nullable();
            $table->boolean('media')->default(false);
            $table->string('mediaUrl')->nullable();
            $table->string('unique_name')->nullable();
            $table->string('friendly_name')->nullable();
            $table->string('to')->nullable();
            $table->string('toCity')->nullable();
            $table->string('toCountry')->nullable();
            $table->string('toState')->nullable();
            $table->string('toZip')->nullable();
            $table->string('from')->nullable();
            $table->string('fromCountry')->nullable();
            $table->string('fromCity')->nullable();
            $table->string('fromState')->nullable();
            $table->string('fromZip')->nullable();
            $table->string('status')->nullable();
            $table->string('direction')->nullable();
            $table->text('body')->nullable();
            $table->string('dateCreated')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
