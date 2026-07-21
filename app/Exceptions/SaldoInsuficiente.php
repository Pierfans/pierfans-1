<?php

namespace App\Exceptions;

/**
 * Saldo de carteira não cobre a compra. Existe como tipo próprio pra separar "o usuário não
 * tem dinheiro" (erro dele, 400, mensagem amigável) de "alguma coisa quebrou" (500) — o
 * catch genérico não consegue distinguir os dois por mensagem sem virar comparação de string.
 */
class SaldoInsuficiente extends \RuntimeException
{
    public function __construct(public readonly float $saldo, public readonly float $preco)
    {
        parent::__construct(sprintf(
            'Seu saldo é de R$ %s e essa compra custa R$ %s. Faltam R$ %s.',
            number_format($saldo, 2, ',', '.'),
            number_format($preco, 2, ',', '.'),
            number_format($preco - $saldo, 2, ',', '.')
        ));
    }
}
