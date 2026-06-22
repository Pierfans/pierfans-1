<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCreatorOnboarding
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user->creator_status === 'approved') {
            return redirect()->route('dashboard')
                ->with('success', 'Você já é um criador aprovado!');
        }
        return $next($request);
    }
}
