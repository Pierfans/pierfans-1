<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Meu Conteúdo - {{ config('app.name', 'Laravel') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/profile-overlay.css">
    <script src="/js/app.js"></script>
    <script src="/js/profile-overlay.js"></script>

    <style>
        body { background: #F5F7FA; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
    </style>
</head>
<body class="text-[#1a202c] min-h-screen">

    <x-topnav />

    <main class="max-w-5xl mx-auto px-4 pt-20 md:pt-24 pb-28">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Meu Conteúdo</h1>
                <p class="text-gray-600 text-sm mt-1">Veja e gerencie todas as suas publicações</p>
            </div>
            <a href="{{ route('posts.create') }}" class="bg-pink-600 hover:bg-pink-700 text-white text-sm font-medium px-4 py-2 rounded-lg">+ Criar</a>
        </div>

        @if($posts->count() > 0)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Publicação</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mídias</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($posts as $post)
                                @php $first = $post->media->first(); @endphp
                                <tr data-post-id="{{ $post->id }}">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                                                @if($first && $first->file_type === 'image')
                                                    <img src="{{ $first->url }}" alt="" class="w-full h-full object-cover">
                                                @elseif($first)
                                                    <span class="text-gray-400 text-xs">🎬</span>
                                                @else
                                                    <span class="text-gray-300 text-xs">—</span>
                                                @endif
                                            </div>
                                            <span class="text-sm text-gray-900 max-w-xs truncate">{{ Str::limit($post->description ?? 'Sem descrição', 60) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($post->visibility === 'free')
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Gratuito</span>
                                        @elseif($post->visibility === 'paid')
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Conteúdo Único</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Assinantes</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $post->media->count() }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $post->created_at->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                        <div class="flex gap-3">
                                            <a href="{{ route('posts.edit', $post->id) }}" class="text-green-600 hover:text-green-900">Editar</a>
                                            <button onclick="deleteMyPost({{ $post->id }})" class="text-red-600 hover:text-red-900">Excluir</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($posts->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $posts->firstItem() }}–{{ $posts->lastItem() }} de {{ $posts->total() }}</span>
                        <div class="flex gap-2">
                            @if(!$posts->onFirstPage())
                                <a href="{{ $posts->previousPageUrl() }}" class="px-3 py-1 text-pink-600 hover:text-pink-800 text-sm">Anterior</a>
                            @endif
                            @if($posts->hasMorePages())
                                <a href="{{ $posts->nextPageUrl() }}" class="px-3 py-1 text-pink-600 hover:text-pink-800 text-sm">Próximo</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <p class="text-gray-600">Você ainda não tem publicações.</p>
                <a href="{{ route('posts.create') }}" class="inline-block mt-3 text-pink-600 hover:text-pink-800 font-medium">Criar minha primeira publicação</a>
            </div>
        @endif
    </main>

    <x-bottomnav />
    <x-profile-overlay />

    <script>
        function deleteMyPost(id) {
            if (!confirm('Excluir esta publicação? Ela vai para a lixeira e pode ser restaurada pelo suporte.')) {
                return;
            }
            fetch(`/posts/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.querySelector(`tr[data-post-id="${id}"]`)?.remove();
                } else {
                    alert(data.message || 'Erro ao excluir publicação');
                }
            })
            .catch(() => alert('Erro ao excluir publicação'));
        }
    </script>
</body>
</html>
