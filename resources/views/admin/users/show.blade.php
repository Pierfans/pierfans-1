@extends('layouts.admin')

@section('title', 'Detalhes do Usuário')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $user->name }}</h1>
                    <p class="text-gray-600 mt-2">{{ $user->email }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.users.edit', $user->id) }}"
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Editar usuário
                    </a>
                    <a href="{{ route('admin.users.index') }}"
                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Voltar
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Coluna Principal -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Dados do Usuário -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Dados do Usuário</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Nome</label>
                            <p class="text-sm text-gray-900">{{ $user->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                            <p class="text-sm text-gray-900">{{ $user->email }}</p>
                        </div>
                        @if($user->username)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Username</label>
                            <p class="text-sm text-gray-900"><span>@</span> {{ $user->username }}</p>
                        </div>
                        @endif
                        @if($user->slug)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Slug</label>
                            <p class="text-sm text-gray-900">{{ $user->slug }}</p>
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Status Criador</label>
                            @if($user->creator_status === 'approved')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aprovado</span>
                            @elseif($user->creator_status === 'pending')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendente</span>
                            @elseif($user->creator_status === 'rejected')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rejeitado</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Não é criador</span>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Data de Cadastro</label>
                            <p class="text-sm text-gray-900">{{ $user->created_at->emBrasilia()->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Assinaturas do Usuário -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Assinaturas</h2>
                    @if($userSubscriptions->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Criador</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plano</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($userSubscriptions as $subscription)
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $subscription->creator->name ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $subscription->plan->name ?? 'N/A' }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">R$ {{ number_format($subscription->total_amount, 2, ',', '.') }}</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if($subscription->is_active && $subscription->end_date >= now()->toDateString())
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Ativa</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Expirada</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="text-sm text-gray-500">
                                                    {{ $subscription->start_date->format('d/m/Y') }} - {{ $subscription->end_date->format('d/m/Y') }}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-600 text-center py-4">Nenhuma assinatura encontrada.</p>
                    @endif
                </div>

                <!-- Indicados -->
                @if($referredUsers->count() > 0)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Usuários Indicados</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data de Cadastro</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($referredUsers as $referredUser)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $referredUser->name }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ $referredUser->email }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ $referredUser->created_at->emBrasilia()->format('d/m/Y') }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.users.show', $referredUser->id) }}"
                                               class="text-blue-600 hover:text-blue-900">Ver Detalhes</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Saldo do Criador -->
                @if($user->creator_status === 'approved')
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Saldo do Criador</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Saldo Disponível</label>
                            <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($availableBalance, 2, ',', '.') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Saldo Bloqueado</label>
                            <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($pendingBalance, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Saldo do Afiliado -->
                @if($affiliateAvailableBalance > 0 || $affiliatePendingBalance > 0)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Saldo do Afiliado</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Saldo Disponível</label>
                            <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($affiliateAvailableBalance, 2, ',', '.') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Saldo Bloqueado</label>
                            <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($affiliatePendingBalance, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Adicionar Crédito -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Adicionar Crédito</h2>
                    <form id="addCreditForm" class="space-y-4">
                        @csrf
                        <div>
                            <label for="credit_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Tipo de Crédito *
                            </label>
                            <select
                                id="credit_type"
                                name="type"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">Selecione o tipo...</option>
                                <option value="creator">Criador de Conteúdo</option>
                                <option value="affiliate">Afiliado</option>
                            </select>
                        </div>
                        <div>
                            <label for="credit_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Valor (R$)
                            </label>
                            <input
                                type="number"
                                id="credit_amount"
                                name="amount"
                                step="0.01"
                                min="0.01"
                                max="999999.99"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="0.00"
                            >
                        </div>
                        <div>
                            <label for="credit_reason" class="block text-sm font-medium text-gray-700 mb-2">
                                Motivo (opcional)
                            </label>
                            <input
                                type="text"
                                id="credit_reason"
                                name="reason"
                                maxlength="255"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Ex: Bonificação, Reembolso, etc."
                            >
                        </div>
                        <div>
                            <label for="credit_admin_notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Observações (opcional)
                            </label>
                            <textarea
                                id="credit_admin_notes"
                                name="admin_notes"
                                rows="3"
                                maxlength="1000"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Observações internas..."
                            ></textarea>
                        </div>
                        <button
                            type="submit"
                            id="addCreditBtn"
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
                        >
                            Adicionar Crédito
                        </button>
                    </form>
                    @if($totalManualCredits > 0)
                    <div class="mt-4 pt-4 border-t border-gray-200 space-y-2">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">Total de créditos manuais:</span>
                            <span class="text-green-600 font-bold">R$ {{ number_format($totalManualCredits, 2, ',', '.') }}</span>
                        </p>
                        @if($totalCreatorCredits > 0)
                        <p class="text-xs text-gray-500">
                            <span>Criador:</span> <span class="text-blue-600">R$ {{ number_format($totalCreatorCredits, 2, ',', '.') }}</span>
                        </p>
                        @endif
                        @if($totalAffiliateCredits > 0)
                        <p class="text-xs text-gray-500">
                            <span>Afiliado:</span> <span class="text-green-600">R$ {{ number_format($totalAffiliateCredits, 2, ',', '.') }}</span>
                        </p>
                        @endif
                    </div>
                    @endif
                </div>

                <!-- Estatísticas -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Estatísticas</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Total de Assinaturas</span>
                            <span class="text-sm font-medium text-gray-900">{{ $userSubscriptions->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Assinaturas Ativas</span>
                            <span class="text-sm font-medium text-gray-900">
                                {{ $userSubscriptions->where('is_active', true)->where('end_date', '>=', now()->toDateString())->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Usuários Indicados</span>
                            <span class="text-sm font-medium text-gray-900">{{ $referredUsers->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Histórico de Créditos Manuais -->
        @if($manualCredits->count() > 0)
        <div class="mt-6 bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Histórico de Créditos Manuais</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motivo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adicionado por</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Observações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($manualCredits as $credit)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $credit->created_at->emBrasilia()->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($credit->type === 'creator')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Criador</span>
                                    @elseif($credit->type === 'affiliate')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Afiliado</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{ $credit->type ?? 'Criador' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-green-600">R$ {{ number_format($credit->amount, 2, ',', '.') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-900">{{ $credit->reason ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $credit->adminUser->name ?? 'Sistema' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-500">{{ $credit->admin_notes ?? '-' }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('addCreditForm');
            const submitBtn = document.getElementById('addCreditBtn');

            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const type = document.getElementById('credit_type').value;
                    if (!type) {
                        alert('Por favor, selecione o tipo de crédito.');
                        return;
                    }

                    const amount = parseFloat(document.getElementById('credit_amount').value);
                    if (isNaN(amount) || amount <= 0) {
                        alert('Por favor, insira um valor válido maior que zero.');
                        return;
                    }

                    // Desabilita o botão
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Processando...';

                    const formData = new FormData(form);
                    formData.append('_token', '{{ csrf_token() }}');

                    fetch('{{ route("admin.users.add-credit", $user->id) }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const typeLabels = {
                                'creator': 'Criador de Conteúdo',
                                'affiliate': 'Afiliado',
                            };
                            const typeLabel = typeLabels[data.type] || 'Crédito';
                            alert(`${typeLabel}: Crédito de R$ ${parseFloat(data.credit.amount).toFixed(2).replace('.', ',')} adicionado com sucesso!`);
                            // Recarrega a página para atualizar os dados
                            window.location.reload();
                        } else {
                            alert('Erro: ' + (data.message || 'Erro ao adicionar crédito.'));
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Adicionar Crédito';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Erro ao processar requisição. Tente novamente.');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Adicionar Crédito';
                    });
                });
            }
        });
    </script>
@endsection

