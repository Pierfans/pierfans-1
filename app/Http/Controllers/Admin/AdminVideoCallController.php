<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoCall;
use Illuminate\Http\Request;

/** Lista das chamadas de vídeo (spec 24/09). Sem ação na primeira entrega. */
class AdminVideoCallController extends Controller
{
    public function index(Request $request)
    {
        $filtro = $request->input('filter', 'todas');
        $estados = ['requested', 'scheduled', 'done', 'refunded'];

        $query = VideoCall::with(['creator', 'user'])->where('status', '!=', 'awaiting_payment')->orderByDesc('id');
        if (in_array($filtro, $estados, true)) {
            $query->where('status', $filtro);
        }

        $contagens = VideoCall::whereIn('status', $estados)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        // "Medir a febre" (bento): quanto do plano grátis do LiveKit (~37 h/mês) já foi
        $mes = VideoCall::where('status', 'done')->where('creator_joined_at', '>=', now()->startOfMonth())->get(['duration_minutes']);

        return view('admin.chamadas.index', [
            'chamadas'  => $query->paginate(50)->withQueryString(),
            'filtro'    => $filtro,
            'contagens' => $contagens,
            'mesQtd'    => $mes->count(),
            'mesMin'    => (int) $mes->sum('duration_minutes'),
        ]);
    }

    /** Denúncia procedente: devolve ao fã mesmo com a chamada 'done'. */
    public function devolver(VideoCall $videoCall)
    {
        $msg = \App\Http\Controllers\VideoCallController::devolver($videoCall, 'admin', forcar: true);

        return back()->with($msg ? 'success' : 'error', $msg ? 'Valor devolvido ao fã.' : 'Esta chamada não pode ser devolvida.');
    }
}
