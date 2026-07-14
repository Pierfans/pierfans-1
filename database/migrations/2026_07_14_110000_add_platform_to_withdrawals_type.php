<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A plataforma passa a sacar o próprio caixa pelo app (saque type='platform'), em vez de
 * retirada manual no painel do SuitPay — que não passa por lugar nenhum do sistema e vira
 * buraco na reconciliação. Mesmo pipeline dos outros saques: PIX, webhook, ledger, logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE withdrawals MODIFY type ENUM('creator','affiliate','platform') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE withdrawals MODIFY type ENUM('creator','affiliate') NOT NULL");
    }
};
