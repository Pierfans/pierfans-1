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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade')->comment('ID da conversa');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('ID do usuário que enviou a mensagem');
            $table->enum('message_type', ['text', 'image'])->default('text')->comment('Tipo de mensagem');
            $table->text('content')->nullable()->comment('Conteúdo da mensagem (texto ou null para imagens)');
            $table->string('file_path')->nullable()->comment('Caminho do arquivo (para imagens)');
            $table->timestamp('read_at')->nullable()->comment('Data em que a mensagem foi lida');
            $table->timestamps();
            
            // Índices para melhor performance
            $table->index('conversation_id');
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
