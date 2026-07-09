<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('didit_session_id')->nullable()->after('creator_submitted_at');
            $table->string('didit_status')->nullable()->after('didit_session_id');
            $table->timestamp('didit_verified_at')->nullable()->after('didit_status');
            $table->json('didit_decision')->nullable()->after('didit_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'didit_session_id',
                'didit_status',
                'didit_verified_at',
                'didit_decision',
            ]);
        });
    }
};
