<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Spread;
use App\Models\Game;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Casino;
use App\Models\NflMargin;
use App\Models\MoneyLine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SpreadCoverProbabilityTest extends TestCase
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

        // Create NFL sport
        $this->sport = Sport::firstOrCreate(
            ['key' => 'americanfootball_nfl'],
            [
                'group' => 'American Football',
                'title' => 'NFL',
                'active' => true
            ]
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

    public function test_negative_seven_spread_probability()
    {
        // Test -7 spread using normal distribution (sigma=14 for NFL)
        $spread = Spread::create([
            'game_id' => $this->game->id,
            'casino_id' => $this->casino->id,
            'spread' => -7,
            'home_odds' => -110,
            'away_odds' => -110,
            'recorded_at' => Carbon::now()
        ]);
        
        $modelProbability = $spread->cover_probability;
        
        echo "\n=== -7 Spread Calculation (Normal Distribution) ===";
        echo "\nUsing sigma=14 for NFL";
        echo "\nZ-score: 7/14 = 0.5";
        echo "\nExpected probability: ~30.9%";
        echo "\nModel calculation: " . $modelProbability . "%";
        
        // With sigma=14, -7 spread should give ~30.9% probability
        $this->assertEquals(30.9, $modelProbability);
    }

    public function test_negative_seven_and_half_spread_probability()
    {
        // Test -7.5 spread using normal distribution (sigma=14 for NFL)
        $spread = Spread::create([
            'game_id' => $this->game->id,
            'casino_id' => $this->casino->id,
            'spread' => -7.5,
            'home_odds' => -110,
            'away_odds' => -110,
            'recorded_at' => Carbon::now()
        ]);

        $modelProbability = $spread->cover_probability;
        
        echo "\n=== -7.5 Spread Calculation (Normal Distribution) ===";
        echo "\nUsing sigma=14 for NFL";
        echo "\nZ-score: 7.5/14 = 0.536";
        echo "\nExpected probability: ~29.6%";
        echo "\nModel calculation: " . $modelProbability . "%";
        
        // With sigma=14, -7.5 spread should give ~29.6% probability
        $this->assertEquals(29.6, $modelProbability);
    }

    public function test_positive_seven_spread_probability()
    {
        // Test +7 spread (home team underdog by 7)
        $spread = Spread::create([
            'game_id' => $this->game->id,
            'casino_id' => $this->casino->id,
            'spread' => 7,
            'home_odds' => -110,
            'away_odds' => -110,
            'recorded_at' => Carbon::now()
        ]);

        $probability = $spread->cover_probability;

        echo "\n=== +7 Spread Calculation (Normal Distribution) ===";
        echo "\nUsing sigma=14 for NFL";
        echo "\nZ-score: 7/14 = 0.5";
        echo "\nExpected probability: ~69.1%";
        echo "\nModel calculation: " . $probability . "%";

        // With sigma=14, +7 spread should give ~69.1% probability
        $this->assertEquals(69.1, $probability);
    }

    public function test_positive_seven_and_half_spread_probability()
    {
        // Test +7.5 spread (home team underdog by 7.5)
        $spread = Spread::create([
            'game_id' => $this->game->id,
            'casino_id' => $this->casino->id,
            'spread' => 7.5,
            'home_odds' => -110,
            'away_odds' => -110,
            'recorded_at' => Carbon::now()
        ]);

        $probability = $spread->cover_probability;

        echo "\n=== +7.5 Spread Calculation (Normal Distribution) ===";
        echo "\nUsing sigma=14 for NFL";
        echo "\nZ-score: 7.5/14 = 0.536";
        echo "\nExpected probability: ~70.4%";
        echo "\nModel calculation: " . $probability . "%";

        // With sigma=14, +7.5 spread should give ~70.4% probability
        $this->assertEquals(70.4, $probability);
    }

    public function test_college_football_sigma_fifteen()
    {
        // Create college football sport and game
        $collegeSport = Sport::firstOrCreate(
            ['key' => 'americanfootball_ncaaf'],
            [
                'group' => 'American Football',
                'title' => 'NCAAF',
                'active' => true
            ]
        );

        $collegeGame = Game::create([
            'sport_id' => $collegeSport->id,
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'commence_time' => Carbon::now(),
            'game_id' => 'test_college_game_' . uniqid(),
        ]);

        // Test -7 spread for college football (sigma=15)
        $spread = Spread::create([
            'game_id' => $collegeGame->id,
            'casino_id' => $this->casino->id,
            'spread' => -7,
            'home_odds' => -110,
            'away_odds' => -110,
            'recorded_at' => Carbon::now()
        ]);

        $probability = $spread->cover_probability;

        echo "\n=== College Football -7 Spread (sigma=15) ===";
        echo "\nZ-score: 7/15 = 0.467";
        echo "\nModel calculation: " . $probability . "%";

        // With sigma=15, should be higher probability than NFL (sigma=14)
        $this->assertGreaterThan(30.9, $probability);
        $this->assertLessThan(40.0, $probability);
    }

    public function test_win_probability_calculation()
    {
        // Create moneyline for comparison
        $moneyLine = MoneyLine::create([
            'game_id' => $this->game->id,
            'casino_id' => $this->casino->id,
            'home_odds' => 228, // Giants +228
            'away_odds' => -280,
            'recorded_at' => Carbon::now()
        ]);

        // Test Giants +6.5 scenario to verify win probability vs cover probability
        $spread = Spread::create([
            'game_id' => $this->game->id,
            'casino_id' => $this->casino->id,
            'spread' => 6.5, // Giants are 6.5 point underdogs
            'home_odds' => -110, // Spread odds
            'away_odds' => -110,
            'recorded_at' => Carbon::now()
        ]);

        $coverProbability = $spread->cover_probability; // Should be ~67.9%
        $winProbability = $spread->win_probability; // Should be ~32.1%
        $moneylineImplied = $spread->home_moneyline_implied_probability; // Should be 30.5%

        echo "\n=== Win vs Cover Probability Test ===";
        echo "\nGiants +6.5 spread";
        echo "\nCover probability (lose by <6.5): " . $coverProbability . "%";
        echo "\nWin probability from spread: " . $winProbability . "%";
        echo "\nMoneyline +228 implied: " . $moneylineImplied . "%";

        // Cover probability should be much higher than win probability
        $this->assertGreaterThan($winProbability + 30, $coverProbability);
        
        // Win probability should be close to moneyline implied probability
        $this->assertEqualsWithDelta(30.5, $winProbability, 2.0);
        $this->assertEquals(30.5, $moneylineImplied);
    }
}
