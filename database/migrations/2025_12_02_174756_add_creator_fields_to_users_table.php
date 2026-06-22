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
        Schema::table('users', function (Blueprint $table) {
            // Status do criador
            $table->enum('creator_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('email_verified_at');
            
            // Dados pessoais (Step 1)
            $table->string('creator_full_name')->nullable()->after('creator_status');
            $table->string('creator_cpf')->nullable()->after('creator_full_name');
            $table->date('creator_birth_date')->nullable()->after('creator_cpf');
            $table->string('creator_phone')->nullable()->after('creator_birth_date');
            
            // Endereço (Step 2)
            $table->string('creator_zipcode')->nullable()->after('creator_phone');
            $table->string('creator_address')->nullable()->after('creator_zipcode');
            $table->string('creator_address_number')->nullable()->after('creator_address');
            $table->string('creator_address_complement')->nullable()->after('creator_address_number');
            $table->string('creator_neighborhood')->nullable()->after('creator_address_complement');
            $table->string('creator_city')->nullable()->after('creator_neighborhood');
            $table->string('creator_state')->nullable()->after('creator_city');
            
            // Dados bancários (Step 3)
            $table->string('creator_bank_name')->nullable()->after('creator_state');
            $table->string('creator_bank_agency')->nullable()->after('creator_bank_name');
            $table->string('creator_bank_account')->nullable()->after('creator_bank_agency');
            $table->string('creator_bank_account_type')->nullable()->after('creator_bank_account'); // 'checking' ou 'savings'
            $table->string('creator_pix_key')->nullable()->after('creator_bank_account_type');
            
            // Documentos (Step 4) - caminhos dos arquivos
            $table->string('creator_document_front')->nullable()->after('creator_pix_key');
            $table->string('creator_document_back')->nullable()->after('creator_document_front');
            $table->string('creator_selfie')->nullable()->after('creator_document_back');
            
            // Data de submissão
            $table->timestamp('creator_submitted_at')->nullable()->after('creator_selfie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'creator_status',
                'creator_full_name',
                'creator_cpf',
                'creator_birth_date',
                'creator_phone',
                'creator_zipcode',
                'creator_address',
                'creator_address_number',
                'creator_address_complement',
                'creator_neighborhood',
                'creator_city',
                'creator_state',
                'creator_bank_name',
                'creator_bank_agency',
                'creator_bank_account',
                'creator_bank_account_type',
                'creator_pix_key',
                'creator_document_front',
                'creator_document_back',
                'creator_selfie',
                'creator_submitted_at',
            ]);
        });
    }
};
