{{-- Exige pelo menos um plano com is_active = true para visibilidade "Somente assinantes" --}}
<div id="noPlansModal" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4 sm:p-6">
    <div class="js-no-plans-modal-backdrop absolute inset-0 bg-gray-900/55 backdrop-blur-[2px] transition-opacity" aria-hidden="true"></div>

    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="noPlansModalTitle"
        class="relative w-full max-w-md animate-fade-in"
    >
        <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-2xl shadow-gray-900/10 ring-1 ring-black/5">
            <div class="h-1.5 w-full bg-gradient-to-r from-pink-500 via-rose-500 to-orange-400"></div>

            <div class="relative px-6 pb-6 pt-5 sm:px-8 sm:pb-8 sm:pt-6">
                <button
                    type="button"
                    onclick="closeNoPlansModal()"
                    class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                    aria-label="Fechar"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-pink-50 to-orange-50 ring-1 ring-pink-100/80">
                    <svg class="h-7 w-7 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>

                <h3 id="noPlansModalTitle" class="pr-10 text-center text-xl font-bold tracking-tight text-gray-900">
                    Planos de assinatura
                </h3>
                <p class="mt-2 text-center text-sm leading-relaxed text-gray-500">
                    Para publicar como <span class="font-semibold text-gray-700">Somente assinantes</span>, é preciso ter pelo menos um plano <span class="text-pink-600">ativo</span>.
                </p>

                <ul class="mt-6 space-y-3">
                    <li class="flex gap-3 rounded-xl bg-gray-50/90 px-4 py-3 text-sm leading-snug text-gray-600 ring-1 ring-gray-100">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>Planos <strong class="font-semibold text-gray-800">inativos</strong> não permitem esse tipo de visibilidade em novas postagens.</span>
                    </li>
                    <li class="flex gap-3 rounded-xl bg-gray-50/90 px-4 py-3 text-sm leading-snug text-gray-600 ring-1 ring-gray-100">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-pink-100 text-pink-600">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span>Por agora, a visibilidade volta para <strong class="font-semibold text-gray-800">Gratuito</strong> ao fechar esta janela.</span>
                    </li>
                </ul>

                <a
                    href="{{ route('subscription-plans.index') }}"
                    class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl border border-pink-200 bg-pink-50/50 px-4 py-3 text-sm font-semibold text-pink-700 transition-colors hover:border-pink-300 hover:bg-pink-50"
                >
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    Abrir planos de assinatura
                </a>

                <button
                    type="button"
                    onclick="closeNoPlansModal()"
                    class="mt-3 w-full rounded-xl bg-orange-500 px-4 py-3.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                >
                    Postar gratuito
                </button>
            </div>
        </div>
    </div>
</div>
