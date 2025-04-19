<?php

namespace App\Http\Controllers;

use App\Models\Casino;
use App\Models\Game;
use App\Services\GameTransformationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Remove service injection since we'll create sport-specific instances
    }

    /**
     * Calculate game weight based on team BPI ratings and time to game
     *
     * @param float $homeBpi
     * @param float $awayBpi
     * @param Carbon $commenceTime
     * @return float
     */
    private function calculateGameWeight(float $homeBpi, float $awayBpi, Carbon $commenceTime): float
    {
        $combinedBpi = $homeBpi + $awayBpi;
        $hoursUntilGame = now()->diffInHours($commenceTime);
        
        $timeMultiplier = match(true) {
            $hoursUntilGame <= 24 => 1.5,  // Next 24 hours
            $hoursUntilGame <= 48 => 1.25, // 1-2 days away
            $hoursUntilGame <= 72 => 1.1,  // 2-3 days away
            default => 1.0
        };

        return $combinedBpi * $timeMultiplier;
    }

    /**
     * Get filtered and transformed games for a specific sport
     *
     * @param string $sportTitle
     * @param array $casinoNames
     * @return \Illuminate\Support\Collection
     */
    private function getFilteredGames(string $sportTitle, array $casinoNames = ['novig', 'draftkings', 'fanduel'])
    {
        // Get casino IDs
        $casinoIds = Casino::whereIn('name', $casinoNames)
            ->pluck('id');

        $oneDayAgo = Carbon::now()->subDay();

        $games = Game::with([
            'spreads' => fn($query) => $query
                ->whereIn('casino_id', $casinoIds)
                ->where('created_at', '>=', $oneDayAgo),
            'moneyLines' => fn($query) => $query
                ->whereIn('casino_id', $casinoIds)
                ->where('created_at', '>=', $oneDayAgo),
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
            ->whereHas('spreads', function($query) use ($casinoIds, $oneDayAgo) {
                $query->whereIn('casino_id', $casinoIds)
                    ->where('created_at', '>=', $oneDayAgo);
            })
            ->orderBy('commence_time', 'asc')
            ->take(30)
            ->select('games.*')
            ->get()
            ->map(function($game) {
                $homeBpi = $game->homeTeam->latestFpi?->rating ?? 0;
                $awayBpi = $game->awayTeam->latestFpi?->rating ?? 0;
                
                $game->weight = $this->calculateGameWeight(
                    $homeBpi,
                    $awayBpi,
                    $game->commence_time
                );
                return $game;
            })
            ->sortByDesc('weight');

        // Create sport-specific transformation service
        $transformationService = new GameTransformationService(strtolower($sportTitle));
        return $transformationService->transformGames($games);
    }

    /**
     * Display NFL games dashboard
     *
     * @param Request $request
     * @return View
     */
    public function nfl(Request $request): View
    {
        $casinos = $this->getCasinos($request);
        $games = $this->getFilteredGames('nfl', $casinos['selectedCasinos']);

        return view('dashboard.nfl', [
            'games' => $games,
            'sport' => 'NFL',
            'availableCasinos' => $casinos['availableCasinos'],
            'selectedCasinos' => $casinos['selectedCasinos']
        ]);
    }

    public function ncaaf(Request $request): View
    {
        $casinos = $this->getCasinos($request);
        $games = $this->getFilteredGames('ncaaf', $casinos['selectedCasinos']);

        return view('dashboard.ncaaf', [
            'games' => $games,
            'sport' => 'ncaaf',  // Make sure this is set
            'availableCasinos' => $casinos['availableCasinos'],
            'selectedCasinos' => $casinos['selectedCasinos']
        ]);
    }

    public function ncaab(Request $request): View
    {
        $casinos = $this->getCasinos($request);
        $games = $this->getFilteredGames('ncaab', $casinos['selectedCasinos']);

        return view('dashboard.ncaab', [
            'games' => $games,
            'sport' => 'ncaab',  // Make sure this is set
            'availableCasinos' => $casinos['availableCasinos'],
            'selectedCasinos' => $casinos['selectedCasinos']
        ]);
    }
    /**
     * Display NBA games dashboard
     *
     * @param Request $request
     * @return View
     */
    public function nba(Request $request): View
    {
        $casinos = $this->getCasinos($request);
        $games = $this->getFilteredGames('nba', $casinos['selectedCasinos']);

        return view('dashboard.nba', [
            'games' => $games,
            'sport' => 'NBA',
            'availableCasinos' => $casinos['availableCasinos'],
            'selectedCasinos' => $casinos['selectedCasinos']
        ]);
    }

    /**
     * Display MLB games dashboard
     *
     * @param Request $request
     * @return View
     */
    public function mlb(Request $request): View
    {
        $casinos = $this->getCasinos($request);
        $games = $this->getFilteredGames('mlb', $casinos['selectedCasinos']);

        return view('dashboard.mlb', [
            'games' => $games,
            'sport' => 'MLB',
            'availableCasinos' => $casinos['availableCasinos'],
            'selectedCasinos' => $casinos['selectedCasinos']
        ]);
    }

    /**
     * Display NHL games dashboard
     *
     * @param Request $request
     * @return View
     */
    public function nhl(Request $request): View
    {
        $casinos = $this->getCasinos($request);
        $games = $this->getFilteredGames('nhl', $casinos['selectedCasinos']);

        return view('dashboard.coming-soon', [
            'games' => $games,
            'sport' => 'NHL',
            'availableCasinos' => $casinos['availableCasinos'],
            'selectedCasinos' => $casinos['selectedCasinos']
        ]);
    }

    /**
     * Get available and selected casinos
     *
     * @param Request $request
     * @return array
     */
    private function getCasinos(Request $request): array
    {
        $availableCasinos = Casino::where('is_active', true)
            ->orderBy('name')
            ->get();

        $defaultCasinos = ['novig', 'fanduel', 'draftkings', 'betmgm'];

        // Handle array or string input
        $selectedCasinos = $request->input('casinos');
        if (is_array($selectedCasinos)) {
            $selectedCasinos = $selectedCasinos[0] ?? '';
        }
        $selectedCasinos = $selectedCasinos ? explode(',', $selectedCasinos) : $defaultCasinos;

        $selectedCasinos = array_slice($selectedCasinos, 0, 4);
        $validCasinos = $availableCasinos->pluck('name')->toArray();
        $selectedCasinos = array_intersect($selectedCasinos, $validCasinos);

        if (empty($selectedCasinos)) {
            $selectedCasinos = array_intersect($defaultCasinos, $validCasinos);
        }

        return [
            'availableCasinos' => $availableCasinos,
            'selectedCasinos' => $selectedCasinos
        ];
    }
}
