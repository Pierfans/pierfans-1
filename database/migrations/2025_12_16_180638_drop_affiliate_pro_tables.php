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
        Schema::dropIfExists('affiliate_pro_referrals');
        Schema::dropIfExists('affiliate_pros');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recria tabela affiliate_pros
        Schema::create('affiliate_pros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_pro_user_id')->constrained('users')->onDelete('cascade')->comment('Usuário que é Afiliado PRO');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade')->comment('Criador que o Afiliado PRO recebe comissão');
            $table->decimal('commission_percentage', 5, 2)->comment('Porcentagem de comissão configurada pelo admin');
            $table->integer('limit_months')->comment('Limite de meses que o Afiliado PRO recebe comissão');
            $table->timestamp('started_at')->nullable()->comment('Data de início (preenchida na primeira venda)');
            $table->timestamp('ends_at')->nullable()->comment('Data de término (calculada automaticamente)');
            $table->timestamps();
            $table->unique(['affiliate_pro_user_id', 'creator_id']);
        });

        // Recria tabela affiliate_pro_referrals
        Schema::create('affiliate_pro_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_pro_user_id')->constrained('users')->onDelete('cascade')->comment('Usuário que é Afiliado PRO');
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade')->comment('Usuário indicado pelo Afiliado PRO');
            $table->timestamp('referred_at')->comment('Data e hora da indicação');
            $table->timestamps();
            $table->unique(['affiliate_pro_user_id', 'referred_user_id'], 'aff_pro_ref_unique');
        });
    }
};
