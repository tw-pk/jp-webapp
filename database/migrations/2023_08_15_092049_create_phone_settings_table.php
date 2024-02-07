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
        Schema::create('phone_settings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->string('phone_number');
            $table->string('fwd_incoming_call')->nullable();
            $table->string('unanswered_fwd_call')->nullable();
            $table->string('ring_order')->nullable();
            $table->text('ring_order_value')->nullable();
            $table->text('incoming_caller_id')->nullable();
            $table->text('outbound_caller_id')->nullable();
            $table->string('vunanswered_fwd_call')->nullable();
            $table->string('vemail_id')->nullable();
            $table->text('voice_message')->nullable();
            $table->string('transcription')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_settings');
    }
};
