<?php

namespace App\Services;

use Carbon\Carbon;

class GameTransformationService
{
    /**
     * Transform a collection of games
     *
     * @param \Illuminate\Database\Eloquent\Collection $games
     * @return \Illuminate\Support\Collection
     */
    public function transformGames($games)
    {
        return $games->map(function ($game) {
            return $this->transformGame($game);
        });
    }

    /**
     * Transform a single game
     *
     * @param \App\Models\Game $game
     * @return array
     */
    public function transformGame($game)
    {
        $fpiData = $this->calculateFpiData($game);
        $casinoData = $this->transformCasinoData($game);
        $bestValues = $this->calculateBestValues($casinoData);

        return [
            'id' => $game->id,
            'commence_time' => $game->commence_time,
            'home_team' => [
                'name' => $game->homeTeam->name,
                'fpi' => $fpiData['home_fpi'],
                'win_probability' => $fpiData['home_win_probability'],
                'best_value_casinos' => $bestValues['home']
            ],
            'away_team' => [
                'name' => $game->awayTeam->name,
                'fpi' => $fpiData['away_fpi'],
                'win_probability' => $fpiData['away_win_probability'],
                'best_value_casinos' => $bestValues['away']
            ],
            'casinos' => $casinoData
        ];
    }

    /**
     * Calculate FPI data for both teams
     *
     * @param \App\Models\Game $game
     * @return array
     */
    private function calculateFpiData($game)
    {
        $homeTeamFpi = $game->homeTeam->latestFpi()->first();
        $awayTeamFpi = $game->awayTeam->latestFpi()->first();
        $fpiDiff = null;

        if ($homeTeamFpi && $awayTeamFpi) {
            $fpiDiff = $homeTeamFpi->rating - $awayTeamFpi->rating + 2;
            $homeWinProb = (1 / (1 + exp(-$fpiDiff/8)) * 100);
        }

        return [
            'home_fpi' => $homeTeamFpi ? $homeTeamFpi->rating : null,
            'away_fpi' => $awayTeamFpi ? $awayTeamFpi->rating : null,
            'home_win_probability' => $fpiDiff ? $homeWinProb : null,
            'away_win_probability' => $fpiDiff ? (100 - $homeWinProb) : null
        ];
    }

    /**
     * Transform casino data for spreads and money lines
     *
     * @param \App\Models\Game $game
     * @return array
     */
    private function transformCasinoData($game)
    {
        $casinoData = [];

        foreach ($game->spreads->groupBy('casino_id') as $casinoId => $casinoSpreads) {
            $spread = $casinoSpreads->sortByDesc('recorded_at')->first();
            $moneyLine = $game->moneyLines
                ->where('casino_id', $casinoId)
                ->sortByDesc('recorded_at')
                ->first();

            $casinoData[$spread->casino->name] = $this->formatCasinoEntry($spread, $moneyLine);
        }

        return $casinoData;
    }

    /**
     * Format a single casino's entry with spread and money line data
     *
     * @param \App\Models\Spread $spread
     * @param \App\Models\MoneyLine|null $moneyLine
     * @return array
     */
    private function formatCasinoEntry($spread, $moneyLine)
    {
        return [
            'spread' => [
                'home' => [
                    'line' => $spread->spread,
                    'odds' => $spread->home_odds,
                    'probability' => $spread->home_cover_probability_with_juice
                ],
                'away' => [
                    'line' => -$spread->spread,
                    'odds' => $spread->away_odds,
                    'probability' => $spread->away_cover_probability_with_juice
                ]
            ],
            'moneyLine' => [
                'home' => [
                    'odds' => $moneyLine ? $moneyLine->home_odds : null,
                    'probability' => $moneyLine ? $moneyLine->home_implied_probability : null
                ],
                'away' => [
                    'odds' => $moneyLine ? $moneyLine->away_odds : null,
                    'probability' => $moneyLine ? $moneyLine->away_implied_probability : null
                ]
            ],
            'updated_at' => $spread->recorded_at
        ];
    }

    /**
     * Calculate which casinos offer the best values for each team
     *
     * @param array $casinoData
     * @return array
     */
    private function calculateBestValues($casinoData)
    {
        $lowestHomeProbs = [];
        $lowestAwayProbs = [];

        foreach ($casinoData as $casinoName => $data) {
            // For home team
            $homeSpreadProb = $data['spread']['home']['probability'];
            $homeMLProb = isset($data['moneyLine']) ? $data['moneyLine']['home']['probability'] : 100;
            $lowestHomeProbs[$casinoName] = min($homeSpreadProb, $homeMLProb);

            // For away team
            $awaySpreadProb = $data['spread']['away']['probability'];
            $awayMLProb = isset($data['moneyLine']) ? $data['moneyLine']['away']['probability'] : 100;
            $lowestAwayProbs[$casinoName] = min($awaySpreadProb, $awayMLProb);
        }

        $minHomeProb = min($lowestHomeProbs);
        $minAwayProb = min($lowestAwayProbs);

        return [
            'home' => array_keys($lowestHomeProbs, $minHomeProb),
            'away' => array_keys($lowestAwayProbs, $minAwayProb)
        ];
    }
}
