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
        // Insere configurações padrão de saque se não existirem
        \App\Models\PlatformSetting::updateOrCreate(
            ['key' => 'daily_withdraw_limit'],
            [
                'value' => '5',
                'description' => 'Número máximo de saques que um criador pode fazer por dia',
            ]
        );

        \App\Models\PlatformSetting::updateOrCreate(
            ['key' => 'min_withdraw_amount'],
            [
                'value' => '30.00',
                'description' => 'Valor mínimo que um criador pode solicitar para saque',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\PlatformSetting::where('key', 'daily_withdraw_limit')->delete();
        \App\Models\PlatformSetting::where('key', 'min_withdraw_amount')->delete();
    }
};
