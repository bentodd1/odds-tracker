<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NflMargin extends Model
{
    protected $fillable = ['margin', 'occurrences', 'cumulative_percentage', 'is_key_number'];

    protected $table = 'nfl_margins'; // Explicitly set the table name


    public static function calculateSpreadProbability($spread, $isHalf = false)
    {
        if ($isHalf) {
            return self::calculateHalfSpreadProbability($spread);
        }
        return self::calculateFullSpreadProbability($spread);
    }

    private static function calculateFullSpreadProbability($spread)
    {
        // For full spreads, we need the exact margin
        $spreadValue = abs($spread);

        // Get the distribution data
        $lowerBound = self::where('margin', '=', $spreadValue)->first();
        $upperBound = self::where('margin', '>', $spreadValue)
            ->orderBy('margin', 'asc')
            ->first();

        if (!$lowerBound || !$upperBound) {
            return null;
        }

        // Calculate the probability of this exact margin
        $probability = ($upperBound->cumulative_percentage - $lowerBound->cumulative_percentage) / 100;

        return $probability;
    }

    private static function calculateHalfSpreadProbability($spread)
    {
        // For half spreads, we need to consider all margins above/below
        $spreadValue = abs($spread);

        // Get the cumulative probability at this point
        $distribution = self::where('margin', '>=', floor($spreadValue))
            ->orderBy('margin', 'asc')
            ->first();

        if (!$distribution) {
            return null;
        }

        // For half spreads, return the cumulative probability
        return $distribution->cumulative_percentage / 100;
    }
}
