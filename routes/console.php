<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

$schedule = new Schedule;
$schedule->command('odds:fetch-current americanfootball_nfl')->everyFifteenMinutes();
$schedule->command('odds:fetch-current americanfootball_ncaaf')->everyFifteenMinutes();
$schedule->command('espn:fetch-fpi')->daily();
$schedule->command('espn:fetch-college-fpi')->daily();
