<?php

namespace Database\Factories;

use App\Models\Score;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScoreFactory extends Factory
{
    protected $model = Score::class;

    public function definition()
    {
        return [
            'game_id' => Game::factory(),
            'home_score' => $this->faker->numberBetween(0, 45),
            'away_score' => $this->faker->numberBetween(0, 45),
            'period' => $this->faker->randomElement(['1', '2', '3', '4', 'F']),
        ];
    }
}
