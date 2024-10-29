<?php

namespace Database\Factories;

use App\Models\SpreadResult;
use App\Models\Spread;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpreadResultFactory extends Factory
{
    protected $model = SpreadResult::class;

    public function definition()
    {
        $homeScore = $this->faker->numberBetween(0, 45);
        $awayScore = $this->faker->numberBetween(0, 45);
        $spread = $this->faker->randomFloat(1, -14, 14);
        $actualMargin = $homeScore - $awayScore;

        // Determine result
        $adjustedMargin = $actualMargin + $spread;
        if (abs($adjustedMargin) < 0.1) {
            $result = 'push';
        } else {
            $result = $adjustedMargin > 0 ? 'home_covered' : 'away_covered';
        }

        return [
            'spread_id' => Spread::factory(),
            'game_id' => Game::factory(),
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'spread' => $spread,
            'actual_margin' => $actualMargin,
            'result' => $result,
            'home_profit_loss' => $result === 'home_covered' ? 100 : -110,
            'away_profit_loss' => $result === 'away_covered' ? 100 : -110,
        ];
    }
}
