<?php
/**
 * Teste da trava de telefone do chat. Roda sozinho, sem Laravel:
 *   php tests/phone_filter_test.php
 */
require __DIR__ . '/../app/Support/PhoneFilter.php';

use App\Support\PhoneFilter;

$bloqueia = [
    'meu zap eh 47996712232',
    '(47) 99671-2232',
    '47 9 9671 2232',
    '+55 47 99671 2232',
    'chama no 99671.2232',
    'salva ai 9 9 6 7 1 2 2 3 2',
    'me liga 47/99671/2232',
    'oi amor, meu numero eh (11) 98888-7777 beijos',
];

$libera = [
    'custa R$ 10.000,00',
    'tenho 25 anos',
    'de 2020-2026 morei fora',
    'amanha as 20:30',
    'meu insta eh @fulana',
    'ja vendi 1.500 fotos',
    'te mando 3 videos hoje',
    '',
    null,
];

$falhas = 0;
foreach ($bloqueia as $t) {
    if (!PhoneFilter::hasPhoneNumber($t)) { echo "DEVIA BLOQUEAR: " . var_export($t, true) . "\n"; $falhas++; }
}
foreach ($libera as $t) {
    if (PhoneFilter::hasPhoneNumber($t)) { echo "DEVIA PASSAR: " . var_export($t, true) . "\n"; $falhas++; }
}

if ($falhas) { echo "\n{$falhas} falha(s)\n"; exit(1); }
echo 'ok: ' . (count($bloqueia) + count($libera)) . " casos\n";
