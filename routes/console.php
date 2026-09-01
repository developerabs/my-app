<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tenant:check-subscription')->daily();
Schedule::command('currency:update-rates')->dailyAt('12:00');
Schedule::command('app:apply-overdue-late-fees')
    ->dailyAt('00:15')
    ->withoutOverlapping()
    ->runInBackground();
