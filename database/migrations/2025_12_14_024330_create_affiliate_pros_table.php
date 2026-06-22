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
        Schema::create('affiliate_pros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_pro_user_id')->constrained('users')->onDelete('cascade')->comment('Usuário que é Afiliado PRO');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade')->comment('Criador que o Afiliado PRO vai receber comissão');
            $table->decimal('commission_percentage', 5, 2)->comment('Porcentagem de comissão que o Afiliado PRO recebe');
            $table->integer('limit_months')->comment('Limite de meses que o Afiliado PRO recebe comissão deste criador');
            $table->timestamp('started_at')->nullable()->comment('Data de início do período de comissão (quando o criador começou a vender)');
            $table->timestamp('ends_at')->nullable()->comment('Data de fim do período de comissão (started_at + limit_months)');
            $table->timestamps();
            
            // Garante que um Afiliado PRO só pode ter uma relação ativa com cada criador
            $table->unique(['affiliate_pro_user_id', 'creator_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_pros');
    }
};
