<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'creator.onboarding' => \App\Http\Middleware\EnsureCreatorOnboarding::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         | Visitante batendo numa rota protegida: o padrao do Laravel e redirect()->guest(),
         | que grava a URL pedida como 'intended' pra devolver o usuario ali depois do login.
         |
         | Isso so faz sentido quando o pedido e uma NAVEGACAO. Se for subrecurso (uma <img>,
         | um <video>, um fetch) de uma pagina publica, o 'intended' vira o endereco do arquivo
         | e o proximo login joga o usuario nele em vez do dashboard. Aconteceu em 01/08/2026
         | com a imagem do post em destaque na tela de login (ver routes/web.php, post-media.stream).
         |
         | Sec-Fetch-Dest e mandado pelo browser: 'document' e navegacao, o resto e subrecurso.
         | Browser velho nao manda o header — nesse caso assumimos 'document' e nada muda.
         */
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return null; // deixa o padrao responder 401
            }

            if ($request->header('Sec-Fetch-Dest', 'document') === 'document') {
                return null; // navegacao de verdade: padrao (guest(), grava o intended)
            }

            // to(), nao guest(): redireciona sem gravar o intended
            return redirect()->to(route('login'));
        });
    })->create();
