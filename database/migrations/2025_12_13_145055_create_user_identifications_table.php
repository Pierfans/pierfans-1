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
        Schema::create('user_identifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            // Dados pessoais
            $table->string('name')->nullable();
            $table->string('document', 20)->nullable(); // CPF/CNPJ
            $table->string('phone_number', 20)->nullable();
            $table->string('email')->nullable();
            
            // Endereço
            $table->string('cod_ibge', 10)->nullable();
            $table->string('street')->nullable();
            $table->string('number', 20)->nullable();
            $table->string('complement')->nullable();
            $table->string('zip_code', 10)->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_identifications');
    }
};
