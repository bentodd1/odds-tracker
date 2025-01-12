<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NCAABMargin extends Model
{
    protected $fillable = ['margin', 'occurrences', 'cumulative_percentage', 'is_key_number'];

    protected $table = 'ncaab_margins';

    public static function calculateSpreadProbability($spread, $isHalf = false)
    {
        if ($isHalf) {
            return self::calculateHalfSpreadProbability($spread);
        }
        return self::calculateFullSpreadProbability($spread);
    }

    private static function calculateFullSpreadProbability($spread)
    {
        $spreadValue = abs($spread);

        $lowerBound = self::where('margin', '=', $spreadValue)->first();
        $upperBound = self::where('margin', '>', $spreadValue)
            ->orderBy('margin', 'asc')
            ->first();

        if (!$lowerBound || !$upperBound) {
            return null;
        }

        $probability = ($upperBound->cumulative_percentage - $lowerBound->cumulative_percentage) / 100;

        return $probability;
    }

    private static function calculateHalfSpreadProbability($spread)
    {
        $spreadValue = abs($spread);

        $distribution = self::where('margin', '>=', floor($spreadValue))
            ->orderBy('margin', 'asc')
            ->first();

        if (!$distribution) {
            return null;
        }

        return $distribution->cumulative_percentage / 100;
    }
}
