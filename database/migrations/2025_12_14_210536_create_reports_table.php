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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('Usuário que fez a denúncia');
            $table->text('reason')->nullable()->comment('Motivo da denúncia');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable()->comment('Observações do admin');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null')->comment('Admin que revisou');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            // Evita denúncias duplicadas do mesmo usuário para a mesma postagem
            $table->unique(['post_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
