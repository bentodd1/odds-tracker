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

        if ($this->spread < 0) {  // Home team is favorite
            if ($isHalf) {
                // For spreads like -14.5
                $marginGames = $marginModel::where('margin', '<=', floor($spreadValue))
                    ->sum('occurrences');
                return round((($marginGames / 2) / $totalGames * 100) + 50, 1);
            } else {
                // For spreads like -14
                $marginGames = $marginModel::where('margin', '<=', $spreadValue - 1)
                    ->sum('occurrences');
                $currentMarginGames = $marginModel::where('margin', '=', $spreadValue)
                    ->first()
                    ->occurrences ?? 0;
                $adjustedTotal = $totalGames - ($currentMarginGames / 2);
                return round((($marginGames / 2) / $adjustedTotal * 100) + 50, 1);
            }
        } else {  // Home team is underdog
            if ($isHalf) {
                // For spreads like +14.5
                $marginGames = $marginModel::where('margin', '<=', floor($spreadValue))
                    ->sum('occurrences');
                $favProb = (($marginGames / 2) / $totalGames * 100) + 50;
                return round(100 - $favProb, 1);
            } else {
                // For spreads like +14
                $marginGames = $marginModel::where('margin', '<=', $spreadValue - 1)
                    ->sum('occurrences');
                $currentMarginGames = $marginModel::where('margin', '=', $spreadValue)
                    ->first()
                    ->occurrences ?? 0;
                $adjustedTotal = $totalGames - ($currentMarginGames / 2);
                $favProb = (($marginGames / 2) / $adjustedTotal * 100) + 50;
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
