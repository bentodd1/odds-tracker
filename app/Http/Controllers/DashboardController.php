<?php

namespace App\Http\Controllers;

use App\Models\Casino;
use App\Models\Game;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private function getFilteredGames($sportTitle)
    {
        // Get DraftKings and FanDuel casino IDs
        $casinoIds = Casino::whereIn('name', ['draftkings', 'fanduel'])
            ->pluck('id');

        return Game::with([
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
            ->join('sports', 'games.sport_id', '=', 'sports.id')
            ->where('sports.title', $sportTitle)
            ->where('commence_time', '>', Carbon::now())
            ->orderBy('commence_time', 'asc')
            ->take(30)
            ->select('games.*')  // This ensures we only get games columns
            ->get();
    }

    public function nfl()
    {
        $games = $this->getFilteredGames('nfl');
        return view('dashboard.nfl', compact('games'));
    }

    public function nba()
    {
        $games = $this->getFilteredGames('nba');
        return view('dashboard.nba', compact('games'));
    }

    public function mlb()
    {
        $games = $this->getFilteredGames('mlb');
        return view('dashboard.mlb', compact('games'));
    }

    public function nhl()
    {
        $games = $this->getFilteredGames('nhl');
        return view('dashboard.nhl', compact('games'));
    }

    public function ncaaf()
    {
        $games = $this->getFilteredGames('ncaaf');
        return view('dashboard.ncaaf', compact('games'));
    }
}
