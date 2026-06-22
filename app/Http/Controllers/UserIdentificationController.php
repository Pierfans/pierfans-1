<?php

namespace App\Http\Controllers;

use App\Helpers\CpfValidator;
use App\Models\UserIdentification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserIdentificationController extends Controller
{
    /**
     * Mostra o formulário de cadastro de dados de identificação
     * BLOQUEIO: Não exibe o formulário se já tiver dados completos
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        
        // BLOQUEIO: Se já tem dados completos, redireciona automaticamente
        if ($user->hasCompleteIdentification()) {
            $planId = $request->get('plan_id');
            $paymentMethod = $request->get('payment_method');
            
            // Se tem plano e método, redireciona para checkout
            if ($planId && $paymentMethod) {
                return redirect()->route('checkout.show', [
                    'planId' => $planId,
                    'method' => $paymentMethod,
                ])->with('info', 'Seus dados de identificação já estão cadastrados.');
            }
            
            // Se veio da carteira, redireciona de volta para a carteira
            if ($request->get('wallet')) {
                $amount = session('wallet_deposit_amount');
                session()->forget('wallet_deposit_amount');
                
                if ($amount) {
                    return redirect()->route('wallet.index')
                        ->with('info', 'Seus dados de identificação já estão cadastrados.')
                        ->with('deposit_amount', $amount);
                }
                
                return redirect()->route('wallet.index')
                    ->with('info', 'Seus dados de identificação já estão cadastrados.');
            }
            
            // Caso contrário, redireciona para dashboard
            return redirect()->route('dashboard')->with('info', 'Seus dados de identificação já estão cadastrados.');
        }
        
        // Se não tem dados completos, permite exibir o formulário
        $identification = $user->identification ?? new \App\Models\UserIdentification();
        
        // Plano selecionado (vem da sessão ou query string)
        $planId = $request->get('plan_id');
        $paymentMethod = $request->get('payment_method'); // 'card' ou 'pix'
        
        return view('user-identification.create', compact('identification', 'planId', 'paymentMethod'));
    }

    /**
     * Salva os dados de identificação (apenas primeira vez, imutável após salvar)
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // BLOQUEIO: Se já tem dados completos, não permite sobrescrever
        if ($user->hasCompleteIdentification()) {
            $planId = $request->get('plan_id');
            $paymentMethod = $request->get('payment_method');
            
            // Se tem plano e método, redireciona para checkout
            if ($planId && $paymentMethod) {
                return redirect()->route('checkout.show', [
                    'planId' => $planId,
                    'method' => $paymentMethod,
                ])->with('info', 'Seus dados de identificação já estão cadastrados e não podem ser alterados.');
            }
            
            // Caso contrário, redireciona para dashboard
            return redirect()->route('dashboard')->with('info', 'Seus dados de identificação já estão cadastrados e não podem ser alterados.');
        }
        
        // Remove formatação dos campos
        $document = preg_replace('/\D/', '', $request->document);
        $phoneNumber = preg_replace('/\D/', '', $request->phone_number);
        $zipCode = preg_replace('/\D/', '', $request->zip_code);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document' => [
                'required',
                'string',
                'size:14', // CPF formatado: 000.000.000-00
                function ($attribute, $value, $fail) use ($document) {
                    // Verifica se tem exatamente 11 dígitos (apenas CPF)
                    if (strlen($document) !== 11) {
                        $fail('O campo CPF deve conter exatamente 11 dígitos.');
                    }
                    // Valida CPF
                    if (!CpfValidator::validate($document)) {
                        $fail('O CPF informado é inválido.');
                    }
                },
            ],
            'phone_number' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($phoneNumber) {
                    // Verifica se tem exatamente 11 dígitos (celular)
                    if (strlen($phoneNumber) !== 11) {
                        $fail('O campo telefone deve ser um número de celular com 11 dígitos.');
                    }
                    // Verifica se começa com 9 (celular)
                    if (strlen($phoneNumber) === 11 && substr($phoneNumber, 2, 1) !== '9') {
                        $fail('Por favor, insira um número de celular válido.');
                    }
                },
            ],
            'cod_ibge' => 'required|string|max:10',
            'street' => 'required|string|max:255',
            'number' => 'required|string|max:20',
            'complement' => 'nullable|string|max:255',
            'zip_code' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($zipCode) {
                    // Verifica se tem exatamente 8 dígitos
                    if (strlen($zipCode) !== 8) {
                        $fail('O CEP deve conter exatamente 8 dígitos.');
                    }
                },
            ],
            'neighborhood' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|size:2',
            'plan_id' => 'nullable|exists:subscription_plans,id',
            'payment_method' => 'nullable|in:card,pix',
        ]);

        // Remove formatação e salva apenas números
        $validated['document'] = $document;
        $validated['phone_number'] = $phoneNumber;
        $validated['zip_code'] = $zipCode;

        // Remove plan_id e payment_method do validated (não são campos da tabela)
        $planId = $validated['plan_id'] ?? null;
        $paymentMethod = $validated['payment_method'] ?? null;
        unset($validated['plan_id'], $validated['payment_method']);

        // Verifica se já existe (dupla verificação de segurança - imutável após primeiro salvamento)
        if ($user->identification && $user->identification->isComplete()) {
            // Se já existe e está completo, redireciona (não permite sobrescrever)
            $planId = $request->get('plan_id');
            $paymentMethod = $request->get('payment_method');
            
            if ($planId && $paymentMethod) {
                return redirect()->route('checkout.show', [
                    'planId' => $planId,
                    'method' => $paymentMethod,
                ])->with('info', 'Seus dados de identificação já estão cadastrados e não podem ser alterados.');
            }
            
            return redirect()->route('dashboard')->with('info', 'Seus dados de identificação já estão cadastrados e não podem ser alterados.');
        }
        
        // Cria ou atualiza os dados de identificação (apenas se não estiver completo)
        if ($user->identification) {
            // Se existe mas não está completo, atualiza
            $user->identification()->update($validated);
        } else {
            // Se não existe, cria
            $user->identification()->create($validated);
        }

        // SEMPRE redireciona após salvar (não permite permanecer no formulário)
        if ($planId && $paymentMethod) {
            // Redireciona para o checkout correspondente
            return redirect()->route('checkout.show', [
                'planId' => $planId,
                'method' => $paymentMethod,
            ])->with('success', 'Dados salvos com sucesso! Redirecionando para o checkout...');
        }

        // Se veio da carteira (wallet), redireciona de volta para a carteira
        if ($request->get('wallet')) {
            $amount = session('wallet_deposit_amount');
            session()->forget('wallet_deposit_amount');
            
            if ($amount) {
                // Redireciona para a página da carteira com o valor pré-preenchido
                return redirect()->route('wallet.index')
                    ->with('success', 'Dados salvos com sucesso! Você pode prosseguir com o depósito.')
                    ->with('deposit_amount', $amount);
            }
            
            return redirect()->route('wallet.index')
                ->with('success', 'Dados salvos com sucesso!');
        }

        // Se não tem plano/método, redireciona para dashboard
        return redirect()->route('dashboard')->with('success', 'Dados salvos com sucesso!');
    }
}
