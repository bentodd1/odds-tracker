<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\SpreadResult;
use App\Models\Spread;
use App\Models\Game;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Score;
use App\Models\Casino;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SpreadResultTest extends TestCase
{
    use DatabaseTransactions;

    private Sport $sport;
    private Team $homeTeam;
    private Team $awayTeam;
    private Game $game;
    private Casino $casino;
    private Spread $spread;

    protected function setUp(): void
    {
        parent::setUp();

        // Find existing sport or create new one
        $this->sport = Sport::firstOrCreate(
            ['key' => 'americanfootball_nfl'],
            [
                'group' => 'American Football',
                'title' => 'NFL',
                'active' => true
            ]
        );

        $this->homeTeam = Team::firstOrCreate(
            [
                'sport_id' => $this->sport->id,
                'name' => 'Green Bay Packers'
            ]
        );

        $this->awayTeam = Team::firstOrCreate(
            [
                'sport_id' => $this->sport->id,
                'name' => 'Chicago Bears'
            ]
        );

        $this->game = Game::create([
            'sport_id' => $this->sport->id,
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'commence_time' => Carbon::now(),
            'game_id' => 'test_game_' . uniqid(),
        ]);

        $this->casino = Casino::firstOrCreate(
            ['name' => 'DraftKings']
        );

        $this->spread = Spread::create([
            'game_id' => $this->game->id,
            'casino_id' => $this->casino->id,
            'spread' => -7,
            'home_odds' => -110,
            'away_odds' => -110,
            'recorded_at' => Carbon::now()
        ]);
    }

    public function test_home_team_covers_when_favorite()
    {
        // Home team favored by 7, wins by 14
        $result = SpreadResult::calculateResult(28, 14, -7);
        $this->assertEquals('home_covered', $result);
    }

    public function test_away_team_covers_against_favorite()
    {
        // Home team favored by 7, only wins by 3
        $result = SpreadResult::calculateResult(24, 21, -7);
        $this->assertEquals('away_covered', $result);
    }

    public function test_push_when_margin_equals_spread()
    {
        // Home team favored by 7, wins by exactly 7
        $result = SpreadResult::calculateResult(27, 20, -7);
        $this->assertEquals('push', $result);
    }

    public function test_home_team_covers_as_underdog()
    {
        // Home team getting 3.5 points, loses by 3
        $result = SpreadResult::calculateResult(20, 23, 3.5);
        $this->assertEquals('home_covered', $result);
    }

    public function test_create_spread_result_from_completed_game()
    {
        Score::create([
            'game_id' => $this->game->id,
            'home_score' => 31,
            'away_score' => 17,
            'period' => 'F'
        ]);

        $spreadResult = SpreadResult::createFromGame($this->game, $this->spread);

        $this->assertEquals('home_covered', $spreadResult->result);
        $this->assertEquals(14, $spreadResult->actual_margin);
        $this->assertEquals(-7, $spreadResult->spread);
        $this->assertEquals(31, $spreadResult->home_score);
        $this->assertEquals(17, $spreadResult->away_score);
    }

    public function test_half_point_spreads()
    {
        // Home team favored by 7.5, wins by 7
        $result = SpreadResult::calculateResult(27, 20, -7.5);
        $this->assertEquals('away_covered', $result);

        // Home team getting 3.5, loses by 3
        $result = SpreadResult::calculateResult(20, 23, 3.5);
        $this->assertEquals('home_covered', $result);
    }

    public function test_spread_result_requires_final_score()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Game scores not available');

        SpreadResult::createFromGame($this->game, $this->spread);
    }

    public function test_home_team_covers_on_exact_spread_plus_half()
    {
        // Home team favored by 7, wins by 7.5
        $result = SpreadResult::calculateResult(27, 19, -7);
        $this->assertEquals('home_covered', $result);
    }

    public function test_away_team_covers_on_exact_spread_minus_half()
    {
        // Home team favored by 7, wins by 6.5
        $result = SpreadResult::calculateResult(26, 20, -7);
        $this->assertEquals('away_covered', $result);
    }
}
