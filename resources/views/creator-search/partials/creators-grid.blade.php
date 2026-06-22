@if($creators->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($creators as $creator)
            <a href="{{ route('profile.show', $creator->username ?? $creator->slug) }}" class="block">
                <div class="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                    <!-- Imagem Principal (Cover) com Foto de Perfil sobreposta -->
                    <div class="relative w-full" style="height: 110px;">
                        @if($creator->cover_photo)
                            <img
                                src="{{ $creator->cover_photo_url }}"
                                alt="{{ $creator->name }}"
                                class="w-full h-full object-cover"
                            >
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-pink-200 to-orange-200"></div>
                        @endif

                        <!-- Foto de Perfil sobreposta na parte inferior -->
                        <div class="absolute bottom-0 left-4 transform translate-y-1/2">
                            <div class="relative">
                                <div class="w-20 h-20 rounded-full overflow-hidden border-4 border-white shadow-lg bg-white">
                                    @if($creator->profile_photo)
                                        <img
                                            src="{{ $creator->profile_photo_url }}"
                                            alt="{{ $creator->name }}"
                                            class="w-full h-full object-cover"
                                        >
                                    @else
                                        <div class="w-full h-full bg-gradient-to-br from-pink-300 to-orange-300 flex items-center justify-center text-white font-bold text-xl">
                                            {{ strtoupper(substr($creator->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informações do Criador -->
                    <div class="pt-10 pb-4 px-4">
                        <div class="mt-2">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">
                                {{ $creator->name ?? $creator->creator_full_name }}
                            </h3>
                            <p class="text-xs text-gray-500 truncate mt-1">
                                <span>@</span>{{ $creator->username ?? 'sem-username' }}
                            </p>
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    <!-- Paginação -->
    <div class="mt-8" id="pagination-container">
        {{ $creators->links() }}
    </div>
@else
    <div class="text-center py-12">
        <p class="text-gray-500 text-lg">
            Nenhum criador encontrado.
        </p>
        @if(request('search'))
            <p class="text-gray-400 text-sm mt-2">
                Tente buscar com outros termos.
            </p>
        @endif
    </div>
@endif

