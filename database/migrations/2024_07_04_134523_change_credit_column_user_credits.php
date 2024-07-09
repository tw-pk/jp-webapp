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
        if (Schema::hasColumn('user_credits', 'credit')) {
            Schema::table('user_credits', function (Blueprint $table) {
                $table->decimal('credit', 14, 6)->default(0)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('user_credits', 'credit')) {
            Schema::table('user_credits', function (Blueprint $table) {
                $table->unsignedBigInteger('credit')->default(0)->change();
            });
        }
    }
};
