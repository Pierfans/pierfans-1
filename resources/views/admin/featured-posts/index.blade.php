@extends('layouts.admin')

@section('title', 'Posts em Destaque')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Posts em Destaque</h1>
            <p class="text-gray-600 mt-2">Gerencie quais posts gratuitos aparecem na tela de login e no dashboard</p>
        </div>

        @if($posts->count() > 0)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mídia</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criadora</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visibilidade</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Exibir no Login</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Exibir no Dashboard</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($posts as $post)
                                @php $firstMedia = $post->media->first(); @endphp
                                <tr class="hover:bg-gray-50">
                                    <!-- Mídia -->
                                    <td class="px-6 py-4">
                                        @if($firstMedia)
                                            @if($firstMedia->file_type === 'image')
                                                <img src="{{ $firstMedia->url }}" alt="preview"
                                                     class="w-20 h-20 object-cover rounded-lg">
                                            @else
                                                <div class="w-20 h-20 bg-gray-900 rounded-lg flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M8 5v14l11-7z"/>
                                                    </svg>
                                                </div>
                                            @endif
                                        @else
                                            <div class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Criadora -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            @if($post->user->profile_photo_url)
                                                <img src="{{ $post->user->profile_photo_url }}" alt="{{ $post->user->name }}"
                                                     class="w-10 h-10 rounded-full object-cover mr-3">
                                            @else
                                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $post->user->name ?? $post->user->creator_full_name }}</div>
                                                <div class="text-sm text-gray-500">{{ $post->user->username }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Visibilidade -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($post->visibility === 'free')
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Gratuito</span>
                                        @else
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Assinante</span>
                                        @endif
                                    </td>

                                    <!-- Toggle Login -->
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input
                                                type="checkbox"
                                                class="sr-only peer"
                                                {{ $post->featured_on_login ? 'checked' : '' }}
                                                onchange="toggleFeaturedLogin({{ $post->id }}, this.checked, this)"
                                                id="login-{{ $post->id }}"
                                            >
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#14d1bc]"></div>
                                        </label>
                                    </td>

                                    <!-- Toggle Dashboard -->
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input
                                                type="checkbox"
                                                class="sr-only peer"
                                                {{ $post->featured_on_dashboard ? 'checked' : '' }}
                                                onchange="toggleFeaturedDashboard({{ $post->id }}, this.checked, this)"
                                                id="dashboard-{{ $post->id }}"
                                            >
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#14d1bc]"></div>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">
                {{ $posts->links() }}
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <p class="text-gray-600">Nenhum post gratuito encontrado.</p>
            </div>
        @endif
    </div>

    <script>
        function toggleFeaturedLogin(postId, value, checkbox) {
            $.ajax({
                url: `/admin/featured-posts/${postId}/toggle-login`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function(response) {
                    checkbox.checked = response.featured_on_login;
                },
                error: function(xhr) {
                    checkbox.checked = !value;
                    alert(xhr.responseJSON?.message || 'Erro ao atualizar post');
                }
            });
        }

        function toggleFeaturedDashboard(postId, value, checkbox) {
            $.ajax({
                url: `/admin/featured-posts/${postId}/toggle-dashboard`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function(response) {
                    checkbox.checked = response.featured_on_dashboard;
                },
                error: function(xhr) {
                    checkbox.checked = !value;
                    alert(xhr.responseJSON?.message || 'Erro ao atualizar post');
                }
            });
        }
    </script>
@endsection
