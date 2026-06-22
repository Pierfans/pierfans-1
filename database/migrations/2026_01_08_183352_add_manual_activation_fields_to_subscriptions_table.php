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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('activated_manually')->default(false)->after('is_active')->comment('Indica se a assinatura foi ativada manualmente pelo admin');
            $table->foreignId('activated_by_admin_id')->nullable()->after('activated_manually')->constrained('users')->onDelete('set null')->comment('ID do admin que ativou manualmente');
            $table->timestamp('activated_at')->nullable()->after('activated_by_admin_id')->comment('Data e hora da ativação manual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['activated_by_admin_id']);
            $table->dropColumn(['activated_manually', 'activated_by_admin_id', 'activated_at']);
        });
    }
};
