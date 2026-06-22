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
            $table->string('username')->unique()->nullable()->after('email');
            $table->text('description')->nullable()->after('username');
            $table->string('cover_photo')->nullable()->after('description');
            $table->string('profile_photo')->nullable()->after('cover_photo');
            $table->json('social_media')->nullable()->after('profile_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'description', 'cover_photo', 'profile_photo', 'social_media']);
        });
    }
};
