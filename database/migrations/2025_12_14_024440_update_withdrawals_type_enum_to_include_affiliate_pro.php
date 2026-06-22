<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Atualiza o enum para incluir 'affiliate_pro'
        DB::statement("ALTER TABLE withdrawals MODIFY COLUMN type ENUM('creator', 'affiliate', 'affiliate_pro') DEFAULT 'creator'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volta ao enum original (sem affiliate_pro)
        DB::statement("ALTER TABLE withdrawals MODIFY COLUMN type ENUM('creator', 'affiliate') DEFAULT 'creator'");
    }
};
