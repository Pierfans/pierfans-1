<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tornar-se Criador - {{ config('app.name', 'Laravel') }}</title>
    
    <!-- TailwindCSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- jQuery via CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Estilos e scripts customizados -->
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/app.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Top Navigation (Desktop) -->
    <x-topnav />

    <!-- Bottom Navigation (Mobile) -->
    <x-bottomnav />

    <!-- Main Content -->
    <div class="pt-16 md:pt-16 pb-16 md:pb-0">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            @if($status === 'pending')
                <!-- Status: Pendente -->
                <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Documentos em Análise</h2>
                    <p class="text-gray-600 mb-6">{{ $message }}</p>
                    <a href="{{ route('dashboard') }}" class="inline-block px-6 py-2 bg-pink-500 text-white rounded-lg hover:bg-pink-600 transition-colors">
                        Voltar ao Dashboard
                    </a>
                </div>
            @elseif($status === 'rejected')
                <!-- Status: Rejeitado -->
                <div class="bg-white rounded-lg shadow-sm p-8 mb-6">
                    <div class="text-center mb-6">
                        <div class="mb-4">
                            <svg class="w-16 h-16 mx-auto text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Cadastro Recusado</h2>
                        <p class="text-gray-600">{{ $message }}</p>
                    </div>
                </div>
            @endif

            @if($status !== 'pending')
                <!-- Formulário Multi-Step -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <!-- Progress Bar -->
                    <div class="bg-gray-100 h-2">
                        <div id="progressBar" class="bg-pink-500 h-full transition-all duration-300" style="width: 25%"></div>
                    </div>

                    <!-- Steps Indicator -->
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2 step-indicator" data-step="1">
                                <div class="w-8 h-8 rounded-full bg-pink-500 text-white flex items-center justify-center font-semibold">1</div>
                                <span class="text-sm font-medium text-gray-700">Dados Pessoais</span>
                            </div>
                            <div class="flex-1 h-0.5 bg-gray-300 mx-4"></div>
                            <div class="flex items-center space-x-2 step-indicator" data-step="2">
                                <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-semibold">2</div>
                                <span class="text-sm font-medium text-gray-500">Endereço</span>
                            </div>
                            <div class="flex-1 h-0.5 bg-gray-300 mx-4"></div>
                            <div class="flex items-center space-x-2 step-indicator" data-step="3">
                                <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-semibold">3</div>
                                <span class="text-sm font-medium text-gray-500">Dados Bancários</span>
                            </div>
                            <div class="flex-1 h-0.5 bg-gray-300 mx-4"></div>
                            <div class="flex items-center space-x-2 step-indicator" data-step="4">
                                <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-semibold">4</div>
                                <span class="text-sm font-medium text-gray-500">Documentos</span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Content -->
                    <form id="creatorForm" class="p-6">
                        @csrf

                        <!-- Step 1: Dados Pessoais -->
                        <div id="step1" class="step-content">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Dados Pessoais</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome de exibição *</label>
                                    <input type="text" name="name" id="name" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                           placeholder="Ex.: Nome artístico ou como quer ser chamado"
                                           required>
                                    <p class="mt-1.5 text-sm text-gray-500">Este nome será exibido para assinantes e visitantes.</p>
                                    <div class="error-message text-red-500 text-sm mt-1"></div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                                    <input type="text" name="creator_full_name" id="creator_full_name" 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                           value="{{ $user->creator_full_name ?? '' }}" required>
                                    <div class="error-message text-red-500 text-sm mt-1"></div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">CPF (apenas números) *</label>
                                    <input type="text" name="creator_cpf" id="creator_cpf" maxlength="11"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                           value="{{ $user->creator_cpf ?? '' }}" required>
                                    <div class="error-message text-red-500 text-sm mt-1"></div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento *</label>
                                    <input type="date" name="creator_birth_date" id="creator_birth_date"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                           value="{{ $user->creator_birth_date ?? '' }}" required>
                                    <div class="error-message text-red-500 text-sm mt-1"></div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Telefone *</label>
                                    <input type="text" name="creator_phone" id="creator_phone"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                           value="{{ $user->creator_phone ?? '' }}" required>
                                    <div class="error-message text-red-500 text-sm mt-1"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Endereço -->
                        <div id="step2" class="step-content hidden">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Endereço</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">CEP (apenas números) *</label>
                                    <input type="text" name="creator_zipcode" id="creator_zipcode" maxlength="8"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                           value="{{ $user->creator_zipcode ?? '' }}" required>
                                    <div class="error-message text-red-500 text-sm mt-1"></div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Endereço *</label>
                                        <input type="text" name="creator_address" id="creator_address"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                               value="{{ $user->creator_address ?? '' }}" required>
                                        <div class="error-message text-red-500 text-sm mt-1"></div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Número *</label>
                                        <input type="text" name="creator_address_number" id="creator_address_number"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                               value="{{ $user->creator_address_number ?? '' }}" required>
                                        <div class="error-message text-red-500 text-sm mt-1"></div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                                    <input type="text" name="creator_address_complement" id="creator_address_complement"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                           value="{{ $user->creator_address_complement ?? '' }}">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Bairro *</label>
                                    <input type="text" name="creator_neighborhood" id="creator_neighborhood"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                           value="{{ $user->creator_neighborhood ?? '' }}" required>
                                    <div class="error-message text-red-500 text-sm mt-1"></div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Cidade *</label>
                                        <input type="text" name="creator_city" id="creator_city"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                               value="{{ $user->creator_city ?? '' }}" required>
                                        <div class="error-message text-red-500 text-sm mt-1"></div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado (UF) *</label>
                                        <input type="text" name="creator_state" id="creator_state" maxlength="2"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent uppercase"
                                               value="{{ $user->creator_state ?? '' }}" required>
                                        <div class="error-message text-red-500 text-sm mt-1"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Dados Bancários -->
                        <div id="step3" class="step-content hidden">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Dados Bancários</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Banco *</label>
                                    <input type="text" name="creator_bank_name" id="creator_bank_name"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                           value="{{ $user->creator_bank_name ?? '' }}" required>
                                    <div class="error-message text-red-500 text-sm mt-1"></div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Agência *</label>
                                        <input type="text" name="creator_bank_agency" id="creator_bank_agency"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                               value="{{ $user->creator_bank_agency ?? '' }}" required>
                                        <div class="error-message text-red-500 text-sm mt-1"></div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Conta *</label>
                                        <input type="text" name="creator_bank_account" id="creator_bank_account"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                               value="{{ $user->creator_bank_account ?? '' }}" required>
                                        <div class="error-message text-red-500 text-sm mt-1"></div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Conta *</label>
                                    <select name="creator_bank_account_type" id="creator_bank_account_type"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                                        <option value="">Selecione...</option>
                                        <option value="checking" {{ $user->creator_bank_account_type === 'checking' ? 'selected' : '' }}>Conta Corrente</option>
                                        <option value="savings" {{ $user->creator_bank_account_type === 'savings' ? 'selected' : '' }}>Conta Poupança</option>
                                    </select>
                                    <div class="error-message text-red-500 text-sm mt-1"></div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Chave PIX *</label>
                                    <input type="text" name="creator_pix_key" id="creator_pix_key"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                                           value="{{ $user->creator_pix_key ?? '' }}" required>
                                    <div class="error-message text-red-500 text-sm mt-1"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Documentos -->
                        <div id="step4" class="step-content hidden">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Documentos</h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">RG/CNH - Frente *</label>
                                    <input type="file" name="creator_document_front" id="creator_document_front" accept="image/jpeg,image/jpg,image/png,image/heic,image/heif"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                                    <p class="text-xs text-gray-500 mt-1">Formatos aceitos: JPG, PNG, HEIC. Tamanho máximo: 300MB</p>
                                    <div id="preview_front" class="mt-2"></div>
                                    <div class="error-message text-red-500 text-sm mt-1"></div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">RG/CNH - Verso *</label>
                                    <input type="file" name="creator_document_back" id="creator_document_back" accept="image/jpeg,image/jpg,image/png,image/heic,image/heif"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                                    <p class="text-xs text-gray-500 mt-1">Formatos aceitos: JPG, PNG, HEIC. Tamanho máximo: 300MB</p>
                                    <div id="preview_back" class="mt-2"></div>
                                    <div class="error-message text-red-500 text-sm mt-1"></div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Selfie com Documento *</label>
                                    <input type="file" name="creator_selfie" id="creator_selfie" accept="image/jpeg,image/jpg,image/png,image/heic,image/heif"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                                    <p class="text-xs text-gray-500 mt-1">Formatos aceitos: JPG, PNG, HEIC. Tamanho máximo: 300MB</p>
                                    <div id="preview_selfie" class="mt-2"></div>
                                    <div class="error-message text-red-500 text-sm mt-1"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
                            <button type="button" id="prevBtn" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors hidden">
                                Voltar
                            </button>
                            <button type="button" id="nextBtn" class="ml-auto px-6 py-2 bg-pink-500 text-white rounded-lg hover:bg-pink-600 transition-colors">
                                Próximo
                            </button>
                            <button type="submit" id="submitBtn" class="ml-auto px-6 py-2 bg-pink-500 text-white rounded-lg hover:bg-pink-600 transition-colors hidden">
                                Enviar Documentos
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <script src="/js/creator-form.js?v={{ time() }}"></script>
</body>
</html>

