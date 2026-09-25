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
