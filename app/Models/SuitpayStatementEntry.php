<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Linha do extrato real do SuitPay (exportado do painel — "Exportar Excel/CSV").
 * É o dado-verdade: traz o saldo corrente da conta e a taxa REAL por linha, que a
 * API do SuitPay não expõe. Usado pra reconciliar contra o ledger interno e achar
 * o que ele não vê (abertura da conta, retiradas manuais que não passam pelo gateway).
 */
class SuitpayStatementEntry extends Model
{
    protected $fillable = [
        'occurred_at', 'descricao', 'tipo', 'beneficiario',
        'valor', 'saldo', 'control_id', 'status', 'line_hash',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'valor'       => 'decimal:2',
        'saldo'       => 'decimal:2',
    ];

    /** Categoria a partir da descrição do extrato. */
    public static function tipoFromDescricao(string $d): string
    {
        return match (trim($d)) {
            'PIX Gateway'          => 'pix_in',     // venda (cash-in)
            'Taxa Pix'             => 'fee_in',     // taxa da entrada
            'Pgt. via PIX Gateway' => 'cashout',    // saque de criador/afiliado
            'Taxa Pgt. via PIX'    => 'fee_out',    // taxa da saída
            'Pgt. via PIX'         => 'manual_out', // retirada manual (não passa pelo ledger)
            default                => 'outro',
        };
    }

    /** "- 1.234,56" / "+ 29,90" / "R$ 317,23" → float com sinal. Null se vazio. */
    public static function parseValor(string $s): ?float
    {
        $s = trim(str_replace(['R$', ' '], '', $s));
        if ($s === '' || $s === '-') {
            return null;
        }
        $neg = str_starts_with($s, '-');
        $s = str_replace(['+', '-', '.'], '', $s);
        $s = str_replace(',', '.', $s);
        if (!is_numeric($s)) {
            return null;
        }
        return $neg ? -((float) $s) : (float) $s;
    }

    /** "06/07/2026 - 08:18" → Carbon. Null se não parsear. */
    public static function parseDataHora(string $s): ?Carbon
    {
        $s = trim(str_replace(' - ', ' ', trim($s)));
        foreach (['d/m/Y H:i:s', 'd/m/Y H:i'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $s);
            } catch (\Throwable $e) {
                // tenta o próximo formato
            }
        }
        return null;
    }

    /**
     * Importa o conteúdo bruto de um CSV do extrato SuitPay (separador ';').
     * Idempotente por line_hash. Retorna quantas linhas novas entraram.
     */
    public static function importCsv(string $content): int
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // tira BOM
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $novas = 0;

        foreach ($lines as $i => $line) {
            if ($i === 0 || trim($line) === '') {
                continue; // cabeçalho / linha vazia
            }
            $c = str_getcsv($line, ';', '"', '\\');
            $descricao = trim($c[0] ?? '');
            if ($descricao === '') {
                continue;
            }
            $valor = self::parseValor($c[3] ?? '');
            $occurredAt = self::parseDataHora($c[2] ?? '');
            if ($valor === null || $occurredAt === null) {
                continue; // linha sem valor/data útil
            }
            $saldo = self::parseValor($c[4] ?? '');
            $controlId = trim($c[5] ?? '') ?: null;
            $status = trim($c[6] ?? '') ?: null;

            $hash = md5($descricao . '|' . $occurredAt->toDateTimeString() . '|' . $valor . '|' . ($saldo ?? ''));

            $entry = self::firstOrCreate(['line_hash' => $hash], [
                'occurred_at'  => $occurredAt,
                'descricao'    => $descricao,
                'tipo'         => self::tipoFromDescricao($descricao),
                'beneficiario' => trim($c[1] ?? '') ?: null,
                'valor'        => $valor,
                'saldo'        => $saldo,
                'control_id'   => $controlId,
                'status'       => $status,
            ]);
            if ($entry->wasRecentlyCreated) {
                $novas++;
            }
        }

        return $novas;
    }
}
