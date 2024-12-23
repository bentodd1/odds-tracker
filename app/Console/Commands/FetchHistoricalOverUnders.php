<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sport;
use App\Models\Game;
use App\Models\OverUnder;
use App\Models\Casino;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class FetchHistoricalOverUnders extends Command
{
    protected $signature = 'odds:fetch-historical-overunders {sportKey=americanfootball_nfl : The sport key to fetch for}';
    protected $description = 'Fetch historical over/unders from The Odds API';

    private $apiKey = '56a885ebf57cc92936f9c8bba25df6d1';

    public function handle()
    {
        $sportKey = $this->argument('sportKey');
        $defaultStartDate = '2023-01-01';

        $sport = Sport::where('key', $sportKey)->first();
        if (!$sport) {
            $this->error("Sport not found");
            return 1;
        }

        $lastOverUnder = OverUnder::whereHas('game', function ($query) use ($sportKey) {
            $query->whereHas('sport', function ($q) use ($sportKey) {
                $q->where('key', $sportKey);
            });
        })
            ->orderBy('recorded_at', 'desc')
            ->first();

        $currentDate = $lastOverUnder
            ? Carbon::parse($lastOverUnder->recorded_at)->addDay()
            : Carbon::parse($defaultStartDate);

        while ($currentDate->lte(now())) {
            $dateString = $currentDate->format('Y-m-d');
            $this->info("\nProcessing {$dateString}");

            try {
                $isoDate = Carbon::parse($dateString)->toIso8601ZuluString();

                $response = Http::accept('application/json')
                    ->get("https://api.the-odds-api.com/v4/sports/{$sportKey}/odds-history", [
                        'apiKey' => $this->apiKey,
                        'regions' => 'us',
                        'markets' => 'totals',
                        'oddsFormat' => 'american',
                        'date' => $isoDate
                    ]);

                if ($response->failed()) {
                    $this->error("API request failed: " . $response->body());
                    $currentDate->addDay();
                    continue;
                }

                $responseData = $response->json();

                // Get the actual game data from the 'data' key
                $data = $responseData['data'] ?? [];

                $this->info("Found " . count($data) . " games");

                foreach ($data as $gameData) {
                    try {
                        DB::beginTransaction();

                        $game = Game::where('game_id', $gameData['id'])->first();

                        if (!$game) {
                            $this->warn("Game not found: {$gameData['id']}");
                            DB::rollBack();
                            continue;
                        }

                        $this->info("Processing game: {$game->id} ({$gameData['home_team']} vs {$gameData['away_team']})");

                        foreach ($gameData['bookmakers'] as $bookmaker) {
                            if ($bookmaker['key'] != 'fanduel') continue;

                            $casino = Casino::firstOrCreate(['name' => $bookmaker['key']]);

                            foreach ($bookmaker['markets'] as $market) {
                                if ($market['key'] !== 'totals') continue;

                                foreach ($market['outcomes'] as $outcome) {
                                    if ($outcome['name'] == 'Over') {
                                        $underOutcome = collect($market['outcomes'])
                                            ->firstWhere('name', 'Under');

                                        if (!$underOutcome) continue;

                                        $overOdds = $this->americanToDecimal($outcome['price']);
                                        $underOdds = $this->americanToDecimal($underOutcome['price']);

                                        $this->info("Creating over/under:");
                                        $this->info("Total: {$outcome['point']}");
                                        $this->info("Over: {$overOdds}");
                                        $this->info("Under: {$underOdds}");

                                        OverUnder::create([
                                            'game_id' => $game->id,
                                            'casino_id' => $casino->id,
                                            'total' => $outcome['point'],
                                            'over_odds' => $overOdds,
                                            'under_odds' => $underOdds,
                                            'recorded_at' => Carbon::parse($bookmaker['last_update'])
                                        ]);
                                    }
                                }
                            }
                        }

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $this->error("Error processing game: " . $e->getMessage());
                    }
                }

            } catch (\Exception $e) {
                $this->error("Error for {$dateString}: " . $e->getMessage());
            }

            $currentDate->addDay();
        }

        $this->info("\nCompleted fetching over/unders");
    }

    private function americanToDecimal($americanOdds)
    {
        if ($americanOdds > 0) {
            return round(($americanOdds / 100) + 1, 2);
        } else {
            return round((-100 / $americanOdds) + 1, 2);
        }
    }
}
