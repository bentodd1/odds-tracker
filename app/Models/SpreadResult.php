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
        'fpi_adjusted_spread',
        'fpi_correctly_predicted',
        'fpi_better_than_spread',
        'fpi_spread_difference'
    ];

    protected $casts = [
        'fpi_correctly_predicted' => 'boolean',
        'fpi_better_than_spread' => 'boolean',
        'fpi_spread' => 'decimal:1',
        'fpi_adjusted_spread' => 'decimal:1',
        'fpi_spread_difference' => 'decimal:1'
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
     * Calculate if the spread was covered and if FPI was more accurate than the spread
     * @param int $homeScore
     * @param int $awayScore
     * @param float $spread Positive means home team gets points, negative means home team gives points
     * @param float|null $homeFpi Home team's FPI rating
     * @param float|null $awayFpi Away team's FPI rating
     * @param float $homeFieldAdvantage Home field advantage points (defaults to 3)
     * @param bool $neutralField Whether the game is at a neutral site
     * @return array Returns ['result' => string, 'fpi_correct' => bool|null, 'fpi_spread' => float|null, 'fpi_better_than_spread' => bool|null]
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

        // Ensure we're using string values for the result
        $result = abs($adjustedMargin) < 0.0001 ? 'push' : ($adjustedMargin > 0 ? 'home_covered' : 'away_covered');

        // Debug output
        echo "\nCalculated result: " . $result;
        echo "\nAdjusted margin: " . $adjustedMargin;
        
        // Calculate FPI spread and prediction if both FPI values are available
        $fpiSpread = null;
        $fpiCorrect = null;
        $fpiBetterThanSpread = null;
        $fpiSpreadDifference = null;

        if ($homeFpi !== null && $awayFpi !== null) {
            // Calculate raw FPI spread: Away FPI - Home FPI
            // Positive means away team is favored, negative means home team is favored
            $rawFpiSpread = $awayFpi - $homeFpi;
            
            // Apply home field advantage to get the adjusted FPI spread
            // Home field advantage always benefits the home team
            if (!$neutralField) {
                // Subtract home field advantage from the raw FPI spread
                // This makes the spread more favorable to the home team
                $adjustedFpiSpread = $rawFpiSpread - $homeFieldAdvantage;
            } else {
                // No home field advantage for neutral sites
                $adjustedFpiSpread = $rawFpiSpread;
            }
            
            // Round to 1 decimal place
            $fpiSpread = round($rawFpiSpread, 1);
            $adjustedFpiSpread = round($adjustedFpiSpread, 1);
            
            // Debug output
            echo "\nDEBUG - Home FPI: " . $homeFpi . ", Away FPI: " . $awayFpi;
            echo "\nDEBUG - Raw FPI Spread: " . $fpiSpread;
            echo "\nDEBUG - Home Field Advantage: " . ($neutralField ? 0 : $homeFieldAdvantage);
            echo "\nDEBUG - Adjusted FPI Spread: " . $adjustedFpiSpread;
            
            // Calculate absolute difference between FPI spread and market spread
            $fpiSpreadDifference = round(abs($adjustedFpiSpread - $spread), 1);
            
            // Calculate how far off each prediction was
            // Market spread is already from home perspective
            $spreadPrediction = -$spread; // Convert to predicted margin
            $spreadError = abs($actualMargin - $spreadPrediction);
            
            // FPI spread needs to be converted to a predicted margin
            $fpiPredictionMargin = -$adjustedFpiSpread; // Convert to predicted margin
            $fpiError = abs($actualMargin - $fpiPredictionMargin);
            
            $fpiBetterThanSpread = $fpiError < $spreadError;

            // FPI predicts home team wins if adjusted spread is negative
            $fpiPrediction = $adjustedFpiSpread < 0 ? 'home' : 'away';
            $actualWinner = $actualMargin > 0 ? 'home' : ($actualMargin < 0 ? 'away' : 'push');
            $fpiCorrect = $actualWinner !== 'push' && $fpiPrediction === $actualWinner;
        }

        return [
            'result' => $result,
            'fpi_correct' => $fpiCorrect,
            'fpi_spread' => $fpiSpread,
            'fpi_better_than_spread' => $fpiBetterThanSpread,
            'fpi_spread_difference' => $fpiSpreadDifference,
            'adjusted_fpi_spread' => $adjustedFpiSpread ?? null
        ];
    }

    /**
     * Create a spread result from a score and spread
     */
    public static function createFromScore(Score $score, Spread $spread)
    {
        // Get the sport key and extract the identifier (e.g., "ncaab" from "basketball_ncaab")
        $sportKey = $spread->game->sport->key;
        $sportIdentifier = str_contains($sportKey, '_') ? explode('_', $sportKey)[1] : $sportKey;
        
        // Initialize GameTransformationService with the sport identifier
        $gameService = new \App\Services\GameTransformationService($sportIdentifier);
        
        // Get home field advantage with a default of 3.0 if the service returns null
        $homeFieldAdvantage = $gameService->getHomeFieldAdvantage() ?? 3.0;

        $result = self::calculateResult(
            $score->home_score,
            $score->away_score,
            $spread->spread,
            $score->home_fpi,
            $score->away_fpi,
            $homeFieldAdvantage,
            $spread->game->neutral_field ?? false
        );

        return self::create([
            'spread_id' => $spread->id,
            'score_id' => $score->id,
            'result' => $result['result'],
            'fpi_spread' => $result['fpi_spread'],
            'fpi_adjusted_spread' => $result['adjusted_fpi_spread'],
            'fpi_correctly_predicted' => $result['fpi_correct'],
            'fpi_better_than_spread' => $result['fpi_better_than_spread'],
            'fpi_spread_difference' => $result['fpi_spread_difference']
        ]);
    }
}
