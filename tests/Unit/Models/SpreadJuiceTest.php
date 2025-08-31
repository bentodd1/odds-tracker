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

        echo "\n=== No Juice Calculation Test ===";
        echo "\nBase probability (normal distribution): " . $baseProbability . "%";
        echo "\nProbability with juice (should be same): " . $probabilityWithJuice . "%";

        // Since we removed vig, both should be equal
        $this->assertEquals($baseProbability, $probabilityWithJuice);
    }

    public function test_juice_calculation_for_different_odds()
    {
        // Test that juice calculation now returns base probability regardless of odds
        $testCases = [-105, -120, +100, +110];

        foreach ($testCases as $odds) {
            $spread = Spread::create([
                'game_id' => $this->game->id,
                'casino_id' => $this->casino->id,
                'spread' => -7,
                'home_odds' => $odds,
                'away_odds' => $odds,
                'recorded_at' => Carbon::now()
            ]);

            $baseProbability = $spread->cover_probability;
            $probabilityWithJuice = $spread->cover_probability_with_juice;
            
            echo "\n\nTesting odds: " . $odds;
            echo "\nBase probability: " . $baseProbability . "%";
            echo "\nWith juice: " . $probabilityWithJuice . "%";

            // Should be equal since we removed vig calculation
            $this->assertEquals($baseProbability, $probabilityWithJuice);
        }
    }
}
