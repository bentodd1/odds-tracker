<?php

namespace Database\Factories;

use App\Models\Casino;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Casino>
 */
class CasinoFactory extends Factory
{
    protected $model = Casino::class;

    public function definition()
    {
        return [
            'name' => $this->faker->randomElement([
                'DraftKings', 'FanDuel', 'BetMGM', 'Caesars',
                'PointsBet', 'BetRivers', 'WynnBET'
            ]),
        ];
    }
}
