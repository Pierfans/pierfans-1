@extends('layouts.admin')

@section('title', 'Detalhes da Carteira')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $user->name }}</h1>
                    <p class="text-gray-600 mt-2">{{ $user->email }}</p>
                </div>
                <a href="{{ route('admin.wallets.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Voltar
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Coluna Principal -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Saldo Atual -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Saldo Atual</h2>
                    <div class="text-4xl font-bold text-pink-600 mb-4">
                        R$ {{ number_format($wallet->balance, 2, ',', '.') }}
                    </div>
                    <p class="text-sm text-gray-500">Este saldo foi adicionado pelo administrador e não pode ser sacado pelo usuário.</p>
                </div>

                <!-- Histórico de Transações -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Histórico de Transações</h2>
                    @if($transactions->count() > 0)
                        <div class="space-y-4">
                            @foreach($transactions as $transaction)
                                <div class="border-b border-gray-200 pb-4 last:border-b-0">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-sm font-semibold text-gray-900">
                                                    + R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                                                </span>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    Crédito
                                                </span>
                                            </div>
                                            @if($transaction->description)
                                                <p class="text-sm text-gray-600 mb-1">{{ $transaction->description }}</p>
                                            @endif
                                            @if($transaction->admin_notes)
                                                <p class="text-xs text-gray-500 mb-1"><strong>Nota do Admin:</strong> {{ $transaction->admin_notes }}</p>
                                            @endif
                                            <div class="flex items-center gap-4 text-xs text-gray-500">
                                                <span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
                                                @if($transaction->adminUser)
                                                    <span>Adicionado por: {{ $transaction->adminUser->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Paginação -->
                        <div class="mt-6">
                            {{ $transactions->links() }}
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">Nenhuma transação encontrada.</p>
                    @endif
                </div>
            </div>

            <!-- Coluna Lateral -->
            <div class="space-y-6">
                <!-- Adicionar Saldo -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Adicionar Saldo</h2>
                    <form id="addBalanceForm">
                        @csrf
                        <div class="mb-4">
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                                Valor (R$)
                            </label>
                            <input 
                                type="number" 
                                id="amount" 
                                name="amount" 
                                step="0.01" 
                                min="0.01" 
                                max="999999.99"
                                placeholder="0,00" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                required
                            >
                        </div>
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Descrição (opcional)
                            </label>
                            <input 
                                type="text" 
                                id="description" 
                                name="description" 
                                maxlength="255"
                                placeholder="Ex: Bônus de fidelidade" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                            >
                        </div>
                        <div class="mb-4">
                            <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Notas Internas (opcional)
                            </label>
                            <textarea 
                                id="admin_notes" 
                                name="admin_notes" 
                                rows="3"
                                maxlength="1000"
                                placeholder="Notas visíveis apenas para administradores" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                            ></textarea>
                        </div>
                        <button 
                            type="submit" 
                            class="w-full bg-pink-500 hover:bg-pink-600 text-white font-semibold py-2 px-4 rounded-lg transition-colors"
                        >
                            Adicionar Saldo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#addBalanceForm').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.text();
                
                // Desabilita o botão
                submitBtn.prop('disabled', true).text('Processando...');
                
                $.ajax({
                    url: '{{ route("admin.wallets.add-balance", $user->id) }}',
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Saldo adicionado com sucesso!');
                            location.reload();
                        } else {
                            alert('Erro: ' + (response.message || 'Erro ao adicionar saldo.'));
                            submitBtn.prop('disabled', false).text(originalText);
                        }
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON?.message || 'Erro ao adicionar saldo. Tente novamente.';
                        alert('Erro: ' + errorMessage);
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
    </script>
@endsection

