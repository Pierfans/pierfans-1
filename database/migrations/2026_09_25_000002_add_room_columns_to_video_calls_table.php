<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Segunda entrega da chamada de vídeo (spec 24/09, seção 4): a sala do LiveKit. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_calls', function (Blueprint $t) {
            $t->string('room_name', 60)->nullable()->after('creator_joined_at')->comment('sala no LiveKit: chamada-{id}');
            $t->timestamp('user_joined_at')->nullable()->after('room_name')->comment('fã conectou (webhook)');
            $t->timestamp('ended_at')->nullable()->after('user_joined_at')->comment('sala fechada (robô ou webhook)');
        });

        // 'admin': devolução por denúncia, mesmo com a chamada realizada (única intervenção humana prevista)
        DB::statement("ALTER TABLE video_calls MODIFY refund_reason ENUM('refused','no_show','expired','admin') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE video_calls MODIFY refund_reason ENUM('refused','no_show','expired') NULL");

        Schema::table('video_calls', function (Blueprint $t) {
            $t->dropColumn(['room_name', 'user_joined_at', 'ended_at']);
        });
    }
};
