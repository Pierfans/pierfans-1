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
        Schema::create('affiliate_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('users')->onDelete('cascade')->comment('ID do afiliado (usuário)');
            $table->string('slug', 10)->comment('Slug do afiliado usado na URL');
            
            // Informações de origem
            $table->boolean('is_external')->default(false)->comment('Se a visita veio de um site externo');
            $table->text('referer')->nullable()->comment('HTTP_REFERER');
            $table->string('ip_address', 45)->nullable()->comment('IP do visitante');
            $table->text('user_agent')->nullable()->comment('User-Agent do visitante');
            
            // Parâmetros UTM
            $table->string('utm_source')->nullable()->comment('utm_source');
            $table->string('utm_medium')->nullable()->comment('utm_medium');
            $table->string('utm_campaign')->nullable()->comment('utm_campaign');
            $table->string('utm_content')->nullable()->comment('utm_content');
            $table->string('utm_term')->nullable()->comment('utm_term');
            
            // IDs de rastreamento de plataformas
            $table->string('gclid')->nullable()->comment('Google Click ID');
            $table->string('fbclid')->nullable()->comment('Facebook Click ID');
            
            $table->timestamps();
            
            // Índices para melhor performance nas consultas
            $table->index('affiliate_id');
            $table->index('slug');
            $table->index('created_at');
            $table->index('is_external');
            $table->index('utm_source');
            $table->index('utm_campaign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_visits');
    }
};
