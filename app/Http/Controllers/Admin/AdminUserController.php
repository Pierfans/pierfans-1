<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualCredit;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Lista todos os usuários do sistema
     */
    public function index(Request $request)
    {
        $query = User::orderBy('created_at', 'desc');

        // Busca por nome ou email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20)->appends($request->query());

        return view('admin.users.index', compact('users'));
    }

    /**
     * Busca usuários via AJAX (para autocomplete)
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Digite pelo menos 2 caracteres',
            ]);
        }

        $users = User::where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'email')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }

    /**
     * Mostra os detalhes completos de um usuário
     */
    public function show($id)
    {
        $user = User::with([
            'subscriptions.creator',
            'subscriptions.plan',
            'referrals.referredUser',
            'referrals.creator'
        ])->findOrFail($id);

        // Assinaturas do usuário (onde ele é o assinante)
        $userSubscriptions = Subscription::where('user_id', $user->id)
            ->with(['creator', 'plan'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Saldo do criador (se for criador aprovado)
        $availableBalance = null;
        $pendingBalance = null;
        if ($user->creator_status === 'approved') {
            $availableBalance = $user->getAvailableBalance();
            $pendingBalance = $user->getPendingBalance();
        }

        // Saldo do afiliado
        $affiliateAvailableBalance = $user->getAffiliateAvailableBalance();
        $affiliatePendingBalance = $user->getAffiliatePendingBalance();

        // Indicados (usuários que foram indicados por este usuário)
        $referredUsers = $user->referrals()
            ->with('referredUser')
            ->get()
            ->map(function ($referral) {
                return $referral->referredUser;
            })
            ->filter();

        // Créditos manuais
        $manualCredits = $user->manualCredits()
            ->with('adminUser')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalManualCredits = $manualCredits->sum('amount');
        $totalCreatorCredits = $manualCredits->where('type', 'creator')->sum('amount');
        $totalAffiliateCredits = $manualCredits->where('type', 'affiliate')->sum('amount');

        return view('admin.users.show', compact(
            'user',
            'userSubscriptions',
            'availableBalance',
            'pendingBalance',
            'affiliateAvailableBalance',
            'affiliatePendingBalance',
            'referredUsers',
            'manualCredits',
            'totalManualCredits',
            'totalCreatorCredits',
            'totalAffiliateCredits'
        ));
    }

    /**
     * Exibe o formulário de edição do usuário/criador
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Atualiza os dados do usuário/criador
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'slug' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        $isCreator = in_array($user->creator_status, ['pending', 'approved', 'rejected']);
        if ($isCreator) {
            $rules['creator_full_name'] = ['nullable', 'string', 'max:255'];
            $rules['creator_cpf'] = ['nullable', 'string', 'size:11', 'regex:/^[0-9]{11}$/'];
            $rules['creator_birth_date'] = ['nullable', 'date', 'before:today'];
            $rules['creator_phone'] = ['nullable', 'string', 'max:20'];
            $rules['creator_zipcode'] = ['nullable', 'string', 'size:8', 'regex:/^[0-9]{8}$/'];
            $rules['creator_address'] = ['nullable', 'string', 'max:255'];
            $rules['creator_address_number'] = ['nullable', 'string', 'max:20'];
            $rules['creator_address_complement'] = ['nullable', 'string', 'max:255'];
            $rules['creator_neighborhood'] = ['nullable', 'string', 'max:255'];
            $rules['creator_city'] = ['nullable', 'string', 'max:255'];
            $rules['creator_state'] = ['nullable', 'string', 'size:2'];
            $rules['creator_bank_name'] = ['nullable', 'string', 'max:255'];
            $rules['creator_bank_agency'] = ['nullable', 'string', 'max:20'];
            $rules['creator_bank_account'] = ['nullable', 'string', 'max:20'];
            $rules['creator_bank_account_type'] = ['nullable', Rule::in(['checking', 'savings'])];
            $rules['creator_pix_key'] = ['nullable', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? null,
            'slug' => $validated['slug'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($isCreator) {
            $data['creator_full_name'] = $validated['creator_full_name'] ?? null;
            $data['creator_cpf'] = $validated['creator_cpf'] ?? null;
            $data['creator_birth_date'] = $validated['creator_birth_date'] ?? null;
            $data['creator_phone'] = $validated['creator_phone'] ?? null;
            $data['creator_zipcode'] = $validated['creator_zipcode'] ?? null;
            $data['creator_address'] = $validated['creator_address'] ?? null;
            $data['creator_address_number'] = $validated['creator_address_number'] ?? null;
            $data['creator_address_complement'] = $validated['creator_address_complement'] ?? null;
            $data['creator_neighborhood'] = $validated['creator_neighborhood'] ?? null;
            $data['creator_city'] = $validated['creator_city'] ?? null;
            $data['creator_state'] = $validated['creator_state'] ?? null;
            $data['creator_bank_name'] = $validated['creator_bank_name'] ?? null;
            $data['creator_bank_agency'] = $validated['creator_bank_agency'] ?? null;
            $data['creator_bank_account'] = $validated['creator_bank_account'] ?? null;
            $data['creator_bank_account_type'] = $validated['creator_bank_account_type'] ?? null;
            $data['creator_pix_key'] = $validated['creator_pix_key'] ?? null;
        }

        $user->update($data);

        Log::info('ADMIN UPDATED USER', [
            'admin_user_id' => Auth::id(),
            'user_id' => $user->id,
        ]);

        $redirect = $isCreator && in_array($user->creator_status, ['pending', 'approved', 'rejected'])
            ? route('admin.creators.show', $user->id)
            : route('admin.users.show', $user->id);

        return redirect($redirect)->with('success', 'Dados atualizados com sucesso!');
    }

    /**
     * Adiciona crédito manual para um usuário
     */
    public function addCredit(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'type' => ['required', 'in:creator,affiliate'],
            'reason' => ['nullable', 'string', 'max:255'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'amount.required' => 'O valor é obrigatório.',
            'amount.numeric' => 'O valor deve ser um número.',
            'amount.min' => 'O valor mínimo é R$ 0,01.',
            'amount.max' => 'O valor máximo é R$ 999.999,99.',
            'type.required' => 'O tipo de crédito é obrigatório.',
            'type.in' => 'Tipo de crédito inválido.',
        ]);

        DB::beginTransaction();
        try {
            $manualCredit = ManualCredit::create([
                'user_id' => $user->id,
                'type' => $validated['type'],
                'admin_user_id' => Auth::id(),
                'amount' => $validated['amount'],
                'reason' => $validated['reason'] ?? null,
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            DB::commit();

            Log::info('ADMIN ADD MANUAL CREDIT', [
                'admin_user_id' => Auth::id(),
                'user_id' => $user->id,
                'amount' => $validated['amount'],
                'manual_credit_id' => $manualCredit->id,
            ]);

            // Retorna o saldo correto baseado no tipo de crédito
            $newBalance = match($validated['type']) {
                'creator' => $user->fresh()->getAvailableBalance(),
                'affiliate' => $user->fresh()->getAffiliateAvailableBalance(),
                default => 0,
            };

            return response()->json([
                'success' => true,
                'message' => 'Crédito adicionado com sucesso!',
                'credit' => $manualCredit->load('adminUser'),
                'new_balance' => $newBalance,
                'type' => $validated['type'],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('ADMIN ADD MANUAL CREDIT ERROR', [
                'admin_user_id' => Auth::id(),
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar crédito: ' . $e->getMessage(),
            ], 500);
        }
    }
}
