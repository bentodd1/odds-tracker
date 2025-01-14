<?php

namespace App\Services;

use App\Models\NCAABMargin;
use App\Models\NCAAFMargin;
use App\Models\NflMargin;


class GameTransformationService
{
    private $marginModel;
    private $homeFieldAdvantage;

    public function __construct($sport = 'nfl')
    {
        $this->setMarginModel($sport);
        $this->setHomeFieldAdvantage($sport);
    }

    private function setHomeFieldAdvantage($sport)
    {
        $this->homeFieldAdvantage = match (strtolower($sport)) {
            'nfl' => 2.0,
            'ncaaf' => 2.5,
            'ncaab' => 4.5,
            'nba' => 2.4,
            default => throw new \InvalidArgumentException("Unsupported sport: {$sport}")
        };
    }
    public function transformGames($games)
    {
        return $games->map(function ($game) {
            return $this->transformGame($game);
        });
    }

    public function transformGame($game)
    {
        // 1) Calculate FPI
        $fpiData    = $this->calculateFpiData($game);

        // 2) Gather odds data (spreads & moneyLines by casino)
        $casinoData = $this->transformCasinoData($game);

        // 3) Determine the “best value” cells (highlighted in green)
        $bestValues = $this->calculateBestValues($casinoData);

        // 4) Calculate EV separately for home & away
        $homeEv = !is_null($fpiData['home_win_probability'])
            ? $this->calculateEvValue(
                $fpiData['home_win_probability'],
                $casinoData,
                'home' // <--- Only check home probabilities
            )
            : null;

        $awayEv = !is_null($fpiData['away_win_probability'])
            ? $this->calculateEvValue(
                $fpiData['away_win_probability'],
                $casinoData,
                'away' // <--- Only check away probabilities
            )
            : null;

        // Adjust commence time if needed (as your code does)
        $commenceTime = $game->commence_time->subHours(6);

        return [
            'id'            => $game->id,
            'commence_time' => $commenceTime,

            'home_team' => [
                'name'            => $game->homeTeam->name,
                'fpi'             => $fpiData['home_fpi'],
                'win_probability' => $fpiData['home_win_probability'],
                'ev_value'        => $homeEv,   // <--- The corrected EV
                'best_value'      => [
                    'casino' => $bestValues['home']['casino'],
                    'type'   => $bestValues['home']['type']
                ],
            ],

            'away_team' => [
                'name'            => $game->awayTeam->name,
                'fpi'             => $fpiData['away_fpi'],
                'win_probability' => $fpiData['away_win_probability'],
                'ev_value'        => $awayEv,   // <--- The corrected EV
                'best_value'      => [
                    'casino' => $bestValues['away']['casino'],
                    'type'   => $bestValues['away']['type']
                ],
            ],

            'casinos' => $casinoData
        ];
    }

    private function calculateFpiData($game)
    {
        $homeTeamFpi = $game->homeTeam->latestFpi()->first();
        $awayTeamFpi = $game->awayTeam->latestFpi()->first();
        if (!$homeTeamFpi || !$awayTeamFpi) {
            return [
                'home_fpi'            => $homeTeamFpi ? $homeTeamFpi->rating : null,
                'away_fpi'            => $awayTeamFpi ? $awayTeamFpi->rating : null,
                'home_win_probability'=> null,
                'away_win_probability'=> null
            ];
        }

        $fpiDiff     = $homeTeamFpi->rating - $awayTeamFpi->rating + $this->homeFieldAdvantage;
        $spreadValue = abs($fpiDiff);
        $isHalf      = (floor($spreadValue) != $spreadValue);
        $totalGames  = $this->marginModel::sum('occurrences');

        $homeWinProb = 50; // default
        if ($fpiDiff < 0) {
            // away favored
            if ($isHalf) {
                $marginGames = $this->marginModel::where('margin', '<=', floor($spreadValue))
                    ->sum('occurrences');
                $homeWinProb = 100 - ((($marginGames / 2) / $totalGames * 100) + 50);
            } else {
                $marginGames = $this->marginModel::where('margin', '<=', $spreadValue - 1)
                    ->sum('occurrences');
                $currentMarginGames = $this->marginModel::where('margin', '=', $spreadValue)
                    ->value('occurrences') ?? 0;
                $adjustedTotal = $totalGames - ($currentMarginGames / 2);
                $homeWinProb   = 100 - ((($marginGames / 2) / $adjustedTotal * 100) + 50);
            }
        } else {
            // home favored
            if ($isHalf) {
                $marginGames = $this->marginModel::where('margin', '<=', floor($spreadValue))
                    ->sum('occurrences');
                $homeWinProb = (($marginGames / 2) / $totalGames * 100) + 50;
            } else {
                $marginGames = $this->marginModel::where('margin', '<=', $spreadValue - 1)
                    ->sum('occurrences');
                $currentMarginGames = $this->marginModel::where('margin', '=', $spreadValue)
                    ->value('occurrences') ?? 0;
                $adjustedTotal = $totalGames - ($currentMarginGames / 2);
                $homeWinProb   = (($marginGames / 2) / $adjustedTotal * 100) + 50;
            }
        }
        $homeWinProb = round($homeWinProb, 1);

        return [
            'home_fpi'            => $homeTeamFpi->rating,
            'away_fpi'            => $awayTeamFpi->rating,
            'home_win_probability'=> $homeWinProb,
            'away_win_probability'=> round(100 - $homeWinProb, 1),
        ];
    }

    private function transformCasinoData($game)
    {
        $casinoData = [];
        // group each casino's spreads by casino_id
        $groupedSpreads = $game->spreads
            ->filter(fn($spread) => $spread->casino)
            ->groupBy('casino_id');

        foreach ($groupedSpreads as $casinoId => $casinoSpreads) {
            // pick the most recent spread
            $spread = $casinoSpreads->sortByDesc('recorded_at')->first();

            // find the corresponding moneyLine for that casino
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

    private function formatCasinoEntry($spread, $moneyLine)
    {
        return [
            'spread' => [
                'home' => [
                    'line'        => $spread->spread,
                    'odds'        => $spread->home_odds,
                    'probability' => $spread->home_cover_probability_with_juice,
                ],
                'away' => [
                    'line'        => -$spread->spread,
                    'odds'        => $spread->away_odds,
                    'probability' => $spread->away_cover_probability_with_juice,
                ],
            ],
            'moneyLine' => [
                'home' => [
                    'odds'        => $moneyLine ? $moneyLine->home_odds : null,
                    'probability' => $moneyLine ? $moneyLine->home_implied_probability : null,
                ],
                'away' => [
                    'odds'        => $moneyLine ? $moneyLine->away_odds : null,
                    'probability' => $moneyLine ? $moneyLine->away_implied_probability : null,
                ],
            ],
            'updated_at' => $spread->recorded_at,
        ];
    }

    private function calculateBestValues($casinoData)
    {
        $lowestHomeProb  = 100;
        $lowestAwayProb  = 100;
        $bestHomeBook    = null;
        $bestHomeBetType = null;
        $bestAwayBook    = null;
        $bestAwayBetType = null;

        if (!empty($casinoData)) {
            foreach ($casinoData as $casinoName => $data) {
                // 1) spread home
                $homeSpreadProb = $data['spread']['home']['probability'] ?? 100;
                if ($homeSpreadProb < $lowestHomeProb) {
                    $lowestHomeProb  = $homeSpreadProb;
                    $bestHomeBook    = $casinoName;
                    $bestHomeBetType = 'spread';
                }
                // 2) moneyLine home
                $homeMlProb = $data['moneyLine']['home']['probability'] ?? 100;
                if ($homeMlProb < $lowestHomeProb) {
                    $lowestHomeProb  = $homeMlProb;
                    $bestHomeBook    = $casinoName;
                    $bestHomeBetType = 'moneyline';
                }

                // 3) spread away
                $awaySpreadProb = $data['spread']['away']['probability'] ?? 100;
                if ($awaySpreadProb < $lowestAwayProb) {
                    $lowestAwayProb  = $awaySpreadProb;
                    $bestAwayBook    = $casinoName;
                    $bestAwayBetType = 'spread';
                }
                // 4) moneyLine away
                $awayMlProb = $data['moneyLine']['away']['probability'] ?? 100;
                if ($awayMlProb < $lowestAwayProb) {
                    $lowestAwayProb  = $awayMlProb;
                    $bestAwayBook    = $casinoName;
                    $bestAwayBetType = 'moneyline';
                }
            }
        }

        return [
            'home' => [
                'casino' => $bestHomeBook,
                'type'   => $bestHomeBetType,
            ],
            'away' => [
                'casino' => $bestAwayBook,
                'type'   => $bestAwayBetType,
            ],
        ];
    }

    /**
     * Calculate EV for either "home" or "away" team.
     */
    private function calculateEvValue(float $fpiProbability, array $casinoData, string $teamType): float
    {
        $lowestImpliedProbability = 100;

        // For each casino, look ONLY at the side matching $teamType
        foreach ($casinoData as $casinoName => $data) {
            // Spread side
            if (!empty($data['spread'][$teamType]['probability']) &&
                $data['spread'][$teamType]['probability'] < $lowestImpliedProbability) {
                $lowestImpliedProbability = $data['spread'][$teamType]['probability'];
            }

            // MoneyLine side
            if (!empty($data['moneyLine'][$teamType]['probability']) &&
                $data['moneyLine'][$teamType]['probability'] < $lowestImpliedProbability) {
                $lowestImpliedProbability = $data['moneyLine'][$teamType]['probability'];
            }
        }

        // EV = FPI% - lowest implied probability on that same side
        return round($fpiProbability - $lowestImpliedProbability, 1);
    }

    private function setMarginModel($sport)
    {
        $this->marginModel = match (strtolower($sport)) {
            'nfl' => NflMargin::class,
            'ncaaf' => NCAAFMargin::class,
            'ncaab' => NCAABMargin::class,
            'nba' => NCAABMargin::class,
            default => throw new \InvalidArgumentException("Unsupported sport: {$sport}")
        };
    }
}
