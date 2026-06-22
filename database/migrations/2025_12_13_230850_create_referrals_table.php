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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade'); // Usuário que foi indicado
            $table->foreignId('referrer_user_id')->constrained('users')->onDelete('cascade'); // Usuário que indicou
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade'); // Criador que foi acessado
            $table->timestamp('referred_at'); // Data e hora da indicação
            $table->timestamps();
            
            // Garante que um usuário só pode ter um indicador
            $table->unique('referred_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
