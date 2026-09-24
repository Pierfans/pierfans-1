@extends('layouts.admin')

@section('title', 'Chamadas de vídeo')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Chamadas de vídeo</h1>
            <p class="text-gray-600 mt-2">Pedidos pagos pelos fãs. O dinheiro fica reservado até a criadora entrar na sala.</p>
        </div>

        <div class="mb-6 bg-white rounded-lg shadow-sm p-4">
            <div class="flex flex-wrap gap-2">
                @foreach(['todas' => 'Todas', 'requested' => 'Aguardando marcar', 'scheduled' => 'Marcadas', 'done' => 'Realizadas', 'refunded' => 'Devolvidas'] as $chave => $rotulo)
                    <a href="{{ route('admin.chamadas.index', ['filter' => $chave]) }}"
                       class="px-4 py-2 rounded-lg {{ $filtro === $chave ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $rotulo }}{{ $chave !== 'todas' ? ' (' . ($contagens[$chave] ?? 0) . ')' : '' }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach(['#', 'Pedido em', 'Criadora', 'Fã', 'Valor', 'Duração', 'Estado', 'Marcada pra', 'Ela entrou', 'Devolução'] as $th)
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $th }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($chamadas as $c)
                            @php($sp = fn ($d) => $d ? $d->copy()->setTimezone('America/Sao_Paulo')->format('d/m H:i') : '-')
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $c->id }}</td>
                                <td class="px-6 py-4 text-sm">{{ $sp($c->created_at) }}</td>
                                <td class="px-6 py-4 text-sm">@{{ $c->creator->username ?? $c->creator_id }}</td>
                                <td class="px-6 py-4 text-sm">{{ $c->user->name ?? $c->user_id }} (#{{ $c->user_id }})</td>
                                <td class="px-6 py-4 text-sm">R$ {{ number_format($c->amount_paid, 2, ',', '.') }}<br><span class="text-xs text-gray-500">criadora R$ {{ number_format($c->creator_amount, 2, ',', '.') }}</span></td>
                                <td class="px-6 py-4 text-sm">{{ $c->duration_minutes }} min</td>
                                <td class="px-6 py-4 text-sm">{{ $c->statusLabel() }}</td>
                                <td class="px-6 py-4 text-sm">{{ $sp($c->scheduled_at) }}</td>
                                <td class="px-6 py-4 text-sm">{{ $sp($c->creator_joined_at) }}</td>
                                <td class="px-6 py-4 text-sm">{{ $c->refund_reason ? $c->refund_reason . ' em ' . $sp($c->refunded_at) : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-6 py-8 text-center text-gray-500">Nenhuma chamada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $chamadas->links() }}</div>
        </div>
    </div>
@endsection
