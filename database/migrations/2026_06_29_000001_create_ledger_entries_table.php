<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_type'); // subscription_sale | ppv_sale | cashout
            // origem (audit) — só um dos dois é preenchido. unique p/ idempotência (nulls não contam no MySQL)
            $table->foreignId('payment_transaction_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('withdrawal_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->decimal('gross_amount', 10, 2);   // valor cheio: pago na venda / sacado
            $table->decimal('suitpay_fee', 10, 2);    // taxa do SuitPay (entrada ou saída)
            $table->decimal('creator_amount', 10, 2)->default(0);
            $table->decimal('affiliate_amount', 10, 2)->default(0);
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['occurred_at', 'entry_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
