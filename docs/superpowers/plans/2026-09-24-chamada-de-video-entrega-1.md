# Chamada de vídeo paga, primeira entrega: plano de implementação

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** criadora liga a chamada de vídeo com preço e duração, o fã pede e paga com saldo, o dinheiro fica reservado, ela marca ou recusa, e o robô devolve sozinho quando ela não aparece ou o pedido envelhece. Tudo atrás do interruptor `video_calls_enabled` (desligado) até a segunda entrega trazer o vídeo.

**Architecture:** tabela `video_calls` com o estado e a divisão do dinheiro; cada transição grava uma mensagem `video_call` na conversa (é isso que atualiza a tela dos dois lados, pelo polling que já existe); saldos da criadora e do afiliado somam só `status = done`; comando `chamadas:rodar` no cron (o `schedule:run` a cada minuto já está no crontab do servidor, conferido em 24/09). Regras puras (motivo de devolução, janela de entrada) ficam em `App\Support\VideoCallRules`, sem Laravel, testadas com `php` puro como o `PhoneFilter`.

**Tech Stack:** Laravel 12, MySQL, Blade, jQuery no chat. Sem pacote novo. Sem `vendor/` local: teste de Laravel roda em prod, em transação com rollback, via `ssh ... php artisan tinker < script.php` (memória `tinker_ssh_stdin`).

**Spec:** `docs/superpowers/specs/2026-09-24-chamada-de-video-design.md`.

**Desvio do spec, decidido no plano:** entra um quinto estado, `awaiting_payment`. Motivo: o fã sem saldo vai pra recarga e o webhook precisa de um id pra concluir o pedido quando o PIX cair (a mensagem trancada já existia antes da recarga; a chamada não). A linha nasce em `awaiting_payment` sem dinheiro e sem mensagem na conversa, invisível pra criadora; vira `requested` no pagamento. Não conta como "pedido aberto" nem entra em saldo nenhum. Tarefa 0 atualiza o spec.

**Bug antigo que este plano corrige de tabela:** a tela da carteira nunca mandou `message_id` no POST da recarga (só `amount` e `_token`), então "recarga abre a mensagem trancada sozinha" só funcionava no tinker. Tarefa 5 manda `message_id` e `video_call_id`.

---

## Convenções que valem pra todas as tarefas

- **Fim de linha:** `User.php`, `SuitPayWebhookController.php`, `PlatformSettingController.php`, `SubscriptionPlanController.php`, `admin/platform-settings/index.blade.php`, `subscription-plans/index.blade.php` são CRLF; o fim de `PlatformSetting.php` é LF (arquivo misto). Pra editar esses, usar a função `Patch` do PowerShell abaixo, que tenta CRLF e LF e exige âncora única. Arquivos novos: LF.

```powershell
$ErrorActionPreference = 'Stop'
Set-Location C:\Users\PC\Documents\pierfans
$enc = [Text.UTF8Encoding]::new($false)
function Patch($file, $old, $new) {
    $c = [IO.File]::ReadAllText($file)
    $oldCrlf = $old -replace "`r?`n", "`r`n"; $oldLf = $old -replace "`r?`n", "`n"
    if (([regex]::Matches($c, [regex]::Escape($oldCrlf))).Count -eq 1) { $old = $oldCrlf; $new = $new -replace "`r?`n", "`r`n" }
    elseif (([regex]::Matches($c, [regex]::Escape($oldLf))).Count -eq 1) { $old = $oldLf; $new = $new -replace "`r?`n", "`n" }
    else { throw "$file : ancora nao achada exatamente 1 vez" }
    [IO.File]::WriteAllText($file, $c.Replace($old, $new), $enc)
    Write-Host "ok  $file"
}
```

- **Lint local:** `php -l <arquivo>` no PowerShell (php não está no PATH do bash). JS: `node -e "new Function(require('fs').readFileSync('<arquivo>','utf8'))"` só pra arquivos `.js` puros; Blade não tem lint local, é render em prod (tarefa 12).
- **Commit:** um por tarefa, mensagem em português no estilo do repositório, sem deploy. Deploy só na tarefa 12, com autorização do Pedro.
- **Fuso:** entrada do usuário é `America/Sao_Paulo`; banco UTC. Sempre `Carbon::parse($valor, 'America/Sao_Paulo')->utc()` na entrada e `->setTimezone('America/Sao_Paulo')` na saída.

---

### Task 0: Registrar o desvio no spec

**Files:**
- Modify: `docs/superpowers/specs/2026-09-24-chamada-de-video-design.md`

- [ ] **Step 1: Trocar a linha de estados**

Na seção 2, trocar

```
| status | enum | `requested`, `scheduled`, `done`, `refunded` |
```

por

```
| status | enum | `awaiting_payment`, `requested`, `scheduled`, `done`, `refunded` |
```

e acrescentar, logo abaixo da tabela de colunas:

```
`awaiting_payment` (decidido no plano, 24/09): a linha nasce quando o fã clica em pagar. Com saldo, vira `requested` na mesma requisição. Sem saldo, o fã vai pra recarga com `video_call_id`, e o webhook conclui o pagamento quando o PIX cai. Sem dinheiro, sem mensagem na conversa e invisível pra criadora; não conta como pedido aberto nem entra em saldo. Linha que nunca foi paga fica parada, sem efeito.
```

- [ ] **Step 2: Commit**

```bash
git add docs/superpowers/specs/2026-09-24-chamada-de-video-design.md
git commit -m "docs(chamada): estado awaiting_payment pra recarga sem saldo concluir o pedido pelo webhook"
```

---

### Task 1: Regras puras + teste em PHP puro

**Files:**
- Create: `app/Support/VideoCallRules.php`
- Create: `tests/video_call_rules_test.php`

- [ ] **Step 1: Escrever o teste (falha porque a classe não existe)**

```php
<?php
/**
 * Regras da chamada de vídeo que não dependem do Laravel. Roda sozinho:
 *   php tests/video_call_rules_test.php
 */
require __DIR__ . '/../app/Support/VideoCallRules.php';

use App\Support\VideoCallRules;

$d = fn (string $s) => new DateTimeImmutable($s, new DateTimeZone('UTC'));
$agora = $d('2026-09-27 21:00:00');
$tol = 30;

// motivoDevolucao(status, scheduledAt, createdAt, creatorJoinedAt, agora, toleranciaMin)
$casos = [
    // [esperado, status, scheduled, created, joined]
    [null,       'requested', null,                 '2026-09-26 21:00:00', null],
    ['expired',  'requested', null,                 '2026-09-19 20:59:00', null],
    [null,       'requested', null,                 '2026-09-20 21:00:01', null],
    [null,       'scheduled', '2026-09-27 20:35:00', '2026-09-26 21:00:00', null], // 25 min de atraso, dentro da tolerância
    ['no_show',  'scheduled', '2026-09-27 20:29:00', '2026-09-26 21:00:00', null], // 31 min
    [null,       'scheduled', '2026-09-27 20:00:00', '2026-09-26 21:00:00', '2026-09-27 20:05:00'], // ela entrou
    ['expired',  'scheduled', '2026-10-30 21:00:00', '2026-08-27 20:59:00', null], // 30 dias do pagamento, mesmo marcada
    [null,       'done',      '2026-09-01 21:00:00', '2026-08-01 21:00:00', '2026-09-01 21:00:00'],
    [null,       'refunded',  null,                  '2026-08-01 21:00:00', null],
    [null,       'awaiting_payment', null,           '2026-08-01 21:00:00', null],
];
foreach ($casos as [$esperado, $status, $sched, $created, $joined]) {
    $got = VideoCallRules::motivoDevolucao($status, $sched ? $d($sched) : null, $d($created), $joined ? $d($joined) : null, $agora, $tol);
    assert($got === $esperado, "motivo $status sched=$sched created=$created joined=$joined: esperado " . var_export($esperado, true) . ", veio " . var_export($got, true));
}

// janelaAberta(status, scheduledAt, creatorJoinedAt, duracaoMin, agora, toleranciaMin)
$janela = [
    [false, 'requested', null,                  null, 15],
    [false, 'scheduled', '2026-09-27 21:11:00', null, 15], // 11 min antes: fechada
    [true,  'scheduled', '2026-09-27 21:10:00', null, 15], // 10 min antes: abre
    [true,  'scheduled', '2026-09-27 20:30:00', null, 15], // 30 min de atraso: ainda abre
    [false, 'scheduled', '2026-09-27 20:29:00', null, 15], // 31 min: fechou
    [true,  'done',      '2026-09-27 20:30:00', '2026-09-27 20:46:00', 15], // entrou 20:46, dura até 21:01
    [false, 'done',      '2026-09-27 20:30:00', '2026-09-27 20:44:00', 15], // acabou 20:59
    [false, 'refunded',  '2026-09-27 21:00:00', null, 15],
];
foreach ($janela as [$esperado, $status, $sched, $joined, $dur]) {
    $got = VideoCallRules::janelaAberta($status, $sched ? $d($sched) : null, $joined ? $d($joined) : null, $dur, $agora, $tol);
    assert($got === $esperado, "janela $status sched=$sched joined=$joined: esperado " . var_export($esperado, true));
}

echo "ok: " . (count($casos) + count($janela)) . " casos\n";
```

- [ ] **Step 2: Rodar e ver falhar**

PowerShell: `php -d zend.assertions=1 -d assert.exception=1 tests/video_call_rules_test.php`
Esperado: `Failed opening required '.../app/Support/VideoCallRules.php'`.

- [ ] **Step 3: Escrever a classe**

```php
<?php

namespace App\Support;

use DateTimeInterface;

/**
 * Regras da chamada de vídeo paga que não dependem de banco nem de relógio: quem chama
 * passa o "agora". Função pura de propósito, pra rodar o teste com php puro:
 *   php tests/video_call_rules_test.php
 *
 * Decisões do Pedro (24/09): "ela entrou = aconteceu"; devolve ao fã em três casos.
 */
class VideoCallRules
{
    public const DIAS_SEM_HORARIO = 7;   // requested parado sem ela marcar
    public const DIAS_TETO = 30;         // qualquer chamada não realizada, contado do pagamento
    public const MIN_ANTES = 10;         // botão "entrar" aparece 10 min antes do horário

    /**
     * Motivo da devolução automática ('no_show' | 'expired') ou null se não devolve.
     * done e refunded nunca devolvem daqui; awaiting_payment não tem dinheiro pra devolver.
     */
    public static function motivoDevolucao(
        string $status,
        ?DateTimeInterface $scheduledAt,
        DateTimeInterface $createdAt,
        ?DateTimeInterface $creatorJoinedAt,
        DateTimeInterface $agora,
        int $toleranciaMin
    ): ?string {
        if (! in_array($status, ['requested', 'scheduled'], true)) {
            return null;
        }

        $t = $agora->getTimestamp();

        if ($t - $createdAt->getTimestamp() > self::DIAS_TETO * 86400) {
            return 'expired';
        }

        if ($status === 'requested') {
            return ($t - $createdAt->getTimestamp() > self::DIAS_SEM_HORARIO * 86400) ? 'expired' : null;
        }

        // scheduled
        if ($scheduledAt && ! $creatorJoinedAt && $t > $scheduledAt->getTimestamp() + $toleranciaMin * 60) {
            return 'no_show';
        }

        return null;
    }

    /**
     * Se o botão "entrar na chamada" está de pé agora: de 10 min antes do horário até o fim
     * da tolerância e, depois que ela entrou, até o fim da duração.
     */
    public static function janelaAberta(
        string $status,
        ?DateTimeInterface $scheduledAt,
        ?DateTimeInterface $creatorJoinedAt,
        int $duracaoMin,
        DateTimeInterface $agora,
        int $toleranciaMin
    ): bool {
        $t = $agora->getTimestamp();

        if ($status === 'scheduled' && $scheduledAt) {
            $s = $scheduledAt->getTimestamp();
            return $t >= $s - self::MIN_ANTES * 60 && $t <= $s + $toleranciaMin * 60;
        }

        if ($status === 'done' && $creatorJoinedAt) {
            return $t <= $creatorJoinedAt->getTimestamp() + $duracaoMin * 60;
        }

        return false;
    }
}
```

- [ ] **Step 4: Rodar e ver passar**

`php -d zend.assertions=1 -d assert.exception=1 tests/video_call_rules_test.php`
Esperado: `ok: 18 casos`.

- [ ] **Step 5: Commit**

```bash
git add app/Support/VideoCallRules.php tests/video_call_rules_test.php
git commit -m "feat(chamada): regras puras da chamada de video (motivo de devolucao e janela de entrada) com teste em php puro"
```

---

### Task 2: Migration

**Files:**
- Create: `database/migrations/2026_09_25_000001_create_video_calls_table.php`

- [ ] **Step 1: Escrever a migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chamada de vídeo paga no chat (bento, áudio 11; spec de 24/09 em docs/superpowers/specs).
 * O dinheiro sai da carteira do fã no pedido e fica reservado nesta tabela até a criadora
 * entrar na sala (status done). Saldos somam só done.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_calls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('o fã');
            $t->foreignId('message_id')->nullable()->constrained()->nullOnDelete()->comment('mensagem do pedido na conversa');
            $t->foreignId('payment_transaction_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('price', 10, 2)->comment('copiado da criadora no pedido');
            $t->unsignedSmallInteger('duration_minutes');
            $t->enum('status', ['awaiting_payment', 'requested', 'scheduled', 'done', 'refunded'])->default('awaiting_payment');
            $t->timestamp('suggested_at')->nullable()->comment('sugestão do fã');
            $t->timestamp('scheduled_at')->nullable()->comment('marcado pela criadora, UTC');
            $t->timestamp('creator_joined_at')->nullable()->comment('ela entrou = aconteceu');
            $t->decimal('amount_paid', 10, 2)->default(0);
            $t->decimal('platform_percentage', 5, 2)->default(0);
            $t->decimal('platform_amount', 10, 2)->default(0);
            $t->foreignId('affiliate_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->decimal('affiliate_amount', 10, 2)->default(0);
            $t->decimal('creator_amount', 10, 2)->default(0);
            $t->enum('refund_reason', ['refused', 'no_show', 'expired'])->nullable();
            $t->timestamp('refunded_at')->nullable();
            $t->timestamps();

            $t->index(['conversation_id', 'status']);
            $t->index(['creator_id', 'status']);
            $t->index('user_id');
        });

        Schema::table('users', function (Blueprint $t) {
            $t->boolean('video_call_enabled')->default(false)->after('accepts_card');
            $t->decimal('video_call_price', 10, 2)->nullable()->after('video_call_enabled');
            $t->unsignedSmallInteger('video_call_minutes')->nullable()->after('video_call_price');
        });

        Schema::table('payment_transactions', function (Blueprint $t) {
            // recarga feita pra pagar uma chamada: o webhook conclui o pedido quando o PIX cai
            $t->foreignId('video_call_id')->nullable()->after('message_id')->constrained()->nullOnDelete();
        });

        Schema::table('messages', function (Blueprint $t) {
            $t->foreignId('video_call_id')->nullable()->after('price')->constrained()->nullOnDelete();
        });

        // ALTER cru: enum no MySQL é o único jeito determinístico (mesmo padrão de 2026_09_21_000002)
        DB::statement("ALTER TABLE messages MODIFY message_type ENUM('text','image','audio','video','video_call') NOT NULL DEFAULT 'text'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE messages MODIFY message_type ENUM('text','image','audio','video') NOT NULL DEFAULT 'text'");

        Schema::table('messages', function (Blueprint $t) {
            $t->dropForeign(['video_call_id']);
            $t->dropColumn('video_call_id');
        });
        Schema::table('payment_transactions', function (Blueprint $t) {
            $t->dropForeign(['video_call_id']);
            $t->dropColumn('video_call_id');
        });
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['video_call_enabled', 'video_call_price', 'video_call_minutes']);
        });
        Schema::dropIfExists('video_calls');
    }
};
```

- [ ] **Step 2: Lint**

`php -l database/migrations/2026_09_25_000001_create_video_calls_table.php` → `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_09_25_000001_create_video_calls_table.php
git commit -m "feat(chamada): tabela video_calls, config da criadora na users, video_call_id em payment_transactions e messages, tipo video_call na mensagem"
```

---

### Task 3: Modelos e configurações

**Files:**
- Create: `app/Models/VideoCall.php`
- Modify: `app/Models/PlatformSetting.php` (fim do arquivo, LF)
- Modify: `app/Models/Message.php` (fillable, relação, `toChatPayload`)
- Modify: `app/Models/PaymentTransaction.php` (fillable)
- Modify: `app/Models/User.php` (fillable + casts, CRLF)

- [ ] **Step 1: Criar `app/Models/VideoCall.php`**

```php
<?php

namespace App\Models;

use App\Support\VideoCallRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pedido de chamada de vídeo paga no chat. O dinheiro sai da carteira do fã no pedido e
 * fica aqui, reservado, até a criadora entrar na sala (done). Ver spec de 24/09.
 *
 * Estados: awaiting_payment → requested → scheduled → done
 *                             requested/scheduled → refunded (refused | no_show | expired)
 */
class VideoCall extends Model
{
    protected $fillable = [
        'conversation_id', 'creator_id', 'user_id', 'message_id', 'payment_transaction_id',
        'price', 'duration_minutes', 'status', 'suggested_at', 'scheduled_at', 'creator_joined_at',
        'amount_paid', 'platform_percentage', 'platform_amount',
        'affiliate_user_id', 'affiliate_amount', 'creator_amount',
        'refund_reason', 'refunded_at',
    ];

    protected $casts = [
        'price'               => 'decimal:2',
        'amount_paid'         => 'decimal:2',
        'platform_percentage' => 'decimal:2',
        'platform_amount'     => 'decimal:2',
        'affiliate_amount'    => 'decimal:2',
        'creator_amount'      => 'decimal:2',
        'suggested_at'        => 'datetime',
        'scheduled_at'        => 'datetime',
        'creator_joined_at'   => 'datetime',
        'refunded_at'         => 'datetime',
    ];

    public const ABERTAS = ['requested', 'scheduled'];

    public function conversation(): BelongsTo { return $this->belongsTo(Conversation::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'creator_id')->withoutGlobalScope('active'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id')->withoutGlobalScope('active'); }
    public function message(): BelongsTo { return $this->belongsTo(Message::class); }

    public function isOpen(): bool
    {
        return in_array($this->status, self::ABERTAS, true);
    }

    public function janelaAberta(): bool
    {
        return VideoCallRules::janelaAberta(
            $this->status, $this->scheduled_at, $this->creator_joined_at,
            (int) $this->duration_minutes, now(), PlatformSetting::getVideoCallToleranceMinutes()
        );
    }

    public function motivoDevolucao(): ?string
    {
        return VideoCallRules::motivoDevolucao(
            $this->status, $this->scheduled_at, $this->created_at, $this->creator_joined_at,
            now(), PlatformSetting::getVideoCallToleranceMinutes()
        );
    }

    /** "sáb 27/09 às 21:00", em Brasília. */
    public static function rotuloHorario(?\DateTimeInterface $dt): ?string
    {
        if (! $dt) {
            return null;
        }
        $c = \Carbon\Carbon::instance($dt)->setTimezone('America/Sao_Paulo');
        $dias = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
        return $dias[$c->dayOfWeek] . ' ' . $c->format('d/m') . ' às ' . $c->format('H:i');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'awaiting_payment' => 'Aguardando pagamento',
            'requested'        => 'Aguardando a criadora marcar',
            'scheduled'        => 'Marcada pra ' . self::rotuloHorario($this->scheduled_at),
            'done'             => 'Realizada',
            'refunded'         => 'Devolvida ao fã: ' . match ($this->refund_reason) {
                'refused' => 'a criadora recusou',
                'no_show' => 'a criadora não entrou',
                default   => 'passou do prazo',
            },
        };
    }

    /**
     * O que o chat manda pro navegador. Botões só pra quem pode: marcar e recusar são da
     * criadora com pedido aberto; entrar é dos dois, na janela. last_message_id diz qual
     * card da conversa é o atual (os anteriores viram histórico).
     */
    public function toPayload(?User $viewer): array
    {
        $souCriadora = $viewer && $viewer->id === $this->creator_id;
        $aberta = $this->isOpen();
        $sp = fn (?\Carbon\Carbon $c) => $c ? $c->copy()->setTimezone('America/Sao_Paulo')->format('Y-m-d\TH:i') : null;

        return [
            'id'                => $this->id,
            'status'            => $this->status,
            'status_label'      => $this->statusLabel(),
            'price'             => (float) $this->price,
            'duration_minutes'  => (int) $this->duration_minutes,
            'suggested_local'   => $sp($this->suggested_at),   // pro datetime-local
            'scheduled_local'   => $sp($this->scheduled_at),
            'suggested_label'   => self::rotuloHorario($this->suggested_at),
            'scheduled_label'   => self::rotuloHorario($this->scheduled_at),
            'refund_reason'     => $this->refund_reason,
            'sou_criadora'      => $souCriadora,
            'pode_marcar'       => $souCriadora && $aberta,
            'pode_recusar'      => $souCriadora && $aberta,
            'pode_entrar'       => $this->janelaAberta(),
            'last_message_id'   => (int) Message::where('video_call_id', $this->id)->max('id'),
        ];
    }

    /** Quanto a criadora tem de chamada realizada, liberado ou ainda no prazo do chat. */
    public static function creatorAmount(int $creatorId, bool $released): float
    {
        $days = PlatformSetting::getChatReleaseDays();
        $date = $days == 0 ? now() : now()->subDays($days)->endOfDay();

        return (float) self::where('creator_id', $creatorId)->where('status', 'done')
            ->where('creator_joined_at', $released ? '<=' : '>', $date)
            ->sum('creator_amount');
    }

    public static function affiliateAmount(int $affiliateId, bool $released): float
    {
        $days = PlatformSetting::getChatReleaseDays();
        $date = $days == 0 ? now() : now()->subDays($days)->endOfDay();

        return (float) self::where('affiliate_user_id', $affiliateId)->where('status', 'done')
            ->where('creator_joined_at', $released ? '<=' : '>', $date)
            ->sum('affiliate_amount');
    }

    /** Reservado em pedidos abertos: nem dela nem do fã ainda. Só pra ela ver na tela de saque. */
    public static function reservado(int $creatorId): float
    {
        return (float) self::where('creator_id', $creatorId)->whereIn('status', self::ABERTAS)->sum('creator_amount');
    }
}
```

- [ ] **Step 2: PlatformSetting (fim do arquivo é LF)**

Com `Patch`, âncora e substituição:

```php
    public static function isCardEnabled(): bool
    {
        return (string) self::getValue('card_enabled', '0') === '1';
    }
}
```
→
```php
    public static function isCardEnabled(): bool
    {
        return (string) self::getValue('card_enabled', '0') === '1';
    }

    /**
     * Chamada de vídeo paga no chat ligada na plataforma. Desligada até a segunda entrega
     * (a sala de vídeo) estar no ar. Spec de 24/09.
     */
    public static function isVideoCallsEnabled(): bool
    {
        return (string) self::getValue('video_calls_enabled', '0') === '1';
    }

    /** Quanto a criadora pode atrasar antes do robô devolver o dinheiro ao fã (no_show). */
    public static function getVideoCallToleranceMinutes(): int
    {
        return max(0, (int) self::getValue('video_call_tolerance_minutes', 30));
    }
}
```

- [ ] **Step 3: Message.php**

Fillable: depois de `'price',` inserir `'video_call_id',`.

Depois do método `purchases()` inserir:

```php
    public function videoCall(): BelongsTo
    {
        return $this->belongsTo(VideoCall::class);
    }
```

Em `toChatPayload`, trocar

```php
            'media_url' => ($aberta && $this->file_path) ? route('chat.media', $this->id) : null,
        ];
```
por
```php
            'media_url' => ($aberta && $this->file_path) ? route('chat.media', $this->id) : null,
            // Chamada de vídeo: o card desenha o estado ATUAL da chamada, não o da hora da mensagem
            'video_call' => $this->video_call_id ? $this->videoCall?->toPayload($viewer) : null,
        ];
```

- [ ] **Step 4: PaymentTransaction.php**: depois de `'message_id',` no fillable inserir `'video_call_id',`.

- [ ] **Step 5: User.php (CRLF, usar Patch)**

No `$fillable`, depois de `'accepts_card',` inserir `'video_call_enabled', 'video_call_price', 'video_call_minutes',`. Se `'accepts_card',` não for âncora única, usar a linha inteira do `$casts` `'accepts_card' => 'boolean',` e inserir `'video_call_enabled' => 'boolean',` depois dela; e conferir o fillable com `grep -n "accepts_card" app/Models/User.php`.

- [ ] **Step 6: Lint**

```
php -l app/Models/VideoCall.php; php -l app/Models/PlatformSetting.php; php -l app/Models/Message.php; php -l app/Models/PaymentTransaction.php; php -l app/Models/User.php
```

- [ ] **Step 7: Commit**

```bash
git add app/Models
git commit -m "feat(chamada): modelo VideoCall, config global (video_calls_enabled, tolerancia), payload da chamada na mensagem do chat"
```

---

### Task 4: Controller da chamada (pedir, pagar, marcar, recusar, devolver) e rotas

**Files:**
- Create: `app/Http/Controllers/VideoCallController.php`
- Modify: `routes/web.php` (grupo `chat`, linhas 187–195)

- [ ] **Step 1: Criar o controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\PaymentTransaction;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\VideoCall;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Chamada de vídeo paga no chat (bento, áudio 11; spec docs/superpowers/specs/2026-09-24).
 *
 * Criadora configura preço e duração; o fã pede e paga com saldo; o dinheiro fica reservado
 * na video_calls até ela entrar na sala. Pagamento é SEMPRE débito da carteira, como a
 * mensagem trancada: sem saldo, o fã recarrega levando video_call_id e o webhook conclui.
 * Toda transição grava uma mensagem 'video_call' na conversa: é ela que avisa o outro lado.
 */
class VideoCallController extends Controller
{
    private const TZ = 'America/Sao_Paulo';

    /** POST /chat/{conversationId}/chamada — o fã pede. */
    public function pedir(Request $request, $conversationId)
    {
        $user = Auth::user();
        $conversa = Conversation::findOrFail($conversationId);

        if ($erro = self::porQueNaoPodePedir($conversa, $user)) {
            return response()->json(['success' => false, 'message' => $erro], 400);
        }

        $request->validate(['suggested_at' => 'nullable|date']);
        $sugestao = $request->filled('suggested_at')
            ? Carbon::parse($request->input('suggested_at'), self::TZ)->utc()
            : null;

        $criadora = User::withoutGlobalScope('active')->find($conversa->creator_id);
        $preco = round((float) $criadora->video_call_price, 2);

        $chamada = VideoCall::create([
            'conversation_id'  => $conversa->id,
            'creator_id'       => $criadora->id,
            'user_id'          => $user->id,
            'price'            => $preco,
            'duration_minutes' => (int) $criadora->video_call_minutes,
            'status'           => 'awaiting_payment',
            'suggested_at'     => $sugestao,
        ]);

        $wallet = $user->getOrCreateWallet();
        if (round((float) $wallet->balance, 2) < $preco) {
            $falta = round($preco - (float) $wallet->balance, 2);
            return response()->json([
                'success'    => false,
                'recarregar' => true,
                'message'    => 'Faltam R$ ' . number_format($falta, 2, ',', '.') . ' no seu saldo.',
                'redirect'   => route('wallet.index', ['amount' => $preco, 'video_call_id' => $chamada->id]),
            ], 400);
        }

        try {
            $chamada = self::pagar($chamada);
        } catch (\Throwable $e) {
            Log::error('CHAMADA - FALHA NO PAGAMENTO (saldo devolvido pelo rollback)', [
                'user_id' => $user->id, 'video_call_id' => $chamada->id, 'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Não foi possível pedir a chamada. Seu saldo não foi debitado.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pedido enviado! O valor fica reservado até a chamada acontecer.',
            'chat_message' => $chamada->message->load('user')->toChatPayload($user),
        ]);
    }

    /**
     * O débito em si: awaiting_payment → requested. Estático porque o webhook da recarga chama
     * daqui. Idempotente: rodar de novo devolve a chamada como está.
     */
    public static function pagar(VideoCall $chamada, string $formaOriginal = 'wallet'): VideoCall
    {
        return DB::transaction(function () use ($chamada, $formaOriginal) {
            $chamada = VideoCall::lockForUpdate()->find($chamada->id);
            if ($chamada->status !== 'awaiting_payment') {
                return $chamada;
            }

            // Um pedido aberto por conversa (clique duplo, dois pedidos ao mesmo tempo)
            $outra = VideoCall::where('conversation_id', $chamada->conversation_id)
                ->whereIn('status', VideoCall::ABERTAS)->lockForUpdate()->exists();
            if ($outra) {
                throw new \RuntimeException('Já existe um pedido de chamada aberto nesta conversa.');
            }

            $preco = round((float) $chamada->price, 2);
            $wallet = Wallet::where('user_id', $chamada->user_id)->lockForUpdate()->first();
            if (! $wallet || round((float) $wallet->balance, 2) < $preco) {
                throw new \RuntimeException('Saldo insuficiente na carteira.');
            }

            $criadora = User::withoutGlobalScope('active')->find($chamada->creator_id);
            $wallet->subtractBalance($preco, 'Chamada de vídeo com @' . ($criadora->username ?? 'criadora') . ' (reservado)');

            $transacao = PaymentTransaction::create([
                'user_id'        => $chamada->user_id,
                'video_call_id'  => $chamada->id,
                'creator_id'     => $chamada->creator_id,
                'request_number' => (string) Str::uuid(),
                'type'           => 'wallet',
                'status'         => 'paid_out',
                'amount'         => $preco,
                'note'           => $formaOriginal === 'card'
                    ? 'Chamada de vídeo paga com saldo recarregado no cartão'
                    : 'Chamada de vídeo paga com saldo da carteira',
            ]);

            // Mesma divisão da mensagem trancada: percentual do cartão se o saldo veio de cartão;
            // afiliado sai da parte da plataforma.
            $percentual = $formaOriginal === 'card'
                ? PlatformSetting::getPlatformPercentageCard()
                : PlatformSetting::getPlatformPercentage();
            $daPlataforma = round($preco * $percentual / 100, 2);
            $daCriadora   = round($preco - $daPlataforma, 2);
            [$afiliadoId, $doAfiliado] = PlatformSetting::comissaoDoAfiliado($chamada->creator_id, $preco);
            $daPlataforma = round($daPlataforma - $doAfiliado, 2);

            $chamada->update([
                'status'                 => 'requested',
                'payment_transaction_id' => $transacao->id,
                'amount_paid'            => $preco,
                'platform_percentage'    => $percentual,
                'platform_amount'        => $daPlataforma,
                'affiliate_user_id'      => $afiliadoId,
                'affiliate_amount'       => $doAfiliado,
                'creator_amount'         => $daCriadora,
            ]);

            $texto = 'Pediu uma chamada de vídeo de ' . $chamada->duration_minutes . ' min por R$ ' . number_format($preco, 2, ',', '.')
                . ($chamada->suggested_at ? '. Sugeriu ' . VideoCall::rotuloHorario($chamada->suggested_at) . '.' : '.');
            $msg = self::mensagem($chamada, $chamada->user_id, $texto);
            $chamada->update(['message_id' => $msg->id]);

            return $chamada->fresh();
        });
    }

    /** Recarga que nasceu de um pedido sem saldo: paga agora. Chamado pelo webhook e pelo cartão. */
    public static function concluirAposRecarga(PaymentTransaction $transacao): void
    {
        if (! $transacao->video_call_id) {
            return;
        }
        try {
            $chamada = VideoCall::find($transacao->video_call_id);
            if ($chamada && $chamada->status === 'awaiting_payment') {
                self::pagar($chamada, $transacao->type);
                Log::info('CHAMADA - PAGA APOS RECARGA', ['transaction_id' => $transacao->id, 'video_call_id' => $chamada->id]);
            }
        } catch (\Throwable $e) {
            // O saldo já entrou na carteira: o fã só precisa pedir de novo. Não derruba o webhook.
            Log::error('CHAMADA - FALHA AO PAGAR APOS RECARGA', ['transaction_id' => $transacao->id, 'error' => $e->getMessage()]);
        }
    }

    /** POST /chat/chamada/{videoCall}/marcar — criadora marca ou remarca. Vazio = agora. */
    public function marcar(Request $request, VideoCall $videoCall)
    {
        $user = Auth::user();
        if ($videoCall->creator_id !== $user->id || ! $videoCall->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Este pedido não está aberto.'], 400);
        }

        $request->validate(['scheduled_at' => 'nullable|date'], ['scheduled_at.date' => 'Horário inválido.']);
        $horario = $request->filled('scheduled_at')
            ? Carbon::parse($request->input('scheduled_at'), self::TZ)->utc()
            : now();

        if ($horario->lt(now()->subMinutes(5))) {
            return response()->json(['success' => false, 'message' => 'Esse horário já passou.'], 400);
        }

        $remarcou = $videoCall->status === 'scheduled';
        $videoCall->update(['status' => 'scheduled', 'scheduled_at' => $horario]);
        $msg = self::mensagem($videoCall, $user->id, ($remarcou ? 'Remarcou' : 'Marcou') . ' a chamada pra ' . VideoCall::rotuloHorario($horario) . '.');

        return response()->json(['success' => true, 'chat_message' => $msg->load('user')->toChatPayload($user)]);
    }

    /** POST /chat/chamada/{videoCall}/recusar — criadora recusa, estorno na hora. */
    public function recusar(VideoCall $videoCall)
    {
        $user = Auth::user();
        if ($videoCall->creator_id !== $user->id || ! $videoCall->isOpen()) {
            return response()->json(['success' => false, 'message' => 'Este pedido não está aberto.'], 400);
        }

        $msg = self::devolver($videoCall, 'refused');

        return response()->json(['success' => true, 'chat_message' => $msg?->load('user')->toChatPayload($user)]);
    }

    /**
     * Devolve ao fã como saldo na carteira e fecha a chamada. Lock na linha e reconferência
     * do estado: cron rodando duas vezes ou clique repetido não credita duas vezes.
     * Devolve a mensagem gravada, ou null se não havia o que devolver.
     */
    public static function devolver(VideoCall $chamada, string $motivo): ?Message
    {
        return DB::transaction(function () use ($chamada, $motivo) {
            $chamada = VideoCall::lockForUpdate()->find($chamada->id);
            if (! $chamada || ! $chamada->isOpen()) {
                return null;
            }

            $fa = User::withoutGlobalScope('active')->find($chamada->user_id);
            $criadora = User::withoutGlobalScope('active')->find($chamada->creator_id);
            $wallet = Wallet::where('user_id', $fa->id)->lockForUpdate()->first() ?? $fa->getOrCreateWallet();
            $wallet->addBalance(
                round((float) $chamada->amount_paid, 2),
                null,
                'Devolução da chamada de vídeo com @' . ($criadora->username ?? 'criadora'),
                'Motivo: ' . $motivo,
                $chamada->payment_transaction_id
            );

            $chamada->update(['status' => 'refunded', 'refund_reason' => $motivo, 'refunded_at' => now()]);

            $valor = 'R$ ' . number_format((float) $chamada->amount_paid, 2, ',', '.');
            $texto = match ($motivo) {
                'refused' => 'Recusou a chamada. ' . $valor . ' voltaram pra carteira do fã.',
                'no_show' => 'A criadora não entrou na chamada. ' . $valor . ' voltaram pra carteira do fã.',
                default   => 'O pedido passou do prazo. ' . $valor . ' voltaram pra carteira do fã.',
            };

            Log::info('CHAMADA - DEVOLVIDA', ['video_call_id' => $chamada->id, 'motivo' => $motivo, 'valor' => $chamada->amount_paid]);

            return self::mensagem($chamada, $chamada->creator_id, $texto);
        });
    }

    /** Mensagem 'video_call' na conversa: é o que atualiza a tela dos dois lados. */
    private static function mensagem(VideoCall $chamada, int $autorId, string $texto): Message
    {
        $msg = Message::create([
            'conversation_id' => $chamada->conversation_id,
            'user_id'         => $autorId,
            'message_type'    => 'video_call',
            'content'         => $texto,
            'video_call_id'   => $chamada->id,
        ]);
        Conversation::where('id', $chamada->conversation_id)->update(['last_message_at' => now()]);

        return $msg;
    }

    /** Motivo pelo qual este fã não pode pedir nesta conversa, ou null se pode. */
    public static function porQueNaoPodePedir(Conversation $conversa, ?User $user): ?string
    {
        if (! PlatformSetting::isVideoCallsEnabled()) {
            return 'Chamada de vídeo ainda não está disponível.';
        }
        if (! $user || $conversa->subscriber_id !== $user->id) {
            return 'Só o fã da conversa pode pedir a chamada.';
        }
        $criadora = User::withoutGlobalScope('active')->find($conversa->creator_id);
        if (! $criadora || ! $criadora->video_call_enabled || (float) $criadora->video_call_price < 1 || (int) $criadora->video_call_minutes < 1) {
            return 'Esta criadora não está oferecendo chamada de vídeo.';
        }
        if (VideoCall::where('conversation_id', $conversa->id)->whereIn('status', VideoCall::ABERTAS)->exists()) {
            return 'Já existe um pedido de chamada aberto nesta conversa.';
        }

        return null;
    }
}
```

- [ ] **Step 2: Rotas**

Em `routes/web.php`, dentro do grupo `chat`, logo depois da linha

```php
        Route::post('/message/{message}/unlock', [\App\Http\Controllers\PaidMessageController::class, 'unlock'])->name('unlock');
```
inserir
```php
        // Chamada de vídeo paga (spec 24/09). Antes das rotas com {conversationId} pelo mesmo motivo.
        Route::post('/chamada/{videoCall}/marcar', [\App\Http\Controllers\VideoCallController::class, 'marcar'])->name('chamada.marcar');
        Route::post('/chamada/{videoCall}/recusar', [\App\Http\Controllers\VideoCallController::class, 'recusar'])->name('chamada.recusar');
        Route::post('/{conversationId}/chamada', [\App\Http\Controllers\VideoCallController::class, 'pedir'])->name('chamada.pedir');
```

- [ ] **Step 3: Lint e commit**

`php -l app/Http/Controllers/VideoCallController.php`

```bash
git add app/Http/Controllers/VideoCallController.php routes/web.php
git commit -m "feat(chamada): fa pede e paga com saldo (reservado), criadora marca ou recusa, devolucao com lock e mensagem na conversa"
```

---

### Task 5: Recarga carrega `video_call_id` (e conserta o `message_id` que nunca saía da tela)

**Files:**
- Modify: `app/Http/Controllers/WalletController.php` (linhas 173, 178, 269, 316, 249, 414, 486)
- Modify: `app/Http/Controllers/SuitPayWebhookController.php` (linha 533, CRLF)
- Modify: `resources/views/wallet/index.blade.php` (linhas 297–300)

- [ ] **Step 1: WalletController**

Trocar
```php
            return $this->processWalletPixPayment($user, $amount, $request->input('message_id'));
```
por
```php
            return $this->processWalletPixPayment($user, $amount, $request->input('message_id'), $request->input('video_call_id'));
```
Trocar
```php
            return $this->processWalletCardPayment($user, $amount, $request, $request->input('message_id'));
```
por
```php
            return $this->processWalletCardPayment($user, $amount, $request, $request->input('message_id'), $request->input('video_call_id'));
```
Assinaturas:
```php
    private function processWalletPixPayment(User $user, float $amount, $messageId = null)
```
→
```php
    private function processWalletPixPayment(User $user, float $amount, $messageId = null, $videoCallId = null)
```
```php
    private function processWalletCardPayment(User $user, float $amount, Request $request, $messageId = null)
```
→
```php
    private function processWalletCardPayment(User $user, float $amount, Request $request, $messageId = null, $videoCallId = null)
```
Nas duas criações de `PaymentTransaction` (linhas ~249 e ~414), depois de
```php
            'message_id' => $messageId, // mensagem trancada que esta recarga vai abrir
```
inserir
```php
            'video_call_id' => $videoCallId, // chamada de vídeo que esta recarga vai pagar
```
(a linha do `message_id` aparece duas vezes: o `Patch` exige âncora única, então usar `-replace` global com PowerShell: `$c = $c.Replace("'message_id' => `$messageId, // mensagem trancada que esta recarga vai abrir", "'message_id' => `$messageId, // mensagem trancada que esta recarga vai abrir`r`n            'video_call_id' => `$videoCallId, // chamada de vídeo que esta recarga vai pagar")` e conferir com `grep -c video_call_id` = 4.)

Depois de (linha ~486)
```php
            \App\Http\Controllers\PaidMessageController::desbloquearAposRecarga($transaction);
```
inserir
```php
            \App\Http\Controllers\VideoCallController::concluirAposRecarga($transaction);
```

- [ ] **Step 2: SuitPayWebhookController (CRLF, Patch)**

Depois de
```php
            // Recarga feita pra abrir uma mensagem trancada do chat: abre agora.
            \App\Http\Controllers\PaidMessageController::desbloquearAposRecarga($transaction);
```
inserir
```php
            // Recarga feita pra pagar uma chamada de vídeo: paga agora.
            \App\Http\Controllers\VideoCallController::concluirAposRecarga($transaction);
```

- [ ] **Step 3: wallet/index.blade.php**

Trocar
```js
                data: {
                    amount: amount,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
```
por
```js
                data: {
                    amount: amount,
                    // O que esta recarga vai abrir/pagar quando o PIX cair. Vem da URL que o chat
                    // montou. Antes de 25/09 nada disso era enviado e a mensagem não abria sozinha.
                    message_id: new URLSearchParams(location.search).get('message_id') || '',
                    video_call_id: new URLSearchParams(location.search).get('video_call_id') || '',
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
```

- [ ] **Step 4: Lint e commit**

`php -l app/Http/Controllers/WalletController.php; php -l app/Http/Controllers/SuitPayWebhookController.php`

```bash
git add app/Http/Controllers/WalletController.php app/Http/Controllers/SuitPayWebhookController.php resources/views/wallet/index.blade.php
git commit -m "feat(chamada): recarga leva video_call_id e o webhook paga a chamada; fix: a tela da carteira nunca mandava message_id, entao a mensagem trancada nao abria sozinha depois da recarga"
```

---

### Task 6: Saldos da criadora e do afiliado, reservado na tela de saque

**Files:**
- Modify: `app/Models/User.php` (linhas 356, 423, 535, 595, CRLF)
- Modify: `app/Http/Controllers/WithdrawController.php` (linha 28)
- Modify: `resources/views/withdraw/index.blade.php` (linhas 171–175)

- [ ] **Step 1: User.php, quatro somas (Patch, uma por vez)**

```php
        $releasedAmount += \App\Models\MessagePurchase::creatorAmount($this->id, released: true);
```
→
```php
        $releasedAmount += \App\Models\MessagePurchase::creatorAmount($this->id, released: true);
        // Chamada de vídeo: só status done, prazo do chat contado da entrada dela (spec 24/09)
        $releasedAmount += \App\Models\VideoCall::creatorAmount($this->id, released: true);
```
```php
        $pendingAmount += \App\Models\MessagePurchase::creatorAmount($this->id, released: false);
```
→
```php
        $pendingAmount += \App\Models\MessagePurchase::creatorAmount($this->id, released: false);
        $pendingAmount += \App\Models\VideoCall::creatorAmount($this->id, released: false);
```
```php
        $releasedAmount += \App\Models\MessagePurchase::affiliateAmount($this->id, released: true);
```
→
```php
        $releasedAmount += \App\Models\MessagePurchase::affiliateAmount($this->id, released: true);
        $releasedAmount += \App\Models\VideoCall::affiliateAmount($this->id, released: true);
```
```php
        $pendingAmount += \App\Models\MessagePurchase::affiliateAmount($this->id, released: false);
```
→
```php
        $pendingAmount += \App\Models\MessagePurchase::affiliateAmount($this->id, released: false);
        $pendingAmount += \App\Models\VideoCall::affiliateAmount($this->id, released: false);
```

- [ ] **Step 2: WithdrawController**

```php
        $pendingBalance = $user->getPendingBalance();
```
→
```php
        $pendingBalance = $user->getPendingBalance();
        // Chamadas pedidas e ainda não realizadas: nem dela nem do fã. Só pra ela saber que existe.
        $reservedCalls = \App\Models\VideoCall::reservado($user->id);
```
e no `compact(...)`/array do `view('withdraw.index', ...)` acrescentar `'reservedCalls'` (conferir a forma exata com `grep -n "view('withdraw.index'" app/Http/Controllers/WithdrawController.php`).

- [ ] **Step 3: withdraw/index.blade.php**

Depois de
```blade
                    <div>
                        <p class="text-sm text-[#706f6c] mb-2">Saldo a liberar</p>
                        <p class="text-2xl font-bold text-[#1b1b18]">
                            R$ {{ number_format($pendingBalance, 2, ',', '.') }}
                        </p>
                    </div>
```
inserir
```blade
                    @if(($reservedCalls ?? 0) > 0)
                    <div>
                        <p class="text-sm text-[#706f6c] mb-2">Reservado em chamadas marcadas</p>
                        <p class="text-2xl font-bold text-[#1b1b18]">
                            R$ {{ number_format($reservedCalls, 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-[#706f6c]">Entra no saldo quando a chamada acontecer.</p>
                    </div>
                    @endif
```

- [ ] **Step 4: Lint e commit**

`php -l app/Models/User.php; php -l app/Http/Controllers/WithdrawController.php`

```bash
git add app/Models/User.php app/Http/Controllers/WithdrawController.php resources/views/withdraw/index.blade.php
git commit -m "feat(chamada): saldo da criadora e do afiliado somam chamada realizada; reservado aparece na tela de saque"
```

---

### Task 7: Configuração da criadora (página de planos)

**Files:**
- Modify: `resources/views/subscription-plans/index.blade.php` (CRLF; bloco depois do `@endif` das formas de pagamento, e o JS em ~406)
- Modify: `app/Http/Controllers/SubscriptionPlanController.php` (CRLF; `index` ~51 e `store` ~72–95)

- [ ] **Step 1: View, bloco de configuração**

Âncora (fim do bloco de formas de pagamento, dentro do mesmo `plan-card`):
```blade
                        <strong>Cartão de crédito:</strong> em breve. Por enquanto as vendas são só no PIX.
                    </p>
                    @endif
                </div>
```
→
```blade
                        <strong>Cartão de crédito:</strong> em breve. Por enquanto as vendas são só no PIX.
                    </p>
                    @endif
                </div>

                @if(\App\Models\PlatformSetting::isVideoCallsEnabled())
                {{-- Chamada de vídeo paga (bento, áudio 11; spec 24/09). O fã pede pelo chat,
                     paga com saldo e o valor fica reservado até ela entrar na sala. --}}
                <div class="plan-card">
                    <h3 class="plan-title">Chamada de vídeo</h3>
                    <p class="plan-description">
                        Seus fãs pedem pelo chat e pagam na hora. O valor fica reservado e entra no seu
                        saldo quando a chamada acontece. Você marca o horário.
                    </p>
                    <label style="display:flex;align-items:center;gap:10px;margin:14px 0;cursor:pointer">
                        <input type="checkbox" id="video_call_enabled" {{ Auth::user()->video_call_enabled ? 'checked' : '' }}>
                        <span><strong>Oferecer chamada de vídeo</strong></span>
                    </label>
                    <div style="display:flex;gap:12px;flex-wrap:wrap">
                        <div style="flex:1;min-width:140px">
                            <label class="plan-description" for="video_call_price">Preço (R$)</label>
                            <input type="number" id="video_call_price" min="1" step="0.01" class="price-input"
                                   value="{{ Auth::user()->video_call_price ? number_format(Auth::user()->video_call_price, 2, '.', '') : '' }}" placeholder="100.00">
                        </div>
                        <div style="flex:1;min-width:140px">
                            <label class="plan-description" for="video_call_minutes">Duração (min)</label>
                            <input type="number" id="video_call_minutes" min="5" max="120" step="1" class="price-input"
                                   value="{{ Auth::user()->video_call_minutes ?? '' }}" placeholder="15">
                        </div>
                    </div>
                    <p class="plan-description" style="margin-top:12px">
                        Você recebe <strong>{{ number_format(100 - $platformPercentage, 0) }}%</strong> de cada chamada
                        realizada. Se você recusar ou não entrar na sala, o fã recebe o valor de volta.
                    </p>
                </div>
                @endif
```
(Se a classe `price-input` não existir na view, usar `class="w-full px-3 py-2 border rounded-lg"`; conferir com `grep -n "price-input" resources/views/subscription-plans/index.blade.php`.)

- [ ] **Step 2: View, JS que salva**

```js
                accepts_card: document.getElementById('accepts_card').checked ? 1 : 0,
                plans: plansArray
```
→
```js
                accepts_card: document.getElementById('accepts_card').checked ? 1 : 0,
                // Chamada de vídeo: o bloco só existe com o interruptor do admin ligado
                video_call_enabled: document.getElementById('video_call_enabled')?.checked ? 1 : 0,
                video_call_price: document.getElementById('video_call_price')?.value || '',
                video_call_minutes: document.getElementById('video_call_minutes')?.value || '',
                plans: plansArray
```
E no `.then(data => { if (data.success) { showModal(); } else { alert('Erro ao salvar planos. Tente novamente.'); } })` trocar o `alert` por `alert(data.message || 'Erro ao salvar planos. Tente novamente.');` pra mensagem de validação chegar na criadora.

- [ ] **Step 3: Controller `store` (Patch)**

```php
            'accepts_card' => 'nullable|boolean',
            'plans' => 'required|array',
```
→
```php
            'accepts_card' => 'nullable|boolean',
            'video_call_enabled' => 'nullable|boolean',
            'video_call_price' => 'nullable|numeric|min:1|max:9999',
            'video_call_minutes' => 'nullable|integer|min:5|max:120',
            'plans' => 'required|array',
```
e
```php
        Auth::user()->update([
            'accepts_pix' => $aceitaPix,
            'accepts_card' => $aceitaCartao,
        ]);
```
→
```php
        // Chamada de vídeo (spec 24/09): só grava com o interruptor global ligado; ligar exige preço e duração.
        $chamada = [];
        if (\App\Models\PlatformSetting::isVideoCallsEnabled()) {
            $ligada = $request->boolean('video_call_enabled');
            $preco = $request->filled('video_call_price') ? round((float) $request->input('video_call_price'), 2) : null;
            $minutos = $request->filled('video_call_minutes') ? (int) $request->input('video_call_minutes') : null;
            if ($ligada && ($preco === null || $minutos === null)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pra oferecer chamada de vídeo, informe o preço e a duração.',
                ], 400);
            }
            $chamada = ['video_call_enabled' => $ligada, 'video_call_price' => $preco, 'video_call_minutes' => $minutos];
        }

        Auth::user()->update(array_merge([
            'accepts_pix' => $aceitaPix,
            'accepts_card' => $aceitaCartao,
        ], $chamada));
```

- [ ] **Step 4: Lint e commit**

`php -l app/Http/Controllers/SubscriptionPlanController.php`

```bash
git add resources/views/subscription-plans/index.blade.php app/Http/Controllers/SubscriptionPlanController.php
git commit -m "feat(chamada): criadora liga a chamada de video com preco e duracao na pagina de planos"
```

---

### Task 8: Chat, lado do fã e da criadora

**Files:**
- Modify: `app/Http/Controllers/ChatController.php` (`show`, linhas 136–167)
- Modify: `resources/views/chat/show.blade.php` (cabeçalho ~326–362, loop ~388–420, JS `addMessageToDOM` ~526–590, fim do script)
- Modify: `resources/views/chat/index.blade.php` (linha 286)

- [ ] **Step 1: ChatController::show**

```php
        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('chat.show', compact('conversation', 'otherParticipant', 'messages'));
```
→
```php
        $messages = $conversation->messages()
            ->with(['user', 'videoCall'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Chamada de vídeo (spec 24/09): botão de pedir só pro fã, com a criadora oferecendo e sem pedido aberto
        $podePedirChamada = \App\Http\Controllers\VideoCallController::porQueNaoPodePedir($conversation, $user) === null;
        $criadoraDaConversa = $conversation->creator;

        return view('chat.show', compact('conversation', 'otherParticipant', 'messages', 'podePedirChamada', 'criadoraDaConversa'));
```

- [ ] **Step 2: Cabeçalho, botão de pedir**

Trocar
```blade
                    <p class="text-sm text-green-600">• Está online</p>
                </div>
```
por
```blade
                    <p class="text-sm text-green-600">• Está online</p>
                </div>
                @if($podePedirChamada)
                    <button type="button" onclick="abrirChamadaModal()"
                            class="px-3 py-2 rounded-full text-sm font-semibold text-white bg-pink-500 hover:bg-pink-600 transition-colors"
                            title="Chamada de vídeo de {{ $criadoraDaConversa->video_call_minutes }} min">
                        📹 R$ {{ number_format($criadoraDaConversa->video_call_price, 2, ',', '.') }}
                    </button>
                @endif
```

Logo antes de `<script src="/js/app.js">` ou, se não houver, antes do primeiro `<script>` do fim da página, inserir o modal:

```blade
    @if($podePedirChamada)
    <div id="chamadaModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:60;align-items:center;justify-content:center" onclick="if(event.target===this)fecharChamadaModal()">
        <div style="background:#fff;border-radius:16px;padding:24px;max-width:380px;width:92%">
            <h3 class="font-semibold text-lg mb-2">Chamada de vídeo com {{ strtolower($otherParticipant->name) }}</h3>
            <p class="text-sm text-gray-600 mb-3">
                {{ $criadoraDaConversa->video_call_minutes }} minutos por
                <strong>R$ {{ number_format($criadoraDaConversa->video_call_price, 2, ',', '.') }}</strong>, pagos com o seu saldo.
            </p>
            <label class="text-sm text-gray-700">Sugerir um horário (opcional)</label>
            <input type="datetime-local" id="chamadaSugestao" class="w-full border rounded-lg px-3 py-2 mb-3">
            <p class="text-xs text-gray-500 mb-4">
                O valor fica reservado e só vai pra ela quando a chamada acontecer. Se ela recusar ou não
                aparecer, volta pra sua carteira.
            </p>
            <div class="flex gap-2">
                <button type="button" onclick="fecharChamadaModal()" class="flex-1 px-4 py-2 rounded-lg border">Cancelar</button>
                <button type="button" id="chamadaPagar" onclick="pedirChamada()" class="flex-1 px-4 py-2 rounded-lg bg-pink-500 text-white font-semibold">Pagar com saldo</button>
            </div>
            <p id="chamadaErro" class="text-sm text-red-600 mt-2" style="display:none"></p>
        </div>
    </div>
    @endif
```

- [ ] **Step 3: Loop do Blade, card da chamada**

Trocar
```blade
                        <div class="message-content">
                            @if($message->content)
                                <p>{{ $message->content }}</p>
                            @endif
```
por
```blade
                        <div class="message-content">
                            @if($message->message_type === 'video_call' && $message->videoCall)
                                {{-- O JS desenha a partir do payload, o mesmo do polling: um renderizador só --}}
                                <div class="chamada-card" data-chamada-id="{{ $message->videoCall->id }}" data-message-id="{{ $message->id }}"
                                     data-payload='@json($message->toChatPayload(Auth::user()))'></div>
                            @elseif($message->content)
                                <p>{{ $message->content }}</p>
                            @endif
```

- [ ] **Step 4: JS, renderizador e ações**

Em `addMessageToDOM`, trocar
```js
            if (message.content) {
                contentHtml += `<p>${escapeHtml(message.content)}</p>`;
            }
```
por
```js
            if (message.message_type === 'video_call' && message.video_call) {
                // Card novo desta chamada: os anteriores perdem os botões (só o último age)
                document.querySelectorAll(`.chamada-card[data-chamada-id="${message.video_call.id}"] .chamada-acoes`).forEach(e => e.remove());
                contentHtml += `<div class="chamada-card" data-chamada-id="${message.video_call.id}" data-message-id="${message.id}">${chamadaCardHtml(message)}</div>`;
            } else if (message.content) {
                contentHtml += `<p>${escapeHtml(message.content)}</p>`;
            }
```

Antes de `function addMessageToDOM(message) {` inserir:

```js
        // ---- Chamada de vídeo (spec 24/09) ----
        function chamadaCardHtml(message) {
            const c = message.video_call;
            const atual = c.last_message_id === message.id;
            let html = `<p>${escapeHtml(message.content || '')}</p>`;
            html += `<p class="message-time">${escapeHtml(c.status_label)}</p>`;
            if (!atual) return html;

            let acoes = '';
            if (c.pode_marcar) {
                const valor = c.scheduled_local || c.suggested_local || '';
                acoes += `<input type="datetime-local" id="chamadaHorario-${c.id}" value="${valor}" class="border rounded-lg px-2 py-1 text-sm w-full mb-2">
                    <button type="button" class="paid-lock-button" onclick="marcarChamada(${c.id}, this)">${c.status === 'scheduled' ? 'Remarcar' : 'Marcar'}</button>
                    <button type="button" class="paid-lock-button" style="background:#e5e7eb;color:#111" onclick="recusarChamada(${c.id}, this)">Recusar</button>`;
            }
            if (c.pode_entrar) {
                // Primeira entrega: sem sala ainda. A segunda troca este botão pelo link da chamada.
                acoes += `<button type="button" class="paid-lock-button" disabled title="Em breve">Entrar na chamada (em breve)</button>`;
            }
            if (acoes) html += `<div class="chamada-acoes mt-2">${acoes}<div class="paid-lock-erro" style="display:none"></div></div>`;
            return html;
        }

        function chamadaPost(url, body, botao, erroEl) {
            if (botao) botao.disabled = true;
            if (erroEl) erroEl.style.display = 'none';
            return fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (data.recarregar && data.redirect) { window.location.href = data.redirect; return null; }
                if (!ok || !data.success) throw new Error(data.message || 'Não deu certo. Tente de novo.');
                if (data.chat_message) {
                    addMessageToDOM(data.chat_message);
                    lastMessageId = Math.max(lastMessageId, data.chat_message.id);
                    scrollToBottom();
                }
                return data;
            })
            .catch(e => {
                if (erroEl) { erroEl.textContent = e.message; erroEl.style.display = 'block'; } else { alert(e.message); }
                if (botao) botao.disabled = false;
                return null;
            });
        }

        function abrirChamadaModal() { const m = document.getElementById('chamadaModal'); if (m) m.style.display = 'flex'; }
        function fecharChamadaModal() { const m = document.getElementById('chamadaModal'); if (m) m.style.display = 'none'; }

        function pedirChamada() {
            const botao = document.getElementById('chamadaPagar');
            const erro = document.getElementById('chamadaErro');
            chamadaPost(`/chat/${conversationId}/chamada`, { suggested_at: document.getElementById('chamadaSugestao').value || null }, botao, erro)
                .then(data => { if (data) { fecharChamadaModal(); const b = document.querySelector('[onclick="abrirChamadaModal()"]'); if (b) b.remove(); } });
        }

        function marcarChamada(id, botao) {
            const card = botao.closest('.chamada-card');
            chamadaPost(`/chat/chamada/${id}/marcar`, { scheduled_at: document.getElementById('chamadaHorario-' + id).value || null }, botao, card.querySelector('.paid-lock-erro'));
        }

        function recusarChamada(id, botao) {
            if (!confirm('Recusar a chamada? O valor volta pra carteira do fã agora.')) return;
            const card = botao.closest('.chamada-card');
            chamadaPost(`/chat/chamada/${id}/recusar`, {}, botao, card.querySelector('.paid-lock-erro'));
        }

        // Cards que vieram do servidor: desenha com o mesmo renderizador do polling
        document.querySelectorAll('.chamada-card[data-payload]').forEach(el => {
            const message = JSON.parse(el.getAttribute('data-payload'));
            el.innerHTML = chamadaCardHtml(message);
            el.removeAttribute('data-payload');
        });
```

Conferir que `csrfToken`, `conversationId`, `lastMessageId`, `scrollToBottom` e `escapeHtml` já existem no script (linhas 467–473 e 600+). O bloco dos cards do servidor precisa rodar depois de `messagesContainer` existir: se o script está no fim do body, está ok.

- [ ] **Step 5: Lista de conversas**

Em `resources/views/chat/index.blade.php`, trocar a linha 286
```blade
                                        @if($conversation['last_message']['message_type'] === 'image')
```
por
```blade
                                        @if($conversation['last_message']['message_type'] === 'video_call')
                                            📹 Chamada de vídeo
                                        @elseif($conversation['last_message']['message_type'] === 'image')
```
(conferir o fechamento `@endif` existente logo abaixo; nada mais muda).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/ChatController.php resources/views/chat/show.blade.php resources/views/chat/index.blade.php
git commit -m "feat(chamada): botao de pedir no chat, card da chamada com marcar/recusar, um renderizador so pro blade e pro polling"
```

---

### Task 9: Robô do cron

**Files:**
- Create: `app/Console/Commands/RodarChamadas.php`
- Modify: `routes/console.php`

- [ ] **Step 1: Comando**

```php
<?php

namespace App\Console\Commands;

use App\Http\Controllers\VideoCallController;
use App\Models\VideoCall;
use Illuminate\Console\Command;

/**
 * Devoluções automáticas da chamada de vídeo (spec 24/09): criadora que não entrou
 * (no_show) e pedido velho (expired). Roda a cada minuto pelo schedule:run do crontab.
 * Idempotente: devolver() reconfere o estado com lock.
 */
class RodarChamadas extends Command
{
    protected $signature = 'chamadas:rodar';
    protected $description = 'Devolve ao fã as chamadas de vídeo que a criadora não fez ou que passaram do prazo';

    public function handle(): int
    {
        $n = 0;
        VideoCall::whereIn('status', VideoCall::ABERTAS)->orderBy('id')->chunkById(100, function ($chamadas) use (&$n) {
            foreach ($chamadas as $chamada) {
                $motivo = $chamada->motivoDevolucao();
                if ($motivo && VideoCallController::devolver($chamada, $motivo)) {
                    $n++;
                    $this->line("#{$chamada->id} devolvida ({$motivo})");
                }
            }
        });
        $this->info("{$n} devolvida(s)");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Agendar**

`routes/console.php` vira:

```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// O crontab do servidor já roda `php artisan schedule:run` a cada minuto (conferido 24/09/2026).
Schedule::command('chamadas:rodar')->everyMinute()->withoutOverlapping();
```

- [ ] **Step 3: Lint e commit**

`php -l app/Console/Commands/RodarChamadas.php; php -l routes/console.php`

```bash
git add app/Console/Commands/RodarChamadas.php routes/console.php
git commit -m "feat(chamada): comando chamadas:rodar no cron devolve no_show e expired"
```

---

### Task 10: Admin: interruptor, tolerância e lista de chamadas

**Files:**
- Modify: `app/Http/Controllers/Admin/PlatformSettingController.php` (CRLF; linhas 44, 70, 110)
- Modify: `resources/views/admin/platform-settings/index.blade.php` (CRLF; depois do bloco `card_enabled`)
- Create: `app/Http/Controllers/Admin/AdminVideoCallController.php`
- Create: `resources/views/admin/chamadas/index.blade.php`
- Modify: `routes/web.php` (grupo admin, antes de `// Posts em Destaque`)
- Modify: `resources/views/components/admin-sidebar.blade.php` (depois do item Saques)

- [ ] **Step 1: PlatformSettingController (Patch)**

```php
            'card_enabled' => PlatformSetting::isCardEnabled(),
```
→
```php
            'card_enabled' => PlatformSetting::isCardEnabled(),
            'video_calls_enabled' => PlatformSetting::isVideoCallsEnabled(),
            'video_call_tolerance_minutes' => PlatformSetting::getVideoCallToleranceMinutes(),
```
```php
            'card_enabled' => 'nullable|boolean',
```
→
```php
            'card_enabled' => 'nullable|boolean',
            'video_calls_enabled' => 'nullable|boolean',
            'video_call_tolerance_minutes' => 'nullable|integer|min:0|max:240',
```
```php
        PlatformSetting::setValue('card_enabled', $request->boolean('card_enabled') ? '1' : '0', 'Cartão de crédito ligado na plataforma (0 = só PIX)');
```
→
```php
        PlatformSetting::setValue('card_enabled', $request->boolean('card_enabled') ? '1' : '0', 'Cartão de crédito ligado na plataforma (0 = só PIX)');
        PlatformSetting::setValue('video_calls_enabled', $request->boolean('video_calls_enabled') ? '1' : '0', 'Chamada de vídeo paga no chat ligada');
        PlatformSetting::setValue('video_call_tolerance_minutes', (string) (int) ($validated['video_call_tolerance_minutes'] ?? 30), 'Minutos de atraso da criadora antes de devolver a chamada ao fã');
```

- [ ] **Step 2: View do admin (Patch)**

Âncora: o fechamento do bloco do cartão
```blade
                                a porcentagem acima e o que cada criadora escolheu continuam guardados.
                            </p>
                        </div>
                        <div class="ml-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    id="card_enabled"
```
Não é boa âncora (longa). Usar o fim do bloco: a primeira ocorrência de
```blade
                                    id="card_enabled"
```
é única; mas o que queremos é inserir DEPOIS do `</div>` que fecha o `mb-6` do cartão. Então: âncora única = a linha do `<div class="bg-blue-50 border border-blue-200 rounded-lg p-4">` que vem logo depois (conferir com `grep -n 'bg-blue-50 border border-blue-200' resources/views/admin/platform-settings/index.blade.php`, deve ser uma só). Inserir ANTES dela:

```blade
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label for="video_calls_enabled" class="block text-sm font-medium text-gray-700 mb-2">
                                Chamada de vídeo paga no chat ligada
                            </label>
                            <p class="text-sm text-gray-500">
                                Ligada, a criadora vê o bloco "Chamada de vídeo" na página de planos e o fã vê o botão
                                no chat. Fica desligada até a sala de vídeo estar no ar.
                            </p>
                        </div>
                        <div class="ml-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="video_calls_enabled" name="video_calls_enabled" value="1" {{ $video_calls_enabled ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="video_call_tolerance_minutes" class="block text-sm font-medium text-gray-700 mb-2">
                        Chamada de vídeo: minutos de atraso da criadora antes de devolver ao fã
                    </label>
                    <input type="number" id="video_call_tolerance_minutes" name="video_call_tolerance_minutes"
                           value="{{ $video_call_tolerance_minutes }}" min="0" max="240" step="1"
                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg">
                    <p class="mt-2 text-sm text-gray-500">
                        Passou o horário marcado mais esse tempo e ela não entrou na sala: o valor volta pra carteira do fã, sozinho.
                    </p>
                </div>

```

- [ ] **Step 3: AdminVideoCallController**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoCall;
use Illuminate\Http\Request;

/** Lista das chamadas de vídeo (spec 24/09). Sem ação na primeira entrega. */
class AdminVideoCallController extends Controller
{
    public function index(Request $request)
    {
        $filtro = $request->input('filter', 'todas');
        $estados = ['requested', 'scheduled', 'done', 'refunded'];

        $query = VideoCall::with(['creator', 'user'])->where('status', '!=', 'awaiting_payment')->orderByDesc('id');
        if (in_array($filtro, $estados, true)) {
            $query->where('status', $filtro);
        }

        $contagens = VideoCall::whereIn('status', $estados)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('admin.chamadas.index', [
            'chamadas'  => $query->paginate(50)->withQueryString(),
            'filtro'    => $filtro,
            'contagens' => $contagens,
        ]);
    }
}
```

- [ ] **Step 4: View `resources/views/admin/chamadas/index.blade.php`**

```blade
@extends('layouts.admin')

@section('title', 'Chamadas de vídeo')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Chamadas de vídeo</h1>
            <p class="text-gray-600 mt-2">Pedidos pagos pelos fãs. O dinheiro fica reservado até a criadora entrar na sala.</p>
        </div>

        <div class="mb-6 bg-white rounded-lg shadow-sm p-4">
            <div class="flex flex-wrap gap-2">
                @foreach(['todas' => 'Todas', 'requested' => 'Aguardando marcar', 'scheduled' => 'Marcadas', 'done' => 'Realizadas', 'refunded' => 'Devolvidas'] as $chave => $rotulo)
                    <a href="{{ route('admin.chamadas.index', ['filter' => $chave]) }}"
                       class="px-4 py-2 rounded-lg {{ $filtro === $chave ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $rotulo }}{{ $chave !== 'todas' ? ' (' . ($contagens[$chave] ?? 0) . ')' : '' }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach(['#', 'Pedido em', 'Criadora', 'Fã', 'Valor', 'Duração', 'Estado', 'Marcada pra', 'Ela entrou', 'Devolução'] as $th)
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $th }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($chamadas as $c)
                            @php($sp = fn ($d) => $d ? $d->copy()->setTimezone('America/Sao_Paulo')->format('d/m H:i') : '-')
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $c->id }}</td>
                                <td class="px-6 py-4 text-sm">{{ $sp($c->created_at) }}</td>
                                <td class="px-6 py-4 text-sm">@{{ $c->creator->username ?? $c->creator_id }}</td>
                                <td class="px-6 py-4 text-sm">{{ $c->user->name ?? $c->user_id }} (#{{ $c->user_id }})</td>
                                <td class="px-6 py-4 text-sm">R$ {{ number_format($c->amount_paid, 2, ',', '.') }}<br><span class="text-xs text-gray-500">criadora R$ {{ number_format($c->creator_amount, 2, ',', '.') }}</span></td>
                                <td class="px-6 py-4 text-sm">{{ $c->duration_minutes }} min</td>
                                <td class="px-6 py-4 text-sm">{{ $c->statusLabel() }}</td>
                                <td class="px-6 py-4 text-sm">{{ $sp($c->scheduled_at) }}</td>
                                <td class="px-6 py-4 text-sm">{{ $sp($c->creator_joined_at) }}</td>
                                <td class="px-6 py-4 text-sm">{{ $c->refund_reason ? $c->refund_reason . ' em ' . $sp($c->refunded_at) : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-6 py-8 text-center text-gray-500">Nenhuma chamada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $chamadas->links() }}</div>
        </div>
    </div>
@endsection
```

- [ ] **Step 5: Rota e menu**

`routes/web.php`, antes de `        // Posts em Destaque`:
```php
        // Chamadas de vídeo (spec 24/09)
        Route::get('/chamadas', [\App\Http\Controllers\Admin\AdminVideoCallController::class, 'index'])->name('chamadas.index');

```

`admin-sidebar.blade.php`, depois do `</li>` do item Saques (linha 152):
```blade
            <li>
                <a href="{{ route('admin.chamadas.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg hover:bg-gray-900 transition-colors {{ request()->routeIs('admin.chamadas.*') ? 'bg-gray-900' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span class="font-medium">Chamadas</span>
                </a>
            </li>
```

- [ ] **Step 6: Lint e commit**

`php -l app/Http/Controllers/Admin/PlatformSettingController.php; php -l app/Http/Controllers/Admin/AdminVideoCallController.php`

```bash
git add app/Http/Controllers/Admin resources/views/admin routes/web.php resources/views/components/admin-sidebar.blade.php
git commit -m "feat(chamada): admin liga a chamada de video, define a tolerancia e ve a lista de chamadas"
```

---

### Task 11: Scripts de teste em prod (transação + rollback)

**Files:**
- Create: `tests/prod/chamada/t1_pedido.php`
- Create: `tests/prod/chamada/t2_robo.php`
- Create: `tests/prod/chamada/t3_telas.php`

Rodam DEPOIS do deploy e da migration (tarefa 12), com `ssh root@209.126.103.238 "cd /home/pierfans/web/pierfans.com/public_html && php artisan tinker" < tests/prod/chamada/t1_pedido.php`. Sem `use` (o tinker já importa). Interruptor global ligado só dentro da transação.

- [ ] **Step 1: t1_pedido.php**

```php
// Chamada de vídeo, primeira entrega: pedido, pagamento, marcar, remarcar, recusar. Tudo com rollback.
DB::beginTransaction();
try {
    \App\Models\PlatformSetting::setValue('video_calls_enabled', '1', 'teste');
    $criadora = \App\Models\User::where('creator_status', 'approved')->whereHas('subscriptionPlans')->orderByDesc('id')->first();
    $criadora->update(['video_call_enabled' => 1, 'video_call_price' => 100, 'video_call_minutes' => 15]);
    $fa = \App\Models\User::withoutGlobalScopes()->where('email', 'like', 'teste-onboarding-%')->orderByDesc('id')->first();
    $fa->getOrCreateWallet()->addBalance(250, null, 'saldo de teste');
    $conversa = \App\Models\Conversation::firstOrCreate(['creator_id' => $criadora->id, 'subscriber_id' => $fa->id]);
    echo "criadora {$criadora->id} @{$criadora->username} | fa {$fa->id} | conversa {$conversa->id} | saldo fa ", $fa->fresh()->getOrCreateWallet()->balance, "\n";

    $ctrl = app(\App\Http\Controllers\VideoCallController::class);
    $req = fn (array $dados) => tap(\Illuminate\Http\Request::create('/x', 'POST', $dados), fn ($r) => $r->headers->set('Accept', 'application/json'));

    // 1. criadora tentando pedir: recusa
    Auth::login($criadora);
    echo "criadora pede -> ", $ctrl->pedir($req([]), $conversa->id)->getStatusCode(), " (esperado 400)\n";

    // 2. fa pede com sugestao sabado 21h Brasilia
    Auth::login($fa);
    $r = $ctrl->pedir($req(['suggested_at' => '2026-09-27T21:00']), $conversa->id);
    $d = json_decode($r->getContent(), true);
    echo "fa pede -> ", $r->getStatusCode(), " ", $d['message'], "\n";
    $chamada = \App\Models\VideoCall::orderByDesc('id')->first();
    echo "  status={$chamada->status} amount={$chamada->amount_paid} criadora={$chamada->creator_amount} plataforma={$chamada->platform_amount} afiliado={$chamada->affiliate_amount} soma=", round($chamada->creator_amount + $chamada->platform_amount + $chamada->affiliate_amount, 2), "\n";
    echo "  sugestao UTC={$chamada->suggested_at} (esperado 2026-09-28 00:00:00) | saldo fa ", $fa->fresh()->getOrCreateWallet()->balance, " (esperado 150)\n";
    echo "  mensagem: ", $chamada->message->content, " | tipo ", $chamada->message->message_type, "\n";

    // 3. segundo pedido com um aberto: recusa
    echo "segundo pedido -> ", $ctrl->pedir($req([]), $conversa->id)->getStatusCode(), " (esperado 400)\n";

    // 4. pagar de novo a mesma chamada: idempotente
    \App\Http\Controllers\VideoCallController::pagar($chamada);
    echo "pagar 2x -> saldo fa ", $fa->fresh()->getOrCreateWallet()->balance, " (esperado 150)\n";

    // 5. saldo da criadora: reservado, nao liberado nem a liberar
    echo "reservado=", \App\Models\VideoCall::reservado($criadora->id), " liberado(chamadas)=", \App\Models\VideoCall::creatorAmount($criadora->id, true), " a liberar(chamadas)=", \App\Models\VideoCall::creatorAmount($criadora->id, false), "\n";

    // 6. criadora marca (vazio = agora), remarca
    Auth::login($criadora);
    $r = $ctrl->marcar($req([]), $chamada->fresh());
    echo "marcar agora -> ", $r->getStatusCode(), " status=", $chamada->fresh()->status, " scheduled_at=", $chamada->fresh()->scheduled_at, "\n";
    $r = $ctrl->marcar($req(['scheduled_at' => '2026-09-27T21:00']), $chamada->fresh());
    echo "remarcar -> ", $r->getStatusCode(), " scheduled_at=", $chamada->fresh()->scheduled_at, " (esperado 2026-09-28 00:00:00) | payload: ", json_encode(json_decode($r->getContent(), true)['chat_message']['video_call']['status_label']), "\n";
    echo "horario passado -> ", $ctrl->marcar($req(['scheduled_at' => '2020-01-01T10:00']), $chamada->fresh())->getStatusCode(), " (esperado 400)\n";

    // 7. recusa: devolve exato, 2a recusa nao dobra
    $r = $ctrl->recusar($chamada->fresh());
    echo "recusar -> ", $r->getStatusCode(), " status=", $chamada->fresh()->status, "/", $chamada->fresh()->refund_reason, " saldo fa ", $fa->fresh()->getOrCreateWallet()->balance, " (esperado 250)\n";
    echo "recusar 2x -> ", $ctrl->recusar($chamada->fresh())->getStatusCode(), " (esperado 400) saldo fa ", $fa->fresh()->getOrCreateWallet()->balance, " (esperado 250)\n";

    // 8. sem saldo: recarga com o id
    $fa->fresh()->getOrCreateWallet()->subtractBalance(200, 'zera');
    Auth::login($fa);
    $r = $ctrl->pedir($req([]), $conversa->id);
    $d = json_decode($r->getContent(), true);
    echo "sem saldo -> ", $r->getStatusCode(), " recarregar=", var_export($d['recarregar'] ?? null, true), " redirect=", $d['redirect'] ?? '-', "\n";
    $aguardando = \App\Models\VideoCall::orderByDesc('id')->first();
    echo "  status={$aguardando->status} (esperado awaiting_payment) | abertas na conversa: ", \App\Models\VideoCall::where('conversation_id', $conversa->id)->whereIn('status', \App\Models\VideoCall::ABERTAS)->count(), " (esperado 0)\n";
    // recarga cai (simula o webhook): PaymentTransaction com video_call_id + credito + concluir
    $fa->fresh()->getOrCreateWallet()->addBalance(100, null, 'recarga simulada');
    $tx = \App\Models\PaymentTransaction::create(['user_id' => $fa->id, 'video_call_id' => $aguardando->id, 'request_number' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'pix', 'status' => 'paid', 'amount' => 100]);
    \App\Http\Controllers\VideoCallController::concluirAposRecarga($tx);
    echo "  apos recarga: status=", $aguardando->fresh()->status, " (esperado requested) saldo fa ", $fa->fresh()->getOrCreateWallet()->balance, " (esperado 50)\n";
} catch (\Throwable $e) {
    echo "EXCECAO ", get_class($e), ": ", $e->getMessage(), " @ ", $e->getFile(), ":", $e->getLine(), "\n";
} finally {
    DB::rollBack();
    echo "rollback feito; video_calls_enabled agora: ", var_export(\App\Models\PlatformSetting::getValue('video_calls_enabled'), true), "\n";
}
```

- [ ] **Step 2: t2_robo.php**

```php
// Robô: no_show, expired, idempotência, done nunca devolve. Com rollback.
DB::beginTransaction();
try {
    $criadora = \App\Models\User::where('creator_status', 'approved')->orderByDesc('id')->first();
    $fa = \App\Models\User::withoutGlobalScopes()->where('email', 'like', 'teste-onboarding-%')->orderByDesc('id')->first();
    $conversa = \App\Models\Conversation::firstOrCreate(['creator_id' => $criadora->id, 'subscriber_id' => $fa->id]);
    $wallet = $fa->getOrCreateWallet();
    $base = (float) $wallet->balance;

    $nova = function (array $extra) use ($conversa, $criadora, $fa) {
        return \App\Models\VideoCall::create(array_merge([
            'conversation_id' => $conversa->id, 'creator_id' => $criadora->id, 'user_id' => $fa->id,
            'price' => 100, 'duration_minutes' => 15, 'amount_paid' => 100, 'platform_percentage' => 20,
            'platform_amount' => 15, 'affiliate_amount' => 5, 'creator_amount' => 80,
        ], $extra));
    };
    // created_at manual: o create ignora, então força depois
    $set = fn ($c, $created) => \DB::table('video_calls')->where('id', $c->id)->update(['created_at' => $created]);

    $a = $nova(['status' => 'scheduled', 'scheduled_at' => now()->subMinutes(31)]);          // no_show
    $b = $nova(['status' => 'scheduled', 'scheduled_at' => now()->subMinutes(29)]);          // ainda não
    $c = $nova(['status' => 'requested']); $set($c, now()->subDays(8));                       // expired
    $d = $nova(['status' => 'requested']); $set($d, now()->subDays(6));                       // ainda não
    $e = $nova(['status' => 'done', 'scheduled_at' => now()->subDays(40), 'creator_joined_at' => now()->subDays(40)]); $set($e, now()->subDays(41)); // nunca
    $f = $nova(['status' => 'scheduled', 'scheduled_at' => now()->addDays(5)]); $set($f, now()->subDays(31)); // teto 30 dias

    \Illuminate\Support\Facades\Artisan::call('chamadas:rodar');
    echo \Illuminate\Support\Facades\Artisan::output();
    foreach (['a' => [$a, 'refunded/no_show'], 'b' => [$b, 'scheduled/'], 'c' => [$c, 'refunded/expired'], 'd' => [$d, 'requested/'], 'e' => [$e, 'done/'], 'f' => [$f, 'refunded/expired']] as $k => [$ch, $esp]) {
        $ch = $ch->fresh();
        echo "$k: {$ch->status}/{$ch->refund_reason} (esperado $esp)\n";
    }
    echo "saldo fa: ", $fa->fresh()->getOrCreateWallet()->balance - $base, " a mais (esperado 300)\n";

    \Illuminate\Support\Facades\Artisan::call('chamadas:rodar');
    echo "2a rodada: ", trim(\Illuminate\Support\Facades\Artisan::output()), " | saldo fa: ", $fa->fresh()->getOrCreateWallet()->balance - $base, " a mais (esperado 300)\n";

    // saldo da criadora: done de 40 dias atrás está liberado; done de hoje está a liberar
    $g = $nova(['status' => 'done', 'scheduled_at' => now(), 'creator_joined_at' => now()]);
    echo "criadora liberado(chamadas)=", \App\Models\VideoCall::creatorAmount($criadora->id, true), " (esperado 80) a liberar=", \App\Models\VideoCall::creatorAmount($criadora->id, false), " (esperado 80) reservado=", \App\Models\VideoCall::reservado($criadora->id), " (esperado 160: b e d)\n";
    echo "getAvailableBalance inclui? ", $criadora->getAvailableBalance() >= 80 ? 'sim' : 'NAO', " | getPendingBalance >= 80? ", $criadora->getPendingBalance() >= 80 ? 'sim' : 'NAO', "\n";
} catch (\Throwable $e) {
    echo "EXCECAO ", get_class($e), ": ", $e->getMessage(), " @ ", $e->getFile(), ":", $e->getLine(), "\n";
} finally {
    DB::rollBack();
    echo "rollback feito\n";
}
```

- [ ] **Step 3: t3_telas.php**

```php
// Render em prod das telas: planos (criadora), conversa (dois lados), admin. Com rollback.
view()->share('errors', new \Illuminate\Support\ViewErrorBag);
DB::beginTransaction();
try {
    \App\Models\PlatformSetting::setValue('video_calls_enabled', '1', 'teste');
    $criadora = \App\Models\User::where('creator_status', 'approved')->whereHas('subscriptionPlans')->orderByDesc('id')->first();
    $criadora->update(['video_call_enabled' => 1, 'video_call_price' => 100, 'video_call_minutes' => 15]);
    $fa = \App\Models\User::withoutGlobalScopes()->where('email', 'like', 'teste-onboarding-%')->orderByDesc('id')->first();
    $fa->getOrCreateWallet()->addBalance(250, null, 'saldo de teste');
    $conversa = \App\Models\Conversation::firstOrCreate(['creator_id' => $criadora->id, 'subscriber_id' => $fa->id]);
    $req = fn (array $d) => tap(\Illuminate\Http\Request::create('/x', 'POST', $d), fn ($r) => $r->headers->set('Accept', 'application/json'));
    $ctrl = app(\App\Http\Controllers\VideoCallController::class);

    Auth::login($criadora);
    $html = app(\App\Http\Controllers\SubscriptionPlanController::class)->index()->render();
    echo "planos: ", strlen($html), " bytes | video_call_price: ", substr_count($html, 'id="video_call_price"'), " | 'você recebe': ", substr_count($html, 'de cada chamada'), "\n";

    Auth::login($fa);
    $html = app(\App\Http\Controllers\ChatController::class)->show($conversa->id)->render();
    echo "chat fa (antes): ", strlen($html), " bytes | botao pedir: ", substr_count($html, 'abrirChamadaModal()'), " | modal: ", substr_count($html, 'id="chamadaModal"'), "\n";
    $ctrl->pedir($req(['suggested_at' => '2026-09-27T21:00']), $conversa->id);
    $html = app(\App\Http\Controllers\ChatController::class)->show($conversa->id)->render();
    echo "chat fa (depois): botao pedir: ", substr_count($html, 'abrirChamadaModal()'), " (esperado 0) | card: ", substr_count($html, 'class="chamada-card"'), " | payload com pode_marcar false: ", substr_count($html, '&quot;pode_marcar&quot;:false'), "\n";

    Auth::login($criadora);
    $html = app(\App\Http\Controllers\ChatController::class)->show($conversa->id)->render();
    echo "chat criadora: card: ", substr_count($html, 'class="chamada-card"'), " | pode_marcar true: ", substr_count($html, '&quot;pode_marcar&quot;:true'), "\n";

    $lista = app(\App\Http\Controllers\ChatController::class)->index()->render();
    echo "lista de conversas: 'Chamada de vídeo' aparece ", substr_count($lista, 'Chamada de vídeo'), "x\n";

    $admin = \App\Models\User::where('is_admin', 1)->first();
    Auth::login($admin);
    $html = app(\App\Http\Controllers\Admin\PlatformSettingController::class)->index()->render();
    echo "admin config: video_calls_enabled: ", substr_count($html, 'name="video_calls_enabled"'), " | tolerancia: ", substr_count($html, 'name="video_call_tolerance_minutes"'), "\n";
    $html = app(\App\Http\Controllers\Admin\AdminVideoCallController::class)->index(\Illuminate\Http\Request::create('/admin/chamadas', 'GET'))->render();
    echo "admin lista: ", strlen($html), " bytes | linhas com 'Aguardando a criadora marcar': ", substr_count($html, 'Aguardando a criadora marcar'), "\n";

    $html = app(\App\Http\Controllers\WithdrawController::class)->index()->render();
    echo "saque criadora: 'Reservado em chamadas': ", substr_count($html, 'Reservado em chamadas'), "\n";
} catch (\Throwable $e) {
    echo "EXCECAO ", get_class($e), ": ", $e->getMessage(), " @ ", $e->getFile(), ":", $e->getLine(), "\n";
} finally {
    DB::rollBack();
    echo "rollback feito; video_calls_enabled agora: ", var_export(\App\Models\PlatformSetting::getValue('video_calls_enabled'), true), "\n";
}
```
(Se `WithdrawController::index` exigir `Request`, passar `\Illuminate\Http\Request::create('/withdraw', 'GET')`.)

- [ ] **Step 4: Commit**

```bash
git add tests/prod/chamada
git commit -m "test(chamada): scripts de teste em prod com rollback (pedido, robo, telas)"
```

---

### Task 12: Deploy, migration e teste em prod

**Pré-condição:** autorização do Pedro pro deploy e pra migration (sempre perguntar; memória `deploy_permissao`).

- [ ] **Step 1: Deploy**

```bash
export PATH="/c/Program Files/Git/cmd:/c/Users/PC/.config/herd-lite/bin:/c/Program Files/Git/usr/bin:$PATH"
bash deploy.sh "feat(chamada): chamada de video paga no chat, primeira entrega (pedido, custodia, marcar/recusar, devolucao automatica, admin), atras do interruptor video_calls_enabled desligado (spec 24/09)"
```
Conferir: `ssh root@209.126.103.238 "cd /home/pierfans/web/pierfans.com/public_html && git rev-parse --short HEAD"` igual ao local.

- [ ] **Step 2: Migration**

```bash
ssh root@209.126.103.238 "cd /home/pierfans/web/pierfans.com/public_html && php artisan migrate --force"
```
Esperado: `2026_09_25_000001_create_video_calls_table ... DONE`.

- [ ] **Step 3: Rodar os três scripts** e conferir cada "esperado". Qualquer divergência: corrigir, commitar, deployar de novo, rodar de novo.

- [ ] **Step 4: Conferir o cron pegou o comando**

```bash
ssh root@209.126.103.238 "cd /home/pierfans/web/pierfans.com/public_html && php artisan schedule:list"
```
Esperado: linha `* * * * *  php artisan chamadas:rodar`. E 2 minutos depois: `grep -c 'chamadas:rodar' storage/logs/laravel*.log` não precisa ter nada (o comando só loga devolução).

- [ ] **Step 5: Estado final que fica em prod**

`video_calls_enabled` = ausente/0: nenhuma criadora vê o bloco, nenhum fã vê o botão, o robô roda a cada minuto sem achar nada. Registrar na planilha `PierFans_Controle_Dev` e no HANDOFF.

---

## Self-review (feito ao escrever)

- **Cobertura do spec:** seção 1 regras → tarefas 1, 4, 9; seção 2 dados → 2, 3; seção 3 fluxo → 4, 5, 7, 8; seção 5 admin → 10; seção 6 teste → 11, 12; "reservado" na tela → 6; lista de conversas → 8. Fluxo de caixa e vendas do admin: hoje não somam nem a mensagem do chat (conferido em 24/09, `MessagePurchase` só aparece nos saldos do User), então a chamada segue o mesmo: fica de fora, anotado como pendência pra quando o chat entrar nesses relatórios.
- **Nomes:** `VideoCallController::pagar/devolver/concluirAposRecarga/porQueNaoPodePedir`, `VideoCall::ABERTAS/creatorAmount/affiliateAmount/reservado/toPayload/rotuloHorario/statusLabel/janelaAberta/motivoDevolucao`, `VideoCallRules::motivoDevolucao/janelaAberta`, `PlatformSetting::isVideoCallsEnabled/getVideoCallToleranceMinutes` usados com os mesmos nomes em todas as tarefas.
- **Sem placeholder.** Os pontos "conferir com grep" são âncoras de arquivos que o executor precisa validar no momento do patch, com o comando dado.
