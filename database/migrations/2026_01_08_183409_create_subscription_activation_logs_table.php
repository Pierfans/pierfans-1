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
        Schema::create('subscription_activation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade')->comment('ID da assinatura ativada');
            $table->foreignId('admin_user_id')->constrained('users')->onDelete('cascade')->comment('ID do admin que ativou');
            $table->text('reason')->nullable()->comment('Motivo da ativação manual');
            $table->timestamps();
            
            $table->index('subscription_id');
            $table->index('admin_user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_activation_logs');
    }
};
