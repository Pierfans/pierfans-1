<?php

namespace App\Http\Controllers;

use App\Models\VideoCall;
use App\Services\LiveKitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook do LiveKit (spec 24/09, seção 4). Só complementa: presença do fã e fim da sala.
 * Nunca muda 'done' nem devolve dinheiro; se nunca chegar, nada quebra.
 */
class LiveKitWebhookController extends Controller
{
    public function handle(Request $request, LiveKitService $livekit)
    {
        $evento = $livekit->eventoDoWebhook($request->getContent(), $request->header('Authorization'));
        if (! $evento) {
            Log::warning('LIVEKIT webhook: assinatura inválida');

            return response()->json(['message' => 'invalid signature'], 401);
        }

        $tipo = $evento['event'] ?? '';
        $room = $evento['room']['name'] ?? '';
        if (! preg_match('/^chamada-(\d+)$/', $room, $m)) {
            return response()->json(['message' => 'ignored'], 200);
        }
        $chamada = VideoCall::find((int) $m[1]);
        if (! $chamada) {
            return response()->json(['message' => 'ignored'], 200);
        }

        if ($tipo === 'participant_joined') {
            $identity = (string) ($evento['participant']['identity'] ?? '');
            if ($identity === (string) $chamada->user_id && ! $chamada->user_joined_at) {
                $chamada->update(['user_joined_at' => now()]);
            }
        } elseif ($tipo === 'room_finished') {
            if (! $chamada->ended_at) {
                $chamada->update(['ended_at' => now()]);
            }
        }

        return response()->json(['message' => 'ok'], 200);
    }
}
