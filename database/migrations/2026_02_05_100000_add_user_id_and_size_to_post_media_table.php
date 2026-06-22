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
        Schema::table('post_media', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('post_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('size')->nullable()->after('file_type')->comment('File size in bytes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_media', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('size');
        });
    }
};
