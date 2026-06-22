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
        // A configuração será armazenada na tabela platform_settings como uma entrada
        // Não precisamos adicionar coluna, apenas criar o registro padrão se não existir
        \App\Models\PlatformSetting::updateOrCreate(
            ['key' => 'email_verification_required'],
            [
                'value' => '0',
                'description' => 'Exigir confirmação de e-mail para novos usuários. 1 = ativado, 0 = desativado'
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\PlatformSetting::where('key', 'email_verification_required')->delete();
    }
};
