<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comissao de afiliado no conteudo avulso e na mensagem do chat (bento 21/09, audio 15:
 * "se a criadora vende 100 reais de assinatura, 200 em conteudo unico e 200 em chat, ela
 * vendeu 500. O afiliado ganha 5% em cima dos 500").
 *
 * Ate hoje so a assinatura pagava comissao. As colunas espelham o que subscriptions ja
 * tinha: quem e o afiliado e quanto ele levou naquela venda.
 *
 * A comissao sai da parte da PLATAFORMA, nunca da criadora — e a regra que a assinatura
 * ja seguia e o bento confirmou ("5% afiliado se tiver, sai dos 20 do pier").
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['post_purchases', 'message_purchases'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->foreignId('affiliate_user_id')->nullable()->after('creator_id')
                    ->constrained('users')->onDelete('set null');
                $table->decimal('affiliate_amount', 10, 2)->default(0)->after('platform_amount');
            });
        }
    }

    public function down(): void
    {
        foreach (['post_purchases', 'message_purchases'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropForeign(['affiliate_user_id']);
                $table->dropColumn(['affiliate_user_id', 'affiliate_amount']);
            });
        }
    }
};
