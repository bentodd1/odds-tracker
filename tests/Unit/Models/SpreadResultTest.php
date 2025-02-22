<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\SpreadResult;

class SpreadResultTest extends TestCase
{
    public function test_home_team_covers_when_favorite()
    {
        // Home team favored by 7 (-7), wins by 14
        $result = SpreadResult::calculateResult(
            homeScore: 28,
            awayScore: 14,
            spread: -7
        );

        $this->assertEquals('home_covered', $result['result']);
    }

    public function test_away_team_covers_against_favorite()
    {
        // Home team favored by 7 (-7), only wins by 3
        $result = SpreadResult::calculateResult(
            homeScore: 24,
            awayScore: 21,
            spread: -7
        );

        $this->assertEquals('away_covered', $result['result']);
    }

    public function test_push_when_margin_equals_spread()
    {
        // Home team favored by 7 (-7), wins by exactly 7
        $result = SpreadResult::calculateResult(
            homeScore: 27,
            awayScore: 20,
            spread: -7
        );

        $this->assertEquals('push', $result['result']);
    }

    public function test_home_team_covers_as_underdog()
    {
        // Home team getting 3.5 points (+3.5), loses by only 3
        $result = SpreadResult::calculateResult(
            homeScore: 20,
            awayScore: 23,
            spread: 3.5
        );

        $this->assertEquals('home_covered', $result['result']);
    }

    public function test_away_team_covers_against_underdog()
    {
        // Home team getting 3.5 points (+3.5), loses by 7
        $result = SpreadResult::calculateResult(
            homeScore: 17,
            awayScore: 24,
            spread: 3.5
        );

        $this->assertEquals('away_covered', $result['result']);
    }

    public function test_handles_half_point_spreads()
    {
        // Home team favored by 7.5 (-7.5), wins by 7
        $result = SpreadResult::calculateResult(
            homeScore: 27,
            awayScore: 20,
            spread: -7.5
        );

        $this->assertEquals('away_covered', $result['result']);

        // Home team getting 3.5 (+3.5), loses by 3
        $result = SpreadResult::calculateResult(
            homeScore: 20,
            awayScore: 23,
            spread: 3.5
        );

        $this->assertEquals('home_covered', $result['result']);
    }

    public function test_fpi_spread_calculation_from_home_away_fpi()
    {
        $score = new \App\Models\Score([
            'home_score' => 24,
            'away_score' => 21,
            'home_fpi' => 15.5,
            'away_fpi' => 12.2,
            'id' => 1
        ]);
        $score->id = 1; // Explicitly set ID

        $spread = new \App\Models\Spread([
            'spread' => -3,
            'id' => 1
        ]);
        $spread->id = 1; // Explicitly set ID

        // Mock the models to avoid database interaction
        $score->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $spread->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $result = SpreadResult::createFromScore($score, $spread);
        
        // FPI spread should be home_fpi - away_fpi = 3.3
        $this->assertEquals(3.3, $result->fpi_spread);
        $this->assertTrue($result->fpi_correctly_predicted);
    }

    public function test_fpi_spread_calculation_when_away_team_favored()
    {
        $score = new \App\Models\Score([
            'home_score' => 17,
            'away_score' => 27,
            'home_fpi' => 10.2,
            'away_fpi' => 14.8,
            'id' => 2
        ]);
        $score->id = 2;

        $spread = new \App\Models\Spread([
            'spread' => 3,
            'id' => 2
        ]);
        $spread->id = 2;

        // Mock the models to avoid database interaction
        $score->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $spread->shouldReceive('getAttribute')->with('id')->andReturn(2);

        $result = SpreadResult::createFromScore($score, $spread);
        
        // FPI spread should be home_fpi - away_fpi = -4.6
        $this->assertEquals(-4.6, $result->fpi_spread);
        $this->assertTrue($result->fpi_correctly_predicted);
    }

    public function test_fpi_spread_null_when_fpi_missing()
    {
        $score = new \App\Models\Score([
            'home_score' => 24,
            'away_score' => 21,
            'home_fpi' => null,
            'away_fpi' => 12.2,
            'id' => 3
        ]);
        $score->id = 3;

        $spread = new \App\Models\Spread([
            'spread' => -3,
            'id' => 3
        ]);
        $spread->id = 3;

        // Mock the models to avoid database interaction
        $score->shouldReceive('getAttribute')->with('id')->andReturn(3);
        $spread->shouldReceive('getAttribute')->with('id')->andReturn(3);

        $result = SpreadResult::createFromScore($score, $spread);
        
        $this->assertNull($result->fpi_spread);
        $this->assertNull($result->fpi_correctly_predicted);
    }
}
