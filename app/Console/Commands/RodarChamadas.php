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

    public function handle(): int
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

        return self::SUCCESS;
    }
}
