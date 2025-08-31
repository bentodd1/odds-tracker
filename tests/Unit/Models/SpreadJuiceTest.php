<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Spread;
use App\Models\Game;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Casino;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SpreadJuiceTest extends TestCase
{
    use DatabaseTransactions;

    private Sport $sport;
    private Team $homeTeam;
    private Team $awayTeam;
    private Game $game;
    private Casino $casino;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sport = Sport::firstOrCreate(
            ['key' => 'americanfootball_nfl'],
            ['group' => 'American Football', 'title' => 'NFL', 'active' => true]
        );

        $this->homeTeam = Team::firstOrCreate([
            'sport_id' => $this->sport->id,
            'name' => 'Green Bay Packers'
        ]);

        $this->awayTeam = Team::firstOrCreate([
            'sport_id' => $this->sport->id,
            'name' => 'Chicago Bears'
        ]);

        $this->game = Game::create([
            'sport_id' => $this->sport->id,
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'commence_time' => Carbon::now(),
            'game_id' => 'test_game_' . uniqid(),
        ]);

        $this->casino = Casino::firstOrCreate(['name' => 'DraftKings']);
    }

    public function test_juice_calculation_for_minus_110_odds()
    {
        // Create a -7 spread at -110 odds
        $spread = Spread::create([
            'game_id' => $this->game->id,
            'casino_id' => $this->casino->id,
            'spread' => -7,
            'home_odds' => -110,
            'away_odds' => -110,
            'recorded_at' => Carbon::now()
        ]);

        $baseProbability = $spread->cover_probability;
        $probabilityWithJuice = $spread->cover_probability_with_juice;

        echo "\n=== Juice Calculation Test ===";
        echo "\nBase probability (normal distribution): " . $baseProbability . "%";
        echo "\nJuice cost from -110 odds: " . (52.4 - 50) . "% = 2.4%";
        echo "\nExpected total: " . $baseProbability . "% + 2.4% = " . ($baseProbability + 2.4) . "%";
        echo "\nActual calculation: " . $probabilityWithJuice . "%";

        // Test that juice adds exactly 2.4% for -110 odds
        $this->assertEquals($baseProbability + 2.4, $probabilityWithJuice);
    }

    public function test_juice_calculation_for_different_odds()
    {
        // Test with different odds values
        $testCases = [
            ['odds' => -105, 'expected_juice' => 1.2], // 105/(105+100)*100 - 50 = 1.2%
            ['odds' => -120, 'expected_juice' => 4.5], // 120/(120+100)*100 - 50 = 4.5%
            ['odds' => +100, 'expected_juice' => 0.0], // 100/(100+100)*100 - 50 = 0%
            ['odds' => +110, 'expected_juice' => -2.4], // 100/(110+100)*100 - 50 = -2.4%
        ];

        foreach ($testCases as $case) {
            $spread = Spread::create([
                'game_id' => $this->game->id,
                'casino_id' => $this->casino->id,
                'spread' => -7,
                'home_odds' => $case['odds'],
                'away_odds' => $case['odds'],
                'recorded_at' => Carbon::now()
            ]);

            $baseProbability = $spread->cover_probability;
            $probabilityWithJuice = $spread->cover_probability_with_juice;
            $expectedTotal = $baseProbability + $case['expected_juice'];
            
            echo "\n\nTesting odds: " . $case['odds'];
            echo "\nBase probability: " . $baseProbability . "%";
            echo "\nExpected juice: " . $case['expected_juice'] . "%";
            echo "\nExpected total: " . $expectedTotal . "%";
            echo "\nActual: " . $probabilityWithJuice . "%";

            $this->assertEqualsWithDelta($expectedTotal, $probabilityWithJuice, 0.1);
        }
    }
}
