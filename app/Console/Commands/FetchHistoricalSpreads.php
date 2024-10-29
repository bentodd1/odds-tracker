<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OddsApiService;
use App\Models\Sport;
use App\Models\Game;
use App\Models\Team;
use App\Models\Spread;
use App\Models\Casino;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FetchHistoricalSpreads extends Command
{
    protected $signature = 'odds:fetch-historical-spreads
        {sportKey=americanfootball_nfl : The sport key to fetch spreads for}
        {--start-date= : Optional start date (YYYY-MM-DD)}
        {--debug : Show debug information}';
    protected $description = 'Fetch one spread snapshot per day from The Odds API';

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
        $defaultStartDate = '2020-07-01';

        // Get start date from option or last recorded spread or default
        $startDate = $this->option('start-date');
        if (!$startDate) {
            $lastSpread = Spread::whereHas('game', function ($query) use ($sportKey) {
                $query->whereHas('sport', function ($q) use ($sportKey) {
                    $q->where('key', $sportKey);
                });
            })
                ->orderBy('recorded_at', 'desc')
                ->first();

            $startDate = $lastSpread
                ? Carbon::parse($lastSpread->recorded_at)->addDay()->format('Y-m-d')
                : $defaultStartDate;
        }

        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::now();

        $this->info("Fetching spreads from {$startDate} to {$endDate->format('Y-m-d')}");

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            $this->info("\nProcessing {$dateString}");

            try {
                $odds = $this->oddsApi->getHistoricalOdds($sportKey, $dateString);

                if ($this->debug) {
                    $this->info("API Response for {$dateString}:");
                    $this->line(json_encode($odds, JSON_PRETTY_PRINT));
                }

                if (!empty($odds) && is_array($odds)) {
                    $gamesProcessed = 0;
                    foreach ($odds as $game) {
                        if ($this->debug) {
                            $this->info("\nProcessing game:");
                            $this->line(json_encode($game, JSON_PRETTY_PRINT));
                        }

                        if ($this->isValidGameData($game)) {
                            $this->processGame($game, $sportKey);
                            $gamesProcessed++;
                        } else {
                            if ($this->debug) {
                                $this->warn("Invalid game data:");
                                $this->line(json_encode($this->getInvalidGameDataReasons($game), JSON_PRETTY_PRINT));
                            }
                        }
                    }
                    $this->info("✓ Processed {$gamesProcessed} games for {$dateString}");
                } else {
                    $this->info("- No games found for {$dateString}");
                }

            } catch (\Exception $e) {
                $this->error("✗ Error for {$dateString}: " . $e->getMessage());
                if ($this->debug) {
                    $this->error($e->getTraceAsString());
                }
            }

            $currentDate->addDay();
        }

        $this->newLine();
        $this->info("Completed fetching spreads for {$sportKey}");
    }

    protected function isValidGameData($game)
    {
        $valid = isset($game['id']) &&
            isset($game['home_team']) &&
            isset($game['away_team']) &&
            isset($game['commence_time']) &&
            isset($game['bookmakers']) &&
            is_array($game['bookmakers']);

        if ($this->debug && !$valid) {
            $this->warn("Invalid game data: " . json_encode($this->getInvalidGameDataReasons($game)));
        }

        return $valid;
    }

    protected function getInvalidGameDataReasons($game)
    {
        $reasons = [];

        if (!isset($game['id'])) $reasons[] = "Missing game ID";
        if (!isset($game['home_team'])) $reasons[] = "Missing home team";
        if (!isset($game['away_team'])) $reasons[] = "Missing away team";
        if (!isset($game['commence_time'])) $reasons[] = "Missing commence time";
        if (!isset($game['bookmakers'])) $reasons[] = "Missing bookmakers";
        if (isset($game['bookmakers']) && !is_array($game['bookmakers'])) $reasons[] = "Bookmakers is not an array";

        return $reasons;
    }

    protected function processGame($gameData, $sportKey)
    {
        try {
            if ($this->debug) {
                $this->info("Processing game ID: " . ($gameData['id'] ?? 'unknown'));
            }

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

            if ($this->debug) {
                $this->info("Teams created/found: {$homeTeam->name} vs {$awayTeam->name}");
            }

            // Create or update game
            $game = Game::updateOrCreate(
                ['game_id' => $gameData['id']],
                [
                    'sport_id' => $sport->id,
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'commence_time' => Carbon::parse($gameData['commence_time']),
                    'completed' => true
                ]
            );

            if ($this->debug) {
                $this->info("Game record created/updated. ID: {$game->id}");
            }

            // Process spreads
            $spreadsProcessed = 0;
            foreach ($gameData['bookmakers'] as $bookmaker) {
                if (!isset($bookmaker['markets']) || !is_array($bookmaker['markets'])) {
                    if ($this->debug) {
                        $this->warn("Skipping bookmaker - no markets found");
                    }
                    continue;
                }

                foreach ($bookmaker['markets'] as $market) {
                    if (isset($market['key']) && $market['key'] === 'spreads') {
                        $this->processSpread($game, $bookmaker, $market);
                        $spreadsProcessed++;
                    }
                }
            }

            if ($this->debug) {
                $this->info("Processed {$spreadsProcessed} spreads for game");
            }

        } catch (\Exception $e) {
            if ($this->debug) {
                $this->error("Error processing game: " . $e->getMessage());
                $this->error($e->getTraceAsString());
            }
            throw $e;
        }
    }

    protected function processSpread($game, $bookmaker, $market)
    {
        try {
            if (!isset($bookmaker['key']) || !isset($market['outcomes']) || !is_array($market['outcomes'])) {
                if ($this->debug) {
                    $this->warn("Invalid spread data");
                }
                return;
            }

            $casino = Casino::firstOrCreate(['name' => $bookmaker['key']]);

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

                Spread::updateOrCreate(
                    [
                        'game_id' => $game->id,
                        'casino_id' => $casino->id,
                        'recorded_at' => Carbon::parse($bookmaker['last_update'])->startOfDay(),
                    ],
                    [
                        'spread' => $homeOutcome['point'],
                        'home_odds' => $homeOutcome['price'],
                        'away_odds' => $awayOutcome['price'],
                        'created_at' => Carbon::parse($bookmaker['last_update']),
                        'updated_at' => Carbon::parse($bookmaker['last_update'])
                    ]
                );

                if ($this->debug) {
                    $this->info("Spread record created/updated");
                }
            }
        } catch (\Exception $e) {
            if ($this->debug) {
                $this->error("Error processing spread: " . $e->getMessage());
            }
        }
    }
}
