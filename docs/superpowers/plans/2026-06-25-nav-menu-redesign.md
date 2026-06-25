# Nav Menu Redesign — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adicionar labels aos itens do topnav desktop, indicar rota ativa em rosa, substituir ícone genérico de perfil por avatar real, e corrigir bug do `/chat` hardcoded.

**Architecture:** Dois componentes Blade independentes (`topnav.blade.php` e `bottomnav.blade.php`). Todas as mudanças são puramente no template — sem novas rotas, controllers ou migrations. O model `User` já expõe `profile_photo_url` via accessor.

**Tech Stack:** Laravel 11, Blade, TailwindCSS via CDN (sem purge — todas as classes funcionam), `request()->routeIs()` para active state.

---

## Arquivos modificados

| Arquivo | O que muda |
|---|---|
| `resources/views/components/topnav.blade.php` | Labels, active state, avatar, fix `/chat` hardcoded |
| `resources/views/components/bottomnav.blade.php` | Active state nos ícones mobile |

---

### Task 1: Corrigir bug `/chat` hardcoded no topnav

**Arquivo:** `resources/views/components/topnav.blade.php` (linha 39)

O topnav usa `href="/chat"` literal. O bottomnav já usa `route('chat.index')`. Isso impede que o active state do chat funcione corretamente via `request()->routeIs('chat.index')`.

- [ ] **Abrir** `resources/views/components/topnav.blade.php`

- [ ] **Localizar linha ~39** e substituir:

```blade
{{-- ANTES --}}
<a href="/chat"

{{-- DEPOIS --}}
<a href="{{ route('chat.index') }}"
```

- [ ] **Verificar** que o restante do atributo (`class`, `title`) permanece intacto.

- [ ] **Commitar:**
```bash
git add resources/views/components/topnav.blade.php
git commit -m "fix: usar route('chat.index') no topnav em vez de /chat hardcoded"
```

---

### Task 2: Reescrever o topnav com labels + active state

**Arquivo:** `resources/views/components/topnav.blade.php`

Substituição completa do conteúdo do arquivo. Os itens de nav ganham:
- Variáveis PHP de active state no topo (`$isHome`, `$isSearch`, etc.)
- Wrapper `<a>` com classes condicionais: ativo = `bg-pink-50 text-pink-500`, inativo = `text-gray-600 hover:text-pink-500 hover:bg-gray-50`
- Label `<span class="hidden lg:inline text-sm font-medium">` ao lado do ícone (aparece só em ≥1024px)
- Itens com `flex items-center gap-2` para alinhar ícone + label
- `gap-1` entre todos os itens (não `space-x-2` que depende de quantidade)

- [ ] **Substituir** o conteúdo completo de `resources/views/components/topnav.blade.php` por:

```blade
@php
    $isHome    = request()->routeIs('dashboard');
    $isSearch  = request()->routeIs('creator.search');
    $isCreate  = request()->routeIs('posts.create');
    $isChat    = request()->routeIs('chat.index');
@endphp

<nav class="fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 hidden md:block">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            {{-- Logo --}}
            <div class="flex-shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    <img class="w-20" src="/img/logo.svg" />
                </a>
            </div>

            {{-- Itens de navegação --}}
            <div class="flex items-center gap-1">

                @auth
                    {{-- Home --}}
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors {{ $isHome ? 'bg-pink-50 text-pink-500' : 'text-gray-600 hover:text-pink-500 hover:bg-gray-50' }}"
                       title="Home">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="hidden lg:inline text-sm font-medium">Home</span>
                    </a>
                @endauth

                {{-- Buscar Criadores --}}
                <a href="{{ route('creator.search') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors {{ $isSearch ? 'bg-pink-50 text-pink-500' : 'text-gray-600 hover:text-pink-500 hover:bg-gray-50' }}"
                   title="Buscar Criadores">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span class="hidden lg:inline text-sm font-medium">Buscar Criadores</span>
                </a>

                @auth
                    {{-- Criar Conteúdo — só para creators aprovados --}}
                    @if(Auth::user()->creator_status === 'approved')
                        <a href="{{ route('posts.create') }}"
                           class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors {{ $isCreate ? 'bg-pink-50 text-pink-500' : 'text-gray-600 hover:text-pink-500 hover:bg-gray-50' }}"
                           title="Criar Conteúdo">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="hidden lg:inline text-sm font-medium">Criar Conteúdo</span>
                        </a>
                    @endif

                    {{-- Chat --}}
                    <a href="{{ route('chat.index') }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg transition-colors {{ $isChat ? 'bg-pink-50 text-pink-500' : 'text-gray-600 hover:text-pink-500 hover:bg-gray-50' }}"
                       title="Chat">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <span class="hidden lg:inline text-sm font-medium">Chat</span>
                    </a>

                    {{-- Avatar --}}
                    <button onclick="openProfileOverlay()"
                            class="ml-2 flex-shrink-0 focus:outline-none"
                            title="Perfil">
                        @if(Auth::user()->profile_photo_url)
                            <img
                                src="{{ Auth::user()->profile_photo_url }}"
                                alt="{{ Auth::user()->name }}"
                                class="w-8 h-8 rounded-full object-cover ring-2 ring-transparent hover:ring-pink-300 transition-all"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            />
                            <span
                                style="display:none;"
                                class="w-8 h-8 rounded-full bg-pink-500 text-white text-sm font-bold items-center justify-center hover:ring-2 hover:ring-pink-300 transition-all">
                                {{ strtoupper(substr(Auth::user()->name ?? '?', 0, 1)) }}
                            </span>
                        @else
                            <span class="w-8 h-8 rounded-full bg-pink-500 text-white text-sm font-bold flex items-center justify-center hover:ring-2 hover:ring-pink-300 transition-all">
                                {{ strtoupper(substr(Auth::user()->name ?? '?', 0, 1)) }}
                            </span>
                        @endif
                    </button>

                @else
                    {{-- Visitante: botão de perfil genérico --}}
                    <button onclick="openProfileOverlay()"
                            class="p-2 text-gray-600 hover:text-pink-500 transition-colors rounded-lg hover:bg-gray-50"
                            title="Perfil">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </button>
                @endauth

            </div>
        </div>
    </div>
</nav>
```

- [ ] **Verificar** que o arquivo não tem `@{{ ... }}` ou sintaxe Blade inválida (o deploy.sh bloqueia isso automaticamente).

- [ ] **Commitar:**
```bash
git add resources/views/components/topnav.blade.php
git commit -m "feat: topnav com labels, active state e avatar real"
```

---

### Task 3: Adicionar active state ao bottomnav mobile

**Arquivo:** `resources/views/components/bottomnav.blade.php`

O bottomnav mantém só ícones (sem labels). Apenas adicionar feedback visual de rota ativa: ícone rosa na rota atual, cinza nas demais.

- [ ] **Substituir** o conteúdo completo de `resources/views/components/bottomnav.blade.php` por:

```blade
@php
    $isHome   = request()->routeIs('dashboard');
    $isSearch = request()->routeIs('creator.search');
    $isCreate = request()->routeIs('posts.create');
    $isChat   = request()->routeIs('chat.index');
@endphp

<nav class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 md:hidden">
    <div class="flex items-center justify-around h-16">

        @auth
            <a href="{{ route('dashboard') }}"
               class="flex flex-col items-center justify-center flex-1 h-full transition-colors {{ $isHome ? 'text-pink-500' : 'text-gray-500 hover:text-pink-500' }}"
               title="Home">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </a>
        @endauth

        <a href="{{ route('creator.search') }}"
           class="flex flex-col items-center justify-center flex-1 h-full transition-colors {{ $isSearch ? 'text-pink-500' : 'text-gray-500 hover:text-pink-500' }}"
           title="Buscar">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </a>

        @auth
            @if(Auth::user()->creator_status === 'approved')
                <a href="{{ route('posts.create') }}"
                   class="flex flex-col items-center justify-center flex-1 h-full transition-colors {{ $isCreate ? 'text-pink-500' : 'text-gray-500 hover:text-pink-500' }}"
                   title="Criar">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </a>
            @endif

            <a href="{{ route('chat.index') }}"
               class="flex flex-col items-center justify-center flex-1 h-full transition-colors relative {{ $isChat ? 'text-pink-500' : 'text-gray-500 hover:text-pink-500' }}"
               title="Mensagens">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </a>
        @endauth

        <button onclick="openProfileOverlay()"
           class="flex flex-col items-center justify-center flex-1 h-full transition-colors text-gray-500 hover:text-pink-500"
           title="Perfil">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </button>

    </div>
</nav>

<x-whatsapp-float />
```

- [ ] **Commitar:**
```bash
git add resources/views/components/bottomnav.blade.php
git commit -m "feat: bottomnav com active state nos icones mobile"
```

---

### Task 4: Deploy e verificação visual

- [ ] **Fazer deploy:**
```bash
bash deploy.sh "feat: redesign menu — labels desktop, active state, avatar real"
```
O `deploy.sh` faz add+commit+push+deploy automaticamente. Aguardar a conclusão.

- [ ] **Abrir** `https://pierfans.com.br/dashboard` em aba anônima (visitante) e verificar:
  - Topnav visível no desktop
  - "Buscar Criadores" aparece com ícone e label
  - Home, Chat, Avatar **não** aparecem (fora do `@auth`)
  - Sem erros PHP no console

- [ ] **Logar com conta fã** (sem `creator_status approved`) e verificar:
  - Home, Buscar Criadores, Chat aparecem com labels em lg+
  - Em tela md (768–1023px): só ícones, sem overflow
  - "Criar Conteúdo" **não** aparece
  - Avatar: se sem foto → inicial do nome em círculo rosa
  - Item ativo da rota atual destacado em rosa

- [ ] **Logar com conta creator** (`bento@pierfans.com` — `creator_status=approved`) e verificar:
  - "Criar Conteúdo" aparece entre Chat e Avatar
  - Active state funciona ao navegar entre rotas

- [ ] **Verificar avatar com foto:** logar com conta que tem `profile_photo` cadastrado e confirmar que a foto aparece no topnav.

- [ ] **Verificar mobile** (DevTools → toggle device ou celular real):
  - Bottomnav visível, topnav oculto
  - Ícone da rota ativa fica rosa
  - Sem labels visíveis

- [ ] **Testar breakpoint md exato** (DevTools → 768px): só um nav deve estar visível.
