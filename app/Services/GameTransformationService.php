<?php

namespace App\Services;

use App\Models\NflMargin;
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
            $spreadValue = abs($fpiDiff);
            $isHalf = (floor($spreadValue) != $spreadValue);
            $totalGames = NflMargin::sum('occurrences');

            if ($fpiDiff < 0) {  // Away team favored
                if ($isHalf) {
                    $marginGames = NflMargin::where('margin', '<=', floor($spreadValue))
                        ->sum('occurrences');
                    $homeWinProb = 100 - ((($marginGames / 2) / $totalGames * 100) + 50);
                } else {
                    $marginGames = NflMargin::where('margin', '<=', $spreadValue - 1)
                        ->sum('occurrences');
                    $currentMarginGames = NflMargin::where('margin', '=', $spreadValue)
                        ->first()
                        ->occurrences ?? 0;
                    $adjustedTotal = $totalGames - ($currentMarginGames / 2);
                    $homeWinProb = 100 - ((($marginGames / 2) / $adjustedTotal * 100) + 50);
                }
            } else {  // Home team favored
                if ($isHalf) {
                    $marginGames = NflMargin::where('margin', '<=', floor($spreadValue))
                        ->sum('occurrences');
                    $homeWinProb = (($marginGames / 2) / $totalGames * 100) + 50;
                } else {
                    $marginGames = NflMargin::where('margin', '<=', $spreadValue - 1)
                        ->sum('occurrences');
                    $currentMarginGames = NflMargin::where('margin', '=', $spreadValue)
                        ->first()
                        ->occurrences ?? 0;
                    $adjustedTotal = $totalGames - ($currentMarginGames / 2);
                    $homeWinProb = (($marginGames / 2) / $adjustedTotal * 100) + 50;
                }
            }
        }

        return [
            'home_fpi' => $homeTeamFpi ? $homeTeamFpi->rating : null,
            'away_fpi' => $awayTeamFpi ? $awayTeamFpi->rating : null,
            'home_win_probability' => $fpiDiff ? round($homeWinProb, 1) : null,
            'away_win_probability' => $fpiDiff ? round(100 - $homeWinProb, 1) : null
        ];
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

    private function transformCasinoData($game)
    {
        $casinoData = [];

        // Only process spreads that have a corresponding casino
        foreach ($game->spreads->filter(fn($spread) => $spread->casino)->groupBy('casino_id') as $casinoId => $casinoSpreads) {
            $spread = $casinoSpreads->sortByDesc('recorded_at')->first();
            $moneyLine = $game->moneyLines
                ->where('casino_id', $casinoId)
                ->sortByDesc('recorded_at')
                ->first();

            if ($spread && $spread->casino) {
                $casinoData[$spread->casino->name] = $this->formatCasinoEntry($spread, $moneyLine);
            }
        }

        return $casinoData;
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

        // Only process if we have casino data
        if (!empty($casinoData)) {
            foreach ($casinoData as $casinoName => $data) {
                // Check home team probabilities
                $homeSpreadProb = $data['spread']['home']['probability'] ?? 100;
                if ($homeSpreadProb < $lowestHomeProb) {
                    $lowestHomeProb = $homeSpreadProb;
                    $bestHomeBook = $casinoName;
                    $bestHomeBetType = 'spread';
                }

                if (isset($data['moneyLine']) &&
                    isset($data['moneyLine']['home']['probability']) &&
                    $data['moneyLine']['home']['probability'] < $lowestHomeProb) {
                    $lowestHomeProb = $data['moneyLine']['home']['probability'];
                    $bestHomeBook = $casinoName;
                    $bestHomeBetType = 'moneyline';
                }

                // Check away team probabilities
                $awaySpreadProb = $data['spread']['away']['probability'] ?? 100;
                if ($awaySpreadProb < $lowestAwayProb) {
                    $lowestAwayProb = $awaySpreadProb;
                    $bestAwayBook = $casinoName;
                    $bestAwayBetType = 'spread';
                }

                if (isset($data['moneyLine']) &&
                    isset($data['moneyLine']['away']['probability']) &&
                    $data['moneyLine']['away']['probability'] < $lowestAwayProb) {
                    $lowestAwayProb = $data['moneyLine']['away']['probability'];
                    $bestAwayBook = $casinoName;
                    $bestAwayBetType = 'moneyline';
                }
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
