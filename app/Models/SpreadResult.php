<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpreadResult extends Model
{
    protected $fillable = [
        'spread_id',
        'game_id',
        'home_score',
        'away_score',
        'spread',
        'actual_margin',
        'result',
        'home_profit_loss',
        'away_profit_loss'
    ];

    protected $casts = [
        'spread' => 'float',
        'actual_margin' => 'float',
        'home_profit_loss' => 'float',
        'away_profit_loss' => 'float'
    ];

    public function spread()
    {
        return $this->belongsTo(Spread::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public static function calculateResult($homeScore, $awayScore, $spread)
    {
        // Calculate margin (positive means home team won by that amount)
        $actualMargin = $homeScore - $awayScore;

        // Adjust margin by spread (spread is already from home team perspective)
        $adjustedMargin = $actualMargin + $spread;

        if (abs($adjustedMargin) < 0.0001) { // Use small epsilon for float comparison
            return 'push';
        }

        return $adjustedMargin > 0 ? 'home_covered' : 'away_covered';
    }

    public static function calculateProfitLoss($result, $homeOdds, $awayOdds, $betAmount = 100)
    {
        if ($result === 'push') {
            return [
                'home_profit_loss' => 0,
                'away_profit_loss' => 0
            ];
        }

        // Function to calculate win amount based on American odds
        $calculateProfit = function($odds) {
            if ($odds >= 0) {
                // For positive odds (underdog), profit is the odds number
                // e.g., +150 means bet 100 to win 150
                return $odds;
            } else {
                // For negative odds (favorite), calculate how much you win on a $100 bet
                // e.g., -110 means bet 110 to win 100, so profit is 90.91 on a $100 bet
                return (100 / abs($odds)) * 100;
            }
        };

        if ($result === 'home_covered') {
            $homeProfit = $calculateProfit($homeOdds);
            return [
                'home_profit_loss' => round($homeProfit, 2),
                'away_profit_loss' => -100
            ];
        } else if ($result === 'away_covered') {
            $awayProfit = $calculateProfit($awayOdds);
            return [
                'home_profit_loss' => -100,
                'away_profit_loss' => round($awayProfit, 2)
            ];
        }

        return [
            'home_profit_loss' => 0,
            'away_profit_loss' => 0
        ];
    }

    public static function createFromGame(Game $game, Spread $spread)
    {
        if (!$game->scores()->exists()) {
            throw new \Exception('Game scores not available');
        }

        $finalScore = $game->scores()
            ->where('period', 'F')
            ->firstOrFail();

        $result = self::calculateResult(
            $finalScore->home_score,
            $finalScore->away_score,
            $spread->spread
        );

        $profitLoss = self::calculateProfitLoss(
            $result,
            $spread->home_odds,
            $spread->away_odds
        );

        return self::create([
            'spread_id' => $spread->id,
            'game_id' => $game->id,
            'home_score' => $finalScore->home_score,
            'away_score' => $finalScore->away_score,
            'spread' => $spread->spread,
            'actual_margin' => $finalScore->home_score - $finalScore->away_score,
            'result' => $result,
            'home_profit_loss' => $profitLoss['home_profit_loss'],
            'away_profit_loss' => $profitLoss['away_profit_loss']
        ]);
    }
}
