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

        <form method="POST" action="{{ route('admin.platform-settings.update') }}" enctype="multipart/form-data">
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

                <div class="mb-6">
                    <label for="platform_percentage_card" class="block text-sm font-medium text-gray-700 mb-2">
                        Porcentagem da plataforma quando a venda é no cartão (%)
                    </label>
                    <div class="relative">
                        <input
                            type="number"
                            id="platform_percentage_card"
                            name="platform_percentage_card"
                            value="{{ $platform_percentage_card }}"
                            min="0"
                            max="100"
                            step="0.01"
                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                        >
                        <span class="absolute right-4 top-3 text-gray-500 text-lg">%</span>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        No cartão a taxa do gateway é bem maior que no PIX, e hoje quem absorve é a plataforma.
                        Com <strong>{{ number_format($platform_percentage_card, 2, ',', '.') }}%</strong>,
                        numa venda de R$ 100,00 no cartão o criador recebe
                        <strong>R$ {{ number_format(100 - $platform_percentage_card, 2, ',', '.') }}</strong>.
                        Vale para assinatura e conteúdo avulso pagos no cartão; venda paga com saldo da carteira
                        usa a porcentagem normal, porque a taxa já foi paga na recarga.
                    </p>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label for="video_calls_enabled" class="block text-sm font-medium text-gray-700 mb-2">
                                Chamada de vídeo paga no chat ligada
                            </label>
                            <p class="text-sm text-gray-500">
                                Ligada, a criadora vê o bloco "Chamada de vídeo" na página de planos e o fã vê o botão
                                no chat. Fica desligada até a sala de vídeo estar no ar.
                            </p>
                        </div>
                        <div class="ml-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="video_calls_enabled" name="video_calls_enabled" value="1" {{ $video_calls_enabled ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="video_call_tolerance_minutes" class="block text-sm font-medium text-gray-700 mb-2">
                        Chamada de vídeo: minutos de atraso da criadora antes de devolver ao fã
                    </label>
                    <input type="number" id="video_call_tolerance_minutes" name="video_call_tolerance_minutes"
                           value="{{ $video_call_tolerance_minutes }}" min="0" max="240" step="1"
                           class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg">
                    <p class="mt-2 text-sm text-gray-500">
                        Passou o horário marcado mais esse tempo e ela não entrou na sala: o valor volta pra carteira do fã, sozinho.
                    </p>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <label for="card_enabled" class="block text-sm font-medium text-gray-700 mb-2">
                                Pagamento com cartão de crédito ligado
                            </label>
                            <p class="text-sm text-gray-500">
                                Desligado, o site vende só no PIX: some o "pagar com cartão", a criadora não vê a opção
                                e a URL de cartão é recusada. Ligue quando o SuitPay liberar o cartão. Nada é apagado:
                                a porcentagem acima e o que cada criadora escolheu continuam guardados.
                            </p>
                        </div>
                        <div class="ml-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    id="card_enabled"
                                    name="card_enabled"
                                    value="1"
                                    {{ $card_enabled ? 'checked' : '' }}
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

                    <!-- Dias de Bloqueio - Venda no Chat -->
                    <div>
                        <label for="chat_release_days" class="block text-sm font-medium text-gray-700 mb-2">
                            Dias de Bloqueio - Venda no Chat
                        </label>
                        <div class="relative">
                            <input
                                type="number"
                                id="chat_release_days"
                                name="chat_release_days"
                                value="{{ $chat_release_days ?? 7 }}"
                                min="0"
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                            >
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Número de dias que a venda de foto ou áudio no chat fica bloqueada antes de liberar
                            para saque. Independe da forma de pagamento, porque a compra no chat é sempre feita
                            com saldo da carteira.
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

            {{-- Card do banner da collab (login + dashboard). A foto muda todo dia (Bento, 28/08);
                 antes cada troca era um deploy. Campos mostram o que esta no ar agora. --}}
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Banner</h2>
                <p class="mb-6 text-sm text-gray-500">O banner grande da tela de login e do dashboard. O título "MANSÃO DA JUJU" é fixo; aqui trocam a foto, a frase e o perfil pra onde a foto e o botão levam. Na frase, <strong>@ de criadora vira link</strong> pro perfil dela (pode marcar mais de uma).</p>

                <div class="mb-6 flex flex-col sm:flex-row gap-4 sm:items-start">
                    <img src="{{ $banner['image'] }}" alt="" class="w-40 h-40 object-cover rounded-lg bg-gray-100 flex-shrink-0">
                    <div class="flex-1">
                        <label for="banner_image" class="block text-sm font-medium text-gray-700 mb-2">Foto nova</label>
                        <input type="file" id="banner_image" name="banner_image" accept="image/jpeg,image/png,image/webp"
                            class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="mt-2 text-sm text-gray-500">
                            JPG, PNG ou WEBP até 5 MB. Sem escolher nada, a foto de hoje continua. A foto é cortada pra caber:
                            funciona melhor quadrada ou em pé, com as pessoas no centro.
                            <strong class="text-amber-700">Use o arquivo original.</strong> Foto que veio pelo WhatsApp como imagem chega
                            pequena e borrada no banner; peça como <em>documento</em> ou pelo Drive.
                        </p>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="banner_text" class="block text-sm font-medium text-gray-700 mb-2">Frase</label>
                    <input type="text" id="banner_text" name="banner_text" value="{{ $banner['text'] }}" maxlength="150"
                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="mb-2">
                    <label for="banner_username" class="block text-sm font-medium text-gray-700 mb-2">Perfil pra onde o banner leva</label>
                    <div class="flex items-center border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-blue-500">
                        <span class="pl-4 text-gray-400 select-none">pierfans.com/</span>
                        <input type="text" id="banner_username" name="banner_username" value="{{ $banner['username'] }}" maxlength="31"
                            class="flex-1 px-1 py-3 border-0 rounded-r-lg focus:ring-0">
                    </div>
                    <p class="mt-2 text-sm text-gray-500">O @ da criadora, como aparece no link do perfil dela. Só aceita criadora aprovada.</p>
                </div>
            </div>

            {{-- Banner so do dashboard, opcional (pedido do Pedro 28/08). Sem checkbox: campo vazio = usa o geral. --}}
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Banner do dashboard <span class="text-gray-400 font-normal text-base">opcional</span></h2>
                <p class="mb-6 text-sm text-gray-500">Só preencha se o dashboard tiver que mostrar algo diferente do login. Campo vazio usa o do banner acima.</p>

                <div class="mb-6 flex flex-col sm:flex-row gap-4 sm:items-start">
                    @if ($bannerDash['banner_dash_image'] ?? '')
                        <img src="{{ $bannerDash['banner_dash_image'] }}" alt="" class="w-40 h-40 object-cover rounded-lg bg-gray-100 flex-shrink-0">
                    @else
                        <div class="w-40 h-40 rounded-lg bg-gray-100 flex-shrink-0 flex items-center justify-center text-xs text-gray-400 text-center px-3">usa a foto do banner acima</div>
                    @endif
                    <div class="flex-1">
                        <label for="banner_dash_image" class="block text-sm font-medium text-gray-700 mb-2">Foto só do dashboard</label>
                        <input type="file" id="banner_dash_image" name="banner_dash_image" accept="image/jpeg,image/png,image/webp"
                            class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        @if ($bannerDash['banner_dash_image'] ?? '')
                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="banner_dash_image_remove" value="1" class="rounded border-gray-300">
                                Remover esta foto e voltar a usar a do banner acima
                            </label>
                        @endif
                    </div>
                </div>

                <div class="mb-6">
                    <label for="banner_dash_text" class="block text-sm font-medium text-gray-700 mb-2">Frase só do dashboard</label>
                    <input type="text" id="banner_dash_text" name="banner_dash_text" value="{{ $bannerDash['banner_dash_text'] ?? '' }}" maxlength="150" placeholder="vazio = usa a frase do banner acima"
                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="mb-2">
                    <label for="banner_dash_username" class="block text-sm font-medium text-gray-700 mb-2">Perfil só do dashboard</label>
                    <div class="flex items-center border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-blue-500">
                        <span class="pl-4 text-gray-400 select-none">pierfans.com/</span>
                        <input type="text" id="banner_dash_username" name="banner_dash_username" value="{{ $bannerDash['banner_dash_username'] ?? '' }}" maxlength="31" placeholder="vazio = usa o perfil do banner acima"
                            class="flex-1 px-1 py-3 border-0 rounded-r-lg focus:ring-0">
                    </div>
                </div>
            </div>

            <!-- Card da Live -->
            {{-- Pixel do trafego pago (bento 15/09). Html cru, vai no <head> de toda pagina via partials.tracking.
                 Sem maxlength no textarea, pelo mesmo motivo do link da live: maxlength corta a colagem em silencio. --}}
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Código de rastreamento</h2>
                <p class="mb-6 text-sm text-gray-500">O pixel de quem faz o tráfego pago (e, no futuro, Google Ads ou parecido). Cole o código <strong>exatamente como veio</strong>, com as tags <code>&lt;script&gt;</code> e tudo. Entra em todas as páginas do site: login, cadastro, dashboard, perfil das criadoras, compra e pagamento. Vazio = nada é carregado.</p>
                <textarea id="tracking_head" name="tracking_head" rows="8" spellcheck="false" placeholder="&lt;script&gt;...&lt;/script&gt;"
                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ $tracking_head }}</textarea>
                <p class="mt-2 text-sm text-gray-500">Pra conferir se pegou: abra qualquer página do site, clique com o botão direito, "exibir código-fonte" e procure um trecho do código. Só admin edita isto, e o que for colado roda no navegador de todo mundo: cole só código de quem você confia.</p>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Transmissão ao Vivo</h2>

                <div class="mb-6">
                    <label for="live_url" class="block text-sm font-medium text-gray-700 mb-2">
                        Link da live
                    </label>
                    <input
                        type="url"
                        id="live_url"
                        name="live_url"
                        value="{{ $live_url }}"
                        placeholder="https://www.youtube.com/watch?v=..."
                        {{-- sem maxlength de proposito: ele CORTA a colagem em silencio em vez de
                             recusar. Foi o que derrubou a live de 13/08 - um .m3u8 de 1155 chars
                             colado aqui virou um link de 500 no ar, sem erro nenhum na tela.
                             O 'max' do controller recusa e o @if($errors->any()) la em cima mostra. --}}
                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                    <p class="mt-2 text-sm text-gray-500">
                        A página da live no site de quem transmite. Usada no botão <em>"abrir em nova aba"</em> e como
                        reserva, caso o player da casa falhe. Sozinha, ela vira o player (dentro de um quadro do site deles).
                        <strong>Não cole o link .m3u8 aqui</strong> — ele tem campo próprio logo abaixo.
                    </p>
                </div>

                <div class="mb-6">
                    <label for="live_stream_url" class="block text-sm font-medium text-gray-700 mb-2">
                        Link do vídeo (.m3u8) <span class="text-gray-400 font-normal">— opcional, mas é o melhor</span>
                    </label>
                    <input
                        type="url"
                        id="live_stream_url"
                        name="live_stream_url"
                        value="{{ $live_stream_url }}"
                        placeholder="https://....m3u8"
                        {{-- idem: sem maxlength. Ver comentario do campo acima. --}}
                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                    <p class="mt-2 text-sm text-gray-500">
                        Preenchido, o vídeo toca no <strong>nosso player</strong>: sem os botões do site de quem transmite
                        puxando o visitante pra fora, e com um botão de som decente. Peça esse link a quem estiver transmitindo.
                        <strong class="text-amber-700">Atenção:</strong> esse link costuma ter prazo de validade. Se vencer no meio da
                        transmissão, a página cai sozinha para o link de cima — por isso vale preencher os dois.
                    </p>
                </div>

                <p class="mb-6 text-sm text-gray-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
                    <strong>Quando a live acabar, apague os dois campos.</strong> Senão fica um player morto na página.
                </p>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-blue-900">Antes de colar o link:</p>
                            <ul class="text-sm text-blue-700 mt-2 ml-4 list-disc">
                                <li><strong>YouTube funciona.</strong> Pode colar o link normal da barra do navegador, o sistema converte sozinho para o formato de player</li>
                                <li><strong>Instagram e TikTok não abrem dentro do site</strong> — eles bloqueiam. O link continua valendo, mas a pessoa vai assistir pelo botão "abrir em nova aba" que aparece embaixo do player</li>
                                <li>Se a live for de outra plataforma, confira na página antes de divulgar</li>
                            </ul>
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

                    <!-- Teto do Saque Automático -->
                    <div>
                        <label for="auto_withdraw_limit" class="block text-sm font-medium text-gray-700 mb-2">
                            Saque Automático até (R$)
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-gray-500 text-lg">R$</span>
                            <input 
                                type="number" 
                                id="auto_withdraw_limit" 
                                name="auto_withdraw_limit" 
                                value="{{ number_format($auto_withdraw_limit, 2, '.', '') }}" 
                                min="0" 
                                step="0.01"
                                class="block w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                            >
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Saques até este valor vão direto para o SuitPay, sem aprovação manual.
                            <strong>0 = desligado</strong>, todo saque passa pelo admin.
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
                                @if($auto_withdraw_limit > 0)
                                    <li>Saques de até <strong>R$ {{ number_format($auto_withdraw_limit, 2, ',', '.') }}</strong> vão direto para o SuitPay, <strong>sem aprovação manual</strong></li>
                                @else
                                    <li>O saque automático está <strong>desligado</strong>: todo saque espera aprovação do admin</li>
                                @endif
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

