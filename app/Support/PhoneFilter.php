<?php

namespace App\Support;

/**
 * Trava de numero de telefone no chat (bento 21/09: "bloqueia mandar Whatsaap
 * pelo chat, no caso bloqueia mandar numero de celular pelo chat").
 *
 * Funcao pura de proposito, sem nada do Laravel, pra rodar o teste com php puro:
 * php tests/phone_filter_test.php
 */
class PhoneFilter
{
    /**
     * Diz se o texto tem cara de numero de telefone.
     *
     * Cola os digitos separados so pela pontuacao que se usa em telefone
     * (espaco, ponto, hifen, parentese, barra) e procura uma corrida de 9 ou
     * mais. Celular brasileiro tem 9 digitos sem DDD, entao 9 e o piso que pega
     * todo celular escrito de qualquer jeito: "(47) 99671-2232", "47 9 9671
     * 2232", "+55 47 99671 2232".
     *
     * O corte e 9 e nao 8 de proposito: em 8 comecam os falsos positivos que
     * doem mais que a fuga que sobra. "de 2020-2026" cola em 8 digitos e o
     * unico telefone que escapa e fixo antigo digitado sem DDD, que ninguem
     * manda pra combinar no Whatsapp.
     *
     * ponytail: pega digito, nao numero por extenso ("nove nove seis sete um")
     * nem emoji de numero. Se comecarem a driblar assim, o proximo passo e
     * traduzir as palavras pra digito antes de contar, nao trocar de abordagem.
     */
    public static function hasPhoneNumber(?string $text): bool
    {
        if ($text === null || trim($text) === '') {
            return false;
        }

        // Cola so o que esta entre digitos: "10.000,00" nao vira corrida porque
        // a virgula fica de fora e quebra a sequencia.
        $glued = preg_replace('/(?<=\d)[\s.\-()\/]+(?=\d)/u', '', $text);

        return (bool) preg_match('/\d{9,}/', $glued);
    }
}
