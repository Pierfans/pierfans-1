@props([
    'clearMobileNav' => true,
])

@auth
    @php
        $position = $clearMobileNav
            ? 'bottom-20 right-4 md:bottom-6 md:right-6'
            : 'bottom-6 right-4 md:bottom-6 md:right-6';
    @endphp
    <a
        href="https://wa.me/554796712232"
        target="_blank"
        rel="noopener noreferrer"
        class="fixed z-[60] flex h-14 w-14 items-center justify-center rounded-full shadow-lg ring-2 ring-[#25D366]/40 transition-transform hover:scale-105 hover:shadow-xl {{ $position }}"
        title="Fale conosco no WhatsApp"
        aria-label="Abrir conversa no WhatsApp"
    >
        <span class="absolute inset-0 overflow-hidden rounded-full bg-[#25D366]">
            <img
                src="{{ asset('img/contact.png') }}"
                alt=""
                class="h-full w-full object-cover"
                width="56"
                height="56"
                decoding="async"
            >
        </span>
        <span
            class="absolute -right-0.5 -top-0.5 z-10 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold leading-none text-white shadow ring-2 ring-white"
            aria-hidden="true"
        >1</span>
    </a>
@endauth
