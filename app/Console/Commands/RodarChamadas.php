<?php

namespace App\Console\Commands;

use App\Http\Controllers\VideoCallController;
use App\Models\VideoCall;
use Illuminate\Console\Command;

/**
 * Devoluções automáticas da chamada de vídeo (spec 24/09): criadora que não entrou
 * (no_show) e pedido velho (expired). Roda a cada minuto pelo schedule:run do crontab.
 * Idempotente: devolver() reconfere o estado com lock.
 */
class RodarChamadas extends Command
{
    protected $signature = 'chamadas:rodar';

    protected $description = 'Devolve ao fã as chamadas de vídeo que a criadora não fez ou que passaram do prazo';

    public function handle(\App\Services\LiveKitService $livekit): int
    {
        $n = 0;
        VideoCall::whereIn('status', VideoCall::ABERTAS)->orderBy('id')->chunkById(100, function ($chamadas) use (&$n) {
            foreach ($chamadas as $chamada) {
                $motivo = $chamada->motivoDevolucao();
                if ($motivo && VideoCallController::devolver($chamada, $motivo)) {
                    $n++;
                    $this->line("#{$chamada->id} devolvida ({$motivo})");
                }
            }
        });
        $this->info("{$n} devolvida(s)");

        // Segunda entrega: derruba a sala no fim da duração, contado no servidor. Quem fechou
        // a página antes não importa; token vencido não entra de novo.
        $f = 0;
        VideoCall::where('status', 'done')->whereNull('ended_at')->whereNotNull('creator_joined_at')->orderBy('id')
            ->chunkById(100, function ($chamadas) use (&$f, $livekit) {
                foreach ($chamadas as $chamada) {
                    $fim = $chamada->fimDaChamada();
                    if ($fim && now()->gte($fim)) {
                        if (! $chamada->room_name || $livekit->deleteRoom($chamada->room_name)) {
                            $chamada->update(['ended_at' => now()]);
                            $f++;
                            $this->line("#{$chamada->id} sala fechada");
                        }
                    }
                }
            });
        $this->info("{$f} sala(s) fechada(s)");

        return self::SUCCESS;
    }
}
