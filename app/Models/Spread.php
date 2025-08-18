<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spread extends Model
{
    use HasFactory;

    protected $fillable = ['game_id', 'casino_id', 'spread', 'home_odds', 'away_odds', 'recorded_at'];

    protected $dates = ['recorded_at'];

    protected $casts = [
        'spread' => 'decimal:1',
        'home_odds' => 'decimal:2',
        'away_odds' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function casino()
    {
        return $this->belongsTo(Casino::class);
    }

    public function result()
    {
        return $this->hasOne(SpreadResult::class);
    }

    public function moneyLine()
    {
        return $this->hasOne(MoneyLine::class, 'game_id', 'game_id')
            ->where('casino_id', $this->casino_id);
    }

    private function getDynamicDivisor()
    {
        // Try to get the moneyline for the same casino and game
        $moneyLine = $this->moneyLine;
        
        if (!$moneyLine) {
            // Fallback to any moneyline for this game if no match by casino
            $moneyLine = $this->game->moneyLines()->first();
        }
        
        if (!$moneyLine) {
            // Ultimate fallback to constant 2 if no moneyline data available
            return 2;
        }
        
        // Calculate the deviation from 50% (even money)
        if ($this->spread < 0) {
            // Home team is favorite
            $favoriteOdds = $moneyLine->home_odds;
            if ($favoriteOdds < 0) {
                $winPercent = abs($favoriteOdds) / (abs($favoriteOdds) + 100);
            } else {
                $winPercent = 100 / ($favoriteOdds + 100);
            }
            
            // Calculate how much stronger the favorite is than 50%
            $strengthAbove50 = $winPercent - 0.5;
            
            // Make a stronger adjustment: reduce divisor by a fraction of the strength
            // This gives favorites a bigger boost
            return max(2 - ($strengthAbove50 * 1.2), 1.0);
            
        } else {
            // Away team is favorite, home team is underdog
            $underdogOdds = $moneyLine->home_odds;
            if ($underdogOdds < 0) {
                $winPercent = abs($underdogOdds) / (abs($underdogOdds) + 100);
            } else {
                $winPercent = 100 / ($underdogOdds + 100);
            }
            
            // Calculate how much weaker the underdog is than 50%
            $weaknessBelow50 = 0.5 - $winPercent;
            
            // Make a moderate adjustment: increase divisor by a fraction of the weakness
            // This reduces underdog cover probability, but not as dramatically
            return 2 + ($weaknessBelow50 * 0.8);
        }
    }

    public function getCoverProbabilityAttribute()
    {
        // Get the sport key from the game
        $sportKey = $this->game->sport->key;
        $sportIdentifier = str_contains($sportKey, '_') ? explode('_', $sportKey)[1] : $sportKey;

        // Map sport to margin model
        $marginModel = match (strtolower($sportIdentifier)) {
            'nfl' => NflMargin::class,
            'ncaaf' => NCAAFMargin::class,
            'ncaab' => NCAABMargin::class,
            'nba' => NBAMargin::class,
            'mlb' => MLBMargin::class,
            default => throw new \InvalidArgumentException("Unsupported sport: {$sportIdentifier}")
        };

        $spreadValue = abs($this->spread);
        $isHalf = (floor($spreadValue) != $spreadValue);
        $totalGames = $marginModel::sum('occurrences');
        $dynamicDivisor = $this->getDynamicDivisor();

        if ($this->spread < 0) {  // Home team is favorite
            if ($isHalf) {
                // For spreads like -14.5
                $marginGames = $marginModel::where('margin', '<=', floor($spreadValue))
                    ->sum('occurrences');
                return round((($marginGames / $dynamicDivisor) / $totalGames * 100) + 50, 1);
            } else {
                // For spreads like -14
                $marginGames = $marginModel::where('margin', '<=', $spreadValue - 1)
                    ->sum('occurrences');
                $currentMarginGames = $marginModel::where('margin', '=', $spreadValue)
                    ->first()
                    ->occurrences ?? 0;
                $adjustedTotal = $totalGames - ($currentMarginGames / $dynamicDivisor);
                return round((($marginGames / $dynamicDivisor) / $adjustedTotal * 100) + 50, 1);
            }
        } else {  // Home team is underdog
            if ($isHalf) {
                // For spreads like +14.5
                $marginGames = $marginModel::where('margin', '<=', floor($spreadValue))
                    ->sum('occurrences');
                $favProb = (($marginGames / $dynamicDivisor) / $totalGames * 100) + 50;
                return round(100 - $favProb, 1);
            } else {
                // For spreads like +14
                $marginGames = $marginModel::where('margin', '<=', $spreadValue - 1)
                    ->sum('occurrences');
                $currentMarginGames = $marginModel::where('margin', '=', $spreadValue)
                    ->first()
                    ->occurrences ?? 0;
                $adjustedTotal = $totalGames - ($currentMarginGames / $dynamicDivisor);
                $favProb = (($marginGames / $dynamicDivisor) / $adjustedTotal * 100) + 50;
                return round(100 - $favProb, 1);
            }
        }
    }

    public function getCoverProbabilityWithJuiceAttribute()
    {
        // Get base probability from odds
        $odds = $this->spread < 0 ? $this->home_odds : $this->away_odds;

        // Calculate the raw implied probability from the odds
        if ($odds < 0) {
            $probability = abs($odds) / (abs($odds) + 100) * 100;
        } else {
            $probability = 100 / ($odds + 100) * 100;
        }

        // Get the spread adjustment from cover_probability
        $spreadAdjustment = abs($this->cover_probability - 50);

        // For favorites (negative spread), add the spread advantage
        // For underdogs (positive spread), subtract the spread disadvantage
        if ($this->spread < 0) {
            $probability += $spreadAdjustment;
        } else {
            $probability -= $spreadAdjustment;
        }

        return round($probability, 1);
    }

    public function getHomeCoverProbabilityWithJuiceAttribute()
    {
        // Convert home odds to implied probability
        $odds = $this->home_odds;
        
        if ($odds < 0) {
            $probability = abs($odds) / (abs($odds) + 100) * 100;
        } else {
            $probability = 100 / ($odds + 100) * 100;
        }

        // Get the spread adjustment from cover_probability
        $spreadAdjustment = abs($this->cover_probability - 50);

        // If home team is favorite (negative spread), add the advantage
        // If home team is underdog (positive spread), subtract the disadvantage
        if ($this->spread < 0) {
            $probability += $spreadAdjustment;
        } else {
            $probability -= $spreadAdjustment;
        }

        return round($probability, 1);
    }

    public function getAwayCoverProbabilityWithJuiceAttribute()
    {
        // Convert away odds to implied probability
        $odds = $this->away_odds;
        
        if ($odds < 0) {
            $probability = abs($odds) / (abs($odds) + 100) * 100;
        } else {
            $probability = 100 / ($odds + 100) * 100;
        }

        // Get the spread adjustment from cover_probability
        $spreadAdjustment = abs($this->cover_probability - 50);

        // If away team is favorite (positive spread), add the advantage
        // If away team is underdog (negative spread), subtract the disadvantage
        if ($this->spread > 0) {
            $probability += $spreadAdjustment;
        } else {
            $probability -= $spreadAdjustment;
        }

        return round($probability, 1);
    }

    public function getSpreadProbabilityAttribute()
    {
        $isHalf = (floor($this->spread) != $this->spread);
        return NflMargin::calculateSpreadProbability(
            $this->spread,
            $isHalf
        );
    }

    public function getIsKeyNumberAttribute()
    {
        if (floor($this->spread) != $this->spread) {
            return false;
        }

        return NflMargin::where('margin', abs($this->spread))
            ->where('is_key_number', true)
            ->exists();
    }
}
