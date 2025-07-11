<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule as ScheduleFacade;

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

// Run AccuWeather data collection hourly at 1 AM Central Time
app(Schedule::class)->command('fetch:weather-data')
    ->hourly()
    ->appendOutputTo(storage_path('logs/accuweather.log'));

// Record actual weather data daily at 2 AM Central Time (after predictions are collected)
app(Schedule::class)->command('weather:record-current')
                     ->timezone('America/Chicago')
                     ->dailyAt('02:00')
                     ->appendOutputTo(storage_path('logs/weather-current.log'));

// Schedule NWS weather data fetching for today and tomorrow
$today = now()->toDateString();
$tomorrow = now()->addDay()->toDateString();

app(Schedule::class)->command("weather:fetch-nws --date={$today}")
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/nws-weather.log'));

app(Schedule::class)->command("weather:fetch-nws --date={$tomorrow}")
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/nws-weather.log'));

// Schedule NWS actual weather recording
app(Schedule::class)->command('weather:record-nws-actual')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/nws-actual.log'));


