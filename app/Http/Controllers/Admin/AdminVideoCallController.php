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

        return view('admin.chamadas.index', [
            'chamadas'  => $query->paginate(50)->withQueryString(),
            'filtro'    => $filtro,
            'contagens' => $contagens,
        ]);
    }
}
