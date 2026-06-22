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
            $table->decimal('creator_affiliate_amount', 10, 2)->default(0)->after('referrer_amount')->comment('Comissão do afiliado sobre vendas do criador (quando o criador foi indicado pelo afiliado)');
            $table->foreignId('creator_affiliate_user_id')->nullable()->after('creator_affiliate_amount')->constrained('users')->onDelete('set null')->comment('ID do afiliado que indicou o criador e recebe comissão sobre suas vendas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['creator_affiliate_user_id']);
            $table->dropColumn(['creator_affiliate_amount', 'creator_affiliate_user_id']);
        });
    }
};
