<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compra de mensagem trancada. Espelha post_purchases de proposito: mesma ideia, mesmas
 * colunas, pra quem ja conhece uma entender a outra sem reaprender nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('platform_percentage', 5, 2);
            $table->decimal('platform_amount', 10, 2);
            $table->decimal('creator_amount', 10, 2);
            $table->timestamp('purchased_at');
            $table->timestamps();

            // o mesmo fa nao compra a mesma mensagem duas vezes
            $table->unique(['user_id', 'message_id']);
            $table->index('creator_id');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            // igual ao post_id que o PPV ja usa: diz o que a recarga estava querendo abrir,
            // pro webhook desbloquear sozinho quando o PIX cair.
            $table->foreignId('message_id')->nullable()->after('post_id')->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropForeign(['message_id']);
            $table->dropColumn('message_id');
        });

        Schema::dropIfExists('message_purchases');
    }
};
