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

        // Get moneyline for the same casino to determine baseline win probabilities
        $moneyLine = $this->game->moneyLines()
            ->where('casino_id', $this->casino_id)
            ->latest('recorded_at')
            ->first();

        // Calculate baseline win probabilities (subtract 2% for vig)
        if ($moneyLine) {
            $homeWinProbability = ($moneyLine->home_implied_probability - 2) / 100;
            $awayWinProbability = ($moneyLine->away_implied_probability - 2) / 100;
        } else {
            // Default to 50/50 if no moneyline available
            $homeWinProbability = 0.5;
            $awayWinProbability = 0.5;
        }

        $spreadValue = abs($this->spread);
        $isHalf = (floor($spreadValue) != $spreadValue);
        $totalGames = $marginModel::sum('occurrences');

        // Determine who the TRUE favorite is based on moneyline win probability
        $homeFavorite = $homeWinProbability > $awayWinProbability;
        
        // Calculate the probability that the TRUE favorite covers by the spread amount
        if ($isHalf) {
            // For half spreads like 14.5, we need margins > floor(spreadValue)
            $trueFavoriteCoversGames = $marginModel::where('margin', '>', floor($spreadValue))
                ->sum('occurrences');
        } else {
            // For whole spreads like 14, we need margins > spreadValue  
            $trueFavoriteCoversGames = $marginModel::where('margin', '>', $spreadValue)
                ->sum('occurrences');
        }

        $trueFavoriteCoversProb = $trueFavoriteCoversGames / $totalGames;

        if ($this->spread < 0) {
            // Home team gets negative spread (betting favorite)
            if ($homeFavorite) {
                // Home is both betting favorite AND true favorite
                // P(home covers -spread) = P(home wins) * P(true favorite covers by spread+)
                return round(($homeWinProbability * $trueFavoriteCoversProb) * 100, 1);
            } else {
                // Home is betting favorite but away is true favorite (unusual case)
                // P(home covers -spread) = P(home wins) * P(underdog covers when they win) + P(away wins) * 0
                // When true underdog wins, they rarely cover large spreads, so use (1 - trueFavoriteCoversProb)
                return round(($homeWinProbability * (1 - $trueFavoriteCoversProb)) * 100, 1);
            }
        } else {
            // Home team gets positive spread (betting underdog)
            if ($homeFavorite) {
                // Home is true favorite but betting underdog (unusual case) 
                // P(home covers +spread) = P(home wins) * 1 + P(away wins) * P(underdog covers when favorite wins)
                return round(($homeWinProbability * 1 + $awayWinProbability * (1 - $trueFavoriteCoversProb)) * 100, 1);
            } else {
                // Away is true favorite, home is true underdog (normal case)
                // P(home covers +spread) = P(home wins) * 1 + P(away wins) * P(underdog covers when favorite wins)
                return round(($homeWinProbability * 1 + $awayWinProbability * (1 - $trueFavoriteCoversProb)) * 100, 1);
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

        // Get moneyline baseline probability
        $moneyLine = $this->game->moneyLines()
            ->where('casino_id', $this->casino_id)
            ->latest('recorded_at')
            ->first();

        $baselineProbability = 50; // Default fallback
        if ($moneyLine) {
            // Use the appropriate team's baseline probability (subtract 2% for vig)
            $baselineProbability = $this->spread < 0 
                ? ($moneyLine->home_implied_probability - 2) // Home is favorite
                : ($moneyLine->away_implied_probability - 2); // Away is favorite
        }

        // Get the spread adjustment from cover_probability using actual baseline
        $spreadAdjustment = abs($this->cover_probability - $baselineProbability);

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

        // Get moneyline baseline probability for home team
        $moneyLine = $this->game->moneyLines()
            ->where('casino_id', $this->casino_id)
            ->latest('recorded_at')
            ->first();

        $homeBaselineProbability = 50; // Default fallback
        if ($moneyLine) {
            $homeBaselineProbability = $moneyLine->home_implied_probability - 2; // Subtract 2% for vig
        }

        // Get the spread adjustment from cover_probability using actual home baseline
        $spreadAdjustment = abs($this->cover_probability - $homeBaselineProbability);

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

        // Get moneyline baseline probability for away team
        $moneyLine = $this->game->moneyLines()
            ->where('casino_id', $this->casino_id)
            ->latest('recorded_at')
            ->first();

        $awayBaselineProbability = 50; // Default fallback
        if ($moneyLine) {
            $awayBaselineProbability = $moneyLine->away_implied_probability - 2; // Subtract 2% for vig
        }

        // Get the spread adjustment from cover_probability using actual away baseline
        $spreadAdjustment = abs($this->cover_probability - $awayBaselineProbability);

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
