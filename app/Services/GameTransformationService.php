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

        $commenceTime = $game->commence_time->subHours(6);


        return [
            'id' => $game->id,
            'commence_time' =>$commenceTime,
            'home_team' => [
                'name' => $game->homeTeam->name,
                'fpi' => $fpiData['home_fpi'],
                'win_probability' => $fpiData['home_win_probability'],
                'best_value' => [     // Added this structure
                    'casino' => $bestValues['home']['casino'],
                    'type' => $bestValues['home']['type']
                ]
            ],
            'away_team' => [
                'name' => $game->awayTeam->name,
                'fpi' => $fpiData['away_fpi'],
                'win_probability' => $fpiData['away_win_probability'],
                'best_value' => [     // Added this structure
                    'casino' => $bestValues['away']['casino'],
                    'type' => $bestValues['away']['type']
                ]
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
        $lowestHomeProb = 100;
        $lowestAwayProb = 100;
        $bestHomeBook = null;
        $bestHomeBetType = null;
        $bestAwayBook = null;
        $bestAwayBetType = null;

        foreach ($casinoData as $casinoName => $data) {
            // Check home team probabilities
            $homeSpreadProb = $data['spread']['home']['probability'];
            if ($homeSpreadProb < $lowestHomeProb) {
                $lowestHomeProb = $homeSpreadProb;
                $bestHomeBook = $casinoName;
                $bestHomeBetType = 'spread';
            }

            if (isset($data['moneyLine']) && $data['moneyLine']['home']['probability'] < $lowestHomeProb) {
                $lowestHomeProb = $data['moneyLine']['home']['probability'];
                $bestHomeBook = $casinoName;
                $bestHomeBetType = 'moneyline';
            }

            // Check away team probabilities
            $awaySpreadProb = $data['spread']['away']['probability'];
            if ($awaySpreadProb < $lowestAwayProb) {
                $lowestAwayProb = $awaySpreadProb;
                $bestAwayBook = $casinoName;
                $bestAwayBetType = 'spread';
            }

            if (isset($data['moneyLine']) && $data['moneyLine']['away']['probability'] < $lowestAwayProb) {
                $lowestAwayProb = $data['moneyLine']['away']['probability'];
                $bestAwayBook = $casinoName;
                $bestAwayBetType = 'moneyline';
            }
        }

        return [
            'home' => [
                'casino' => $bestHomeBook,
                'type' => $bestHomeBetType
            ],
            'away' => [
                'casino' => $bestAwayBook,
                'type' => $bestAwayBetType
            ]
        ];
    }
}
