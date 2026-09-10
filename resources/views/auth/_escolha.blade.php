{{-- Criadora e plano que o visitante escolheu antes de cair aqui: ele vê que não perdeu nada.
     $escolha vem do AuthController::escolhaPendente(); empty() segura quando a view é montada sem ela. --}}
@if(!empty($escolha['creator']))
    <div class="mb-4 p-4 bg-[#14d1bc]/10 border border-[#14d1bc]/40 rounded-lg flex items-center gap-3">
        @if($escolha['creator']->profile_photo)
            <img src="{{ $escolha['creator']->profile_photo_url }}" alt="" class="w-12 h-12 rounded-full object-cover flex-shrink-0">
        @else
            <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center font-semibold text-gray-600 flex-shrink-0">{{ strtoupper(substr($escolha['creator']->name, 0, 2)) }}</div>
        @endif
        <div class="text-sm min-w-0">
            <p class="font-semibold text-gray-900 truncate">{{ $escolha['creator']->name }} <span class="text-gray-500 font-normal">{{ '@' . $escolha['creator']->username }}</span></p>
            @if(!empty($escolha['plan']))
                <p class="text-gray-800">{{ $escolha['plan']->name }} · R$ {{ number_format($escolha['plan']->price, 2, ',', '.') }}</p>
                <p class="text-gray-500 text-xs">Depois de entrar você vai direto pro pagamento.</p>
            @else
                <p class="text-gray-500 text-xs">Depois de entrar você volta pro perfil.</p>
            @endif
        </div>
    </div>
@endif
