<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE posts MODIFY COLUMN visibility ENUM('free', 'subscriber', 'paid') NOT NULL DEFAULT 'free'");

        Schema::table('posts', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('visibility');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('price');
        });
        DB::statement("ALTER TABLE posts MODIFY COLUMN visibility ENUM('free', 'subscriber') NOT NULL DEFAULT 'free'");
    }
};
