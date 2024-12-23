<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OverUnderResult extends Model
{
    protected $fillable = ['over_under_id', 'score_id', 'total_points', 'result'];

    public function overUnder()
    {
        return $this->belongsTo(OverUnder::class);
    }

    public function score()
    {
        return $this->belongsTo(Score::class);
    }

    /**
     * Calculate over/under result details based on the final score
     *
     * @param float $totalPoints The actual total score of the game
     * @param float $line The over/under line
     * @return array Returns ['result' => string, 'total_points' => float]
     */
    public static function calculateResult($totalPoints, $line)
    {
        // If it's a push
        if (abs($totalPoints - $line) < 0.0001) {
            return [
                'result' => 'push',
                'total_points' => $totalPoints
            ];
        }

        return [
            'result' => $totalPoints > $line ? 'over' : 'under',
            'total_points' => $totalPoints
        ];
    }
}
