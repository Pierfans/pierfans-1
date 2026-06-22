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
            $table->decimal('affiliate_pro_amount', 10, 2)->default(0)->after('referrer_amount')->comment('Valor da comissão do Afiliado PRO');
            $table->foreignId('affiliate_pro_user_id')->nullable()->after('affiliate_pro_amount')->constrained('users')->onDelete('set null')->comment('ID do Afiliado PRO que recebeu esta comissão');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['affiliate_pro_user_id']);
            $table->dropColumn(['affiliate_pro_amount', 'affiliate_pro_user_id']);
        });
    }
};
