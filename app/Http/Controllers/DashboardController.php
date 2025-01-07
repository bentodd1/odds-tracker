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
            ->orderBy('commence_time', 'asc')
            ->take(30)
            ->select('games.*')
            ->get();

        return $this->gameTransformationService->transformGames($games);
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
