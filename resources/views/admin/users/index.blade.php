@extends('layouts.admin')

@section('title', 'Gerenciar Usuários')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Gerenciar Usuários</h1>
            <p class="text-gray-600 mt-2">Todos os usuários do sistema</p>
        </div>

        <!-- Busca -->
        <div class="mb-6 bg-white rounded-lg shadow-sm p-4">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-4">
                <div class="flex-1">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Buscar por nome ou email..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Buscar
                </button>
                @if(request('search'))
                    <a href="{{ route('admin.users.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Limpar
                    </a>
                @endif
            </form>
        </div>

        @if($users->count() > 0)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Criador</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data de Cadastro</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($users as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                        @if($user->username)
                                            <div class="text-sm text-gray-500"><span>@</span>{{ $user->username }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->creator_status === 'approved')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aprovado</span>
                                        @elseif($user->creator_status === 'pending')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendente</span>
                                        @elseif($user->creator_status === 'rejected')
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rejeitado</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Não é criador</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            {{ $user->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('admin.users.show', $user->id) }}"
                                           class="text-blue-600 hover:text-blue-900">Ver Detalhes</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginação -->
            <div class="mt-6">
                {{ $users->links() }}
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <p class="text-gray-600">Nenhum usuário encontrado.</p>
            </div>
        @endif
    </div>
@endsection

