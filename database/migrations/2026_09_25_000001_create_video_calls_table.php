<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chamada de vídeo paga no chat (bento, áudio 11; spec de 24/09 em docs/superpowers/specs).
 * O dinheiro sai da carteira do fã no pedido e fica reservado nesta tabela até a criadora
 * entrar na sala (status done). Saldos somam só done.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_calls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('o fã');
            $t->foreignId('message_id')->nullable()->constrained()->nullOnDelete()->comment('mensagem do pedido na conversa');
            $t->foreignId('payment_transaction_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('price', 10, 2)->comment('copiado da criadora no pedido');
            $t->unsignedSmallInteger('duration_minutes');
            $t->enum('status', ['awaiting_payment', 'requested', 'scheduled', 'done', 'refunded'])->default('awaiting_payment');
            $t->timestamp('suggested_at')->nullable()->comment('sugestão do fã');
            $t->timestamp('scheduled_at')->nullable()->comment('marcado pela criadora, UTC');
            $t->timestamp('creator_joined_at')->nullable()->comment('ela entrou = aconteceu');
            $t->decimal('amount_paid', 10, 2)->default(0);
            $t->decimal('platform_percentage', 5, 2)->default(0);
            $t->decimal('platform_amount', 10, 2)->default(0);
            $t->foreignId('affiliate_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->decimal('affiliate_amount', 10, 2)->default(0);
            $t->decimal('creator_amount', 10, 2)->default(0);
            $t->enum('refund_reason', ['refused', 'no_show', 'expired'])->nullable();
            $t->timestamp('refunded_at')->nullable();
            $t->timestamps();

            $t->index(['conversation_id', 'status']);
            $t->index(['creator_id', 'status']);
            $t->index('user_id');
        });

        Schema::table('users', function (Blueprint $t) {
            $t->boolean('video_call_enabled')->default(false)->after('accepts_card');
            $t->decimal('video_call_price', 10, 2)->nullable()->after('video_call_enabled');
            $t->unsignedSmallInteger('video_call_minutes')->nullable()->after('video_call_price');
        });

        Schema::table('payment_transactions', function (Blueprint $t) {
            // recarga feita pra pagar uma chamada: o webhook conclui o pedido quando o PIX cai
            $t->foreignId('video_call_id')->nullable()->after('message_id')->constrained()->nullOnDelete();
        });

        Schema::table('messages', function (Blueprint $t) {
            $t->foreignId('video_call_id')->nullable()->after('price')->constrained()->nullOnDelete();
        });

        // ALTER cru: enum no MySQL é o único jeito determinístico (mesmo padrão de 2026_09_21_000002)
        DB::statement("ALTER TABLE messages MODIFY message_type ENUM('text','image','audio','video','video_call') NOT NULL DEFAULT 'text'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE messages MODIFY message_type ENUM('text','image','audio','video') NOT NULL DEFAULT 'text'");

        Schema::table('messages', function (Blueprint $t) {
            $t->dropForeign(['video_call_id']);
            $t->dropColumn('video_call_id');
        });
        Schema::table('payment_transactions', function (Blueprint $t) {
            $t->dropForeign(['video_call_id']);
            $t->dropColumn('video_call_id');
        });
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['video_call_enabled', 'video_call_price', 'video_call_minutes']);
        });
        Schema::dropIfExists('video_calls');
    }
};
