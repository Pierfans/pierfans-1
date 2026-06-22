<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminWalletController extends Controller
{
    /**
     * Lista todos os usuários com suas carteiras
     */
    public function index(Request $request)
    {
        $query = User::with('wallet');

        // Busca por nome ou email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20)->appends($request->query());

        return view('admin.wallets.index', compact('users'));
    }

    /**
     * Mostra os detalhes da carteira de um usuário
     */
    public function show($id)
    {
        $user = User::with('wallet')->findOrFail($id);
        
        // Obtém ou cria a carteira do usuário
        $wallet = $user->getOrCreateWallet();
        
        // Busca transações da carteira
        $transactions = $wallet->transactions()
            ->with('adminUser')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.wallets.show', compact('user', 'wallet', 'transactions'));
    }

    /**
     * Adiciona saldo à carteira do usuário
     */
    public function addBalance(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'description' => ['nullable', 'string', 'max:255'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'amount.required' => 'O valor é obrigatório.',
            'amount.numeric' => 'O valor deve ser um número.',
            'amount.min' => 'O valor mínimo é R$ 0,01.',
            'amount.max' => 'O valor máximo é R$ 999.999,99.',
        ]);

        DB::beginTransaction();
        try {
            // Obtém ou cria a carteira do usuário
            $wallet = $user->getOrCreateWallet();
            
            // Adiciona saldo
            $transaction = $wallet->addBalance(
                $validated['amount'],
                Auth::id(),
                $validated['description'] ?? null,
                $validated['admin_notes'] ?? null
            );

            DB::commit();

            Log::info('ADMIN ADD WALLET BALANCE', [
                'admin_user_id' => Auth::id(),
                'user_id' => $user->id,
                'amount' => $validated['amount'],
                'transaction_id' => $transaction->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Saldo adicionado com sucesso!',
                'transaction' => $transaction->load('adminUser'),
                'new_balance' => $wallet->fresh()->balance,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('ADMIN ADD WALLET BALANCE ERROR', [
                'admin_user_id' => Auth::id(),
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar saldo: ' . $e->getMessage(),
            ], 500);
        }
    }
}
