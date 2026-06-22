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
        if (Schema::hasTable('affiliate_pro_referrals')) {
            return;
        }
        
        Schema::create('affiliate_pro_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_pro_user_id')->constrained('users')->onDelete('cascade')->comment('Usuário que é Afiliado PRO');
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade')->comment('Usuário indicado pelo Afiliado PRO');
            $table->timestamp('referred_at')->comment('Data em que o usuário foi indicado');
            $table->timestamps();
            
            // Garante que um usuário só pode ser indicado uma vez por cada Afiliado PRO
            $table->unique(['affiliate_pro_user_id', 'referred_user_id'], 'aff_pro_ref_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_pro_referrals');
    }
};
