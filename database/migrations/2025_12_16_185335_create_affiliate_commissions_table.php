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
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_user_id')->constrained('users')->onDelete('cascade')->comment('Usuário afiliado que recebe a comissão');
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade')->comment('Usuário indicado que realizou a assinatura');
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade')->comment('Assinatura que gerou a comissão');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade')->comment('Criador do conteúdo assinado');
            $table->decimal('subscription_amount', 10, 2)->comment('Valor total da assinatura');
            $table->decimal('commission_percentage', 5, 2)->default(5.00)->comment('Percentual aplicado (5%)');
            $table->decimal('commission_amount', 10, 2)->comment('Valor da comissão calculada');
            $table->enum('status', ['pending', 'released', 'paid', 'cancelled'])->default('pending')->comment('Status: pending = aguardando liberação, released = liberado para saque, paid = pago, cancelled = cancelado');
            $table->timestamp('released_at')->nullable()->comment('Data em que foi liberado para saque');
            $table->timestamp('paid_at')->nullable()->comment('Data em que foi pago');
            $table->text('notes')->nullable()->comment('Observações');
            $table->timestamps();
            
            // Índices
            $table->index('affiliate_user_id');
            $table->index('referred_user_id');
            $table->index('subscription_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
    }
};
