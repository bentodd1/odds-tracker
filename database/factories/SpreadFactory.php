<?php

namespace Database\Factories;

use App\Models\Spread;
use App\Models\Game;
use App\Models\Casino;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpreadFactory extends Factory
{
    protected $model = Spread::class;

    public function definition()
    {
        return [
            'game_id' => Game::factory(),
            'casino_id' => Casino::factory(),
            'spread' => $this->faker->randomFloat(1, -14, 14),
            'home_odds' => $this->faker->randomElement([-110, -105, -115, 100, -120]),
            'away_odds' => $this->faker->randomElement([-110, -105, -115, 100, -120]),
            'recorded_at' => now(),
        ];
    }
}
