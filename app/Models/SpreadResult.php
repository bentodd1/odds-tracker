<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpreadResult extends Model
{
    protected $fillable = [
        'spread_id',
        'score_id',
        'result',
        'fpi_spread',
        'fpi_correctly_predicted'
    ];

    protected $casts = [
        'result' => 'boolean',
        'fpi_correctly_predicted' => 'boolean',
        'fpi_spread' => 'decimal:1'
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
     * @param float|null $fpiSpread FPI spread from home team perspective
     * @return array Returns ['result' => string, 'fpi_correct' => bool|null]
     */
    public static function calculateResult($homeScore, $awayScore, $spread, $fpiSpread = null)
    {
        // Calculate actual margin (positive means home team won by that much)
        $actualMargin = $homeScore - $awayScore;

        // Adjust margin by spread (spread is from home team perspective)
        $adjustedMargin = $actualMargin + $spread;

        $result = abs($adjustedMargin) < 0.0001 ? 'push' : ($adjustedMargin > 0 ? 'home_covered' : 'away_covered');

        // Calculate if FPI was correct (if FPI spread is available)
        $fpiCorrect = null;
        if ($fpiSpread !== null) {
            $fpiAdjustedMargin = $actualMargin + $fpiSpread;
            $fpiPrediction = $fpiSpread > 0 ? 'home' : 'away';
            $actualWinner = $actualMargin > 0 ? 'home' : ($actualMargin < 0 ? 'away' : 'push');
            $fpiCorrect = $actualWinner !== 'push' && $fpiPrediction === $actualWinner;
        }

        return [
            'result' => $result,
            'fpi_correct' => $fpiCorrect
        ];
    }

    /**
     * Create a spread result from a score and spread
     */
    public static function createFromScore(Score $score, Spread $spread)
    {
        // Calculate FPI spread if both home and away FPI are available
        $fpiSpread = null;
        if ($score->home_fpi !== null && $score->away_fpi !== null) {
            $fpiSpread = round($score->home_fpi - $score->away_fpi, 1);
        }

        $result = self::calculateResult(
            $score->home_score,
            $score->away_score,
            $spread->spread,
            $fpiSpread
        );

        return self::create([
            'spread_id' => $spread->id,
            'score_id' => $score->id,
            'result' => $result['result'],
            'fpi_spread' => $fpiSpread,
            'fpi_correctly_predicted' => $result['fpi_correct']
        ]);
    }
}
