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
        $spreadValue = abs($this->spread);
        $isHalf = (floor($spreadValue) != $spreadValue);
        $totalGames = NflMargin::sum('occurrences');

        \Log::info("--------------------");
        \Log::info("Spread: {$this->spread}");
        \Log::info("Spread Value: {$spreadValue}");
        \Log::info("Is Half: " . ($isHalf ? 'true' : 'false'));
        \Log::info("Total Games: {$totalGames}");

        if ($this->spread < 0) {  // Favorite
            if ($isHalf) {
                // For spreads like -14.5
                $marginGames = NflMargin::where('margin', '<=', floor($spreadValue))
                    ->sum('occurrences');

                \Log::info("For {$this->spread}:");
                \Log::info("Total games up to floor margin ({$spreadValue}): {$marginGames}");
                \Log::info("Calculation: ({$marginGames}/2)/{$totalGames} * 100 + 50");

                $probability = (($marginGames / 2) / $totalGames * 100) + 50;
                \Log::info("Calculated probability: {$probability}");

                return round($probability, 1);
            } else {
                // For spreads like -14
                $marginGames = NflMargin::where('margin', '<=', $spreadValue - 1)
                    ->sum('occurrences');

                $currentMarginGames = NflMargin::where('margin', '=', $spreadValue)
                    ->first()
                    ->occurrences ?? 0;

                \Log::info("For {$this->spread}:");
                \Log::info("Total games margin-1 ({$spreadValue}-1) or less: {$marginGames}");
                \Log::info("Current margin ({$spreadValue}) games: {$currentMarginGames}");
                \Log::info("Calculation: ({$marginGames}/2)/({$totalGames}-{$currentMarginGames}/2) * 100 + 50");

                $adjustedTotal = $totalGames - ($currentMarginGames / 2);
                $probability = (($marginGames / 2) / $adjustedTotal * 100) + 50;
                \Log::info("Adjusted total: {$adjustedTotal}");
                \Log::info("Calculated probability: {$probability}");

                return round($probability, 1);
            }
        } else {  // Underdog
            if ($isHalf) {
                // For spreads like +14.5
                $marginGames = NflMargin::where('margin', '<=', floor($spreadValue))
                    ->sum('occurrences');

                \Log::info("For {$this->spread} (underdog half):");
                \Log::info("Total games up to floor margin ({$spreadValue}): {$marginGames}");

                $favProb = (($marginGames / 2) / $totalGames * 100) + 50;
                $probability = 100 - $favProb;
                \Log::info("Favorite probability: {$favProb}");
                \Log::info("Underdog probability: {$probability}");

                return round($probability, 1);
            } else {
                // For spreads like +14
                $marginGames = NflMargin::where('margin', '<=', $spreadValue - 1)
                    ->sum('occurrences');

                $currentMarginGames = NflMargin::where('margin', '=', $spreadValue)
                    ->first()
                    ->occurrences ?? 0;

                \Log::info("For {$this->spread} (underdog whole):");
                \Log::info("Total games margin-1 ({$spreadValue}-1) or less: {$marginGames}");
                \Log::info("Current margin ({$spreadValue}) games: {$currentMarginGames}");

                $adjustedTotal = $totalGames - ($currentMarginGames / 2);
                $favProb = (($marginGames / 2) / $adjustedTotal * 100) + 50;
                $probability = 100 - $favProb;
                \Log::info("Adjusted total: {$adjustedTotal}");
                \Log::info("Favorite probability: {$favProb}");
                \Log::info("Underdog probability: {$probability}");

                return round($probability, 1);
            }
        }
    }

    public function getCoverProbabilityWithJuiceAttribute()
    {
        // Get base cover probability
        $coverProb = $this->cover_probability;

        // Calculate extra juice above 50%
        $odds = $this->spread < 0 ? $this->home_odds : $this->away_odds;
        $juiceProb = (abs($odds) / (abs($odds) + 100) * 100) - 50;

        // Add the extra juice probability
        return round($coverProb + $juiceProb, 1);
    }


    public function getSpreadProbabilityAttribute()
    {
        $isHalf = (floor($this->spread) != $this->spread);
        return NFLMargin::calculateSpreadProbability(
            $this->spread,
            $isHalf
        );
    }

    public function getIsKeyNumberAttribute()
    {
        if (floor($this->spread) != $this->spread) {
            return false;
        }

        return NFLMargin::where('margin', abs($this->spread))
            ->where('is_key_number', true)
            ->exists();
    }
}
