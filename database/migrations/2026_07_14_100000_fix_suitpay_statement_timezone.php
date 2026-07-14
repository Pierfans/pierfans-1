<?php

use App\Models\SuitpayStatementEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * As linhas do extrato foram importadas com o horário cru do painel (Brasília), enquanto o app
 * grava em UTC — 3h de defasagem. A reconciliação compara os dois por data, então o último
 * lançamento do extrato aparecia como "ainda não visto" e era somado duas vezes no saldo real.
 *
 * Converte o que já está no banco para o fuso do app. O line_hash (idempotência do import) inclui
 * o occurred_at, então precisa ser recalculado com a MESMA fórmula do importCsv — senão reimportar
 * o mesmo CSV geraria hash novo e duplicaria tudo.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('suitpay_statement_entries')->get() as $row) {
            $this->shift($row, SuitpayStatementEntry::TZ_PAINEL, config('app.timezone'));
        }
    }

    public function down(): void
    {
        foreach (DB::table('suitpay_statement_entries')->get() as $row) {
            $this->shift($row, config('app.timezone'), SuitpayStatementEntry::TZ_PAINEL);
        }
    }

    private function shift(object $row, string $from, string $to): void
    {
        $occurredAt = Carbon::parse($row->occurred_at, $from)->setTimezone($to);
        $valor = (float) $row->valor;
        $saldo = $row->saldo === null ? '' : (float) $row->saldo;

        DB::table('suitpay_statement_entries')->where('id', $row->id)->update([
            'occurred_at' => $occurredAt,
            'line_hash'   => md5($row->descricao . '|' . $occurredAt->toDateTimeString() . '|' . $valor . '|' . $saldo),
        ]);
    }
};
