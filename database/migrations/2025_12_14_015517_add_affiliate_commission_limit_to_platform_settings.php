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
            'affiliate_commission_limit',
            '0',
            'Limite de recebimento por indicação. Define quantas vezes um afiliado pode receber comissão pela mesma pessoa indicada. 0 = sem limite'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\PlatformSetting::where('key', 'affiliate_commission_limit')->delete();
    }
};
