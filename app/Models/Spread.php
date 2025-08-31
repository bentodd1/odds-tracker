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

    private function erfApproximation($x)
    {
        // Abramowitz and Stegun approximation for error function
        $a1 =  0.254829592;
        $a2 = -0.284496736;
        $a3 =  1.421413741;
        $a4 = -1.453152027;
        $a5 =  1.061405429;
        $p  =  0.3275911;

        $sign = $x < 0 ? -1 : 1;
        $x = abs($x);

        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

        return $sign * $y;
    }

    private function normalCDF($z)
    {
        return 0.5 * (1 + $this->erfApproximation($z / sqrt(2)));
    }

    public function getCoverProbabilityAttribute()
    {
        // Get the sport key from the game
        $sportKey = $this->game->sport->key;
        $sportIdentifier = str_contains($sportKey, '_') ? explode('_', $sportKey)[1] : $sportKey;

        // Use sport-specific standard deviations based on Stern (1991) research
        $sigma = match (strtolower($sportIdentifier)) {
            'nfl' => 14,        // Pro football standard deviation
            'ncaaf' => 15,      // College football standard deviation
            'ncaab' => 14,      // College basketball (keeping existing)
            'nba' => 14,        // NBA (keeping existing)
            'mlb' => 14,        // MLB (keeping existing)
            default => 14       // Default fallback
        };

        // Calculate probability using normal distribution (Stern 1991 approach)
        // P(favorite covers) = P(margin > spread)
        // For negative spreads: home is favorite, we want P(home margin > |spread|) = 1 - Φ(|spread|/σ)
        // For positive spreads: away is favorite, we want P(home margin > -|spread|) = Φ(|spread|/σ)
        
        $absSpread = abs($this->spread);
        $zScore = $absSpread / $sigma;
        
        // Calculate cumulative normal distribution
        $cumulativeProbability = $this->normalCDF($zScore);
        
        if ($this->spread < 0) {
            // Home team is favorite by |spread| points
            // P(home covers) = P(home margin > |spread|) = 1 - Φ(|spread|/σ)
            $probability = (1 - $cumulativeProbability) * 100;
        } else {
            // Away team is favorite, home team gets +spread points
            // P(home covers) = P(home margin > -|spread|) = Φ(|spread|/σ)
            $probability = $cumulativeProbability * 100;
        }
        
        return round($probability, 1);
    }

    public function getCoverProbabilityWithJuiceAttribute()
    {
        // Since we're using normal distribution which gives fair probability,
        // return the same as cover_probability (no additional vig)
        return $this->cover_probability;
    }

    public function getHomeCoverProbabilityWithJuiceAttribute()
    {
        // Return fair probability for home team covering
        return $this->spread < 0 ? $this->cover_probability : (100 - $this->cover_probability);
    }

    public function getAwayCoverProbabilityWithJuiceAttribute()
    {
        // Return fair probability for away team covering
        return $this->spread > 0 ? $this->cover_probability : (100 - $this->cover_probability);
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
