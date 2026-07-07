<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suitpay_statement_entries', function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at');
            $table->string('descricao');
            // categorizado a partir da descrição: pix_in | fee_in | cashout | fee_out | manual_out | outro
            $table->string('tipo');
            $table->string('beneficiario')->nullable();
            $table->decimal('valor', 10, 2);            // com sinal (+ entrada, - saída/taxa)
            $table->decimal('saldo', 10, 2)->nullable(); // saldo corrente da conta naquela linha
            $table->string('control_id')->nullable();    // = request_number (venda) / suitpay_external_id (saque)
            $table->string('status')->nullable();
            // idempotência de re-upload: o saldo corrente torna cada linha única
            $table->string('line_hash', 32)->unique();
            $table->timestamps();

            $table->index(['occurred_at', 'tipo']);
            $table->index('control_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suitpay_statement_entries');
    }
};
