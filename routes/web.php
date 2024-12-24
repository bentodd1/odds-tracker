<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Home page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Sports dashboards
Route::get('/nfl', [DashboardController::class, 'nfl'])->name('dashboard.nfl');
Route::get('/ncaaf', [DashboardController::class, 'ncaaf'])->name('dashboard.ncaaf');
Route::get('/nba', [DashboardController::class, 'nba'])->name('dashboard.nba');
Route::get('/mlb', [DashboardController::class, 'mlb'])->name('dashboard.mlb');
Route::get('/nhl', [DashboardController::class, 'nhl'])->name('dashboard.nhl');
