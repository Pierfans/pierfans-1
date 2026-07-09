<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class DiditService
{
    private string $baseUrl;
    private string $apiKey;
    private ?string $workflowId;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.didit.base_url'), '/');
        $this->apiKey = (string) config('services.didit.api_key');
        $this->workflowId = config('services.didit.workflow_id');
    }

    /**
     * Cria uma sessao de verificacao e retorna ['session_id' => ..., 'url' => ...].
     * O vendor_data e o id do usuario, e como a Didit devolve isso no webhook.
     */
    public function createSession(User $user, ?string $callbackUrl = null): array
    {
        $response = Http::withHeaders(['x-api-key' => $this->apiKey])
            ->acceptJson()
            ->post("{$this->baseUrl}/v2/session/", array_filter([
                'workflow_id' => $this->workflowId,
                'vendor_data' => (string) $user->id,
                'callback' => $callbackUrl,
            ]))
            ->throw();

        return [
            'session_id' => $response->json('session_id'),
            'url' => $response->json('url'),
        ];
    }

    /**
     * Valida a assinatura HMAC do webhook (header X-Signature = HMAC-SHA256 do corpo cru).
     * Rejeita tambem entregas com mais de 5 min (anti-replay).
     */
    public function verifyWebhook(string $rawBody, ?string $signature, ?string $timestamp): bool
    {
        $secret = (string) config('services.didit.webhook_secret');

        if ($secret === '' || ! $signature || ! $timestamp) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }
}
