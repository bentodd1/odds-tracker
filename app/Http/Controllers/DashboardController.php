<?php

namespace App\Http\Controllers;

use App\Models\Casino;
use App\Models\Game;
use App\Services\GameTransformationService;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected $gameTransformationService;

    /**
     * Create a new controller instance.
     *
     * @param GameTransformationService $gameTransformationService
     */
    public function __construct(GameTransformationService $gameTransformationService)
    {
        $this->gameTransformationService = $gameTransformationService;
    }

    /**
     * Get filtered and transformed games for a specific sport
     *
     * @param string $sportTitle
     * @return \Illuminate\Support\Collection
     */
    private function getFilteredGames(string $sportTitle)
    {
        // Get DraftKings and FanDuel casino IDs
        $casinoIds = Casino::whereIn('name', ['draftkings', 'fanduel'])
            ->pluck('id');

        $games = Game::with([
            'spreads' => fn($query) => $query->whereIn('casino_id', $casinoIds),
            'moneyLines' => fn($query) => $query->whereIn('casino_id', $casinoIds),
            'homeTeam',
            'awayTeam',
            'spreads.casino',
            'moneyLines.casino',
            'homeTeam.latestFpi',
            'awayTeam.latestFpi'
        ])
            ->join('sports', 'games.sport_id', '=', 'sports.id')
            ->where('sports.title', $sportTitle)
            ->where('commence_time', '>', Carbon::now())
            ->orderBy('commence_time', 'asc')
            ->take(30)
            ->select('games.*')
            ->get();

        return $this->gameTransformationService->transformGames($games);
    }

    /**
     * Display NFL games dashboard
     *
     * @return View
     */
    public function nfl(): View
    {
        $games = $this->getFilteredGames('nfl');
        return view('dashboard.nfl', [
            'games' => $games,
            'sport' => 'NFL'
        ]);
    }

    /**
     * Display NBA games dashboard
     *
     * @return View
     */
    public function nba(): View
    {
        $games = $this->getFilteredGames('nba');
        return view('dashboard.coming-soon', [
            'games' => $games,
            'sport' => 'NBA'
        ]);
    }

    /**
     * Display MLB games dashboard
     *
     * @return View
     */
    public function mlb(): View
    {
        $games = $this->getFilteredGames('mlb');
        return view('dashboard.coming-soon', [
            'games' => $games,
            'sport' => 'MLB'
        ]);
    }

    /**
     * Display NHL games dashboard
     *
     * @return View
     */
    public function nhl(): View
    {
        $games = $this->getFilteredGames('nhl');
        return view('dashboard.coming-soon', [
            'games' => $games,
            'sport' => 'NHL'
        ]);
    }

    /**
     * Display NCAAF games dashboard
     *
     * @return View
     */
    public function ncaaf(): View
    {
        $games = $this->getFilteredGames('ncaaf');
        return view('dashboard.ncaaf', [
            'games' => $games,
            'sport' => 'NCAAF'
        ]);
    }
}
