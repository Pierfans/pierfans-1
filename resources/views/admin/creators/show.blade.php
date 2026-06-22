@extends('layouts.admin')

@section('title', 'Detalhes do Criador')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <a href="{{ route('admin.creators.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                    ← Voltar
                </a>
                <h1 class="text-3xl font-bold text-gray-900">
                    Detalhes do Criador
                    <span id="activeBadge" class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $creator->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $creator->is_active ? 'Ativa' : 'Desativada' }}
                    </span>
                </h1>
            </div>
            <a href="{{ route('admin.users.edit', $creator->id) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Editar usuário
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 space-y-6">
            <!-- Dados Pessoais -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-4">Dados Pessoais</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome de exibição</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->name ?? $creator->creator_full_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome Completo</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_full_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CPF</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_cpf ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_birth_date ? \Carbon\Carbon::parse($creator->creator_birth_date)->format('d/m/Y') : 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Telefone</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_phone ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Endereço -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-4">Endereço</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CEP</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_zipcode ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Endereço</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_address ?? 'N/A' }}, {{ $creator->creator_address_number ?? '' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Complemento</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_address_complement ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bairro</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_neighborhood ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cidade</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_city ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_state ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Dados Bancários -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-4">Dados Bancários</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Banco</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_bank_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Agência</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_bank_agency ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Conta</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_bank_account ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo de Conta</label>
                        <p class="mt-1 text-sm text-gray-900">
                            {{ $creator->creator_bank_account_type === 'checking' ? 'Conta Corrente' : ($creator->creator_bank_account_type === 'savings' ? 'Conta Poupança' : 'N/A') }}
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Chave PIX</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $creator->creator_pix_key ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Documentos -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-4">Documentos</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if($creator->creator_document_front_url)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">RG/CNH - Frente</label>
                            <a href="{{ $creator->creator_document_front_url }}" target="_blank" class="block">
                                <img src="{{ $creator->creator_document_front_url }}" alt="Documento Frente" class="w-full rounded-lg border border-gray-300">
                            </a>
                        </div>
                    @endif
                    @if($creator->creator_document_back_url)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">RG/CNH - Verso</label>
                            <a href="{{ $creator->creator_document_back_url }}" target="_blank" class="block">
                                <img src="{{ $creator->creator_document_back_url }}" alt="Documento Verso" class="w-full rounded-lg border border-gray-300">
                            </a>
                        </div>
                    @endif
                    @if($creator->creator_selfie_url)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Selfie</label>
                            <a href="{{ $creator->creator_selfie_url }}" target="_blank" class="block">
                                <img src="{{ $creator->creator_selfie_url }}" alt="Selfie" class="w-full rounded-lg border border-gray-300">
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Status da Conta -->
            <div class="pt-6 border-t border-gray-200">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Status da Conta</h2>
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-600">
                        @if($creator->is_active)
                            A conta está <strong>ativa</strong>. Desativar impedirá o login e ocultará o conteúdo.
                        @else
                            A conta está <strong>desativada</strong>. A criadora não consegue fazer login e o conteúdo está oculto.
                        @endif
                    </p>
                    <button id="toggleActiveBtn" onclick="toggleActive({{ $creator->id }})"
                            class="px-4 py-2 rounded-lg text-white text-sm font-medium transition-colors {{ $creator->is_active ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600' }}">
                        {{ $creator->is_active ? 'Desativar conta' : 'Reativar conta' }}
                    </button>
                </div>
            </div>

            <!-- Ações -->
            @if($creator->creator_status === 'pending')
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <button onclick="rejectCreator({{ $creator->id }})" 
                            class="px-6 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                        Reprovar
                    </button>
                    <button onclick="approveCreator({{ $creator->id }})" 
                            class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                        Aprovar
                    </button>
                </div>
            @endif

        </div>
    </div>

    <script>
        function approveCreator(id) {
            if (!confirm('Tem certeza que deseja aprovar este criador?')) {
                return;
            }

            $.ajax({
                url: `/admin/creators/${id}/approve`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Criador aprovado com sucesso!');
                        window.location.href = '/admin/creators';
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Erro ao aprovar criador');
                }
            });
        }

        function toggleActive(id) {
            const btn = document.getElementById('toggleActiveBtn');
            const isCurrentlyActive = btn.classList.contains('bg-red-500');
            const action = isCurrentlyActive ? 'desativar' : 'reativar';

            if (!confirm(`Tem certeza que deseja ${action} esta criadora?`)) {
                return;
            }

            $.ajax({
                url: `/admin/creators/${id}/toggle-active`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        const badge = document.getElementById('activeBadge');
                        if (response.is_active) {
                            badge.textContent = 'Ativa';
                            badge.className = 'ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                            btn.textContent = 'Desativar conta';
                            btn.className = 'px-4 py-2 rounded-lg text-white text-sm font-medium transition-colors bg-red-500 hover:bg-red-600';
                        } else {
                            badge.textContent = 'Desativada';
                            badge.className = 'ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800';
                            btn.textContent = 'Reativar conta';
                            btn.className = 'px-4 py-2 rounded-lg text-white text-sm font-medium transition-colors bg-green-500 hover:bg-green-600';
                        }
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Erro ao alterar status da conta');
                }
            });
        }

        function rejectCreator(id) {
            if (!confirm('Tem certeza que deseja reprovar este criador? Ele poderá reenviar os documentos.')) {
                return;
            }

            $.ajax({
                url: `/admin/creators/${id}/reject`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Criador reprovado. O usuário poderá reenviar os documentos.');
                        window.location.href = '/admin/creators';
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'Erro ao reprovar criador');
                }
            });
        }

    </script>
@endsection

