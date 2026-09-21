<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mensagem trancada no chat (bento 21/09: "incluir mensagem de audio" e "incluir campo
 * ou botao que permita venda de conteudos avulsos"; preco: "cada uma escolhe").
 *
 * A criadora manda o conteudo JA trancado com preco, o fa paga e abre — o modelo do
 * conteudo avulso que ja existe aqui, e nao "cobra antes e entrega depois", que deixaria
 * o fa pagando e esperando entrega.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ALTER cru em vez de ->change(): enum no MySQL e o unico jeito deterministico.
        DB::statement("ALTER TABLE messages MODIFY message_type ENUM('text','image','audio','video') NOT NULL DEFAULT 'text'");

        Schema::table('messages', function (Blueprint $table) {
            // null = mensagem comum, aberta. Preenchido = trancada ate alguem comprar.
            $table->decimal('price', 10, 2)->nullable()->after('file_path');
            // midia paga nao pode morar no disco publico: vai no 'local' e sai por rota
            // com verificacao. O campo diz em qual disco o arquivo esta, porque as
            // mensagens antigas (imagem gratis) continuam no 'public'.
            $table->string('file_disk', 20)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['price', 'file_disk']);
        });

        DB::statement("ALTER TABLE messages MODIFY message_type ENUM('text','image') NOT NULL DEFAULT 'text'");
    }
};
