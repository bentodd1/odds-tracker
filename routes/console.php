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
app(Schedule::class)->command('odds:fetch-current baseball_mlb')->everyFifteenMinutes();

app(Schedule::class)->command('espn:fetch-fpi')->daily();
app(Schedule::class)->command('espn:fetch-college-fpi')->daily();
app(Schedule::class)->command('espn:fetch-college-basketball-bpi')->daily();
app(Schedule::class)->command('espn:fetch-nba-bpi')->daily();

app(Schedule::class)->command('match:ncaab-scores')->daily();

// Dratings MLB predictions - run at 6 PM and 11:59 PM Central Time
app(Schedule::class)->command('dratings:fetch-mlb')
                    ->timezone('America/Chicago')
                    ->at('18:00')
                    ->appendOutputTo(storage_path('logs/dratings-mlb.log'));

app(Schedule::class)->command('dratings:fetch-mlb')
                    ->timezone('America/Chicago')
                    ->at('23:59')
                    ->appendOutputTo(storage_path('logs/dratings-mlb.log'));

// Add Kalshi weather data fetch hourly
app(Schedule::class)->command('kalshi:fetch-weather')
                     ->hourly()
                     ->appendOutputTo(storage_path('logs/kalshi-weather.log'));


