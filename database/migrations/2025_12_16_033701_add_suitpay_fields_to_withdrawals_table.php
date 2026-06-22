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
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('suitpay_transaction_id')->nullable()->after('processed_at');
            $table->string('suitpay_external_id')->nullable()->after('suitpay_transaction_id');
            $table->text('suitpay_response_data')->nullable()->after('suitpay_external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['suitpay_transaction_id', 'suitpay_external_id', 'suitpay_response_data']);
        });
    }
};
