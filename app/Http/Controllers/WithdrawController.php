<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\PlatformSetting;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WithdrawController extends Controller
{
    /**
     * Mostra a página de saque
     */
    public function index()
    {
        $user = Auth::user();
        
        // Verifica se é criador aprovado
        if ($user->creator_status !== 'approved') {
            return redirect()->route('dashboard')->with('error', 'Você precisa ser um criador aprovado para acessar esta página.');
        }

        // Saldos (mock)
        $availableBalance = $user->getAvailableBalance();
        $pendingBalance = $user->getPendingBalance();
        // Chamadas pedidas e ainda não realizadas: nem dela nem do fã. Só pra ela saber que existe.
        $reservedCalls = \App\Models\VideoCall::reservado($user->id);

        // Configurações de saque
        $minWithdrawAmount = PlatformSetting::getMinWithdrawAmount();
        $dailyWithdrawLimit = PlatformSetting::getDailyWithdrawLimit();

        // Extrato (últimas 5 transações)
        $extract = Withdrawal::where('user_id', $user->id)
            ->with('bankAccount')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Contas bancárias
        $bankAccounts = BankAccount::where('user_id', $user->id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Saldo negativo tem o motivo escrito no ultimo lancamento negativo (hoje so o
        // estorno de cartao gera isso). Sem isso a criadora ve um numero negativo e nada
        // explicando (bento 21/09: "informa o porque isso ta acontecendo").
        $ultimoDebito = $availableBalance < 0
            ? $user->manualCredits()->where('type', 'creator')->where('amount', '<', 0)->latest()->first()
            : null;

        return view('withdraw.index', compact('availableBalance', 'pendingBalance', 'reservedCalls', 'extract', 'bankAccounts', 'minWithdrawAmount', 'dailyWithdrawLimit', 'ultimoDebito'));
    }

    /**
     * Cria uma solicitação de saque
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Verifica se é criador aprovado
        if ($user->creator_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Você precisa ser um criador aprovado.',
            ], 403);
        }

        $minWithdrawAmount = PlatformSetting::getMinWithdrawAmount();
        
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:' . $minWithdrawAmount, 'max:999999.99'],
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ], [
            'amount.min' => 'O valor mínimo para saque é de R$ ' . number_format($minWithdrawAmount, 2, ',', '.'),
            'bank_account_id.required' => 'Selecione uma conta bancária',
            'bank_account_id.exists' => 'Conta bancária inválida',
        ]);

        // Verifica se a conta pertence ao usuário
        $bankAccount = BankAccount::where('id', $validated['bank_account_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Cria a solicitação de saque (teto diário + taxa avaliados dentro da transação)
        DB::beginTransaction();
        try {
            $assess = Withdrawal::assessDailyFee($user->id, 'creator', (float) $validated['amount']);
            if (!$assess['allowed']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Você atingiu o limite de {$assess['limit']} saques por dia.",
                ], 400);
            }
            $fee = $assess['fee'];

            // Saldo precisa cobrir o valor + a taxa do saque
            $availableBalance = $user->getAvailableBalance();
            if ($validated['amount'] + $fee > $availableBalance) {
                DB::rollBack();
                $msg = $fee > 0
                    ? 'Saldo insuficiente para o valor + a taxa de R$ ' . number_format($fee, 2, ',', '.') . ' deste saque.'
                    : 'Valor solicitado excede o saldo disponível para saque.';
                return response()->json(['success' => false, 'message' => $msg], 400);
            }

            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'type' => 'creator',
                'bank_account_id' => $bankAccount->id,
                'amount' => $validated['amount'],
                'fee' => $fee,
                'status' => 'pending',
            ]);

            DB::commit();

            // Saque automatico (bento 21/09): ate o teto vai direto pro SuitPay, sem
            // esperar o admin; acima disso segue pendente, o fluxo de sempre.
            // Roda DEPOIS do commit de proposito: chamada HTTP nao pode segurar o lock
            // da linha do saque. Se falhar, o saque fica pendente e o admin aprova na mao,
            // que e exatamente o que ja acontece quando o SuitPay recusa na aprovacao.
            $autoLimit = PlatformSetting::getAutoWithdrawLimit();
            $enviadoAgora = false;
            if ($autoLimit > 0
                && (float) $withdrawal->amount <= $autoLimit
                && $bankAccount->pix_key
                && $bankAccount->pix_key_type) {
                try {
                    $withdrawal->sendPixTransfer();
                    $enviadoAgora = $withdrawal->status === 'transferred';
                } catch (\Exception $e) {
                    \Log::error('SAQUE AUTOMATICO - ERRO AO TRANSFERIR', [
                        'withdrawal_id' => $withdrawal->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => $enviadoAgora
                    ? 'Saque enviado! O PIX cai na sua conta em instantes.'
                    : 'Solicitação de saque criada com sucesso!',
                'withdrawal' => $withdrawal->load('bankAccount'),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erro ao criar saque: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar solicitação. Tente novamente.',
            ], 500);
        }
    }

    /**
     * Cria ou atualiza uma conta bancária
     */
    public function storeBankAccount(Request $request)
    {
        $user = Auth::user();
        
        // Verifica se pode gerenciar contas bancárias
        // Permite se: criador aprovado ou tem comissões de afiliado
        $canManageBankAccounts = $user->creator_status === 'approved' 
            || \App\Models\Subscription::where('referrer_user_id', $user->id)->where('referrer_amount', '>', 0)->exists();
        
        if (!$canManageBankAccounts) {
            return response()->json([
                'success' => false,
                'message' => 'Você precisa ser um criador aprovado, afiliado ou afiliado PRO para gerenciar contas bancárias.',
            ], 403);
        }

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_code' => 'required|string|max:10',
            'account_type' => 'required|in:corrente,poupanca',
            'agency' => 'required|string|max:20',
            'account_number' => 'required|string|max:20',
            'pix_key_type' => 'required|in:cpf,email,telefone,aleatoria',
            'pix_key' => 'required|string|max:255',
            'is_primary' => 'boolean',
        ]);

        // Se for marcada como primária, remove primary de outras
        if ($request->input('is_primary', false)) {
            BankAccount::where('user_id', $user->id)
                ->update(['is_primary' => false]);
        }

        // Se não houver conta primária, esta será a primeira
        $hasPrimary = BankAccount::where('user_id', $user->id)
            ->where('is_primary', true)
            ->exists();
        
        if (!$hasPrimary) {
            $validated['is_primary'] = true;
        }

        $bankAccount = BankAccount::create([
            'user_id' => $user->id,
            ...$validated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conta bancária cadastrada com sucesso!',
            'bank_account' => $bankAccount,
        ]);
    }

    /**
     * Retorna os dados de uma conta bancária
     */
    public function getBankAccount($id)
    {
        $user = Auth::user();
        
        $bankAccount = BankAccount::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'bank_account' => $bankAccount,
        ]);
    }

    /**
     * Atualiza uma conta bancária
     */
    public function updateBankAccount(Request $request, $id)
    {
        $user = Auth::user();
        
        $bankAccount = BankAccount::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'bank_code' => 'required|string|max:10',
            'account_type' => 'required|in:corrente,poupanca',
            'agency' => 'required|string|max:20',
            'account_number' => 'required|string|max:20',
            'pix_key_type' => 'required|in:cpf,email,telefone,aleatoria',
            'pix_key' => 'required|string|max:255',
            'is_primary' => 'boolean',
        ]);

        // Se for marcada como primária, remove primary de outras
        if ($request->input('is_primary', false)) {
            BankAccount::where('user_id', $user->id)
                ->where('id', '!=', $id)
                ->update(['is_primary' => false]);
        }

        $bankAccount->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Conta bancária atualizada com sucesso!',
            'bank_account' => $bankAccount,
        ]);
    }

    /**
     * Remove uma conta bancária
     */
    public function deleteBankAccount($id)
    {
        $user = Auth::user();
        
        $bankAccount = BankAccount::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Não permite excluir se houver saques pendentes usando esta conta
        $hasPendingWithdrawals = Withdrawal::where('bank_account_id', $bankAccount->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingWithdrawals) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível excluir uma conta com saques pendentes.',
            ], 400);
        }

        $bankAccount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conta bancária excluída com sucesso!',
        ]);
    }
}
