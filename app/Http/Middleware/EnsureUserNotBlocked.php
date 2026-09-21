<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Derruba quem esta bloqueado (bento 21/09, estorno de cartao: "bloqueia o usuario
 * assinante, para ele nao poder mais usar a plataforma"). Roda junto com o 'auth',
 * entao cobre o site logado inteiro, inclusive o admin.
 */
class EnsureUserNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->blocked_at) {
            $aviso = 'Sua conta foi bloqueada. Se você acha que houve engano, responda o e-mail que enviamos ou fale com o suporte.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $aviso], 403);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $aviso]);
        }

        return $next($request);
    }
}
