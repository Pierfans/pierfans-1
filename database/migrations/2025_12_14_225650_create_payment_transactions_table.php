<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            
            // Dados da transação SuitPay
            $table->string('request_number')->unique(); // UUID gerado para a requisição
            $table->string('transaction_id')->nullable(); // ID retornado pela SuitPay
            $table->enum('type', ['pix', 'card'])->default('pix');
            $table->enum('status', ['pending', 'paid_out', 'unpaid', 'canceled', 'chargeback', 'waiting_for_approval'])->default('pending');
            
            // Dados do pagamento
            $table->decimal('amount', 10, 2);
            $table->string('payment_code')->nullable(); // Código PIX para copiar
            $table->text('payment_code_base64')->nullable(); // QR Code em base64
            
            // Relacionamento com assinatura (pode ser null até confirmar pagamento)
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            
            // Dados adicionais
            $table->text('response_data')->nullable(); // JSON com resposta completa da SuitPay
            $table->text('webhook_data')->nullable(); // JSON com dados do webhook
            
            $table->timestamps();
            
            // Índices
            $table->index('request_number');
            $table->index('transaction_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
