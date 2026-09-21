<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloqueio de usuario, campo proprio e nao o is_active (bento 21/09, sobre estorno
 * de cartao: "bloqueia o usuario assinante, para ele nao poder mais usar a plataforma").
 *
 * Nao da pra reusar o is_active: o User tem um global scope 'active' que faz a conta
 * sumir de TODA consulta do Eloquent. O admin precisa continuar enxergando o bloqueado
 * pra desbloquear, e o email precisa do endereco dele.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->after('is_active');
            $table->string('blocked_reason')->nullable()->after('blocked_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['blocked_at', 'blocked_reason']);
        });
    }
};
