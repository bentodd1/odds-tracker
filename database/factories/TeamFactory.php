<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition()
    {
        return [
            'sport_id' => Sport::factory(),
            'name' => $this->faker->unique()->city . ' ' . $this->faker->unique()->word,
        ];
    }
}
