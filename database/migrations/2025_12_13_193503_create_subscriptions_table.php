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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Assinante
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade'); // Criador
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            
            // Valores
            $table->decimal('total_amount', 10, 2); // Valor total pago
            $table->decimal('platform_percentage', 5, 2); // % da plataforma no momento da assinatura
            $table->decimal('platform_amount', 10, 2); // Valor destinado à plataforma
            $table->decimal('creator_amount', 10, 2); // Valor destinado ao criador
            
            // Datas
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            
            // Método de pagamento
            $table->enum('payment_method', ['card', 'pix'])->default('card');
            
            $table->timestamps();
        });
        
        // Índice parcial para evitar múltiplas assinaturas ativas do mesmo criador
        // Nota: MySQL não suporta índices parciais, então usamos uma abordagem diferente
        // A validação será feita no código da aplicação
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
