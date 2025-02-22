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

    public function test_home_team_covers_as_favorite()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 27,    // Home team wins by 10
            awayScore: 17,
            spread: -7        // Home team favored by 7
        );
        
        $this->assertEquals('home_covered', $result['result']); // Won by 10, covered -7
    }

    public function test_home_team_doesnt_cover_as_favorite()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 24,    // Home team wins by 3
            awayScore: 21,
            spread: -7        // Home team favored by 7
        );
        
        $this->assertEquals('away_covered', $result['result']); // Won by 3, didn't cover -7
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

    public function test_home_team_covers_as_underdog()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 21,    // Home team loses by 3
            awayScore: 24,
            spread: +7        // Home team getting 7 points
        );
        
        $this->assertEquals('home_covered', $result['result']); // Lost by 3, covered +7
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

    public function test_fpi_prediction_with_home_favorite()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 27,
            awayScore: 17,
            spread: -7,       // Home team favored by 7
            homeFpi: 15.5,    // FPI predicts home by 3.3 (plus HFA)
            awayFpi: 12.2,
            homeFieldAdvantage: 3.0
        );
        
        $this->assertEquals(3.3, $result['fpi_spread']);
        $this->assertTrue($result['fpi_correct']); // FPI predicted home win correctly
    }

    public function test_fpi_prediction_with_away_favorite()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 21,
            awayScore: 24,
            spread: +3,       // Home team getting 3 points
            homeFpi: 12.2,    // FPI predicts away by 2.6 (before HFA)
            awayFpi: 14.8,
            homeFieldAdvantage: 3.0
        );
        
        // FPI spread = 12.2 - 14.8 = -2.6
        // With 3.0 HFA = +0.4 (predicts home win)
        $this->assertEquals(-2.6, $result['fpi_spread']);
        $this->assertFalse($result['fpi_correct']); // FPI predicted home win but away team won
    }

    public function test_fpi_prediction_with_strong_away_favorite()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 17,
            awayScore: 27,
            spread: +7,       // Home team getting 7 points
            homeFpi: 10.2,    // FPI predicts away by 4.6 (before HFA)
            awayFpi: 14.8,
            homeFieldAdvantage: 3.0
        );
        
        // FPI spread = 10.2 - 14.8 = -4.6
        // With 3.0 HFA = -1.6 (still predicts away win)
        $this->assertEquals(-4.6, $result['fpi_spread']);
        $this->assertTrue($result['fpi_correct']); // FPI predicted away win correctly
    }

    public function test_push_scenario()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 24,    // Home team wins by 7
            awayScore: 17,
            spread: -7        // Home favored by 7
        );
        
        $this->assertEquals('push', $result['result']);
        $this->assertNull($result['fpi_correct']); // FPI correctness is null on pushes
    }

    public function test_fpi_prediction_with_negative_fpi_values()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 24,
            awayScore: 17,
            spread: -3,
            homeFpi: -5.5,    // Both teams have negative FPI
            awayFpi: -8.8,    // Home team still better by 3.3
            homeFieldAdvantage: 3.0
        );
        
        // FPI spread = -5.5 - (-8.8) = 3.3
        // With 3.0 HFA = 6.3 (predicts home win)
        $this->assertEquals(3.3, $result['fpi_spread']);
        $this->assertTrue($result['fpi_correct']); // Home team won as predicted
    }

    public function test_fpi_prediction_with_negative_home_positive_away()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 17,
            awayScore: 27,
            spread: +7,
            homeFpi: -2.5,    // Home team negative FPI
            awayFpi: 3.8,     // Away team positive FPI
            homeFieldAdvantage: 3.0
        );
        
        // FPI spread = -2.5 - 3.8 = -6.3
        // With 3.0 HFA = -3.3 (predicts away win)
        $this->assertEquals(-6.3, $result['fpi_spread']);
        $this->assertTrue($result['fpi_correct']); // Away team won as predicted
    }

    public function test_fpi_prediction_with_positive_home_negative_away()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 28,
            awayScore: 14,
            spread: -7,
            homeFpi: 4.2,     // Home team positive FPI
            awayFpi: -3.1,    // Away team negative FPI
            homeFieldAdvantage: 3.0
        );
        
        // FPI spread = 4.2 - (-3.1) = 7.3
        // With 3.0 HFA = 10.3 (strongly predicts home win)
        $this->assertEquals(7.3, $result['fpi_spread']);
        $this->assertTrue($result['fpi_correct']); // Home team won as predicted
    }

    public function test_fpi_more_accurate_than_spread()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 27,
            awayScore: 20,
            spread: -10,      // Market predicts home by 10
            homeFpi: 15.5,    // FPI predicts home by 6.3 (3.3 + 3.0 HFA)
            awayFpi: 12.2,
            homeFieldAdvantage: 3.0
        );
        
        // Actual margin: home by 7
        // Spread prediction: home by 10 (off by 3)
        // FPI prediction: home by 6.3 (off by 0.7)
        $this->assertTrue($result['fpi_better_than_spread']); // FPI was closer
    }

    public function test_spread_more_accurate_than_fpi()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 24,
            awayScore: 21,
            spread: -3,       // Market predicts home by 3
            homeFpi: 15.5,    // FPI predicts home by 6.3 (3.3 + 3.0 HFA)
            awayFpi: 12.2,
            homeFieldAdvantage: 3.0
        );
        
        // Actual margin: home by 3
        // Spread prediction: home by 3 (perfect)
        // FPI prediction: home by 6.3 (off by 3.3)
        $this->assertFalse($result['fpi_better_than_spread']); // Spread was closer
    }

    public function test_fpi_accuracy_null_when_fpi_missing()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 24,
            awayScore: 21,
            spread: -3,
            homeFpi: null,
            awayFpi: 12.2
        );
        
        $this->assertNull($result['fpi_better_than_spread']);
    }

    public function test_fpi_spread_difference_calculation()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 27,
            awayScore: 20,
            spread: -7,       // Market predicts home by 7
            homeFpi: 15.5,    // FPI predicts home by 6.3 (3.3 + 3.0 HFA)
            awayFpi: 12.2,
            homeFieldAdvantage: 3.0
        );
        
        // Market spread: -7 (home by 7)
        // FPI spread: 3.3 + 3.0 = 6.3 (home by 6.3)
        // Difference should be |6.3 - 7| = 0.7
        $this->assertEquals(0.7, $result['fpi_spread_difference']);
    }

    public function test_fpi_spread_difference_with_opposite_predictions()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 24,
            awayScore: 21,
            spread: 3,        // Market predicts away by 3
            homeFpi: 15.5,    // FPI predicts home by 6.3 (3.3 + 3.0 HFA)
            awayFpi: 12.2,
            homeFieldAdvantage: 3.0
        );
        
        // Market spread: +3 (away by 3)
        // FPI spread: 3.3 + 3.0 = 6.3 (home by 6.3)
        // Difference should be |6.3 - (-3)| = 9.3
        $this->assertEquals(9.3, $result['fpi_spread_difference']);
    }

    public function test_fpi_spread_difference_null_when_fpi_missing()
    {
        $result = SpreadResult::calculateResult(
            homeScore: 24,
            awayScore: 21,
            spread: -3,
            homeFpi: null,
            awayFpi: 12.2
        );
        
        $this->assertNull($result['fpi_spread_difference']);
    }
}
