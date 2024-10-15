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
        Schema::table('calls', function (Blueprint $table) {
            $table->string('forwarded_call_sid')->nullable();  
            $table->string('forwarded_to')->nullable();        
            $table->decimal('forward_call_price', 10, 4)->nullable(); 
            $table->integer('forward_call_duration')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn('forwarded_call_sid');
            $table->dropColumn('forwarded_to');
            $table->dropColumn('forward_call_price');
            $table->dropColumn('forward_call_duration');
        });
    }
};
