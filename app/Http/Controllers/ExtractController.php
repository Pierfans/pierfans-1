<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Subscription;
use App\Models\Withdrawal;
use App\Models\PostPurchase;
use Carbon\Carbon;

class ExtractController extends Controller
{
    /**
     * Mostra a página de extrato completo
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Verifica se é criador aprovado
        if ($user->creator_status !== 'approved') {
            return redirect()->route('dashboard')->with('error', 'Você precisa ser um criador aprovado para acessar esta página.');
        }

        // Filtros
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status');

        // Busca apenas saques do criador
        $withdrawals = Withdrawal::where('user_id', $user->id)
            ->where('type', 'creator')
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            })
            ->when($status && $status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->with('bankAccount')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($withdrawal) {
                $statusLabels = [
                    'pending' => 'Pendente',
                    'transferred' => 'Completa',
                    'rejected' => 'Reprovado',
                ];
                
                return [
                    'id' => 'with_' . $withdrawal->id,
                    'type' => 'withdrawal',
                    'type_label' => 'Saque',
                    'amount' => $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'status_label' => $statusLabels[$withdrawal->status] ?? $withdrawal->status,
                    'date' => $withdrawal->created_at,
                    'data' => $withdrawal,
                ];
            });

        // Vendas PPV do criador
        $ppvSales = PostPurchase::where('creator_id', $user->id)
            ->with(['post', 'buyer'])
            ->when($startDate, fn($q) => $q->whereDate('purchased_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('purchased_at', '<=', $endDate))
            ->orderBy('purchased_at', 'desc')
            ->get()
            ->map(function ($purchase) {
                return [
                    'id'           => 'ppv_' . $purchase->id,
                    'type'         => 'ppv_sale',
                    'type_label'   => 'Venda PPV',
                    'amount'       => $purchase->creator_amount,
                    'status'       => 'confirmed',
                    'status_label' => 'Confirmado',
                    'date'         => $purchase->purchased_at,
                    'data'         => $purchase,
                ];
            });

        // Mescla saques e vendas PPV; quando filtro por status de saque, oculta PPV
        if (!$status || $status === 'all') {
            $transactions = $withdrawals->concat($ppvSales)->sortByDesc('date')->values();
        } else {
            $transactions = $withdrawals;
        }

        // Prepara dados para JavaScript
        $transactionsForJs = $transactions->map(function ($t) {
            $data = $t['data'];

            if ($t['type'] === 'ppv_sale') {
                return [
                    'id'            => $t['id'],
                    'type'          => $t['type'],
                    'amount'        => $data->creator_amount,
                    'amount_paid'   => $data->amount_paid,
                    'status'        => 'confirmed',
                    'purchased_at'  => $data->purchased_at->toISOString(),
                    'post_description' => $data->post ? mb_strimwidth($data->post->description ?? '', 0, 80, '...') : '',
                    'buyer_name'    => $data->buyer ? $data->buyer->name : '',
                ];
            }

            return [
                'id' => $t['id'],
                'type' => $t['type'],
                'amount' => $data->amount,
                'status' => $data->status,
                'created_at' => $data->created_at->toISOString(),
                'processed_at' => $data->processed_at ? $data->processed_at->toISOString() : null,
                'bank_account' => $data->bankAccount ? [
                    'bank_name' => $data->bankAccount->bank_name,
                    'pix_key_type' => $data->bankAccount->pix_key_type,
                    'pix_key' => $data->bankAccount->pix_key,
                ] : null,
            ];
        });

        $pendingBalance = $user->getPendingBalance();

        return view('extract.index', [
            'transactions' => $transactions,
            'transactionsForJs' => $transactionsForJs,
            'pendingBalance' => $pendingBalance,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status ?? 'all',
        ]);
    }
}
