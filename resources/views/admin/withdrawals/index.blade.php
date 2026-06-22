@extends('layouts.admin')

@section('title', 'Gerenciar Saques')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Gerenciar Saques</h1>
            <p class="text-gray-600 mt-2">Aprove ou reprove solicitações de saque dos criadores</p>
        </div>

        <!-- Filtros -->
        <div class="mb-6 bg-white rounded-lg shadow-sm p-4">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.withdrawals.index', ['filter' => 'pending']) }}" 
                   class="px-4 py-2 rounded-lg {{ $filter === 'pending' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Pendentes ({{ $pendingCount }})
                </a>
                <a href="{{ route('admin.withdrawals.index', ['filter' => 'transferred']) }}" 
                   class="px-4 py-2 rounded-lg {{ $filter === 'transferred' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Aprovados ({{ $transferredCount }})
                </a>
                <a href="{{ route('admin.withdrawals.index', ['filter' => 'rejected']) }}" 
                   class="px-4 py-2 rounded-lg {{ $filter === 'rejected' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Reprovar ({{ $rejectedCount }})
                </a>
                <a href="{{ route('admin.withdrawals.index', ['filter' => 'all']) }}" 
                   class="px-4 py-2 rounded-lg {{ $filter === 'all' ? 'bg-gray-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Todos ({{ $totalCount }})
                </a>
            </div>
        </div>

        @if($withdrawals->count() > 0)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criador</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conta Bancária</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($withdrawals as $withdrawal)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $withdrawal->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $withdrawal->user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">
                                            R$ {{ number_format($withdrawal->amount, 2, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ $withdrawal->bankAccount->bank_name }}</div>
                                        <div class="text-sm text-gray-500">
                                            {{ ucfirst($withdrawal->bankAccount->pix_key_type) }}: {{ $withdrawal->bankAccount->pix_key }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            {{ $withdrawal->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($withdrawal->status === 'pending')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Pendente
                                            </span>
                                        @elseif($withdrawal->status === 'transferred')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Aprovado
                                            </span>
                                        @elseif($withdrawal->status === 'rejected')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                Reprovar
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @if($withdrawal->status === 'pending')
                                            <div class="flex space-x-2">
                                                <button onclick="approveWithdrawal({{ $withdrawal->id }})" 
                                                        class="text-green-600 hover:text-green-900 font-medium">
                                                    Aprovar
                                                </button>
                                                <button onclick="rejectWithdrawal({{ $withdrawal->id }})" 
                                                        class="text-red-600 hover:text-red-900 font-medium">
                                                    Reprovar
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginação -->
            <div class="mt-4">
                {{ $withdrawals->links() }}
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <p class="text-gray-600">
                    @if($filter === 'pending')
                        Nenhum saque pendente de aprovação.
                    @elseif($filter === 'transferred')
                        Nenhum saque aprovado.
                    @elseif($filter === 'rejected')
                        Nenhum saque reprovado.
                    @else
                        Nenhum saque encontrado.
                    @endif
                </p>
            </div>
        @endif
    </div>

    <!-- Modal de Aprovação -->
    <div id="approveModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Aprovar Saque</h3>
            <p class="text-sm text-gray-600 mb-4">Tem certeza que deseja aprovar este saque? O valor será transferido para a conta do criador.</p>
            <form id="approveForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações (opcional)</label>
                    <textarea id="approveNotes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeApproveModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Aprovar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Reprovação -->
    <div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Reprovar Saque</h3>
            <p class="text-sm text-gray-600 mb-4">Tem certeza que deseja reprovar este saque? O valor será estornado para o saldo liberado do criador.</p>
            <form id="rejectForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Motivo da reprovação (opcional)</label>
                    <textarea id="rejectNotes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeRejectModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Reprovar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentWithdrawalId = null;

        function approveWithdrawal(id) {
            currentWithdrawalId = id;
            document.getElementById('approveModal').classList.remove('hidden');
        }

        function closeApproveModal() {
            document.getElementById('approveModal').classList.add('hidden');
            document.getElementById('approveForm').reset();
            currentWithdrawalId = null;
        }

        function rejectWithdrawal(id) {
            currentWithdrawalId = id;
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejectForm').reset();
            currentWithdrawalId = null;
        }

        // Submit aprovação
        document.getElementById('approveForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const notes = document.getElementById('approveNotes').value;
            const submitBtn = this.querySelector('button[type="submit"]');

            // Desabilita o botão imediatamente para evitar duplo clique
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processando...';

            $.ajax({
                url: `/admin/withdrawals/${currentWithdrawalId}/approve`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    notes: notes
                },
                success: function(response) {
                    if (response.success) {
                        alert('Saque aprovado com sucesso!');
                        location.reload();
                    } else {
                        alert(response.message || 'Erro ao aprovar saque.');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Aprovar';
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    alert(response?.message || 'Erro ao aprovar saque.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Aprovar';
                }
            });
        });

        // Submit reprovação
        document.getElementById('rejectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const notes = document.getElementById('rejectNotes').value;

            $.ajax({
                url: `/admin/withdrawals/${currentWithdrawalId}/reject`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    notes: notes
                },
                success: function(response) {
                    if (response.success) {
                        alert('Saque reprovado. O valor foi estornado para o saldo liberado do criador.');
                        location.reload();
                    } else {
                        alert(response.message || 'Erro ao reprovar saque.');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    alert(response?.message || 'Erro ao reprovar saque.');
                }
            });
        });

        // Fechar modais ao clicar fora
        document.getElementById('approveModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeApproveModal();
            }
        });

        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
    </script>
@endsection

