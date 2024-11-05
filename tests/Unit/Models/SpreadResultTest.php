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

        $this->assertEquals('home_covered', $result);
    }

    public function test_away_team_covers_against_favorite()
    {
        // Home team favored by 7 (-7), only wins by 3
        $result = SpreadResult::calculateResult(
            homeScore: 24,
            awayScore: 21,
            spread: -7
        );

        $this->assertEquals('away_covered', $result);
    }

    public function test_push_when_margin_equals_spread()
    {
        // Home team favored by 7 (-7), wins by exactly 7
        $result = SpreadResult::calculateResult(
            homeScore: 27,
            awayScore: 20,
            spread: -7
        );

        $this->assertEquals('push', $result);
    }

    public function test_home_team_covers_as_underdog()
    {
        // Home team getting 3.5 points (+3.5), loses by only 3
        $result = SpreadResult::calculateResult(
            homeScore: 20,
            awayScore: 23,
            spread: 3.5
        );

        $this->assertEquals('home_covered', $result);
    }

    public function test_away_team_covers_against_underdog()
    {
        // Home team getting 3.5 points (+3.5), loses by 7
        $result = SpreadResult::calculateResult(
            homeScore: 17,
            awayScore: 24,
            spread: 3.5
        );

        $this->assertEquals('away_covered', $result);
    }

    public function test_handles_half_point_spreads()
    {
        // Home team favored by 7.5 (-7.5), wins by 7
        $result = SpreadResult::calculateResult(
            homeScore: 27,
            awayScore: 20,
            spread: -7.5
        );

        $this->assertEquals('away_covered', $result);

        // Home team getting 3.5 (+3.5), loses by 3
        $result = SpreadResult::calculateResult(
            homeScore: 20,
            awayScore: 23,
            spread: 3.5
        );

        $this->assertEquals('home_covered', $result);
    }
}
