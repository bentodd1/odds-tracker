<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpreadResult extends Model
{
    protected $fillable = [
        'spread_id',
        'score_id',
        'result'
    ];

    public function spread()
    {
        return $this->belongsTo(Spread::class);
    }

    public function score()
    {
        return $this->belongsTo(Score::class);
    }

    /**
     * Calculate if the spread was covered based on the final score
     * @param int $homeScore
     * @param int $awayScore
     * @param float $spread Positive means home team gets points, negative means home team gives points
     * @return string 'home_covered', 'away_covered', or 'push'
     */
    public static function calculateResult($homeScore, $awayScore, $spread)
    {
        // Calculate actual margin (positive means home team won by that much)
        $actualMargin = $homeScore - $awayScore;

        // Adjust margin by spread (spread is from home team perspective)
        $adjustedMargin = $actualMargin + $spread;

        if (abs($adjustedMargin) < 0.0001) { // Use small epsilon for float comparison
            return 'push';
        }

        return $adjustedMargin > 0 ? 'home_covered' : 'away_covered';
    }

    /**
     * Create a spread result from a score and spread
     */
    public static function createFromScore(Score $score, Spread $spread)
    {
        return self::create([
            'spread_id' => $spread->id,
            'score_id' => $score->id,
            'result' => self::calculateResult(
                $score->home_score,
                $score->away_score,
                $spread->spread
            )
        ]);
    }
}
