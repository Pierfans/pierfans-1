<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTopCreatorsController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('creator_status', 'approved')
            ->whereNotNull('username');

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

        $topCreators = User::where('creator_status', 'approved')
            ->where('featured_in_top_creators', true)
            ->whereNotNull('username')
            ->orderByRaw('top_creators_order IS NULL, top_creators_order ASC')
            ->get();

        return view('admin.top-creators.index', compact('creators', 'topCreators'));
    }

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

        $data = ['featured_in_top_creators' => $request->featured_in_top_creators];
        if (!$request->featured_in_top_creators) {
            $data['top_creators_order'] = null;
        }
        $creator->update($data);

        return response()->json([
            'success' => true,
            'message' => $request->featured_in_top_creators
                ? 'Criador adicionado ao TOP com sucesso!'
                : 'Criador removido do TOP com sucesso!',
            'featured_in_top_creators' => $creator->featured_in_top_creators,
        ]);
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:users,id',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->order as $position => $userId) {
                User::where('id', $userId)->update(['top_creators_order' => $position + 1]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Ordem atualizada com sucesso!']);
    }
}
