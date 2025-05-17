<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OddsApiService;
use App\Models\Sport;
use App\Models\Game;
use App\Models\Team;
use App\Models\MoneyLine;
use App\Models\Casino;
use Carbon\Carbon;

class FetchHistoricalMoneyLines extends Command
{
    protected $signature = 'odds:fetch-historical-moneylines
        {sportKey=baseball_mlb : The sport key to fetch money lines for}
        {--region=us : Region to fetch odds for (us, uk, eu)}
        {--start-date=2023-03-30 : Optional start date (YYYY-MM-DD)}
        {--end-date=2024-10-01 : Optional end date (YYYY-MM-DD)}
        {--debug : Show debug information}';

    protected $description = 'Fetch one money line snapshot per day from The Odds API';

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
        $region = $this->option('region');
        $startDate = $this->option('start-date') ?? '2023-03-30';
        $endDate = $this->option('end-date') ?? '2024-10-01';

        $currentDate = Carbon::parse($startDate);
        $finalDate = Carbon::parse($endDate);

        while ($currentDate->lte($finalDate)) {
            $dateString = $currentDate->format('Y-m-d');
            $this->info("Fetching money lines for {$sportKey} on {$dateString}");

            try {
                $odds = $this->oddsApi->getHistoricalOdds($sportKey, $dateString, 'h2h', $region);

                if ($this->debug) {
                    $this->info("API Response for {$dateString}:");
                    $this->line(json_encode($odds, JSON_PRETTY_PRINT));
                }

                if (!empty($odds) && is_array($odds)) {
                    $gamesProcessed = 0;
                    foreach ($odds as $game) {
                        if ($this->isValidGameData($game)) {
                            $this->processGame($game, $sportKey, $region);
                            $gamesProcessed++;
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
        $this->info("Completed fetching money lines for {$sportKey} in {$region} region");
    }

    protected function isValidGameData($game)
    {
        // You can add validation logic here if needed
        return true;
    }

    protected function processGame($gameData, $sportKey, $region)
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

            $commenceTime = Carbon::parse($gameData['commence_time']);

            // NEW: Match by teams and commence_time within ±12 hours
            $game = Game::where('sport_id', $sport->id)
                ->where('home_team_id', $homeTeam->id)
                ->where('away_team_id', $awayTeam->id)
                ->whereBetween('commence_time', [
                    $commenceTime->copy()->subHours(12),
                    $commenceTime->copy()->addHours(12)
                ])
                ->first();

            if ($game) {
                // Optionally update commence_time or other fields if needed
                $game->update([
                    'commence_time' => $commenceTime,
                    'completed' => false,
                    // ... any other fields you want to update
                ]);
            } else {
                // Create a new game
                $game = Game::create([
                    'sport_id' => $sport->id,
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'commence_time' => $commenceTime,
                    'season' => $gameData['season'] ?? null,
                    'completed' => false,
                    // 'game_id' => $gameData['id'], // Optionally store Odds API id for reference
                ]);
            }

            foreach ($gameData['bookmakers'] as $bookmaker) {
                if (!isset($bookmaker['markets']) || !is_array($bookmaker['markets'])) {
                    continue;
                }

                $casino = Casino::firstOrCreate(['name' => $bookmaker['key']]);
                $timestamp = Carbon::parse($bookmaker['last_update']);

                foreach ($bookmaker['markets'] as $market) {
                    if ($market['key'] === 'h2h') {
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
} 