<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EarlyAccessController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\TrackVisitor;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\AuthController;
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

    Route::post('/signup', [EarlyAccessController::class, 'store'])->name('signup.store');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Subscription routes
    Route::get('/dashboard/subscribe', [SubscriptionController::class, 'show'])->name('dashboard.subscribe');
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('/subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');

    // Registration routes
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

});
