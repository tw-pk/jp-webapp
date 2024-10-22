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
        Schema::table('credit_histories', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->after('user_credit_id');
            $table->decimal('amount', 14, 6)->nullable()->after('transaction_id');
            $table->string('status')->nullable()->after('amount');
            $table->text('receipt_url')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_histories', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
            $table->dropColumn('amount');
            $table->dropColumn('status');
            $table->dropColumn('receipt_url');
        });
    }
};
