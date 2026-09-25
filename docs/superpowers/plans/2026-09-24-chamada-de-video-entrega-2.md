# Chamada de vídeo paga, segunda entrega (LiveKit): plano de implementação

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** o botão "Entrar na chamada" passa a abrir uma sala de vídeo de verdade: a criadora entrando marca `done` (ela entrou = aconteceu), o cronômetro é contado no servidor, o robô fecha a sala no fim da duração e o webhook do LiveKit grava a presença do fã e o fim da sala. Depois disso o Bento pode ligar `video_calls_enabled`.

**Architecture:** `LiveKitService` assina o JWT (HS256, sem pacote), chama a API Twirp (`DeleteRoom`) e confere o webhook. `VideoCallController::entrar` é a única porta pra sala: confere quem é, o estado e a janela, marca `done` se for a criadora, e devolve a página da chamada com token e o fim contado no servidor. A página usa `livekit-client` por CDN. O robô de cada minuto ganha um segundo trabalho: derrubar salas vencidas.

**Tech Stack:** Laravel 12, LiveKit Cloud (projeto `pierfans`, credenciais já no `.env` do servidor e testadas em 24/09 com `ListRooms` → 200), `livekit-client` 2.x UMD via jsDelivr.

**Spec:** `docs/superpowers/specs/2026-09-24-chamada-de-video-design.md`, seção 4. **Primeira entrega:** plano `2026-09-24-chamada-de-video-entrega-1.md`, em prod (`1446d29`).

**Convenções:** as mesmas do plano 1 (função `Patch` do PowerShell pra arquivos CRLF, `php -l`, um commit por tarefa, deploy só com autorização, teste em prod em transação com rollback via tinker).

---

### Task 1: Migration das colunas da sala

**Files:**
- Create: `database/migrations/2026_09_25_000002_add_room_columns_to_video_calls_table.php`

- [ ] **Step 1: Escrever**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
    }

    public function down(): void
    {
        Schema::table('video_calls', function (Blueprint $t) {
            $t->dropColumn(['room_name', 'user_joined_at', 'ended_at']);
        });
    }
};
```

- [ ] **Step 2: Lint e commit**

`php -l database/migrations/2026_09_25_000002_add_room_columns_to_video_calls_table.php`

```bash
git add database/migrations/2026_09_25_000002_add_room_columns_to_video_calls_table.php
git commit -m "feat(chamada): colunas da sala (room_name, user_joined_at, ended_at)"
```

---

### Task 2: LiveKitService (token, DeleteRoom, webhook) + config

**Files:**
- Create: `app/Services/LiveKitService.php`
- Modify: `config/services.php` (depois do bloco `'didit'`)
- Create: `tests/livekit_jwt_test.php` (php puro: assina e confere um token contra um segredo de teste)

- [ ] **Step 1: config/services.php**

Depois de
```php
        'base_url' => env('DIDIT_BASE_URL', 'https://verification.didit.me'),
    ],
```
inserir
```php

    // Chamada de vídeo (spec 24/09). Projeto 'pierfans' no LiveKit Cloud, plano grátis.
    'livekit' => [
        'url' => env('LIVEKIT_URL'),
        'api_key' => env('LIVEKIT_API_KEY'),
        'api_secret' => env('LIVEKIT_API_SECRET'),
    ],
```

- [ ] **Step 2: Teste do JWT em php puro (escrever antes, ver falhar)**

`tests/livekit_jwt_test.php`:
```php
<?php
/**
 * Assinatura e conferência do JWT do LiveKit, sem Laravel:
 *   php -d zend.assertions=1 -d assert.exception=1 tests/livekit_jwt_test.php
 */
require __DIR__ . '/../app/Support/LiveKitJwt.php';

use App\Support\LiveKitJwt;

$jwt = LiveKitJwt::sign('APIkey', 'segredo', ['iss' => 'APIkey', 'sub' => '42', 'video' => ['room' => 'chamada-1', 'roomJoin' => true]], 60);
$partes = explode('.', $jwt);
assert(count($partes) === 3, 'tres partes');
$header = json_decode(LiveKitJwt::b64d($partes[0]), true);
assert($header['alg'] === 'HS256' && $header['typ'] === 'JWT', 'header');
$payload = json_decode(LiveKitJwt::b64d($partes[1]), true);
assert($payload['iss'] === 'APIkey' && $payload['sub'] === '42' && $payload['video']['room'] === 'chamada-1', 'payload');
assert($payload['exp'] - $payload['nbf'] >= 60, 'ttl');

// verify: certo, segredo errado, adulterado, vencido
assert(LiveKitJwt::verify($jwt, 'segredo') !== null, 'verifica ok');
assert(LiveKitJwt::verify($jwt, 'outro') === null, 'segredo errado');
assert(LiveKitJwt::verify($partes[0] . '.' . $partes[1] . 'x.' . $partes[2], 'segredo') === null, 'adulterado');
$vencido = LiveKitJwt::sign('APIkey', 'segredo', ['iss' => 'APIkey'], -120);
assert(LiveKitJwt::verify($vencido, 'segredo') === null, 'vencido');

echo "ok\n";
```

Rodar: `php -d zend.assertions=1 -d assert.exception=1 tests/livekit_jwt_test.php` → falha: arquivo não existe.

- [ ] **Step 3: `app/Support/LiveKitJwt.php` (puro, sem Laravel)**

```php
<?php

namespace App\Support;

/**
 * JWT HS256 do LiveKit, feito à mão: 3 partes base64url, assinatura HMAC-SHA256 com o
 * API secret. É tudo que o LiveKit exige pra token de acesso, pra chamar a API e pra
 * conferir o webhook. Sem pacote de propósito: o deploy não roda composer.
 *   php tests/livekit_jwt_test.php
 */
class LiveKitJwt
{
    public static function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    public static function b64d(string $s): string
    {
        return base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4));
    }

    /** Assina. $claims leva iss/sub/video etc.; nbf e exp entram aqui. */
    public static function sign(string $apiKey, string $secret, array $claims, int $ttlSeconds): string
    {
        $now = time();
        $claims = array_merge(['iss' => $apiKey, 'nbf' => $now - 10, 'exp' => $now + $ttlSeconds], $claims);
        $h = self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $p = self::b64(json_encode($claims));

        return $h . '.' . $p . '.' . self::b64(hash_hmac('sha256', $h . '.' . $p, $secret, true));
    }

    /** Confere assinatura e validade. Devolve o payload ou null. */
    public static function verify(string $jwt, string $secret): ?array
    {
        $partes = explode('.', $jwt);
        if (count($partes) !== 3) {
            return null;
        }
        [$h, $p, $s] = $partes;
        $esperada = self::b64(hash_hmac('sha256', $h . '.' . $p, $secret, true));
        if (! hash_equals($esperada, $s)) {
            return null;
        }
        $payload = json_decode(self::b64d($p), true);
        if (! is_array($payload)) {
            return null;
        }
        if (isset($payload['exp']) && time() > (int) $payload['exp']) {
            return null;
        }
        if (isset($payload['nbf']) && time() < (int) $payload['nbf'] - 60) {
            return null;
        }

        return $payload;
    }
}
```

Rodar o teste → `ok`.

- [ ] **Step 4: `app/Services/LiveKitService.php`**

```php
<?php

namespace App\Services;

use App\Support\LiveKitJwt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fala com o LiveKit Cloud (spec 24/09, seção 4). Token de acesso pra sala, DeleteRoom
 * pelo robô no fim da duração, e conferência do webhook.
 */
class LiveKitService
{
    private string $url;
    private string $key;
    private string $secret;

    public function __construct()
    {
        $this->url = rtrim((string) config('services.livekit.url'), '/');
        $this->key = (string) config('services.livekit.api_key');
        $this->secret = (string) config('services.livekit.api_secret');
    }

    public function configurado(): bool
    {
        return $this->url !== '' && $this->key !== '' && $this->secret !== '';
    }

    public function wsUrl(): string
    {
        return $this->url;
    }

    private function httpUrl(): string
    {
        return preg_replace('/^wss?:/', 'https:', $this->url);
    }

    /** Token pra ENTRAR na sala. identity = id do usuário, name = o que aparece pro outro. */
    public function tokenDeAcesso(string $room, int $identity, string $name, int $ttlSeconds): string
    {
        return LiveKitJwt::sign($this->key, $this->secret, [
            'sub'   => (string) $identity,
            'name'  => $name,
            'video' => ['room' => $room, 'roomJoin' => true, 'canPublish' => true, 'canSubscribe' => true, 'canPublishData' => false],
        ], max(60, $ttlSeconds));
    }

    /** Derruba a sala. true se derrubou ou se ela já não existia. */
    public function deleteRoom(string $room): bool
    {
        $token = LiveKitJwt::sign($this->key, $this->secret, ['video' => ['roomCreate' => true, 'roomAdmin' => true, 'room' => $room]], 60);
        $r = Http::withToken($token)->timeout(10)->post($this->httpUrl() . '/twirp/livekit.RoomService/DeleteRoom', ['room' => $room]);
        if ($r->successful() || $r->status() === 404 || str_contains($r->body(), 'not_found')) {
            return true;
        }
        Log::warning('LIVEKIT - DeleteRoom falhou', ['room' => $room, 'status' => $r->status(), 'body' => substr($r->body(), 0, 200)]);

        return false;
    }

    /**
     * Webhook: o header Authorization é um JWT assinado com o mesmo secret, com o sha256
     * (base64) do corpo cru na claim 'sha256'. Devolve o evento decodificado ou null.
     */
    public function eventoDoWebhook(string $rawBody, ?string $authorization): ?array
    {
        $jwt = trim((string) preg_replace('/^Bearer\s+/i', '', (string) $authorization));
        $claims = $jwt !== '' ? LiveKitJwt::verify($jwt, $this->secret) : null;
        if (! $claims || ($claims['iss'] ?? null) !== $this->key) {
            return null;
        }
        if (! hash_equals(base64_encode(hash('sha256', $rawBody, true)), (string) ($claims['sha256'] ?? ''))) {
            return null;
        }
        $evento = json_decode($rawBody, true);

        return is_array($evento) ? $evento : null;
    }
}
```

- [ ] **Step 5: Lint e commit**

```
php -l app/Support/LiveKitJwt.php; php -l app/Services/LiveKitService.php; php -l config/services.php
git add app/Support/LiveKitJwt.php app/Services/LiveKitService.php config/services.php tests/livekit_jwt_test.php
git commit -m "feat(chamada): LiveKit sem pacote: jwt hs256 a mao (com teste puro), token de acesso, DeleteRoom e conferencia do webhook"
```

---

### Task 3: Entrar na sala (`VideoCallController::entrar`) e a página da chamada

**Files:**
- Modify: `app/Http/Controllers/VideoCallController.php` (novo método `entrar`)
- Modify: `app/Models/VideoCall.php` (`toPayload`: `entrar_url`; `fimDaChamada()`)
- Create: `resources/views/chat/chamada.blade.php`
- Modify: `routes/web.php` (grupo chat)
- Modify: `resources/views/chat/show.blade.php` (botão "Entrar" vira link)

- [ ] **Step 1: VideoCall.php**

Depois de `janelaAberta()` inserir:

```php
    /** Fim da chamada, contado no servidor: entrada da criadora (ou horário marcado) + duração. */
    public function fimDaChamada(): ?\Carbon\Carbon
    {
        $inicio = $this->creator_joined_at ?? $this->scheduled_at;

        return $inicio ? $inicio->copy()->addMinutes((int) $this->duration_minutes) : null;
    }
```

Em `toPayload`, depois de `'pode_entrar' => $this->janelaAberta(),` inserir:
```php
            'entrar_url'       => $this->janelaAberta() ? route('chat.chamada.entrar', $this->id) : null,
```

- [ ] **Step 2: Controller, método `entrar`** (inserir antes de `/** Recarga que nasceu de um pedido sem saldo`)

```php
    /**
     * GET /chat/chamada/{videoCall}/entrar — abre a sala. Única porta pro LiveKit.
     * Criadora entrando em 'scheduled' vira 'done' aqui mesmo ("ela entrou = aconteceu",
     * decisão do Pedro 24/09): o sinal é nosso, o webhook só complementa.
     */
    public function entrar(VideoCall $videoCall, \App\Services\LiveKitService $livekit)
    {
        $user = Auth::user();
        $souCriadora = $videoCall->creator_id === $user->id;
        if (! $souCriadora && $videoCall->user_id !== $user->id) {
            abort(403, 'Você não participa desta chamada.');
        }
        if (! PlatformSetting::isVideoCallsEnabled() || ! $livekit->configurado()) {
            return redirect()->route('chat.show', $videoCall->conversation_id)->with('error', 'Chamada de vídeo indisponível no momento.');
        }
        if (! $videoCall->janelaAberta()) {
            return redirect()->route('chat.show', $videoCall->conversation_id)->with('error', 'Fora do horário da chamada.');
        }

        if ($souCriadora && $videoCall->status === 'scheduled') {
            DB::transaction(function () use ($videoCall) {
                $c = VideoCall::lockForUpdate()->find($videoCall->id);
                if ($c->status === 'scheduled') {
                    $c->update(['status' => 'done', 'creator_joined_at' => now(), 'room_name' => 'chamada-' . $c->id]);
                    self::mensagem($c, $c->creator_id, 'Entrou na chamada.');
                }
            });
            $videoCall->refresh();
        }
        if (! $videoCall->room_name) {
            $videoCall->update(['room_name' => 'chamada-' . $videoCall->id]);
        }

        $fim = $videoCall->fimDaChamada();
        $tolerancia = PlatformSetting::getVideoCallToleranceMinutes();
        // Token vale até o fim da duração (mais a tolerância enquanto ela não entrou), nunca menos de 1 min
        $ttl = max(60, ($videoCall->status === 'done' ? $fim : $fim->copy()->addMinutes($tolerancia))->diffInSeconds(now(), false) * -1);
        $outro = User::withoutGlobalScope('active')->find($souCriadora ? $videoCall->user_id : $videoCall->creator_id);

        return view('chat.chamada', [
            'chamada'     => $videoCall,
            'token'       => $livekit->tokenDeAcesso($videoCall->room_name, $user->id, $souCriadora ? '@' . $user->username : $user->name, (int) $ttl),
            'wsUrl'       => $livekit->wsUrl(),
            'souCriadora' => $souCriadora,
            'outroNome'   => $souCriadora ? $outro->name : '@' . $outro->username,
            // fim em epoch (segundos) pro cronômetro não depender do relógio do celular
            'fimEpoch'    => $videoCall->status === 'done' ? $fim->getTimestamp() : null,
            'esperando'   => $videoCall->status !== 'done',
        ]);
    }
```

Nota: `diffInSeconds(now(), false) * -1` dá segundos positivos até o fim; se já passou, `max(60, ...)` segura.

- [ ] **Step 3: Rota** (grupo chat, depois de `chamada.recusar`)

```php
        Route::get('/chamada/{videoCall}/entrar', [\App\Http\Controllers\VideoCallController::class, 'entrar'])->name('chamada.entrar');
```

- [ ] **Step 4: View `resources/views/chat/chamada.blade.php`**

```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chamada com {{ $outroNome }} - {{ config('app.name') }}</title>
    <script src="https://cdn.jsdelivr.net/npm/livekit-client@2/dist/livekit-client.umd.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #111; color: #fff; font-family: system-ui, sans-serif; height: 100dvh; display: flex; flex-direction: column; }
        #remoto { flex: 1; position: relative; background: #000; display: flex; align-items: center; justify-content: center; }
        #remoto video { width: 100%; height: 100%; object-fit: contain; }
        #local { position: absolute; right: 12px; bottom: 12px; width: 28%; max-width: 180px; aspect-ratio: 3/4; background: #222; border-radius: 12px; overflow: hidden; border: 2px solid #333; }
        #local video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        #topo { position: absolute; top: 0; left: 0; right: 0; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(rgba(0,0,0,.6), transparent); font-size: 14px; }
        #aviso { color: #ccc; text-align: center; padding: 24px; font-size: 16px; line-height: 1.5; }
        #barra { display: flex; gap: 12px; justify-content: center; padding: 14px; background: #181818; }
        #barra button { border: 0; border-radius: 999px; padding: 12px 18px; font-size: 15px; font-weight: 600; cursor: pointer; background: #333; color: #fff; }
        #barra button.off { background: #b91c1c; }
        #sair { background: #e11d48 !important; }
        #tempo { font-variant-numeric: tabular-nums; font-weight: 700; }
    </style>
</head>
<body>
    <div id="remoto">
        <div id="topo">
            <span>{{ $outroNome }}</span>
            <span id="tempo">{{ $esperando ? 'aguardando a criadora' : '' }}</span>
        </div>
        <div id="aviso">Conectando...</div>
        <div id="local"></div>
    </div>
    <div id="barra">
        <button id="mic" type="button">Microfone</button>
        <button id="cam" type="button">Câmera</button>
        <button id="sair" type="button">Sair</button>
    </div>

<script>
(function () {
    const token = @json($token);
    const wsUrl = @json($wsUrl);
    const fimEpoch = @json($fimEpoch);          // null enquanto a criadora não entrou
    const voltar = @json(route('chat.show', $chamada->conversation_id));
    const aviso = document.getElementById('aviso');
    const remoto = document.getElementById('remoto');
    const local = document.getElementById('local');
    const tempo = document.getElementById('tempo');

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        aviso.innerHTML = 'Este navegador não libera a câmera (o do Instagram faz isso).<br>Abra este link no Chrome ou no Safari.';
        return;
    }
    if (!window.LivekitClient) {
        aviso.textContent = 'Não foi possível carregar o vídeo. Recarregue a página.';
        return;
    }

    const { Room, RoomEvent, Track } = LivekitClient;
    const room = new Room({ adaptiveStream: true, dynacast: true });
    let encerrou = false;

    function encerrar(msg) {
        if (encerrou) return;
        encerrou = true;
        room.disconnect();
        aviso.style.display = 'block';
        aviso.textContent = msg;
        setTimeout(() => { window.location.href = voltar; }, 2500);
    }

    // Cronômetro: fim vem do servidor em epoch. Zerou, desconecta. O servidor também derruba a sala.
    if (fimEpoch) {
        const tick = () => {
            const s = Math.max(0, fimEpoch - Math.floor(Date.now() / 1000));
            tempo.textContent = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
            if (s <= 0) encerrar('Tempo esgotado. A chamada terminou.');
        };
        tick();
        setInterval(tick, 1000);
    }

    room
        .on(RoomEvent.TrackSubscribed, (track) => {
            if (track.kind === Track.Kind.Video || track.kind === Track.Kind.Audio) {
                const el = track.attach();
                if (track.kind === Track.Kind.Video) { remoto.querySelectorAll('video.remoto').forEach(v => v.remove()); el.className = 'remoto'; }
                remoto.appendChild(el);
                aviso.style.display = 'none';
            }
        })
        .on(RoomEvent.TrackUnsubscribed, (track) => { track.detach().forEach(el => el.remove()); })
        .on(RoomEvent.ParticipantDisconnected, () => { aviso.style.display = 'block'; aviso.textContent = 'A outra pessoa saiu.'; })
        .on(RoomEvent.Disconnected, () => { if (!encerrou) encerrar('Chamada encerrada.'); });

    room.connect(wsUrl, token).then(async () => {
        aviso.textContent = fimEpoch ? 'Esperando a outra pessoa...' : 'Esperando a criadora entrar...';
        await room.localParticipant.enableCameraAndMicrophone();
        const camPub = room.localParticipant.getTrackPublication(Track.Source.Camera);
        if (camPub && camPub.track) local.appendChild(camPub.track.attach());
    }).catch((e) => {
        aviso.textContent = 'Não deu pra entrar: ' + (e.message || e);
    });

    document.getElementById('mic').onclick = async function () {
        const on = room.localParticipant.isMicrophoneEnabled;
        await room.localParticipant.setMicrophoneEnabled(!on);
        this.classList.toggle('off', on);
    };
    document.getElementById('cam').onclick = async function () {
        const on = room.localParticipant.isCameraEnabled;
        await room.localParticipant.setCameraEnabled(!on);
        this.classList.toggle('off', on);
    };
    document.getElementById('sair').onclick = () => encerrar('Você saiu da chamada.');
})();
</script>
</body>
</html>
```

- [ ] **Step 5: Card no chat** (`show.blade.php`, JS `chamadaCardHtml`)

Trocar
```js
            if (c.pode_entrar) {
                // Primeira entrega: sem sala ainda. A segunda troca este botão pelo link da chamada.
                acoes += `<button type="button" class="paid-lock-button" disabled title="Em breve">Entrar na chamada (em breve)</button>`;
            }
```
por
```js
            if (c.pode_entrar && c.entrar_url) {
                acoes += `<a href="${c.entrar_url}" target="_blank" rel="noopener" class="paid-lock-button" style="display:inline-block;text-decoration:none">Entrar na chamada</a>`;
            }
```

- [ ] **Step 6: Lint e commit**

```
php -l app/Http/Controllers/VideoCallController.php; php -l app/Models/VideoCall.php; php -l routes/web.php
git add app/Http/Controllers/VideoCallController.php app/Models/VideoCall.php routes/web.php resources/views/chat/chamada.blade.php resources/views/chat/show.blade.php
git commit -m "feat(chamada): entrar na sala: criadora entrando vira done, token so na janela, pagina da chamada com cronometro contado no servidor"
```

---

### Task 4: Webhook do LiveKit

**Files:**
- Create: `app/Http/Controllers/LiveKitWebhookController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\VideoCall;
use App\Services\LiveKitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook do LiveKit (spec 24/09, seção 4). Só complementa: presença do fã e fim da sala.
 * Nunca muda 'done' nem devolve dinheiro; se nunca chegar, nada quebra.
 */
class LiveKitWebhookController extends Controller
{
    public function handle(Request $request, LiveKitService $livekit)
    {
        $evento = $livekit->eventoDoWebhook($request->getContent(), $request->header('Authorization'));
        if (! $evento) {
            Log::warning('LIVEKIT webhook: assinatura inválida');
            return response()->json(['message' => 'invalid signature'], 401);
        }

        $tipo = $evento['event'] ?? '';
        $room = $evento['room']['name'] ?? '';
        if (! preg_match('/^chamada-(\d+)$/', $room, $m)) {
            return response()->json(['message' => 'ignored'], 200);
        }
        $chamada = VideoCall::find((int) $m[1]);
        if (! $chamada) {
            return response()->json(['message' => 'ignored'], 200);
        }

        if ($tipo === 'participant_joined') {
            $identity = (string) ($evento['participant']['identity'] ?? '');
            if ($identity === (string) $chamada->user_id && ! $chamada->user_joined_at) {
                $chamada->update(['user_joined_at' => now()]);
            }
        } elseif ($tipo === 'room_finished') {
            if (! $chamada->ended_at) {
                $chamada->update(['ended_at' => now()]);
            }
        }

        return response()->json(['message' => 'ok'], 200);
    }
}
```

- [ ] **Step 2: Rota** (`routes/api.php`, depois da rota da Didit)

```php

// Webhook do LiveKit (chamada de vídeo: presença do fã e fim da sala)
Route::post('/livekit/webhook', [App\Http\Controllers\LiveKitWebhookController::class, 'handle'])->name('api.livekit.webhook');
```

- [ ] **Step 3: Lint e commit**

```
php -l app/Http/Controllers/LiveKitWebhookController.php; php -l routes/api.php
git add app/Http/Controllers/LiveKitWebhookController.php routes/api.php
git commit -m "feat(chamada): webhook do LiveKit grava presenca do fa e fim da sala, com assinatura conferida"
```

Depois do deploy: cadastrar `https://pierfans.com/api/livekit/webhook` em Settings → Webhooks no painel do LiveKit.

---

### Task 5: Robô fecha sala vencida

**Files:**
- Modify: `app/Console/Commands/RodarChamadas.php`

- [ ] **Step 1: Trocar o `handle`**

```php
    public function handle(\App\Services\LiveKitService $livekit): int
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

        // Segunda entrega: derruba a sala no fim da duração, contado no servidor. Quem fechou
        // a página antes não importa; token vencido não entra de novo.
        $f = 0;
        VideoCall::where('status', 'done')->whereNull('ended_at')->whereNotNull('creator_joined_at')->orderBy('id')
            ->chunkById(100, function ($chamadas) use (&$f, $livekit) {
                foreach ($chamadas as $chamada) {
                    $fim = $chamada->fimDaChamada();
                    if ($fim && now()->gte($fim)) {
                        if (! $chamada->room_name || $livekit->deleteRoom($chamada->room_name)) {
                            $chamada->update(['ended_at' => now()]);
                            $f++;
                            $this->line("#{$chamada->id} sala fechada");
                        }
                    }
                }
            });
        $this->info("{$f} sala(s) fechada(s)");

        return self::SUCCESS;
    }
```

- [ ] **Step 2: Lint e commit**

```
php -l app/Console/Commands/RodarChamadas.php
git add app/Console/Commands/RodarChamadas.php
git commit -m "feat(chamada): robo derruba a sala no fim da duracao e grava ended_at"
```

---

### Task 6: Admin: "devolver ao fã" e contador do mês

**Files:**
- Modify: `app/Http/Controllers/Admin/AdminVideoCallController.php`
- Modify: `resources/views/admin/chamadas/index.blade.php`
- Modify: `app/Http/Controllers/VideoCallController.php` (`devolver` aceita `done` quando `$forcar`)
- Modify: `routes/web.php` (admin)

- [ ] **Step 1: `devolver` com força**

Trocar a assinatura e a checagem:
```php
    public static function devolver(VideoCall $chamada, string $motivo): ?Message
    {
        return DB::transaction(function () use ($chamada, $motivo) {
            $chamada = VideoCall::lockForUpdate()->find($chamada->id);
            if (! $chamada || ! $chamada->isOpen()) {
                return null;
            }
```
por
```php
    public static function devolver(VideoCall $chamada, string $motivo, bool $forcar = false): ?Message
    {
        return DB::transaction(function () use ($chamada, $motivo, $forcar) {
            $chamada = VideoCall::lockForUpdate()->find($chamada->id);
            // $forcar = admin devolvendo uma chamada 'done' por denúncia (única intervenção humana prevista)
            if (! $chamada || ! ($chamada->isOpen() || ($forcar && $chamada->status === 'done'))) {
                return null;
            }
```
E no `match ($motivo)` do texto, acrescentar `'admin' => 'A equipe devolveu a chamada. ' . $valor . ' voltaram pra carteira do fã.',` antes do `default`. O enum `refund_reason` precisa aceitar `admin`: acrescentar na migration da Task 1 (`up`): `DB::statement("ALTER TABLE video_calls MODIFY refund_reason ENUM('refused','no_show','expired','admin') NULL");` (e o inverso no `down`), com `use Illuminate\Support\Facades\DB;`.

- [ ] **Step 2: Controller do admin**

Acrescentar ao `index` os totais do mês e um método `devolver`:
```php
        $mes = VideoCall::where('status', 'done')->where('creator_joined_at', '>=', now()->startOfMonth())->get(['duration_minutes']);

        return view('admin.chamadas.index', [
            'chamadas'  => $query->paginate(50)->withQueryString(),
            'filtro'    => $filtro,
            'contagens' => $contagens,
            'mesQtd'    => $mes->count(),
            'mesMin'    => (int) $mes->sum('duration_minutes'),
        ]);
    }

    /** Denúncia procedente: devolve ao fã mesmo com a chamada 'done'. */
    public function devolver(VideoCall $videoCall)
    {
        $msg = \App\Http\Controllers\VideoCallController::devolver($videoCall, 'admin', forcar: true);

        return back()->with($msg ? 'success' : 'error', $msg ? 'Valor devolvido ao fã.' : 'Esta chamada não pode ser devolvida.');
    }
```

- [ ] **Step 3: Rota** (junto da `chamadas.index`)
```php
        Route::post('/chamadas/{videoCall}/devolver', [\App\Http\Controllers\Admin\AdminVideoCallController::class, 'devolver'])->name('chamadas.devolver');
```

- [ ] **Step 4: View**

Debaixo do parágrafo do título acrescentar:
```blade
            <p class="text-gray-600 mt-1">Este mês: <strong>{{ $mesQtd }}</strong> realizada(s), <strong>{{ $mesMin }}</strong> minuto(s). O plano grátis do LiveKit dá por volta de 37 horas por mês.</p>
```
Coluna "Ações" no fim da tabela (novo `<th>` `Ações` e, em cada linha):
```blade
                                <td class="px-6 py-4 text-sm">
                                    @if(in_array($c->status, ['requested', 'scheduled', 'done']))
                                        <form method="POST" action="{{ route('admin.chamadas.devolver', $c->id) }}" onsubmit="return confirm('Devolver R$ {{ number_format($c->amount_paid, 2, ',', '.') }} ao fã?')">
                                            @csrf
                                            <button type="submit" class="text-red-600 hover:underline">Devolver ao fã</button>
                                        </form>
                                    @else
                                        -
                                    @endif
                                </td>
```
(e `colspan="11"` na linha vazia). Se o layout do admin mostra `session('success')`/`session('error')`, nada mais; senão acrescentar no topo da view:
```blade
        @if(session('success'))<div class="mb-4 p-3 rounded bg-green-50 text-green-700">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 p-3 rounded bg-red-50 text-red-700">{{ session('error') }}</div>@endif
```

- [ ] **Step 5: Lint e commit**

```
php -l app/Http/Controllers/Admin/AdminVideoCallController.php; php -l app/Http/Controllers/VideoCallController.php; php -l routes/web.php
git add app/Http/Controllers/Admin/AdminVideoCallController.php app/Http/Controllers/VideoCallController.php routes/web.php resources/views/admin/chamadas/index.blade.php database/migrations/2026_09_25_000002_add_room_columns_to_video_calls_table.php
git commit -m "feat(chamada): admin devolve ao fa por denuncia (mesmo done) e ve realizadas e minutos do mes"
```

---

### Task 7: Teste em prod (transação + rollback) e teste real

**Files:**
- Create: `tests/prod/chamada/t4_sala.php`

- [ ] **Step 1: Script**

```php
// Segunda entrega: entrar na sala (janela, done, token), webhook, robo fechando sala, admin devolvendo done. Com rollback.
view()->share('errors', new \Illuminate\Support\ViewErrorBag);
DB::beginTransaction();
try {
    \App\Models\PlatformSetting::setValue('video_calls_enabled', '1', 'teste');
    $criadora = \App\Models\User::where('creator_status', 'approved')->orderByDesc('id')->first();
    $fa = \App\Models\User::withoutGlobalScopes()->where('email', 'like', 'teste-onboarding-%')->orderByDesc('id')->first();
    $conversa = \App\Models\Conversation::firstOrCreate(['creator_id' => $criadora->id, 'subscriber_id' => $fa->id]);
    $lk = app(\App\Services\LiveKitService::class);
    echo "livekit configurado: ", var_export($lk->configurado(), true), "\n";
    $nova = fn (array $extra) => \App\Models\VideoCall::create(array_merge(['conversation_id' => $conversa->id, 'creator_id' => $criadora->id, 'user_id' => $fa->id, 'price' => 100, 'duration_minutes' => 15, 'amount_paid' => 100, 'platform_percentage' => 20, 'platform_amount' => 15, 'affiliate_amount' => 5, 'creator_amount' => 80], $extra));
    $ctrl = app(\App\Http\Controllers\VideoCallController::class);
    $tipo = fn ($r) => $r instanceof \Illuminate\View\View ? 'VIEW' : (method_exists($r, 'getTargetUrl') ? 'REDIRECT ' . $r->getTargetUrl() . ' | ' . ($r->getSession()->get('error') ?? '') : get_class($r));

    // fora da janela: redireciona
    $c1 = $nova(['status' => 'scheduled', 'scheduled_at' => now()->addHours(2)]);
    Auth::login($criadora);
    echo "criadora 2h antes -> ", $tipo($ctrl->entrar($c1, $lk)), " | status=", $c1->fresh()->status, " (esperado scheduled)\n";

    // na janela: fa entra primeiro (nao muda), depois criadora (vira done)
    $c2 = $nova(['status' => 'scheduled', 'scheduled_at' => now()->addMinutes(5)]);
    Auth::login($fa);
    $r = $ctrl->entrar($c2, $lk);
    echo "fa na janela -> ", $tipo($r), " | status=", $c2->fresh()->status, " (esperado scheduled) | esperando=", var_export($r->getData()['esperando'] ?? null, true), "\n";
    Auth::login($criadora);
    $r = $ctrl->entrar($c2, $lk);
    $c2->refresh();
    echo "criadora na janela -> ", $tipo($r), " | status={$c2->status} (esperado done) joined={$c2->creator_joined_at} room={$c2->room_name} | fimEpoch-agora=", ($r->getData()['fimEpoch'] ?? 0) - time(), "s (esperado ~900)\n";
    $tok = $r->getData()['token'];
    $claims = \App\Support\LiveKitJwt::verify($tok, config('services.livekit.api_secret'));
    echo "token: sub=", $claims['sub'] ?? '-', " room=", $claims['video']['room'] ?? '-', " exp-agora=", ($claims['exp'] ?? 0) - time(), "s\n";
    echo "mensagem 'Entrou na chamada': ", \App\Models\Message::where('video_call_id', $c2->id)->where('content', 'Entrou na chamada.')->count(), "\n";
    $html = $r->render();
    echo "pagina: ", strlen($html), " bytes | livekit-client: ", substr_count($html, 'livekit-client'), " | id=\"tempo\": ", substr_count($html, 'id="tempo"'), "\n";
    // criadora de novo: continua done, sem 2a mensagem
    $ctrl->entrar($c2->fresh(), $lk);
    echo "criadora 2x: mensagens 'Entrou': ", \App\Models\Message::where('video_call_id', $c2->id)->where('content', 'Entrou na chamada.')->count(), " (esperado 1)\n";

    // webhook: assinatura errada, participant_joined do fa, room_finished, repetido
    $wh = app(\App\Http\Controllers\LiveKitWebhookController::class);
    $corpo = json_encode(['event' => 'participant_joined', 'room' => ['name' => $c2->room_name], 'participant' => ['identity' => (string) $fa->id]]);
    $assina = fn ($body, $secret) => \App\Support\LiveKitJwt::sign(config('services.livekit.api_key'), $secret, ['sha256' => base64_encode(hash('sha256', $body, true))], 60);
    $req = fn ($body, $auth) => tap(\Illuminate\Http\Request::create('/api/livekit/webhook', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => $auth], $body));
    echo "webhook assinatura errada -> ", $wh->handle($req($corpo, $assina($corpo, 'errado')), $lk)->getStatusCode(), " (esperado 401)\n";
    echo "webhook joined fa -> ", $wh->handle($req($corpo, $assina($corpo, config('services.livekit.api_secret'))), $lk)->getStatusCode(), " user_joined_at=", $c2->fresh()->user_joined_at, "\n";
    $primeiro = $c2->fresh()->user_joined_at;
    sleep(1);
    $wh->handle($req($corpo, $assina($corpo, config('services.livekit.api_secret'))), $lk);
    echo "webhook repetido: user_joined_at igual? ", var_export((string) $c2->fresh()->user_joined_at === (string) $primeiro, true), "\n";
    $fimCorpo = json_encode(['event' => 'room_finished', 'room' => ['name' => $c2->room_name]]);
    $wh->handle($req($fimCorpo, $assina($fimCorpo, config('services.livekit.api_secret'))), $lk);
    echo "webhook room_finished -> ended_at=", $c2->fresh()->ended_at, " | status=", $c2->fresh()->status, " (continua done)\n";

    // robo: done com duracao vencida e sem ended_at -> fecha
    $c3 = $nova(['status' => 'done', 'scheduled_at' => now()->subMinutes(30), 'creator_joined_at' => now()->subMinutes(16), 'room_name' => 'chamada-teste-robo']);
    \Illuminate\Support\Facades\Artisan::call('chamadas:rodar');
    echo trim(\Illuminate\Support\Facades\Artisan::output()), "\n";
    echo "c3 ended_at=", $c3->fresh()->ended_at, " (esperado agora) | c2 nao mexeu: ", var_export((string) $c2->fresh()->ended_at !== '', true), "\n";

    // admin devolve done
    $adm = app(\App\Http\Controllers\Admin\AdminVideoCallController::class);
    $admin = \App\Models\User::where('is_admin', 1)->first();
    Auth::login($admin);
    $saldoAntes = (float) $fa->fresh()->getOrCreateWallet()->balance;
    $adm->devolver($c2->fresh());
    echo "admin devolve done -> status=", $c2->fresh()->status, "/", $c2->fresh()->refund_reason, " saldo fa +", (float) $fa->fresh()->getOrCreateWallet()->balance - $saldoAntes, " (esperado 100)\n";
    $html = $adm->index(\Illuminate\Http\Request::create('/admin/chamadas', 'GET'))->render();
    echo "admin lista: 'Este mês': ", substr_count($html, 'Este mês'), " | 'Devolver ao fã': ", substr_count($html, 'Devolver ao fã'), "\n";
} catch (\Throwable $e) {
    echo "EXCECAO ", get_class($e), ": ", $e->getMessage(), " @ ", $e->getFile(), ":", $e->getLine(), "\n";
} finally {
    DB::rollBack();
    echo "rollback feito; video_calls_enabled agora: ", var_export(\App\Models\PlatformSetting::getValue('video_calls_enabled'), true), "\n";
}
```

- [ ] **Step 2: Commit**

```bash
git add tests/prod/chamada/t4_sala.php
git commit -m "test(chamada): sala, webhook, robo e admin com rollback"
```

- [ ] **Step 3: Deploy + migration** (autorização do Pedro), rodar `t4_sala.php`, cadastrar o webhook no painel do LiveKit (`https://pierfans.com/api/livekit/webhook`).

- [ ] **Step 4: Teste real, com o Pedro.** Ligar `video_calls_enabled` no admin só durante o teste (ou deixar ligado se o Bento já quiser). Com uma criadora de teste (ou a @pierfans) e a conta de teste 1928: pedir pelo chat, marcar "agora", clicar em "Entrar" nos dois lados (celular e PC, ou duas abas), ver vídeo e áudio dos dois, cronômetro descendo, sair, e conferir no admin `done`, `user_joined_at`, `ended_at`. Devolver ao fã pelo admin no fim pra zerar o dinheiro. Depois desligar o interruptor até o Bento decidir.

---

## Self-review

- **Spec seção 4:** token na janela (Task 3), criadora vira done (3), página (3), robô fecha sala (5), webhook só complementa (4), medir a febre (6). **Seção 5:** devolver ao fã (6). **Seção 6 item 7:** Task 7.
- **Nomes:** `LiveKitJwt::sign/verify/b64/b64d`, `LiveKitService::configurado/wsUrl/tokenDeAcesso/deleteRoom/eventoDoWebhook`, `VideoCall::fimDaChamada`, rota `chat.chamada.entrar`, `VideoCallController::entrar`, `devolver(..., bool $forcar)`, comando com `LiveKitService` injetado (o `Schedule::command` resolve pelo container).
- **Detalhe de ordem:** a Task 6 muda a migration da Task 1 (enum `admin`); como nada foi deployado entre elas, é uma migration só.
