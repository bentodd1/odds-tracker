<?php

namespace Database\Factories;

use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;

class SportFactory extends Factory
{
    protected $model = Sport::class;

    public function definition()
    {
        return [
            'key' => 'americanfootball_nfl',
            'group' => 'American Football',
            'title' => 'NFL',  // title instead of name
            'active' => true
        ];
    }

    // Add some helpful states
    public function nfl()
    {
        return $this->state(function (array $attributes) {
            return [
                'key' => 'americanfootball_nfl',
                'group' => 'American Football',
                'title' => 'NFL'
            ];
        });
    }

    public function nba()
    {
        return $this->state(function (array $attributes) {
            return [
                'key' => 'basketball_nba',
                'group' => 'Basketball',
                'title' => 'NBA'
            ];
        });
    }

    public function mlb()
    {
        return $this->state(function (array $attributes) {
            return [
                'key' => 'baseball_mlb',
                'group' => 'Baseball',
                'title' => 'MLB'
            ];
        });
    }
}
