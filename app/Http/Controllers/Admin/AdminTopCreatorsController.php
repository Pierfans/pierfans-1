<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminTopCreatorsController extends Controller
{
    /**
     * Lista todos os criadores aprovados para seleção do TOP
     */
    public function index(Request $request)
    {
        // Busca criadores aprovados
        $query = User::where('creator_status', 'approved')
            ->whereNotNull('username'); // Apenas criadores com username

        // Busca por nome, email ou username
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('creator_full_name', 'like', "%{$search}%");
            });
        }

        $creators = $query->orderBy('created_at', 'desc')->paginate(20)->appends($request->query());

        // Conta quantos estão no TOP
        $topCreatorsCount = User::where('creator_status', 'approved')
            ->where('featured_in_top_creators', true)
            ->whereNotNull('username')
            ->count();

        return view('admin.top-creators.index', compact('creators', 'topCreatorsCount'));
    }

    /**
     * Atualiza o status de TOP de um criador
     */
    public function toggle(Request $request, $id)
    {
        $creator = User::findOrFail($id);
        
        if ($creator->creator_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas criadores aprovados podem estar no TOP.',
            ], 400);
        }

        if (!$creator->username) {
            return response()->json([
                'success' => false,
                'message' => 'O criador precisa ter um username para aparecer no TOP.',
            ], 400);
        }

        $request->validate([
            'featured_in_top_creators' => 'required|boolean',
        ]);

        $creator->update([
            'featured_in_top_creators' => $request->featured_in_top_creators,
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->featured_in_top_creators 
                ? 'Criador adicionado ao TOP com sucesso!' 
                : 'Criador removido do TOP com sucesso!',
            'featured_in_top_creators' => $creator->featured_in_top_creators,
        ]);
    }
}

