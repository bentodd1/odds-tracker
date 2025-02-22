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
     * @param float|null $homeFpi Home team's FPI rating
     * @param float|null $awayFpi Away team's FPI rating
     * @param float $homeFieldAdvantage Home field advantage points (defaults to 3)
     * @param bool $neutralField Whether the game is at a neutral site
     * @return array Returns ['result' => string, 'fpi_correct' => bool|null, 'fpi_spread' => float|null]
     */
    public static function calculateResult(
        $homeScore, 
        $awayScore, 
        $spread, 
        $homeFpi = null, 
        $awayFpi = null,
        $homeFieldAdvantage = 3.0,
        $neutralField = false
    ) {
        // Calculate actual margin (positive means home team won by that much)
        $actualMargin = $homeScore - $awayScore;

        // Adjust margin by spread (spread is from home team perspective)
        $adjustedMargin = $actualMargin + $spread;

        $result = abs($adjustedMargin) < 0.0001 ? 'push' : ($adjustedMargin > 0 ? 'home_covered' : 'away_covered');

        // Calculate FPI spread and prediction if both FPI values are available
        $fpiSpread = null;
        $fpiCorrect = null;

        if ($homeFpi !== null && $awayFpi !== null) {
            $fpiSpread = round($homeFpi - $awayFpi, 1);
            // Add home field advantage only if not a neutral field
            $adjustedFpiSpread = $fpiSpread + ($neutralField ? 0 : $homeFieldAdvantage);
            $fpiPrediction = $adjustedFpiSpread > 0 ? 'home' : 'away';
            $actualWinner = $actualMargin > 0 ? 'home' : ($actualMargin < 0 ? 'away' : 'push');
            $fpiCorrect = $actualWinner !== 'push' && $fpiPrediction === $actualWinner;
        }

        return [
            'result' => $result,
            'fpi_correct' => $fpiCorrect,
            'fpi_spread' => $fpiSpread
        ];
    }

    /**
     * Create a spread result from a score and spread
     */
    public static function createFromScore(Score $score, Spread $spread)
    {
        $result = self::calculateResult(
            $score->home_score,
            $score->away_score,
            $spread->spread,
            $score->home_fpi,
            $score->away_fpi
        );

        return self::create([
            'spread_id' => $spread->id,
            'score_id' => $score->id,
            'result' => $result['result'],
            'fpi_spread' => $result['fpi_spread'],
            'fpi_correctly_predicted' => $result['fpi_correct']
        ]);
    }
}
