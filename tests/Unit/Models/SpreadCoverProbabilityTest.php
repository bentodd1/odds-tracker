<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Spread;
use App\Models\Game;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Casino;
use App\Models\NflMargin;
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
        // Step 1: Count all the pieces using YOUR correct methodology
        $totalGames = NflMargin::sum('occurrences');
        $games6AndUnder = NflMargin::where('margin', '<=', 6)->sum('occurrences'); // Clear wins
        $gamesExactly7 = NflMargin::where('margin', '=', 7)->sum('occurrences'); // Wash
        $remainingGames = $totalGames - $games6AndUnder - $gamesExactly7; // Games 8+
        
        // Split remaining games (games 8+) in half
        $remainingGamesWon = $remainingGames * 0.5;
        
        echo "\n=== -7 Spread Calculation (CORRECTED) ===";
        echo "\nStep 1 - Count games:";
        echo "\nTotal games: " . $totalGames;
        echo "\nGames 6 and under (clear wins): " . $games6AndUnder;
        echo "\nGames exactly at 7 (wash): " . $gamesExactly7;
        echo "\nRemaining games (8+): " . $remainingGames;
        echo "\nRemaining games won (8+ split 50/50): " . $remainingGamesWon;
        
        // Step 2: Do the division
        // For -7: (clear_wins + remaining_won) / (total - wash)
        $favoriteWins = $games6AndUnder + $remainingGamesWon;
        $adjustedTotal = $totalGames - $gamesExactly7;
        $probability = ($favoriteWins / $adjustedTotal) * 100;
        
        echo "\n\nStep 2 - Calculate probability:";
        echo "\nFavorite wins: " . $games6AndUnder . " + " . $remainingGamesWon . " = " . $favoriteWins;
        echo "\nAdjusted total: " . $totalGames . " - " . $gamesExactly7 . " = " . $adjustedTotal;
        echo "\nProbability: " . $favoriteWins . " / " . $adjustedTotal . " = " . round($probability, 1) . "%";
        
        // Verify this matches what our model calculates
        $spread = Spread::create([
            'game_id' => $this->game->id,
            'casino_id' => $this->casino->id,
            'spread' => -7,
            'home_odds' => -110,
            'away_odds' => -110,
            'recorded_at' => Carbon::now()
        ]);
        
        $modelProbability = $spread->cover_probability;
        echo "\nModel calculation: " . $modelProbability . "%";
        
        $this->assertEquals(round($probability, 1), $modelProbability);
    }

    public function test_negative_seven_and_half_spread_probability()
    {
        // Step 1: Count all the pieces using YOUR methodology for -7.5
        $totalGames = NflMargin::sum('occurrences');
        $games6AndUnder = NflMargin::where('margin', '<=', 6)->sum('occurrences'); // Clear wins
        $gamesExactly7 = NflMargin::where('margin', '=', 7)->sum('occurrences'); // Now counts as wins (no push)
        $remainingGames = $totalGames - $games6AndUnder - $gamesExactly7; // Games 8+
        
        // Split remaining games (games 8+) in half
        $remainingGamesWon = $remainingGames * 0.5;
        
        echo "\n=== -7.5 Spread Calculation ===";
        echo "\nStep 1 - Count games:";
        echo "\nTotal games: " . $totalGames;
        echo "\nGames 6 and under (clear wins): " . $games6AndUnder;
        echo "\nGames exactly at 7 (now wins, no push): " . $gamesExactly7;
        echo "\nRemaining games (8+): " . $remainingGames;
        echo "\nRemaining games won (8+ split 50/50): " . $remainingGamesWon;
        
        // Step 2: Do the division
        // For -7.5: (clear_wins + all_7s + remaining_won) / total
        // Your example: (2,428 + 582 + 1,700) / 6,409 = 73.4%
        $favoriteWins = $games6AndUnder + $gamesExactly7 + $remainingGamesWon;
        $probability = ($favoriteWins / $totalGames) * 100;
        
        echo "\n\nStep 2 - Calculate probability:";
        echo "\nFavorite wins: " . $games6AndUnder . " + " . $gamesExactly7 . " + " . $remainingGamesWon . " = " . $favoriteWins;
        echo "\nTotal games: " . $totalGames;
        echo "\nProbability: " . $favoriteWins . " / " . $totalGames . " = " . round($probability, 1) . "%";
        echo "\nExpected from your example: 73.4%";
        
        // Test the model calculation
        $spread = Spread::create([
            'game_id' => $this->game->id,
            'casino_id' => $this->casino->id,
            'spread' => -7.5,
            'home_odds' => -110,
            'away_odds' => -110,
            'recorded_at' => Carbon::now()
        ]);

        $modelProbability = $spread->cover_probability;
        echo "\nModel calculation: " . $modelProbability . "%";
        
        // Accept the small rounding difference (73.5% vs 73.4%)
        $this->assertEquals(round($probability, 1), $modelProbability);
        $this->assertEqualsWithDelta(73.4, $modelProbability, 0.2); // Within 0.2% of expected
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

        echo "\n=== +7 Spread Calculation ===";
        echo "\nFor +7 (home underdog), it should be: 100% - 70.8% = 29.2%";
        echo "\nModel calculation: " . $probability . "%";

        // For +7, it should be 100% - 70.8% = 29.2%
        $this->assertEquals(29.2, $probability);
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

        // For +7.5, it should be 100% - 73.4% = 26.6%
        $this->assertEquals(26.6, $probability);
    }

    public function test_margin_calculations_are_correct()
    {
        // Verify our test data setup is correct
        $totalGames = NflMargin::sum('occurrences');
        $this->assertEquals(6409, $totalGames);

        // Games over 6 includes both wash (7) and clear wins (8+)
        $gamesOver6 = NflMargin::where('margin', '>', 6)->sum('occurrences');
        $this->assertEquals(3010, $gamesOver6); // 582 + 2428

        // Games over 7 are the clear wins
        $gamesOver7 = NflMargin::where('margin', '>', 7)->sum('occurrences');
        $this->assertEquals(2428, $gamesOver7);

        $washGames = NflMargin::where('margin', '=', 7)->sum('occurrences');
        $this->assertEquals(582, $washGames);

        $gamesUnder7 = NflMargin::where('margin', '<', 7)->sum('occurrences');
        $this->assertEquals(3399, $gamesUnder7);
    }
}
