@extends('layouts.admin')

@section('title', 'Assinaturas')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Gerenciamento de Assinaturas</h1>
            <p class="text-gray-600 mt-2">Gerencie assinaturas ativas e pendentes do sistema</p>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.subscriptions.index', ['filter' => 'all']) }}" 
                   class="px-4 py-2 rounded-lg {{ $filter === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Todas
                </a>
                <a href="{{ route('admin.subscriptions.index', ['filter' => 'active']) }}" 
                   class="px-4 py-2 rounded-lg {{ $filter === 'active' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Ativas ({{ $activeCount }})
                </a>
                <a href="{{ route('admin.subscriptions.index', ['filter' => 'pending']) }}" 
                   class="px-4 py-2 rounded-lg {{ $filter === 'pending' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Pendentes ({{ $pendingCount }})
                </a>
            </div>
        </div>

        <!-- Assinaturas Ativas -->
        @if($filter === 'all' || $filter === 'active')
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900">Assinaturas Ativas</h2>
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                        {{ $activeCount }} ativas
                    </span>
                </div>

                @if($activeSubscriptions && $activeSubscriptions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assinante</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criador</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plano</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Período</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ativação</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($activeSubscriptions as $subscription)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $subscription->user->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $subscription->user->email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $subscription->creator->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $subscription->plan->name ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">R$ {{ number_format($subscription->total_amount, 2, ',', '.') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($subscription->start_date)->format('d/m/Y') }} - 
                                                {{ \Carbon\Carbon::parse($subscription->end_date)->format('d/m/Y') }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($subscription->activated_manually)
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    Manual
                                                </span>
                                                @if($subscription->activatedByAdmin)
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        Por: {{ $subscription->activatedByAdmin->name }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    Pagamento
                                                </span>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ strtoupper($subscription->payment_method) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.subscriptions.show', $subscription->id) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">
                                                Ver detalhes
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    @if($activeSubscriptions->hasPages())
                        <div class="mt-4">
                            {{ $activeSubscriptions->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-8 text-gray-500">
                        <p>Nenhuma assinatura ativa encontrada.</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Assinaturas Pendentes -->
        @if($filter === 'all' || $filter === 'pending')
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900">Assinaturas Pendentes</h2>
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
                        {{ $pendingCount }} pendentes
                    </span>
                </div>

                @if($pendingSubscriptions && $pendingSubscriptions->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assinante</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criador</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plano</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($pendingSubscriptions as $subscription)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $subscription->user->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $subscription->user->email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $subscription->creator->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $subscription->plan->name ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">R$ {{ number_format($subscription->total_amount, 2, ',', '.') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if(!$subscription->is_active)
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                    Inativa
                                                </span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Expirada
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('admin.subscriptions.show', $subscription->id) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900">
                                                    Ver detalhes
                                                </a>
                                                <button onclick="openActivateModal({{ $subscription->id }})" 
                                                        class="text-green-600 hover:text-green-900">
                                                    Ativar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    @if($pendingSubscriptions->hasPages())
                        <div class="mt-4">
                            {{ $pendingSubscriptions->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-8 text-gray-500">
                        <p>Nenhuma assinatura pendente encontrada.</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Modal de Ativação -->
    <div id="activateModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Ativar Assinatura Manualmente</h3>
            
            <form id="activateForm">
                <input type="hidden" id="subscriptionId" name="subscription_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Motivo da Ativação (opcional)
                    </label>
                    <textarea 
                        id="activationReason" 
                        name="reason" 
                        rows="3" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Ex: Pagamento confirmado manualmente, acordo comercial, etc."
                    ></textarea>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-yellow-800">
                        <strong>Atenção:</strong> Ao ativar esta assinatura, o criador receberá seus benefícios normalmente e, se houver afiliado associado, a comissão será calculada e creditada automaticamente.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button 
                        type="button" 
                        onclick="closeActivateModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancelar
                    </button>
                    <button 
                        type="submit" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Confirmar Ativação
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function openActivateModal(subscriptionId) {
            document.getElementById('subscriptionId').value = subscriptionId;
            document.getElementById('activateModal').classList.remove('hidden');
            document.getElementById('activateModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeActivateModal() {
            document.getElementById('activateModal').classList.add('hidden');
            document.getElementById('activateModal').classList.remove('flex');
            document.body.style.overflow = '';
            document.getElementById('activateForm').reset();
        }

        document.getElementById('activateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const subscriptionId = document.getElementById('subscriptionId').value;
            const reason = document.getElementById('activationReason').value;

            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Ativando...';

            fetch(`/admin/subscriptions/${subscriptionId}/activate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Assinatura ativada com sucesso!');
                    window.location.reload();
                } else {
                    alert('Erro: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Confirmar Ativação';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao ativar assinatura. Tente novamente.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirmar Ativação';
            });
        });

        // Fecha modal ao clicar fora
        document.getElementById('activateModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeActivateModal();
            }
        });
    </script>
@endsection
