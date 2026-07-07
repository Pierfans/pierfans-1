@extends('layouts.admin')

@section('title', 'Configurações da Plataforma')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Configurações da Plataforma</h1>
            <p class="text-gray-600 mt-2">Gerencie as configurações gerais da plataforma</p>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.platform-settings.update') }}">
            @csrf
            @method('PUT')

            <!-- Card de Porcentagem da Plataforma -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Porcentagem da Plataforma</h2>

                <div class="mb-6">
                    <label for="platform_percentage" class="block text-sm font-medium text-gray-700 mb-2">
                        Porcentagem que a plataforma recebe de cada assinatura (%)
                    </label>
                    <div class="relative">
                        <input 
                            type="number" 
                            id="platform_percentage" 
                            name="platform_percentage" 
                            value="{{ $platform_percentage }}" 
                            min="0" 
                            max="100" 
                            step="0.01"
                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                            required
                        >
                        <span class="absolute right-4 top-3 text-gray-500 text-lg">%</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Esta porcentagem será aplicada a todas as novas assinaturas. 
                        Assinaturas antigas manterão a porcentagem que foi configurada no momento da criação.
                    </p>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-blue-900">Como funciona a distribuição:</p>
                            <p class="text-sm text-blue-700 mt-1">
                                Se a porcentagem da plataforma for <strong>{{ $platform_percentage }}%</strong>, 
                                em uma assinatura de <strong>R$ 100,00</strong>:
                            </p>
                            <ul class="text-sm text-blue-700 mt-2 ml-4 list-disc">
                                <li>Plataforma recebe: <strong>R$ {{ number_format((100 * $platform_percentage) / 100, 2, ',', '.') }}</strong></li>
                                <li>Criador recebe: <strong>R$ {{ number_format(100 - ((100 * $platform_percentage) / 100), 2, ',', '.') }}</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card de Configurações de Liberação de Pagamentos -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Configurações de Liberação de Pagamentos</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Dias de Bloqueio - PIX -->
                    <div>
                        <label for="pix_release_days" class="block text-sm font-medium text-gray-700 mb-2">
                            Dias de Bloqueio - PIX
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                id="pix_release_days" 
                                name="pix_release_days" 
                                value="{{ $pix_release_days ?? 0 }}" 
                                min="0"
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                            >
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Número de dias que pagamentos via PIX ficam bloqueados antes de liberar para saque. 
                            <strong>0 = liberação imediata</strong>.
                        </p>
                    </div>

                    <!-- Dias de Bloqueio - Cartão -->
                    <div>
                        <label for="card_release_days" class="block text-sm font-medium text-gray-700 mb-2">
                            Dias de Bloqueio - Cartão
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                id="card_release_days" 
                                name="card_release_days" 
                                value="{{ $card_release_days ?? 0 }}" 
                                min="0"
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                            >
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Número de dias que pagamentos via Cartão ficam bloqueados antes de liberar para saque. 
                            <strong>0 = liberação imediata</strong>.
                        </p>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-blue-900">Como funciona a liberação:</p>
                            <ul class="text-sm text-blue-700 mt-2 ml-4 list-disc">
                                <li>Pagamentos que ainda não completaram o prazo aparecem como <strong>"Saldo a liberar"</strong></li>
                                <li>Pagamentos que já completaram o prazo aparecem como <strong>"Liberado para saque"</strong></li>
                                <li>O saldo disponível considera apenas pagamentos já liberados, menos saques pendentes e transferidos</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card de Configurações de Afiliados -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Configurações de Afiliados</h2>

                <div class="mb-6">
                    <label for="affiliate_commission_percentage" class="block text-sm font-medium text-gray-700 mb-2">
                        Porcentagem de Comissão por Indicação (%)
                    </label>
                    <div class="relative">
                        <input 
                            type="number" 
                            id="affiliate_commission_percentage" 
                            name="affiliate_commission_percentage" 
                            value="{{ number_format($affiliate_commission_percentage ?? 5, 2, '.', '') }}" 
                            min="0" 
                            max="100" 
                            step="0.01"
                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                            required
                        >
                        <span class="absolute right-4 top-3 text-gray-500 text-lg">%</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Porcentagem de comissão que o afiliado recebe por cada indicação que assinar qualquer criador.
                    </p>
                </div>

                <div class="mb-6">
                    <label for="affiliate_commission_limit" class="block text-sm font-medium text-gray-700 mb-2">
                        Limite de Recebimento por Indicação
                    </label>
                    <div class="relative">
                        <input 
                            type="number" 
                            id="affiliate_commission_limit" 
                            name="affiliate_commission_limit" 
                            value="{{ $affiliate_commission_limit ?? 0 }}" 
                            min="0" 
                            step="1"
                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                            required
                        >
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Define quantas vezes um afiliado pode receber comissão pela mesma pessoa indicada. 
                        <strong>0 = sem limite</strong> (recebe sempre que o indicado gerar receita).
                    </p>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-blue-900">Como funciona a comissão de afiliado:</p>
                            <p class="text-sm text-blue-700 mt-1">
                                Se a porcentagem de comissão for <strong>{{ number_format($affiliate_commission_percentage ?? 5, 2, ',', '.') }}%</strong>, 
                                em uma assinatura de <strong>R$ 100,00</strong>:
                            </p>
                            <ul class="text-sm text-blue-700 mt-2 ml-4 list-disc">
                                <li>Afiliado recebe: <strong>R$ {{ number_format((100 * ($affiliate_commission_percentage ?? 5)) / 100, 2, ',', '.') }}</strong></li>
                                <li>Plataforma recebe: <strong>R$ {{ number_format((100 * ($platform_percentage - ($affiliate_commission_percentage ?? 5))) / 100, 2, ',', '.') }}</strong> ({{ $platform_percentage }}% - {{ number_format($affiliate_commission_percentage ?? 5, 2, ',', '.') }}%)</li>
                                <li>Criador recebe: <strong>R$ {{ number_format(100 - ((100 * $platform_percentage) / 100), 2, ',', '.') }}</strong></li>
                            </ul>
                            @if(($affiliate_commission_limit ?? 0) > 0)
                            <p class="text-sm text-blue-700 mt-3">
                                <strong>Limite configurado:</strong> {{ $affiliate_commission_limit }} comissão(ões) por pessoa indicada.
                                Após atingir este limite, o afiliado não receberá novas comissões daquela pessoa.
                            </p>
                            @else
                            <p class="text-sm text-blue-700 mt-3">
                                <strong>Limite:</strong> Sem limite. O afiliado recebe comissão sempre que o indicado gerar receita.
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card de Upload de Mídia -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Upload de Mídia (Posts)</h2>

                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label for="use_r2_upload" class="block text-sm font-medium text-gray-700 mb-2">
                                Usar R2 (Cloudflare) para upload de mídia
                            </label>
                            <p class="text-sm text-gray-500">
                                Quando ativado, as mídias dos posts são enviadas para o R2. Quando desativado, são salvas localmente no servidor.
                            </p>
                        </div>
                        <div class="ml-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    id="use_r2_upload"
                                    name="use_r2_upload"
                                    value="1"
                                    {{ $use_r2_upload ? 'checked' : '' }}
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card de Configurações de Autenticação -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Configurações de Autenticação</h2>

                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label for="email_verification_required" class="block text-sm font-medium text-gray-700 mb-2">
                                Exigir Confirmação de E-mail
                            </label>
                            <p class="text-sm text-gray-500">
                                Quando ativado, novos usuários precisarão confirmar seu e-mail antes de fazer login.
                            </p>
                        </div>
                        <div class="ml-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    id="email_verification_required" 
                                    name="email_verification_required" 
                                    value="1"
                                    {{ $email_verification_required ? 'checked' : '' }}
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-blue-900">Como funciona:</p>
                            <ul class="text-sm text-blue-700 mt-2 ml-4 list-disc">
                                <li>Quando <strong>ativado</strong>, novos usuários receberão um e-mail de confirmação após o cadastro</li>
                                <li>O usuário precisará clicar no link do e-mail para confirmar sua conta</li>
                                <li>Enquanto o e-mail não for confirmado, o usuário não poderá fazer login</li>
                                <li>Quando <strong>desativado</strong>, novos usuários poderão fazer login imediatamente após o cadastro</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card de Configurações de Saque -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Configurações de Saque</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Limite Diário de Saques -->
                    <div>
                        <label for="daily_withdraw_limit" class="block text-sm font-medium text-gray-700 mb-2">
                            Limite Diário de Saques
                        </label>
                        <div class="relative">
                            <input 
                                type="number" 
                                id="daily_withdraw_limit" 
                                name="daily_withdraw_limit" 
                                value="{{ $daily_withdraw_limit }}" 
                                min="1" 
                                max="100"
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                                required
                            >
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Número máximo de saques que um criador pode fazer por dia.
                        </p>
                    </div>

                    <!-- Valor Mínimo de Saque -->
                    <div>
                        <label for="min_withdraw_amount" class="block text-sm font-medium text-gray-700 mb-2">
                            Valor Mínimo de Saque (R$)
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-gray-500 text-lg">R$</span>
                            <input 
                                type="number" 
                                id="min_withdraw_amount" 
                                name="min_withdraw_amount" 
                                value="{{ number_format($min_withdraw_amount, 2, '.', '') }}" 
                                min="1" 
                                step="0.01"
                                class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                                required
                            >
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Valor mínimo que um criador pode solicitar para saque.
                        </p>
                    </div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-yellow-900">Regras de Saque:</p>
                            <ul class="text-sm text-yellow-700 mt-2 ml-4 list-disc">
                                <li>Os criadores podem fazer até <strong>{{ $daily_withdraw_limit }} saques por dia</strong></li>
                                <li>O primeiro saque do dia é <strong>gratuito</strong></li>
                                <li>Os próximos saques do dia custam <strong>3,5% do valor</strong> (a taxa real do SuitPay, repassada a quem saca)</li>
                                <li>O valor mínimo para saque é <strong>R$ {{ number_format($min_withdraw_amount, 2, ',', '.') }}</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button 
                    type="submit" 
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
                >
                    Salvar Configurações
                </button>
            </div>
        </form>
    </div>
@endsection

