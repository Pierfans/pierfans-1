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
        Schema::table('manual_credits', function (Blueprint $table) {
            $table->enum('type', ['creator', 'affiliate', 'affiliate_pro'])->default('creator')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manual_credits', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
