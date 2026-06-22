<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->text('description')->nullable();
            $table->timestamps();
        });
        
        // Insere a configuração padrão de porcentagem da plataforma
        DB::table('platform_settings')->insert([
            'key' => 'platform_percentage',
            'value' => '20',
            'description' => 'Porcentagem que a plataforma recebe de cada assinatura',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
