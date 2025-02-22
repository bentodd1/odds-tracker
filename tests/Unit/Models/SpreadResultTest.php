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
        $result = SpreadResult::calculateResult(
            homeScore: 24,
            awayScore: 21,
            spread: -3,
            homeFpi: 15.5,
            awayFpi: 12.2
        );
        
        // FPI spread should be home_fpi - away_fpi = 3.3
        $this->assertEquals(3.3, $result['fpi_spread']);
        $this->assertTrue($result['fpi_correct']);
    }

    public function test_fpi_spread_calculation_when_away_team_favored()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 17,
            awayScore: 27,
            spread: 3,
            homeFpi: 10.2,
            awayFpi: 14.8
        );
        
        // FPI spread should be home_fpi - away_fpi = -4.6
        $this->assertEquals(-4.6, $result['fpi_spread']);
        $this->assertTrue($result['fpi_correct']);
    }

    public function test_fpi_spread_null_when_fpi_missing()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 24,
            awayScore: 21,
            spread: -3,
            homeFpi: null,
            awayFpi: 12.2
        );
        
        $this->assertNull($result['fpi_spread']);
        $this->assertNull($result['fpi_correct']);
    }

    public function test_fpi_prediction_with_custom_home_field_advantage()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 24,
            awayScore: 21,
            spread: -3,
            homeFpi: 12.2,
            awayFpi: 14.8,
            homeFieldAdvantage: 4.5 // NCAA basketball HFA
        );
        
        // FPI spread = 12.2 - 14.8 = -2.6
        // With 4.5 HFA = 1.9 (predicts home win)
        $this->assertEquals(-2.6, $result['fpi_spread']);
        $this->assertTrue($result['fpi_correct']); // Home team won
    }

    public function test_fpi_prediction_at_neutral_site()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 24,
            awayScore: 21,
            spread: -3,
            homeFpi: 12.2,
            awayFpi: 14.8,
            homeFieldAdvantage: 3.0,
            neutralField: true
        );
        
        // FPI spread = 12.2 - 14.8 = -2.6
        // No HFA adjustment because neutral site
        $this->assertEquals(-2.6, $result['fpi_spread']);
        $this->assertFalse($result['fpi_correct']); // Predicted away win but home team won
    }

    public function test_fpi_prediction_with_nfl_home_field_advantage()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 24,
            awayScore: 21,
            spread: -3,
            homeFpi: 12.2,
            awayFpi: 14.8,
            homeFieldAdvantage: 2.0 // NFL HFA
        );
        
        // FPI spread = 12.2 - 14.8 = -2.6
        // With 2.0 HFA = -0.6 (predicts away win)
        $this->assertEquals(-2.6, $result['fpi_spread']);
        $this->assertFalse($result['fpi_correct']); // Predicted away win but home team won
    }
}
