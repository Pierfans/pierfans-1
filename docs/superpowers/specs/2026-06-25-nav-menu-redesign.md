# Design Spec: Redesign do Menu de Navegação

**Data:** 2026-06-25
**Status:** Aprovado
**Solicitante:** Bento (CTO)

---

## Problema

O menu atual (`topnav.blade.php`) exibe apenas ícones sem labels. Usuários não sabem o que cada ícone faz — especificamente busca, criação de conteúdo e chat não são autoexplicativos. O ícone de perfil é genérico e não transmite identidade do usuário.

---

## Escopo

Dois arquivos Blade:
- `resources/views/components/topnav.blade.php` — navegação desktop
- `resources/views/components/bottomnav.blade.php` — navegação mobile

---

## Decisões de Design

### Desktop (`topnav.blade.php`)

**Estrutura:** ícone + label ao lado, visível apenas em `lg:` (≥1024px). Entre `md` e `lg` (768–1023px), exibe só ícones (evita overflow).

**Labels:**
| Rota | Label |
|---|---|
| `dashboard` | Home |
| `creator.search` | Buscar Criadores |
| `posts.create` | Criar Conteúdo *(só para `creator_status === 'approved'`)* |
| `chat.index` | Chat |

**Active state:** item ativo recebe `bg-pink-50 text-pink-500` no wrapper, ícone e label ficam rosa. Inativo: `text-gray-600 hover:text-pink-500`.

Implementação do active state com variável PHP para evitar repetição inline:
```blade
@php $isHome = request()->routeIs('dashboard'); @endphp
```

**Avatar:**
- Se `Auth::user()->profile_photo` existe: `<img>` circular com `onerror` fallback
- Se não existe: círculo com inicial do nome em rosa
- Deve estar dentro do bloco `@auth` — visitantes não podem chamar `Auth::user()`
- Confirmar se `profile_photo` armazena path relativo (usar `asset('storage/...')`) ou URL absoluta

**Bug a corrigir:** topnav usa `href="/chat"` hardcoded (linha 39). Unificar para `route('chat.index')` para que o active state funcione corretamente.

### Mobile (`bottomnav.blade.php`)

Sem alterações de conteúdo — mantém ícones sem labels (padrão de mercado mobile: Instagram, TikTok). Apenas aplicar active state visual consistente com o desktop (ícone rosa quando na rota ativa).

---

## Comportamento Responsivo

| Breakpoint | Comportamento |
|---|---|
| `< md` (< 768px) | `topnav` oculto, `bottomnav` visível — só ícones |
| `md` a `lg` (768–1023px) | `topnav` visível, labels ocultos (`hidden lg:inline`) — só ícones |
| `≥ lg` (≥ 1024px) | `topnav` visível, ícone + label ao lado |

---

## Edge Cases

- **Avatar null:** fallback para `<span>` com inicial do nome
- **Imagem 404:** `onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"` no `<img>`
- **Visitante não logado:** avatar e Home ficam dentro de `@auth`; Buscar Criadores fica fora (visível para todos)
- **`creator_status` null/pending/rejected:** botão "Criar Conteúdo" não aparece — `@if(Auth::user()->creator_status === 'approved')`
- **Nome vazio:** `strtoupper(substr(Auth::user()->name ?? '?', 0, 1))` com fallback
- **Layout sem "Criar Conteúdo"** (fã): os itens restantes devem manter espaçamento uniforme com `gap` fixo, não `space-x` dependente de quantidade

---

## O que NÃO muda

- Estrutura geral de dois navs (top + bottom)
- Lógica de visibilidade condicional por `creator_status`
- Mobile bottom nav permanece só com ícones
- Componentes `profile-overlay.blade.php` e `whatsapp-float.blade.php` não são tocados

---

## Checklist de Teste (QA)

- [ ] Desktop `lg+`: todos os labels visíveis ao lado dos ícones
- [ ] Desktop `md`: só ícones, sem overflow
- [ ] Mobile: bottom nav inalterado visualmente
- [ ] Item ativo destacado em rosa em cada rota (Home, Buscar, Chat)
- [ ] Usuário com foto: avatar exibe foto corretamente
- [ ] Usuário sem foto: exibe inicial do nome em círculo rosa
- [ ] Usuário com foto corrompida (404): fallback para inicial do nome
- [ ] Visitante não logado: sem erros JS/PHP, Buscar aparece, Home/Chat/Avatar não
- [ ] Fã sem `creator_status approved`: "Criar Conteúdo" não aparece
- [ ] Creator aprovado: "Criar Conteúdo" aparece
- [ ] Chat active state funciona (após fix do `/chat` hardcoded)
- [ ] Breakpoint `md` exato no DevTools: só um nav visível por vez
