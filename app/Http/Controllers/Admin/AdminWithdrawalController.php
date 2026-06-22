<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminWithdrawalController extends Controller
{
    /**
     * Lista todos os saques
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'pending'); // pending, all, transferred, rejected

        $query = Withdrawal::with(['user', 'bankAccount'])
            ->orderBy('created_at', 'desc');

        if ($filter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($filter === 'transferred') {
            $query->where('status', 'transferred');
        } elseif ($filter === 'rejected') {
            $query->where('status', 'rejected');
        }

        $withdrawals = $query->paginate(20);

        // Contadores
        $pendingCount = Withdrawal::where('status', 'pending')->count();
        $transferredCount = Withdrawal::where('status', 'transferred')->count();
        $rejectedCount = Withdrawal::where('status', 'rejected')->count();
        $totalCount = Withdrawal::count();

        return view('admin.withdrawals.index', compact('withdrawals', 'filter', 'pendingCount', 'transferredCount', 'rejectedCount', 'totalCount'));
    }

    /**
     * Mostra os detalhes de um saque
     */
    public function show($id)
    {
        $withdrawal = Withdrawal::with(['user', 'bankAccount'])->findOrFail($id);
        return view('admin.withdrawals.show', compact('withdrawal'));
    }

    /**
     * Aprova um saque e realiza transferência PIX via SuitPay
     */
    public function approve($id, Request $request)
    {
        DB::beginTransaction();
        try {
            $withdrawal = Withdrawal::with(['user', 'bankAccount'])
                ->where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($withdrawal->status !== 'pending') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Este saque já foi processado.',
                ], 400);
            }

            if (!$withdrawal->bankAccount) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Conta bancária não encontrada para este saque.',
                ], 400);
            }

            if (!$withdrawal->bankAccount->pix_key || !$withdrawal->bankAccount->pix_key_type) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Chave PIX não configurada na conta bancária.',
                ], 400);
            }

            $externalId = (string) Str::uuid();

            $suitPayController = new \App\Http\Controllers\SuitPayController();
            $suitPayResponse = $suitPayController->pixTransfer(
                $withdrawal->bankAccount->pix_key,
                $withdrawal->bankAccount->pix_key_type,
                (float) $withdrawal->amount,
                $externalId
            );

            Log::info('ADMIN WITHDRAWAL APPROVE - RESPOSTA SUITPAY', [
                'withdrawal_id' => $withdrawal->id,
                'suitpay_response' => $suitPayResponse,
            ]);

            $updateData = [
                'processed_at' => now(),
                'admin_notes' => $request->input('notes'),
                'suitpay_external_id' => $externalId,
                'suitpay_response_data' => $suitPayResponse,
            ];

            if ($suitPayResponse['success'] && $suitPayResponse['status'] === 'PAID_OUT') {
                $updateData['status'] = 'transferred';
                $updateData['suitpay_transaction_id'] = $suitPayResponse['transaction_id'] ?? null;

                $withdrawal->update($updateData);
                DB::commit();

                Log::info('ADMIN WITHDRAWAL APPROVE - SAQUE APROVADO E TRANSFERIDO', [
                    'withdrawal_id' => $withdrawal->id,
                    'transaction_id' => $suitPayResponse['transaction_id'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Saque aprovado e transferência PIX realizada com sucesso!',
                    'transaction_id' => $suitPayResponse['transaction_id'],
                ]);
            }

            $withdrawal->update($updateData);
            DB::commit();

            $errorMessage = $suitPayResponse['message'] ?? 'Erro desconhecido ao processar transferência PIX.';

            Log::warning('ADMIN WITHDRAWAL APPROVE - ERRO NA TRANSFERÊNCIA SUITPAY', [
                'withdrawal_id' => $withdrawal->id,
                'error' => $errorMessage,
                'response_code' => $suitPayResponse['response_code'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Saque aprovado, mas a transferência PIX falhou: ' . $errorMessage,
                'suitpay_error' => $suitPayResponse['response_code'] ?? null,
            ], 400);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('ADMIN WITHDRAWAL APPROVE - EXCEÇÃO', [
                'withdrawal_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar aprovação: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reprova um saque
     */
    public function reject($id, Request $request)
    {
        $withdrawal = Withdrawal::with('user')->findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Este saque não está pendente de aprovação.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $withdrawal->update([
                'status' => 'rejected',
                'processed_at' => now(),
                'admin_notes' => $request->input('notes'),
            ]);

            // O valor volta automaticamente para o saldo disponível
            // (será considerado no cálculo de getAvailableBalance)

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Saque reprovado. O valor foi estornado para o saldo liberado do criador.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar reprovação: ' . $e->getMessage(),
            ], 500);
        }
    }
}
