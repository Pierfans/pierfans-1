<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Trava: Conteúdo Único (paid) com comprador não pode ser excluído.
 * Testa a fonte da verdade (Post::isPurchasedUnique), usada pelos guards
 * do criador (PostController::destroy) e do admin (AdminPostController::destroy).
 */
class PpvDeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    private function post(string $visibility): Post
    {
        $user = User::factory()->create();

        return Post::create([
            'user_id'    => $user->id,
            'visibility' => $visibility,
            'price'      => $visibility === 'paid' ? 10 : null,
        ]);
    }

    private function addPurchase(Post $post): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('post_purchases')->insert([
            'user_id'               => 1,
            'post_id'               => $post->id,
            'creator_id'            => $post->user_id,
            'payment_transaction_id' => 1,
            'amount_paid'           => 10,
            'platform_percentage'   => 20,
            'platform_amount'       => 2,
            'creator_amount'        => 8,
            'purchased_at'          => now(),
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
        Schema::enableForeignKeyConstraints();
    }

    public function test_free_e_subscriber_nunca_travam(): void
    {
        $this->assertFalse($this->post('free')->isPurchasedUnique());
        $this->assertFalse($this->post('subscriber')->isPurchasedUnique());
    }

    public function test_paid_sem_compra_nao_trava(): void
    {
        $this->assertFalse($this->post('paid')->isPurchasedUnique());
    }

    public function test_paid_com_compra_trava(): void
    {
        $post = $this->post('paid');
        $this->addPurchase($post);
        $this->assertTrue($post->fresh()->isPurchasedUnique());
    }
}
