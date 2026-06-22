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
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Remove as foreign keys primeiro
            $table->dropForeign(['subscription_plan_id']);
            $table->dropForeign(['creator_id']);
            
            // Torna as colunas nullable
            $table->foreignId('subscription_plan_id')->nullable()->change();
            $table->foreignId('creator_id')->nullable()->change();
            
            // Recria as foreign keys
            $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans')->onDelete('cascade');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            // Remove as foreign keys
            $table->dropForeign(['subscription_plan_id']);
            $table->dropForeign(['creator_id']);
            
            // Torna as colunas NOT NULL novamente
            $table->foreignId('subscription_plan_id')->nullable(false)->change();
            $table->foreignId('creator_id')->nullable(false)->change();
            
            // Recria as foreign keys
            $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans')->onDelete('cascade');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
