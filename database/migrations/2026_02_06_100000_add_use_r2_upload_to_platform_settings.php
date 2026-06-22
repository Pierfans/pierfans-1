<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('platform_settings')->insertOrIgnore([
            'key' => 'use_r2_upload',
            'value' => '1',
            'description' => 'Quando ativado, upload de mídia de posts é feito no R2 (Cloudflare). Quando desativado, é feito localmente.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('platform_settings')->where('key', 'use_r2_upload')->delete();
    }
};
