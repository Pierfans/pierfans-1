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
