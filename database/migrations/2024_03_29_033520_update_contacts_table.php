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
        Schema::table('contacts', function (Blueprint $table) {
            // Drop the address_home and address_office columns
            $table->dropColumn('address_home');
            $table->dropColumn('address_office');

            // Add the company_name column after the phone column
            $table->string('company_name')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Reverse the changes in the "up" method
            $table->text('address_home')->nullable()->after('phone');
            $table->text('address_office')->nullable()->after('address_home');
            $table->dropColumn('company_name');
        });
    }
};
