<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EarlyAccessController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\TrackVisitor;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WeatherPredictionController;
use App\Http\Controllers\AccuWeatherAnalysisController;
use App\Http\Controllers\NwsWeatherAnalysisController;
use Illuminate\Support\Facades\Route;

Route::middleware([TrackVisitor::class])->group(function () {
    // Home page
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Sports dashboards
    Route::get('/nfl', [DashboardController::class, 'nfl'])->name('dashboard.nfl');
    Route::get('/ncaaf', [DashboardController::class, 'ncaaf'])->name('dashboard.ncaaf');
    Route::get('/nba', [DashboardController::class, 'nba'])->name('dashboard.nba');
    Route::get('/mlb', [DashboardController::class, 'mlb'])->name('dashboard.mlb');
    Route::get('/nhl', [DashboardController::class, 'nhl'])->name('dashboard.nhl');
    Route::get('/ncaab', [DashboardController::class, 'ncaab'])->name('dashboard.ncaab');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Subscription routes
    Route::get('/dashboard/subscribe', [SubscriptionController::class, 'show'])->name('dashboard.subscribe');
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('/subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');

    // Registration routes
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Auth routes
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Weather predictions routes
    Route::get('/weather-predictions', [WeatherPredictionController::class, 'index'])->name('weather-predictions.index');
    Route::get('/weather-predictions/stats', [WeatherPredictionController::class, 'stats'])->name('weather-predictions.stats');
    Route::get('/weather-predictions/{city}', [WeatherPredictionController::class, 'show'])->name('weather-predictions.show');
    
    // AccuWeather analysis route
    Route::get('/accuweather/analysis', [AccuWeatherAnalysisController::class, 'index'])->name('accuweather.analysis');
    
    // NWS analysis route
    Route::get('/nws/analysis', [NwsWeatherAnalysisController::class, 'index'])->name('nws.analysis');

    // Weather Dashboard route
    Route::get('/dashboard/weather', [\App\Http\Controllers\WeatherDashboardController::class, 'index'])->name('dashboard.weather');
    Route::get('/dashboard/nws-weather', [\App\Http\Controllers\WeatherDashboardController::class, 'nwsIndex'])->name('dashboard.nws-weather');
});
