@extends('layouts.admin')

@section('title', 'Carteiras')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Carteiras</h1>
            <p class="text-gray-600 mt-2">Gerencie as carteiras dos usuários</p>
        </div>

        <!-- Busca -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form method="GET" action="{{ route('admin.wallets.index') }}" class="flex gap-4">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}"
                    placeholder="Buscar por nome ou email..." 
                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                >
                <button 
                    type="submit" 
                    class="px-6 py-2 bg-pink-500 text-white rounded-lg hover:bg-pink-600 transition-colors"
                >
                    Buscar
                </button>
                @if(request('search'))
                    <a 
                        href="{{ route('admin.wallets.index') }}" 
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                    >
                        Limpar
                    </a>
                @endif
            </form>
        </div>

        <!-- Lista de Usuários -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        R$ {{ number_format($user->wallet ? $user->wallet->balance : 0, 2, ',', '.') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a 
                                        href="{{ route('admin.wallets.show', $user->id) }}" 
                                        class="text-pink-600 hover:text-pink-900"
                                    >
                                        Ver Detalhes
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    Nenhum usuário encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection

