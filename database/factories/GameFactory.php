<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition()
    {
        $sport = Sport::factory()->create();
        return [
            'sport_id' => $sport->id,
            'home_team_id' => Team::factory()->create(['sport_id' => $sport->id]),
            'away_team_id' => Team::factory()->create(['sport_id' => $sport->id]),
            'game_id' => $this->faker->uuid,
            'commence_time' => $this->faker->dateTimeBetween('now', '+1 week'),
            'completed' => false,
        ];
    }
}
