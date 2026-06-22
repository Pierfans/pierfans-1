<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove campos da tabela users (se existirem)
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];
            
            if (Schema::hasColumn('users', 'is_affiliate_pro')) {
                $columnsToDrop[] = 'is_affiliate_pro';
            }
            if (Schema::hasColumn('users', 'affiliate_pro_commission_percentage')) {
                $columnsToDrop[] = 'affiliate_pro_commission_percentage';
            }
            if (Schema::hasColumn('users', 'affiliate_pro_limit_months')) {
                $columnsToDrop[] = 'affiliate_pro_limit_months';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // Remove campos da tabela subscriptions (se existirem)
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'affiliate_pro_user_id')) {
                // Tenta remover a foreign key se existir
                try {
                    $table->dropForeign(['affiliate_pro_user_id']);
                } catch (\Exception $e) {
                    // Ignora se a foreign key não existir
                }
            }
            
            $columnsToDrop = [];
            if (Schema::hasColumn('subscriptions', 'affiliate_pro_amount')) {
                $columnsToDrop[] = 'affiliate_pro_amount';
            }
            if (Schema::hasColumn('subscriptions', 'affiliate_pro_user_id')) {
                $columnsToDrop[] = 'affiliate_pro_user_id';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // Remove tabelas do Affiliate PRO
        Schema::dropIfExists('affiliate_pro_referrals');
        Schema::dropIfExists('affiliate_pros');

        // Remove 'affiliate_pro' do enum de manual_credits
        // Primeiro, atualiza registros existentes com 'affiliate_pro' para 'affiliate' ou remove
        if (Schema::hasTable('manual_credits') && Schema::hasColumn('manual_credits', 'type')) {
            DB::table('manual_credits')
                ->where('type', 'affiliate_pro')
                ->update(['type' => 'affiliate']);
            
            // Agora pode modificar o enum
            DB::statement("ALTER TABLE manual_credits MODIFY COLUMN type ENUM('creator', 'affiliate') DEFAULT 'creator'");
        }

        // Remove 'affiliate_pro' do enum de withdrawals
        // Primeiro, atualiza registros existentes com 'affiliate_pro' para 'affiliate' ou remove
        if (Schema::hasTable('withdrawals') && Schema::hasColumn('withdrawals', 'type')) {
            DB::table('withdrawals')
                ->where('type', 'affiliate_pro')
                ->update(['type' => 'affiliate']);
            
            // Agora pode modificar o enum
            DB::statement("ALTER TABLE withdrawals MODIFY COLUMN type ENUM('creator', 'affiliate') DEFAULT 'creator'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recria campos na tabela users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_affiliate_pro')->default(false)->after('creator_status');
            $table->decimal('affiliate_pro_commission_percentage', 5, 2)->nullable()->after('is_affiliate_pro');
            $table->integer('affiliate_pro_limit_months')->nullable()->after('affiliate_pro_commission_percentage');
        });

        // Recria campos na tabela subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('affiliate_pro_amount', 10, 2)->default(0)->after('referrer_amount');
            $table->foreignId('affiliate_pro_user_id')->nullable()->after('affiliate_pro_amount')->constrained('users')->onDelete('set null');
        });

        // Recria tabela affiliate_pros
        Schema::create('affiliate_pros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_pro_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('creator_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->decimal('commission_percentage', 5, 2);
            $table->integer('limit_months');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->unique(['affiliate_pro_user_id', 'creator_id']);
        });

        // Recria tabela affiliate_pro_referrals
        Schema::create('affiliate_pro_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_pro_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('referred_at');
            $table->timestamps();
            $table->unique(['affiliate_pro_user_id', 'referred_user_id'], 'aff_pro_ref_unique');
        });

        // Restaura enums
        DB::statement("ALTER TABLE manual_credits MODIFY COLUMN type ENUM('creator', 'affiliate', 'affiliate_pro') DEFAULT 'creator'");
        DB::statement("ALTER TABLE withdrawals MODIFY COLUMN type ENUM('creator', 'affiliate', 'affiliate_pro') DEFAULT 'creator'");
    }
};
