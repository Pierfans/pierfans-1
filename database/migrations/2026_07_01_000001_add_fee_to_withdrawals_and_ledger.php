<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            // Taxa cobrada do usuário no saque (0 = saque grátis do dia; senão a taxa fixa)
            $table->decimal('fee', 10, 2)->default(0)->after('amount');
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            // Taxa de saque que a plataforma embolsou (receita), separada do custo do SuitPay
            $table->decimal('withdraw_fee', 10, 2)->default(0)->after('suitpay_fee');
        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn('fee');
        });
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropColumn('withdraw_fee');
        });
    }
};
