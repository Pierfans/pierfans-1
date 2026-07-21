<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pagar assinatura/PPV com o saldo da carteira.
 *
 * O ponto delicado é `ledger_entries.paid_with`. Até aqui o `entry_type` misturava dois eixos
 * que uma venda paga com saldo separa: ela É receita, mas NÃO é entrada de dinheiro no banco
 * (o dinheiro entrou lá atrás, no depósito). Sem distinguir, a venda com saldo infla a
 * reconciliação contra o extrato e — pior — infla o `appDelta`, que é o número que autoriza saque.
 *
 * Regra: cards de receita somam tudo; reconciliação e saldo real filtram paid_with='suitpay'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->string('paid_with', 20)->default('suitpay')->after('entry_type');
        });

        // Tudo que existe hoje entrou pelo SuitPay (o default já cobre, explicitar não custa).
        DB::table('ledger_entries')->update(['paid_with' => 'suitpay']);

        DB::statement("ALTER TABLE payment_transactions MODIFY type ENUM('pix','card','wallet') NOT NULL");
        DB::statement("ALTER TABLE subscriptions MODIFY payment_method ENUM('card','pix','wallet') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropColumn('paid_with');
        });

        DB::statement("ALTER TABLE payment_transactions MODIFY type ENUM('pix','card') NOT NULL");
        DB::statement("ALTER TABLE subscriptions MODIFY payment_method ENUM('card','pix') NOT NULL");
    }
};
