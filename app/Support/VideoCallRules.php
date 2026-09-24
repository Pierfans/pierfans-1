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
