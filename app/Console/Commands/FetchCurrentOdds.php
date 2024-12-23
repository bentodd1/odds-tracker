<?php

namespace App\Console\Commands;

use App\Models\Spread;
use Illuminate\Console\Command;
use App\Services\OddsApiService;
use App\Models\Sport;
use App\Models\Game;
use App\Models\Team;
use App\Models\MoneyLine;
use App\Models\Casino;
use Carbon\Carbon;

class FetchCurrentOdds extends Command
{
    protected $signature = 'odds:fetch-current
        {sportKey=americanfootball_nfl : The sport key to fetch odds for}
        {--debug : Show debug information}';

    protected $description = 'Fetch current odds from The Odds API';

    protected $oddsApi;
    protected $debug = false;

    public function __construct(OddsApiService $oddsApi)
    {
        parent::__construct();
        $this->oddsApi = $oddsApi;
    }

    public function handle()
    {
        $this->debug = $this->option('debug');
        $sportKey = $this->argument('sportKey');

        $this->info("Fetching current odds for {$sportKey}");

        try {
            $odds = $this->oddsApi->getCurrentOdds($sportKey, 'h2h,spreads');

            if ($this->debug) {
                $this->info("API Response:");
                $this->line(json_encode($odds, JSON_PRETTY_PRINT));
            }

            if (!empty($odds) && is_array($odds)) {
                $gamesProcessed = 0;
                foreach ($odds as $game) {
                    if ($this->isValidGameData($game)) {
                        $this->processGame($game, $sportKey);
                        $gamesProcessed++;
                    }
                }
                $this->info("✓ Processed {$gamesProcessed} games");
            } else {
                $this->info("- No games found");
            }

        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
            if ($this->debug) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }

        return 0;
    }

    protected function processGame($gameData, $sportKey)
    {
        try {
            $sport = Sport::where('key', $sportKey)->firstOrFail();

            // Find or create teams
            $homeTeam = Team::firstOrCreate(
                ['name' => $gameData['home_team'], 'sport_id' => $sport->id],
                ['name' => $gameData['home_team']]
            );

            $awayTeam = Team::firstOrCreate(
                ['name' => $gameData['away_team'], 'sport_id' => $sport->id],
                ['name' => $gameData['away_team']]
            );

            // Create or update game
            $game = Game::updateOrCreate(
                ['game_id' => $gameData['id']],
                [
                    'sport_id' => $sport->id,
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'commence_time' => Carbon::parse($gameData['commence_time']),
                    'completed' => false
                ]
            );

            foreach ($gameData['bookmakers'] as $bookmaker) {
                if (!isset($bookmaker['markets']) || !is_array($bookmaker['markets'])) {
                    continue;
                }

                $casino = Casino::firstOrCreate(['name' => $bookmaker['key']]);
                $timestamp = Carbon::parse($bookmaker['last_update']);

                foreach ($bookmaker['markets'] as $market) {
                    if ($market['key'] === 'spreads') {
                        $this->processSpread($game, $casino, $market, $timestamp);
                    } elseif ($market['key'] === 'h2h') {
                        $this->processMoneyLine($game, $casino, $market, $timestamp);
                    }
                }
            }

        } catch (\Exception $e) {
            if ($this->debug) {
                $this->error("Error processing game: " . $e->getMessage());
            }
            throw $e;
        }
    }

    protected function processMoneyLine($game, $casino, $market, $timestamp)
    {
        try {
            $homeOutcome = collect($market['outcomes'])->first(function ($outcome) use ($game) {
                return $outcome['name'] === $game->homeTeam->name;
            });

            $awayOutcome = collect($market['outcomes'])->first(function ($outcome) use ($game) {
                return $outcome['name'] === $game->awayTeam->name;
            });

            if ($homeOutcome && $awayOutcome) {
                MoneyLine::create([
                    'game_id' => $game->id,
                    'casino_id' => $casino->id,
                    'home_odds' => $homeOutcome['price'],
                    'away_odds' => $awayOutcome['price'],
                    'recorded_at' => $timestamp,
                ]);
            }
        } catch (\Exception $e) {
            if ($this->debug) {
                $this->error("Error processing money line: " . $e->getMessage());
            }
        }
    }

    protected function processSpread($game, $casino, $market, $timestamp)
    {
        try {
            if (!isset($market['outcomes']) || !is_array($market['outcomes'])) {
                if ($this->debug) {
                    $this->warn("Invalid spread data");
                }
                return;
            }

            // Find home and away outcomes
            $homeOutcome = collect($market['outcomes'])->first(function ($outcome) use ($game) {
                return isset($outcome['name']) && $outcome['name'] === $game->homeTeam->name;
            });

            $awayOutcome = collect($market['outcomes'])->first(function ($outcome) use ($game) {
                return isset($outcome['name']) && $outcome['name'] === $game->awayTeam->name;
            });

            if ($this->debug) {
                $this->info("Home outcome: " . json_encode($homeOutcome));
                $this->info("Away outcome: " . json_encode($awayOutcome));
            }

            if ($homeOutcome && $awayOutcome &&
                isset($homeOutcome['point']) && isset($homeOutcome['price']) &&
                isset($awayOutcome['price'])) {

                Spread::create([
                    'game_id' => $game->id,
                    'casino_id' => $casino->id,
                    'spread' => $homeOutcome['point'],
                    'home_odds' => $homeOutcome['price'],
                    'away_odds' => $awayOutcome['price'],
                    'recorded_at' => $timestamp,
                ]);

                if ($this->debug) {
                    $this->info("Spread record created");
                }
            }
        } catch (\Exception $e) {
            if ($this->debug) {
                $this->error("Error processing spread: " . $e->getMessage());
            }
        }
    }

    // Reuse your existing validation methods
    protected function isValidGameData($game)
    {
        return true;
        // Your existing validation logic
    }

    protected function getInvalidGameDataReasons($game)
    {
        // Your existing validation logic
    }
}
