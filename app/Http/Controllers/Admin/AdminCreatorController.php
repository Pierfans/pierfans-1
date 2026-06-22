<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCreatorController extends Controller
{
    /**
     * Lista todos os criadores
     */
    public function index(Request $request)
    {
        $query = User::withoutGlobalScope('active')->whereIn('creator_status', ['pending', 'approved', 'rejected'])
            ->orderBy('creator_submitted_at', 'desc');

        // Filtro por status (opcional)
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('creator_status', $request->status);
        }

        // Busca por nome ou email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('creator_full_name', 'like', "%{$search}%");
            });
        }

        $creators = $query->paginate(20)->appends($request->query());

        return view('admin.creators.index', compact('creators'));
    }

    /**
     * Mostra os detalhes completos de um criador
     */
    public function show($id)
    {
        $creator = User::withoutGlobalScope('active')->findOrFail($id);

        // Permite visualizar qualquer criador (não apenas pendentes)
        if (!in_array($creator->creator_status, ['pending', 'approved', 'rejected'])) {
            return redirect()->route('admin.creators.index')
                ->with('error', 'Este usuário não é um criador.');
        }

        return view('admin.creators.show', compact('creator'));
    }


    /**
     * Gera um username aleatório único
     */
    private function generateUniqueUsername(): string
    {
        $maxAttempts = 10;
        $attempt = 0;
        
        do {
            // Gera um username aleatório com 8 caracteres alfanuméricos
            $username = Str::random(8);
            $attempt++;
            
            // Verifica se já existe no banco
            $exists = User::withoutGlobalScope('active')->where('username', $username)->exists();
            
            if (!$exists) {
                return $username;
            }
        } while ($attempt < $maxAttempts);
        
        // Se após 10 tentativas ainda não encontrou um único, adiciona timestamp
        return Str::random(6) . time();
    }

    /**
     * Aprova um criador
     */
    public function approve($id)
    {
        $creator = User::withoutGlobalScope('active')->findOrFail($id);

        if ($creator->creator_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Este criador não está pendente de aprovação.',
            ], 400);
        }

        // Prepara os dados para atualização
        $updateData = [
            'creator_status' => 'approved',
        ];

        // Gera um username aleatório se o criador ainda não tiver um
        if (!$creator->username) {
            $updateData['username'] = $this->generateUniqueUsername();
        }

        $creator->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Criador aprovado com sucesso!',
            'username' => $creator->fresh()->username,
        ]);
    }

    /**
     * Reprova um criador
     */
    public function reject($id)
    {
        $creator = User::withoutGlobalScope('active')->findOrFail($id);

        if ($creator->creator_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Este criador não está pendente de aprovação.',
            ], 400);
        }

        $creator->update([
            'creator_status' => 'rejected',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Criador reprovado. O usuário poderá reenviar os documentos.',
        ]);
    }

    public function toggleActive($id)
    {
        $user = User::withoutGlobalScope('active')->findOrFail($id);

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success'   => true,
            'message'   => $user->is_active
                ? 'Criadora reativada com sucesso!'
                : 'Criadora desativada com sucesso!',
            'is_active' => $user->is_active,
        ]);
    }
}
