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
        // Insere configurações padrão de dias de liberação (0 = liberação imediata)
        DB::table('platform_settings')->insertOrIgnore([
            [
                'key' => 'pix_release_days',
                'value' => '0',
                'description' => 'Número de dias que pagamentos via PIX ficam bloqueados antes de liberar para saque. 0 = liberação imediata',
            ],
            [
                'key' => 'card_release_days',
                'value' => '0',
                'description' => 'Número de dias que pagamentos via Cartão ficam bloqueados antes de liberar para saque. 0 = liberação imediata',
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('platform_settings')
            ->whereIn('key', ['pix_release_days', 'card_release_days'])
            ->delete();
    }
};
