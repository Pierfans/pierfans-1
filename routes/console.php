<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// O crontab do servidor já roda `php artisan schedule:run` a cada minuto (conferido 24/09/2026).
Schedule::command('chamadas:rodar')->everyMinute()->withoutOverlapping();
