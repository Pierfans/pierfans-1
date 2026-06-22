<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class CreatorSearchController extends Controller
{
    /**
     * Exibe a página de busca de criadores
     */
    public function index(Request $request)
    {
        $query = User::where('creator_status', 'approved')
            ->whereNotNull('username')
            ->orderBy('created_at', 'desc');

        // Busca por nome ou username
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('creator_full_name', 'like', "%{$search}%");
            });
        }

        $creators = $query->with('subscriptionPlans')->paginate(20)->appends($request->query());

        // Se for requisição AJAX, retorna apenas o grid
        if ($request->ajax()) {
            return view('creator-search.partials.creators-grid', compact('creators'))->render();
        }

        return view('creator-search.index', compact('creators'));
    }
}
