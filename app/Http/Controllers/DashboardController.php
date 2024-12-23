<?php

namespace App\Http\Controllers;

use App\Models\Casino;
use App\Models\Game;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Get DraftKings and FanDuel casino IDs
        $casinoIds = Casino::whereIn('name', ['draftkings', 'fanduel'])
            ->pluck('id');

        $games = Game::with([
            'spreads' => function ($query) use ($casinoIds) {
                $query->whereIn('casino_id', $casinoIds);
            },
            'moneyLines' => function ($query) use ($casinoIds) {
                $query->whereIn('casino_id', $casinoIds);
            },
            'homeTeam',
            'awayTeam',
            'spreads.casino',
            'moneyLines.casino'
        ])
            ->where('commence_time', '>', Carbon::now())
            ->orderBy('commence_time', 'asc')
            ->take(10)
            ->get();

        return view('dashboard', compact('games'));
    }
}
