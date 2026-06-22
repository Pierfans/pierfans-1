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
        // Verifica se creator_id já é nullable
        $columnInfo = DB::select("SHOW COLUMNS FROM affiliate_pros WHERE Field = 'creator_id'");
        if (!empty($columnInfo) && $columnInfo[0]->Null === 'YES') {
            // Já é nullable, não precisa alterar
            return;
        }
        
        // Primeiro, remove a foreign key constraint se existir
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'affiliate_pros' 
            AND COLUMN_NAME = 'creator_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE affiliate_pros DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
        }
        
        // Remove o índice único se existir (pode não ser necessário se creator_id for null)
        try {
            DB::statement('ALTER TABLE affiliate_pros DROP INDEX affiliate_pros_affiliate_pro_user_id_creator_id_unique');
        } catch (\Exception $e) {
            // Índice pode não existir ou já ter sido removido
        }
        
        // Torna creator_id nullable
        DB::statement('ALTER TABLE affiliate_pros MODIFY creator_id BIGINT UNSIGNED NULL');
        
        // Recria a foreign key
        DB::statement('ALTER TABLE affiliate_pros ADD CONSTRAINT affiliate_pros_creator_id_foreign FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affiliate_pros', function (Blueprint $table) {
            $table->foreignId('creator_id')->nullable(false)->change();
            $table->unique(['affiliate_pro_user_id', 'creator_id']);
        });
    }
};
