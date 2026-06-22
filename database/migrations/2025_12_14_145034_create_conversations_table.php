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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade')->comment('ID do criador de conteúdo');
            $table->foreignId('subscriber_id')->constrained('users')->onDelete('cascade')->comment('ID do assinante');
            $table->timestamp('last_message_at')->nullable()->comment('Data da última mensagem (para ordenação)');
            $table->timestamps();
            
            // Garante que não haverá conversas duplicadas entre o mesmo par
            $table->unique(['creator_id', 'subscriber_id']);
            
            // Índices para melhor performance
            $table->index('creator_id');
            $table->index('subscriber_id');
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
