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
        Schema::table('invitations', function (Blueprint $table) {
            $table->unsignedBigInteger('member_id')->nullable()->after('role');
            $table->boolean('registered')->default(false)->after('member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            // Reverse the changes made in the "up" method
            $table->dropColumn('member_id');
            $table->dropColumn('registered');
        });
    }
};
