@extends('layouts.admin')

@section('title', 'Editar Usuário')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="{{ $user->creator_status && in_array($user->creator_status, ['pending','approved','rejected']) ? route('admin.creators.show', $user->id) : route('admin.users.show', $user->id) }}"
               class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                ← Voltar
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Editar {{ $user->name ?? 'Usuário' }}</h1>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="text-sm text-red-800 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Dados do usuário -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Dados do usuário</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome de exibição *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail *</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="@username">
                    </div>
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                        <input type="text" name="slug" id="slug" value="{{ old('slug', $user->slug) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Senha -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Alterar senha</h2>
                <p class="text-sm text-gray-500 mb-4">Deixe em branco para não alterar a senha.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Nova senha</label>
                        <input type="password" name="password" id="password"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Mínimo 8 caracteres" minlength="8">
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar nova senha</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            @php $isCreator = $user->creator_status && in_array($user->creator_status, ['pending', 'approved', 'rejected']); @endphp

            @if($isCreator)
            <!-- Dados pessoais (criador) -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Dados pessoais (criador)</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="creator_full_name" class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label>
                        <input type="text" name="creator_full_name" id="creator_full_name" value="{{ old('creator_full_name', $user->creator_full_name) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="creator_cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF (apenas números)</label>
                        <input type="text" name="creator_cpf" id="creator_cpf" value="{{ old('creator_cpf', $user->creator_cpf) }}" maxlength="11"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="creator_birth_date" class="block text-sm font-medium text-gray-700 mb-1">Data de nascimento</label>
                        <input type="date" name="creator_birth_date" id="creator_birth_date"
                               value="{{ old('creator_birth_date', $user->creator_birth_date ? $user->creator_birth_date->format('Y-m-d') : '') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="creator_phone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                        <input type="text" name="creator_phone" id="creator_phone" value="{{ old('creator_phone', $user->creator_phone) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Endereço -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Endereço</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="creator_zipcode" class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                        <input type="text" name="creator_zipcode" id="creator_zipcode" value="{{ old('creator_zipcode', $user->creator_zipcode) }}" maxlength="8"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="creator_address" class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                        <input type="text" name="creator_address" id="creator_address" value="{{ old('creator_address', $user->creator_address) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="creator_address_number" class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                        <input type="text" name="creator_address_number" id="creator_address_number" value="{{ old('creator_address_number', $user->creator_address_number) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="creator_address_complement" class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                        <input type="text" name="creator_address_complement" id="creator_address_complement" value="{{ old('creator_address_complement', $user->creator_address_complement) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="creator_neighborhood" class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                        <input type="text" name="creator_neighborhood" id="creator_neighborhood" value="{{ old('creator_neighborhood', $user->creator_neighborhood) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="creator_city" class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                        <input type="text" name="creator_city" id="creator_city" value="{{ old('creator_city', $user->creator_city) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="creator_state" class="block text-sm font-medium text-gray-700 mb-1">Estado (UF)</label>
                        <input type="text" name="creator_state" id="creator_state" value="{{ old('creator_state', $user->creator_state) }}" maxlength="2"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ex: SP">
                    </div>
                </div>
            </div>

            <!-- Dados bancários -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Dados bancários</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="creator_bank_name" class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                        <input type="text" name="creator_bank_name" id="creator_bank_name" value="{{ old('creator_bank_name', $user->creator_bank_name) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="creator_bank_agency" class="block text-sm font-medium text-gray-700 mb-1">Agência</label>
                        <input type="text" name="creator_bank_agency" id="creator_bank_agency" value="{{ old('creator_bank_agency', $user->creator_bank_agency) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="creator_bank_account" class="block text-sm font-medium text-gray-700 mb-1">Conta</label>
                        <input type="text" name="creator_bank_account" id="creator_bank_account" value="{{ old('creator_bank_account', $user->creator_bank_account) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="creator_bank_account_type" class="block text-sm font-medium text-gray-700 mb-1">Tipo de conta</label>
                        <select name="creator_bank_account_type" id="creator_bank_account_type"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">—</option>
                            <option value="checking" {{ old('creator_bank_account_type', $user->creator_bank_account_type) === 'checking' ? 'selected' : '' }}>Conta Corrente</option>
                            <option value="savings" {{ old('creator_bank_account_type', $user->creator_bank_account_type) === 'savings' ? 'selected' : '' }}>Conta Poupança</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="creator_pix_key" class="block text-sm font-medium text-gray-700 mb-1">Chave PIX</label>
                        <input type="text" name="creator_pix_key" id="creator_pix_key" value="{{ old('creator_pix_key', $user->creator_pix_key) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
            </div>
            @endif

            <div class="flex justify-end gap-3">
                <a href="{{ $isCreator ? route('admin.creators.show', $user->id) : route('admin.users.show', $user->id) }}"
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>

    @if($isCreator)
    <script>
        document.getElementById('creator_cpf').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 11);
        });
        document.getElementById('creator_zipcode').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 8);
        });
        document.getElementById('creator_state').addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Za-z]/g, '').slice(0, 2);
        });
    </script>
    @endif
@endsection
