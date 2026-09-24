<?php
/**
 * Regras da chamada de vídeo que não dependem do Laravel. Roda sozinho:
 *   php -d zend.assertions=1 -d assert.exception=1 tests/video_call_rules_test.php
 */
require __DIR__ . '/../app/Support/VideoCallRules.php';

use App\Support\VideoCallRules;

$d = fn (string $s) => new DateTimeImmutable($s, new DateTimeZone('UTC'));
$agora = $d('2026-09-27 21:00:00');
$tol = 30;

// motivoDevolucao(status, scheduledAt, createdAt, creatorJoinedAt, agora, toleranciaMin)
$casos = [
    // [esperado, status, scheduled, created, joined]
    [null,       'requested', null,                  '2026-09-26 21:00:00', null],
    ['expired',  'requested', null,                  '2026-09-19 20:59:00', null],
    [null,       'requested', null,                  '2026-09-20 21:00:01', null],
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
