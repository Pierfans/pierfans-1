<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\CreatorStatusMail;
use App\Models\User;
use App\Services\DiditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
     * Fotos do documento da verificação Didit, buscadas na hora (as URLs vencem em 4h).
     * Nada é gravado no nosso servidor: a foto continua na Didit, o admin só olha.
     */
    public function documents($id, DiditService $didit)
    {
        $creator = User::withoutGlobalScope('active')->findOrFail($id);

        if (! $creator->didit_session_id) {
            return response()->json([
                'success' => false,
                'message' => 'Este criador não tem verificação Didit.',
            ], 404);
        }

        try {
            $decision = $didit->getDecision($creator->didit_session_id);
        } catch (\Exception $e) {
            Log::error('Falha ao buscar documentos na Didit', [
                'user_id' => $creator->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível buscar os documentos na Didit agora. Tente de novo.',
            ], 502);
        }

        // O endpoint /decision v2 manda singular; o webhook v3 manda array no plural (mesmo
        // par de nomes que o DiditWebhookController ja trata). Aceita os dois.
        $idv = $decision['id_verification'] ?? $decision['id_verifications'][0] ?? [];
        $live = $decision['liveness'] ?? $decision['liveness_checks'][0] ?? [];

        $images = array_values(array_filter([
            ['label' => 'Documento - frente', 'url' => $idv['front_image'] ?? null],
            ['label' => 'Documento - verso', 'url' => $idv['back_image'] ?? null],
            ['label' => 'Foto do documento', 'url' => $idv['portrait_image'] ?? null],
            ['label' => 'Selfie (prova de vida)', 'url' => $live['reference_image'] ?? null],
        ], fn ($i) => ! empty($i['url'])));

        return response()->json([
            'success' => true,
            'images'  => $images,
            'message' => $images ? null : 'A Didit não tem imagem desta sessão (a verificação não chegou a enviar o documento).',
        ]);
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

        $this->notifyCreator($creator, 'approved');

        return response()->json([
            'success' => true,
            'message' => 'Criador aprovado com sucesso!',
            'username' => $creator->fresh()->username,
        ]);
    }

    /**
     * Reprova um criador. O motivo vai no email pra criadora saber o que corrigir.
     */
    public function reject(Request $request, $id)
    {
        $creator = User::withoutGlobalScope('active')->findOrFail($id);

        if ($creator->creator_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Este criador não está pendente de aprovação.',
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $creator->update([
            'creator_status' => 'rejected',
            'creator_rejection_reason' => $validated['reason'],
        ]);

        $this->notifyCreator($creator, 'rejected');

        return response()->json([
            'success' => true,
            'message' => 'Criador reprovado e avisado por email. Ele poderá reenviar os documentos.',
        ]);
    }

    public function toggleActive($id)
    {
        $user = User::withoutGlobalScope('active')->findOrFail($id);

        $user->is_active = !$user->is_active;
        $user->save();

        // Só notifica criadoras aprovadas (toggleActive nunca deveria rodar em usuário comum)
        if ($user->creator_status === 'approved') {
            $this->notifyCreator($user, $user->is_active ? 'activated' : 'deactivated');
        }

        return response()->json([
            'success'   => true,
            'message'   => $user->is_active
                ? 'Criadora reativada com sucesso!'
                : 'Criadora desativada com sucesso!',
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Envia email de mudança de status para a criadora.
     * Falha de email nunca quebra a ação do admin (mesmo padrão do PPV).
     */
    private function notifyCreator(User $creator, string $type): void
    {
        try {
            Mail::to($creator->email)->send(new CreatorStatusMail($creator, $type));
        } catch (\Exception $e) {
            Log::error('Falha ao enviar email de status de criadora', [
                'user_id' => $creator->id,
                'type'    => $type,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
