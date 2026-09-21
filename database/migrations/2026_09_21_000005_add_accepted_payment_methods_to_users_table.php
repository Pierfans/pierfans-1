<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A criadora escolhe quais formas de pagamento aceita (bento 21/09, audio 16: "o ideal e,
 * se possivel, a criadora escolher o que e o tipo de pagamento que ela aceita... na Pix ela
 * ganhou 80% e no cartao ela ganhou 76%. Era bom ate deixar isso bem claro para a criadora").
 *
 * Por CRIADORA e nao por plano/post: o que ele descreveu e uma preferencia dela. O "na hora
 * dela criar o conteudo" do audio e sobre ONDE mostrar os 80/76, nao sobre onde escolher.
 *
 * Padrao true nos dois: ninguem acorda amanha vendendo menos do que vendia hoje.
 *
 * Nao vale pra compra com saldo da carteira: ali o dinheiro ja entrou na plataforma la atras,
 * nao e mais venda no cartao, e travar isso seria inaplicavel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('accepts_pix')->default(true)->after('creator_status');
            $table->boolean('accepts_card')->default(true)->after('accepts_pix');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['accepts_pix', 'accepts_card']);
        });
    }
};
