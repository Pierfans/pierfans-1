@extends('layouts.admin')

@section('title', 'Detalhes da Assinatura')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('admin.subscriptions.index') }}" 
               class="text-indigo-600 hover:text-indigo-900 mb-4 inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Voltar para lista
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Detalhes da Assinatura</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Informações Principais -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Informações da Assinatura</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Status</label>
                        <div class="mt-1">
                            @if($subscription->is_active && $subscription->end_date >= now()->toDateString())
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                                    Ativa
                                </span>
                            @else
                                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                                    Inativa/Expirada
                                </span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Tipo de Ativação</label>
                        <div class="mt-1">
                            @if($subscription->activated_manually)
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                    Ativada Manualmente
                                </span>
                                @if($subscription->activatedByAdmin)
                                    <div class="text-sm text-gray-600 mt-1">
                                        Por: {{ $subscription->activatedByAdmin->name }} em 
                                        {{ $subscription->activated_at ? \Carbon\Carbon::parse($subscription->activated_at)->emBrasilia()->format('d/m/Y H:i') : 'N/A' }}
                                    </div>
                                @endif
                            @else
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                                    Ativada por Pagamento
                                </span>
                                <div class="text-sm text-gray-600 mt-1">
                                    Método: {{ strtoupper($subscription->payment_method) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Assinante</label>
                        <div class="mt-1">
                            <div class="text-sm font-medium text-gray-900">{{ $subscription->user->name }}</div>
                            <div class="text-sm text-gray-600">{{ $subscription->user->email }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Criador</label>
                        <div class="mt-1">
                            <div class="text-sm font-medium text-gray-900">{{ $subscription->creator->name }}</div>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Plano</label>
                        <div class="mt-1 text-sm text-gray-900">{{ $subscription->plan->name ?? 'N/A' }}</div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Período</label>
                        <div class="mt-1 text-sm text-gray-900">
                            De {{ \Carbon\Carbon::parse($subscription->start_date)->format('d/m/Y') }} 
                            até {{ \Carbon\Carbon::parse($subscription->end_date)->format('d/m/Y') }}
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Valor Total</label>
                        <div class="mt-1 text-lg font-bold text-gray-900">
                            R$ {{ number_format($subscription->total_amount, 2, ',', '.') }}
                        </div>
                    </div>
                </div>

                @if(!$subscription->is_active || $subscription->end_date < now()->toDateString())
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <button 
                            onclick="openActivateModal()" 
                            class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold">
                            Ativar Assinatura
                        </button>
                    </div>
                @endif
            </div>

            <!-- Valores e Comissões -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Distribuição de Valores</h2>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                        <span class="text-sm text-gray-600">Valor Total</span>
                        <span class="text-sm font-semibold text-gray-900">
                            R$ {{ number_format($subscription->total_amount, 2, ',', '.') }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                        <span class="text-sm text-gray-600">Plataforma ({{ number_format($subscription->platform_percentage, 2, ',', '.') }}%)</span>
                        <span class="text-sm font-semibold text-gray-900">
                            R$ {{ number_format($subscription->platform_amount, 2, ',', '.') }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center py-2 border-b border-gray-200">
                        <span class="text-sm text-gray-600">Criador</span>
                        <span class="text-sm font-semibold text-gray-900">
                            R$ {{ number_format($subscription->creator_amount, 2, ',', '.') }}
                        </span>
                    </div>

                    @if($subscription->referrer_amount > 0)
                        <div class="flex justify-between items-center py-2 border-b border-gray-200">
                            <span class="text-sm text-gray-600">Comissão Afiliado (Indicado)</span>
                            <span class="text-sm font-semibold text-green-600">
                                R$ {{ number_format($subscription->referrer_amount, 2, ',', '.') }}
                            </span>
                        </div>
                    @endif

                    @if($subscription->creator_affiliate_amount > 0)
                        <div class="flex justify-between items-center py-2 border-b border-gray-200">
                            <span class="text-sm text-gray-600">Comissão Afiliado (Criador)</span>
                            <span class="text-sm font-semibold text-green-600">
                                R$ {{ number_format($subscription->creator_affiliate_amount, 2, ',', '.') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Logs de Ativação -->
        @if($subscription->activationLogs->count() > 0)
            <div class="bg-white rounded-lg shadow-sm p-6 mt-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Histórico de Ativações Manuais</h2>
                
                <div class="space-y-4">
                    @foreach($subscription->activationLogs as $log)
                        <div class="border-l-4 border-blue-500 pl-4 py-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        Ativado por: {{ $log->adminUser->name }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ \Carbon\Carbon::parse($log->created_at)->emBrasilia()->format('d/m/Y H:i') }}
                                    </div>
                                    @if($log->reason)
                                        <div class="text-sm text-gray-700 mt-1">
                                            <strong>Motivo:</strong> {{ $log->reason }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Modal de Ativação -->
    <div id="activateModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Ativar Assinatura Manualmente</h3>
            
            <form id="activateForm">
                <input type="hidden" id="subscriptionId" value="{{ $subscription->id }}">
                
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

        function openActivateModal() {
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
