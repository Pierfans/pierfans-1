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
