<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

$schedule = new Schedule;
Schedule::command('odds:fetch-current americanfootball_nfl')->everyFifteenMinutes();
Schedule::command('odds:fetch-current americanfootball_ncaaf')->everyFifteenMinutes();
Schedule::command('espn:fetch-fpi')->daily();
Schedule::command('espn:fetch-college-fpi')->daily();
