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
     * Get filtered and transformed games for a specific sport
     *
     * @param string $sportTitle
     * @param array $casinoNames
     * @return \Illuminate\Support\Collection
     */
    private function getFilteredGames(string $sportTitle, array $casinoNames = ['draftkings', 'fanduel', 'betmgm'])
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
            ->get();

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

    /**
     * Display NCAAF games dashboard
     *
     * @param Request $request
     * @return View
     */
    public function ncaaf(Request $request): View
    {
        $casinos = $this->getCasinos($request);
        $games = $this->getFilteredGames('ncaaf', $casinos['selectedCasinos']);

        return view('dashboard.ncaaf', [
            'games' => $games,
            'sport' => 'NCAAF',
            'availableCasinos' => $casinos['availableCasinos'],
            'selectedCasinos' => $casinos['selectedCasinos']
        ]);
    }

    /**
     * Display NCAAB games dashboard
     *
     * @param Request $request
     * @return View
     */
    public function ncaab(Request $request): View
    {
        $casinos = $this->getCasinos($request);
        $games = $this->getFilteredGames('ncaab', $casinos['selectedCasinos']);

        return view('dashboard.ncaab', [
            'games' => $games,
            'sport' => 'NCAAB',
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

        return view('dashboard.coming-soon', [
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

        return view('dashboard.coming-soon', [
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

        $defaultCasinos = ['draftkings', 'fanduel', 'betmgm'];

        // Handle array or string input
        $selectedCasinos = $request->input('casinos');
        if (is_array($selectedCasinos)) {
            $selectedCasinos = $selectedCasinos[0] ?? '';
        }
        $selectedCasinos = $selectedCasinos ? explode(',', $selectedCasinos) : $defaultCasinos;

        $selectedCasinos = array_slice($selectedCasinos, 0, 3);
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
