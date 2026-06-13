<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recordatorios automáticos de cuota: una pasada diaria sobre todos los tenants.
Schedule::command('reminders:quota')->dailyAt('07:00')->withoutOverlapping();

// Avisos de trial (7 días antes y al caducar): una pasada diaria.
Schedule::command('trials:notify')->dailyAt('08:00')->withoutOverlapping();

// Alertas de vencimiento de contratos (30 y 7 días): una pasada diaria.
Schedule::command('alerts:contracts')->dailyAt('08:30')->withoutOverlapping();
