<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Trava: quem pode acessar o ARQUIVO de uma postagem.
 * Testa a fonte da verdade (Post::canBeViewedBy), usada pela rota que entrega a
 * mídia guardada no R2 (PostMediaController::stream). Sem essa regra, qualquer
 * usuário logado baixaria conteúdo de assinante e de PPV chutando o id da mídia.
 */
class PostMediaAccessTest extends TestCase
{
    use RefreshDatabase;

    private function postDe(User $criador, string $visibility): Post
    {
        return Post::create([
            'user_id'    => $criador->id,
            'visibility' => $visibility,
            'price'      => $visibility === 'paid' ? 10 : null,
        ]);
    }

    private function assina(User $assinante, User $criador): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('subscriptions')->insert([
            'user_id'              => $assinante->id,
            'creator_id'           => $criador->id,
            'subscription_plan_id' => 1,
            'total_amount'         => 0,
            'start_date'           => now()->subDay()->toDateString(),
            'end_date'             => now()->addYear()->toDateString(),
            'is_active'            => true,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
        Schema::enableForeignKeyConstraints();
    }

    private function compra(User $comprador, Post $post): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('post_purchases')->insert([
            'user_id'                => $comprador->id,
            'post_id'                => $post->id,
            'creator_id'             => $post->user_id,
            'payment_transaction_id' => 1,
            'amount_paid'            => 10,
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);
        Schema::enableForeignKeyConstraints();
    }

    public function test_postagem_gratis_e_publica(): void
    {
        $post = $this->postDe(User::factory()->create(), 'free');

        $this->assertTrue($post->canBeViewedBy(null));
        $this->assertTrue($post->canBeViewedBy(User::factory()->create()));
    }

    public function test_postagem_de_assinante_exige_assinatura_ativa(): void
    {
        $criador = User::factory()->create();
        $post = $this->postDe($criador, 'subscriber');

        $estranho = User::factory()->create();
        $assinante = User::factory()->create();
        $this->assina($assinante, $criador);

        $this->assertFalse($post->canBeViewedBy(null), 'deslogado nao pode ver');
        $this->assertFalse($post->canBeViewedBy($estranho), 'sem assinatura nao pode ver');
        $this->assertTrue($post->canBeViewedBy($assinante));
        $this->assertTrue($post->canBeViewedBy($criador), 'o dono sempre ve');
    }

    public function test_assinatura_vencida_nao_da_acesso(): void
    {
        $criador = User::factory()->create();
        $post = $this->postDe($criador, 'subscriber');
        $expirado = User::factory()->create();

        Schema::disableForeignKeyConstraints();
        DB::table('subscriptions')->insert([
            'user_id'              => $expirado->id,
            'creator_id'           => $criador->id,
            'subscription_plan_id' => 1,
            'total_amount'         => 0,
            'start_date'           => now()->subYear()->toDateString(),
            'end_date'             => now()->subDay()->toDateString(),
            'is_active'            => true,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
        Schema::enableForeignKeyConstraints();

        $this->assertFalse($post->canBeViewedBy($expirado));
    }

    public function test_conteudo_unico_exige_compra(): void
    {
        $criador = User::factory()->create();
        $post = $this->postDe($criador, 'paid');

        $estranho = User::factory()->create();
        $comprador = User::factory()->create();
        $this->compra($comprador, $post);

        $this->assertFalse($post->canBeViewedBy($estranho));
        $this->assertTrue($post->canBeViewedBy($comprador));
        $this->assertTrue($post->canBeViewedBy($criador));
    }

    public function test_assinar_o_criador_nao_libera_o_conteudo_unico(): void
    {
        $criador = User::factory()->create();
        $post = $this->postDe($criador, 'paid');
        $assinante = User::factory()->create();
        $this->assina($assinante, $criador);

        // PPV se paga a parte, assinatura nao inclui
        $this->assertFalse($post->canBeViewedBy($assinante));
    }

    public function test_admin_alcanca_a_midia_pra_telas_do_painel(): void
    {
        $criador = User::factory()->create();
        $post = $this->postDe($criador, 'subscriber');
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertTrue($post->canBeViewedBy($admin));
    }
}
