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
            $table->decimal('affiliate_pro_commission_percentage', 5, 2)->nullable()->after('is_affiliate_pro')->comment('Porcentagem de comissão do Afiliado PRO (configurada pelo admin)');
            $table->integer('affiliate_pro_limit_months')->nullable()->after('affiliate_pro_commission_percentage')->comment('Limite de meses que o Afiliado PRO recebe comissão (configurado pelo admin)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['affiliate_pro_commission_percentage', 'affiliate_pro_limit_months']);
        });
    }
};

