<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

app(Schedule::class)->command('odds:fetch-current americanfootball_nfl')->everyFifteenMinutes();
app(Schedule::class)->command('odds:fetch-current americanfootball_ncaaf')->everyFifteenMinutes();
app(Schedule::class)->command('odds:fetch-current basketball_ncaab')->everyFifteenMinutes();
app(Schedule::class)->command('odds:fetch-current basketball_nba')->everyFifteenMinutes();

app(Schedule::class)->command('espn:fetch-fpi')->daily();
app(Schedule::class)->command('espn:fetch-college-fpi')->daily();
app(Schedule::class)->command('espn:fetch-college-basketball-bpi')->daily();
app(Schedule::class)->command('espn:fetch-nba-bpi')->daily();

app(Schedule::class)->command('match:ncaab-scores')->daily();


