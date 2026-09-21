<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled work
|--------------------------------------------------------------------------
| Add one cron job on the server so these run:
|
|   * * * * * cd /path-to-the-project && php artisan schedule:run >> /dev/null 2>&1
*/

Schedule::command('epic:resolve-visitor-locations')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('epic:prune-visits')
    ->dailyAt('03:15');
