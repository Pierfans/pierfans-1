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
        \App\Models\PlatformSetting::setValue(
            'affiliate_commission_percentage',
            '5',
            'Porcentagem de comissão que o afiliado recebe por cada indicação que assinar'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\PlatformSetting::where('key', 'affiliate_commission_percentage')->delete();
    }
};
