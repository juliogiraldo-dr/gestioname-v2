<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recordatorios automáticos de cuota: una pasada diaria sobre todos los tenants.
Schedule::command('reminders:quota')->dailyAt('07:00')->withoutOverlapping();
