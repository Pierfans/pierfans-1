@extends('layouts.admin')

@section('title', 'Fluxo de Caixa')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Fluxo de Caixa</h1>
            <p class="text-gray-600 mt-2">Entradas, taxas do SuitPay e repasses — receita líquida real da plataforma</p>
        </div>

        @if(session('status'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">{{ $errors->first() }}</div>
        @endif

        <!-- Caixa: saldo real (base do extrato + vendas/saques do app) + caixa da plataforma. -->
        <div class="mb-6">
            <div class="mb-2">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Caixa</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                <div class="space-y-4">
                @if($recon)
                    <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-teal-500">
                        <p class="text-sm text-gray-500">Saldo real (SuitPay)</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">R$ {{ number_format($recon['liveBalance'], 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            Base do extrato R$ {{ number_format($recon['realBalance'], 2, ',', '.') }} ({{ \Illuminate\Support\Carbon::parse($recon['realBalanceAt'])->emBrasilia()->format('d/m/Y H:i') }})
                            @if($recon['appDelta'] != 0)
                                {{ $recon['appDelta'] > 0 ? '+' : '−' }} R$ {{ number_format(abs($recon['appDelta']), 2, ',', '.') }} de vendas/saques registrados depois
                            @endif
                        </p>
                    </div>
                @else
                    <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-gray-300 text-sm text-gray-500">
                        <p class="font-semibold text-gray-700 mb-1">Saldo real (SuitPay)</p>
                        Sem base do extrato ainda. Importe um CSV do painel pelo terminal pra ancorar no saldo real; daí as vendas e saques do app somam sozinhos em cima.
                    </div>
                @endif

                    <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-amber-500">
                        <p class="text-sm text-gray-500">Devido a usuários</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">R$ {{ number_format($owedToUsers, 2, ',', '.') }}</p>
                        {{-- expressão única em vez de @if inline: diretiva colada em palavra (gastaram@endif) o Blade ignora, e o if fica sem fechar --}}
                        <p class="text-xs text-gray-400 mt-1">Não é seu — está na conta, mas é deles: o que criadores e afiliados podem sacar (inclui o que ainda está no prazo de liberação){{ $owedToWallets > 0 ? ', mais R$ ' . number_format($owedToWallets, 2, ',', '.') . ' de saldo em carteira que assinantes depositaram e ainda não gastaram' : '' }}.</p>
                        @if($owedRows->isNotEmpty())
                            <button type="button" onclick="document.getElementById('modalDevido').classList.remove('hidden')"
                                    class="mt-3 text-sm font-medium text-amber-700 hover:text-amber-900 underline underline-offset-2">
                                Ver quem tem a receber ({{ $owedRows->count() }})
                            </button>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-blue-700">
                    <p class="text-sm text-gray-500">Caixa da plataforma</p>
                    @if($platformCash !== null)
                        <p class="text-3xl font-bold {{ $platformCash >= 0 ? 'text-gray-900' : 'text-red-600' }} mt-1">R$ {{ number_format($platformCash, 2, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">O que é de fato seu, e o que dá pra sacar: saldo real menos o que é dos criadores e afiliados.</p>
                        @if($platformCash < 0)
                            <p class="text-xs text-red-600 mt-2 font-medium">Negativo: a conta não cobre o que é dos usuários. Ou saiu dinheiro demais (retirada manual/saque da plataforma), ou falta importar extrato novo, ou os depósitos em carteira ainda não foram gastos. Enquanto estiver negativo não dá pra sacar — o que está lá é deles.</p>
                        @elseif($platformAccounts->isEmpty())
                            <p class="text-xs text-gray-500 mt-3">Pra sacar pelo site, cadastre a chave PIX da plataforma na conta <span class="font-medium">@pierfans</span> (tela de saque, logado nela).</p>
                        @elseif($platformMax > 0)
                            <button type="button" onclick="document.getElementById('modalSaque').classList.remove('hidden')"
                                    class="mt-3 px-4 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800 text-sm font-medium">
                                Sacar
                            </button>
                            <p class="text-xs text-gray-400 mt-2">Máximo R$ {{ number_format($platformMax, 2, ',', '.') }} — o resto é a taxa de {{ number_format($feeOutPct, 1, ',', '.') }}% que a SuitPay cobra pra fazer o PIX.</p>
                        @endif
                    @else
                        <p class="text-sm text-gray-500 mt-2">Precisa do saldo real pra calcular. Importe o extrato do SuitPay.</p>
                    @endif
                </div>
            </div>

        </div>

        {{-- Saque do caixa da plataforma: cai na fila normal de saques (aprovar dispara o PIX). --}}
        @if($platformCash !== null && $platformAccounts->isNotEmpty() && $platformMax > 0)
            <div id="modalSaque" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-bold text-gray-900">Sacar caixa da plataforma</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Sai da conta @pierfans, entra na fila de saques e vira PIX quando você aprovar.
                    </p>

                    <form method="POST" action="{{ route('admin.fluxo-caixa.sacar') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Valor (máximo R$ {{ number_format($platformMax, 2, ',', '.') }})</label>
                            <input type="number" name="amount" step="0.01" min="1" max="{{ $platformMax }}" value="{{ $platformMax }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <p class="text-xs text-gray-400 mt-1">O caixa é R$ {{ number_format($platformCash, 2, ',', '.') }}; a SuitPay ainda cobra {{ number_format($feeOutPct, 1, ',', '.') }}% em cima do valor pra fazer o PIX.</p>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Conta que recebe</label>
                            <select name="bank_account_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @foreach($platformAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->bank_name }} — {{ ucfirst($acc->pix_key_type) }}: {{ $acc->pix_key }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('modalSaque').classList.add('hidden')"
                                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm">Cancelar</button>
                            <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800 text-sm font-medium">Criar saque</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Quem tem a receber. Sobrepõe a tela em vez de expandir dentro do card: é conteúdo
             extra, não muda a estrutura de quem só quer olhar o caixa. Filtro no cliente — a
             lista inteira já veio, ida ao servidor a cada clique só deixaria mais lento. --}}
        @if($owedRows->isNotEmpty())
            <div id="modalDevido" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[85vh] flex flex-col">
                    <div class="flex items-start justify-between p-6 pb-4 border-b border-gray-100">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Quem tem a receber</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Soma exatamente o card: <span class="font-semibold">R$ {{ number_format($owedToUsers, 2, ',', '.') }}</span>
                            </p>
                        </div>
                        <button type="button" onclick="document.getElementById('modalDevido').classList.add('hidden')"
                                class="text-gray-400 hover:text-gray-700 text-2xl leading-none px-2">&times;</button>
                    </div>

                    <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2">
                        @foreach(['todos' => 'Todos', 'criador' => 'Criadores', 'afiliado' => 'Afiliados', 'carteira' => 'Carteiras', 'saque pendente' => 'Saques pedidos'] as $valor => $rotulo)
                            <button type="button" data-filtro="{{ $valor }}"
                                    class="filtro-devido px-3 py-1.5 rounded-full text-xs font-medium border {{ $valor === 'todos' ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                                {{ $rotulo }}
                            </button>
                        @endforeach
                        <input type="text" id="buscaDevido" placeholder="Buscar por nome..."
                               class="ml-auto px-3 py-1.5 border border-gray-300 rounded-lg text-sm w-48">
                    </div>

                    <div class="overflow-y-auto flex-1">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pessoa</th>
                                    <th class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                    <th class="px-6 py-2 text-right text-xs font-medium text-gray-500 uppercase">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($owedRows as $linha)
                                    @php
                                        $cor = [
                                            'criador'        => 'bg-blue-100 text-blue-700',
                                            'afiliado'       => 'bg-purple-100 text-purple-700',
                                            'carteira'       => 'bg-emerald-100 text-emerald-700',
                                            'saque pendente' => 'bg-orange-100 text-orange-700',
                                        ][$linha['tipo']];
                                    @endphp
                                    <tr class="linha-devido hover:bg-gray-50"
                                        data-tipo="{{ $linha['tipo'] }}"
                                        data-nome="{{ Str::lower($linha['user']->name . ' ' . $linha['user']->username) }}">
                                        <td class="px-6 py-3 text-sm text-gray-900">
                                            {{ $linha['user']->name }}
                                            <span class="text-gray-400">{{ '@' . $linha['user']->username }}</span>
                                        </td>
                                        <td class="px-6 py-3">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $cor }}">{{ $linha['tipo'] }}</span>
                                        </td>
                                        <td class="px-6 py-3 text-sm font-semibold text-gray-900 text-right">R$ {{ number_format($linha['valor'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                                <tr id="semResultado" class="hidden">
                                    <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-400">Ninguém encontrado com esse filtro.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 flex justify-between text-sm font-semibold text-gray-900">
                        <span id="totalDevidoRotulo">Total ({{ $owedRows->count() }})</span>
                        <span id="totalDevidoValor">R$ {{ number_format($owedToUsers, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <script>
                (function () {
                    const linhas = Array.from(document.querySelectorAll('.linha-devido'));
                    const busca = document.getElementById('buscaDevido');
                    const botoes = Array.from(document.querySelectorAll('.filtro-devido'));
                    const vazio = document.getElementById('semResultado');
                    let filtroAtual = 'todos';

                    // Os valores vêm do próprio DOM: o total do rodapé é sempre a soma do que está visível.
                    const valorDe = (tr) => parseFloat(
                        tr.lastElementChild.textContent.replace(/[^\d,]/g, '').replace(',', '.')
                    ) || 0;

                    function aplicar() {
                        const termo = busca.value.trim().toLowerCase();
                        let visiveis = 0, soma = 0;
                        linhas.forEach(tr => {
                            const casaTipo = filtroAtual === 'todos' || tr.dataset.tipo === filtroAtual;
                            const casaNome = termo === '' || tr.dataset.nome.includes(termo);
                            const mostrar = casaTipo && casaNome;
                            tr.classList.toggle('hidden', !mostrar);
                            if (mostrar) { visiveis++; soma += valorDe(tr); }
                        });
                        vazio.classList.toggle('hidden', visiveis > 0);
                        document.getElementById('totalDevidoRotulo').textContent = 'Total (' + visiveis + ')';
                        document.getElementById('totalDevidoValor').textContent =
                            'R$ ' + soma.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }

                    botoes.forEach(b => b.addEventListener('click', function () {
                        filtroAtual = this.dataset.filtro;
                        botoes.forEach(o => o.className = o.className
                            .replace('bg-gray-900 text-white border-gray-900', 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'));
                        this.className = this.className
                            .replace('bg-white text-gray-600 border-gray-300 hover:bg-gray-50', 'bg-gray-900 text-white border-gray-900');
                        aplicar();
                    }));
                    busca.addEventListener('input', aplicar);

                    document.getElementById('modalDevido').addEventListener('click', function (e) {
                        if (e.target === this) this.classList.add('hidden');
                    });
                })();
            </script>
        @endif

        <!-- Filtro de período + export -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
            <form method="GET" action="{{ route('admin.fluxo-caixa.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">De</label>
                    <input type="date" name="from" value="{{ $from }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Até</label>
                    <input type="date" name="to" value="{{ $to }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Tipo</label>
                    <select name="tipo" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="todos" @selected($tipo === 'todos')>Todos</option>
                        <option value="subscription_sale" @selected($tipo === 'subscription_sale')>Assinatura</option>
                        <option value="ppv_sale" @selected($tipo === 'ppv_sale')>Conteúdo Único</option>
                        <option value="cashout" @selected($tipo === 'cashout')>Saque</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Dono do saque</label>
                    <select name="dono" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="todos" @selected($dono === 'todos')>Todos</option>
                        <option value="creator" @selected($dono === 'creator')>Criador</option>
                        <option value="affiliate" @selected($dono === 'affiliate')>Afiliado</option>
                        <option value="platform" @selected($dono === 'platform')>Plataforma</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-700 text-sm font-medium">
                    Filtrar
                </button>
                <a href="{{ route('admin.fluxo-caixa.index', array_merge(request()->query(), ['export' => 'csv'])) }}"
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                    Exportar CSV
                </a>
            </form>
        </div>

        <!-- Cards de totais -->
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-2">Movimento do período</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-green-500">
                <p class="text-sm text-gray-500">Bruto recebido (vendas)</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($grossSales, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">Assinatura R$ {{ number_format($subTotal, 2, ',', '.') }} · PPV R$ {{ number_format($ppvTotal, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-red-500">
                <p class="text-sm text-gray-500">Taxas SuitPay</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($feeIn + $feeOut, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">Entrada R$ {{ number_format($feeIn, 2, ',', '.') }} · Saída R$ {{ number_format($feeOut, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-amber-500">
                <p class="text-sm text-gray-500">Comissão dos criadores</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($creatorPaid, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">Creditada nas vendas do período (não é o que foi sacado)</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-purple-500">
                <p class="text-sm text-gray-500">Comissão dos afiliados</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($affiliatePaid, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">Creditada nas vendas do período (não é o que foi sacado)</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-gray-400">
                <p class="text-sm text-gray-500">Total sacado (saídas)</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">R$ {{ number_format($cashoutTotal, 2, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-blue-700">
                <p class="text-sm text-gray-500">Receita líquida da plataforma</p>
                <p class="text-2xl font-bold {{ $platformNet >= 0 ? 'text-gray-900' : 'text-red-600' }} mt-1">R$ {{ number_format($platformNet, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-400 mt-1">Plataforma menos taxas SuitPay (entrada + saída)</p>
            </div>
        </div>

        <!-- Tabela -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900">Movimentos</h2>
                <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm font-semibold">
                    {{ $entries->total() }} registro(s)
                </span>
            </div>

            @if($entries->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bruto</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taxa SuitPay</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criador</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Afiliado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plataforma (líq)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($entries as $e)
                                @php
                                    $label = ['subscription_sale' => 'Assinatura', 'ppv_sale' => 'PPV', 'cashout' => 'Saque'][$e->entry_type] ?? $e->entry_type;
                                    // Saque sem withdrawal = retirada feita direto no painel do SuitPay (não passou pelo app),
                                    // então não tem dono nem franquia do dia — as etiquetas grátis/extra mentiriam aqui.
                                    $manual = $e->entry_type === 'cashout' && !$e->withdrawal;
                                    $platform = $e->entry_type === 'cashout'
                                        ? $e->withdraw_fee - $e->suitpay_fee
                                        : $e->gross_amount - $e->creator_amount - $e->affiliate_amount - $e->suitpay_fee;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">{{ $e->occurred_at->emBrasilia()->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            {{ $e->entry_type === 'cashout' ? 'bg-gray-100 text-gray-700' : 'bg-green-100 text-green-700' }}">
                                            {{ $label }}
                                        </span>
                                        @if($manual)
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 cursor-help"
                                                  title="Retirada feita direto no painel do SuitPay, fora do app. A taxa é a real cobrada no extrato.">
                                                retirada manual
                                            </span>
                                        @elseif($e->entry_type === 'cashout')
                                            {{-- por que a plataforma fica negativa aqui: a franquia do 1º saque do dia sai do nosso bolso --}}
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold cursor-help {{ $e->withdraw_fee > 0 ? 'bg-gray-100 text-gray-500' : 'bg-red-50 text-red-600' }}"
                                                  title="{{ $e->withdraw_fee > 0
                                                        ? 'Saque extra: não foi o primeiro do dia, então a taxa de 3,5% da SuitPay foi cobrada de quem sacou.'
                                                        : 'Saque grátis porque foi o primeiro do dia: a taxa de 3,5% da SuitPay ficou por nossa conta.' }}">
                                                {{ $e->withdraw_fee > 0 ? 'extra' : 'grátis' }}
                                            </span>
                                        @endif
                                        @if($e->entry_type === 'cashout' && $e->withdrawal?->user)
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $e->withdrawal->user->name }}
                                                <span class="text-gray-400">{{ '@' . $e->withdrawal->user->username }}</span>
                                                · {{ ['affiliate' => 'afiliado', 'platform' => 'plataforma'][$e->withdrawal->type] ?? 'criador' }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">R$ {{ number_format($e->gross_amount, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-red-600">R$ {{ number_format($e->suitpay_fee, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-blue-600">R$ {{ number_format($e->creator_amount, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-purple-600">R$ {{ number_format($e->affiliate_amount, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm {{ $platform >= 0 ? 'text-blue-700' : 'text-red-600' }} font-medium"
                                        @if($manual) title="Retirada manual: o valor é seu (só mudou de conta), então só a taxa do SuitPay entra como custo."
                                        @elseif($e->entry_type === 'cashout') title="{{ $e->withdraw_fee > 0 ? 'Saque extra: a taxa de 3,5% foi cobrada de quem sacou, então a plataforma fica zero a zero.' : 'Saque grátis do dia: a taxa de 3,5% da SuitPay ficou por nossa conta.' }}" @endif>
                                        R$ {{ number_format(round($platform, 2), 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr class="font-semibold text-gray-900">
                                <td class="px-4 py-3 text-sm">Total do filtro</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $filtered['count'] }} registro(s)</td>
                                <td class="px-4 py-3 text-sm">R$ {{ number_format($filtered['gross'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-red-600">R$ {{ number_format($filtered['fee'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-blue-600">R$ {{ number_format($filtered['creator'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm text-purple-600">R$ {{ number_format($filtered['affiliate'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-sm {{ $filtered['platform'] >= 0 ? 'text-blue-700' : 'text-red-600' }}">R$ {{ number_format(round($filtered['platform'], 2), 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($entries->hasPages())
                    <div class="mt-4 text-xs text-gray-400">O "Total do filtro" acima soma todos os {{ $filtered['count'] }} registros filtrados, não só esta página.</div>
                    <div class="mt-2">{{ $entries->links() }}</div>
                @endif
            @else
                <div class="text-center py-12 text-gray-500">
                    <p>Nenhum movimento no período selecionado.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
