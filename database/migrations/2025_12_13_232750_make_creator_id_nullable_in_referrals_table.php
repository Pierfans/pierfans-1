<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            // Remove a foreign key constraint primeiro
            $table->dropForeign(['creator_id']);
        });
        
        // Torna a coluna nullable usando DB::statement
        DB::statement('ALTER TABLE referrals MODIFY creator_id BIGINT UNSIGNED NULL');
        
        Schema::table('referrals', function (Blueprint $table) {
            // Recria a foreign key constraint (agora nullable)
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            // Remove a foreign key constraint
            $table->dropForeign(['creator_id']);
        });
        
        // Torna a coluna NOT NULL novamente
        DB::statement('ALTER TABLE referrals MODIFY creator_id BIGINT UNSIGNED NOT NULL');
        
        Schema::table('referrals', function (Blueprint $table) {
            // Recria a foreign key constraint
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
