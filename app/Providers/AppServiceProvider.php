<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // O app grava tudo em UTC, mas quem lê a tela está no Brasil: sem converter, todo
        // horário aparece 3h adiantado (uma venda das 17:28 vira 20:28). Ponto único de
        // conversão pra exibição — usar em QUALQUER data que vá pra tela ou pra CSV.
        Carbon::macro('emBrasilia', fn () => $this->copy()->setTimezone('America/Sao_Paulo'));
    }
}
