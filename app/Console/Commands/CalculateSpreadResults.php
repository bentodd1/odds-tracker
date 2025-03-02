<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Spread;
use App\Models\SpreadResult;
use Illuminate\Support\Facades\DB;

class CalculateSpreadResults extends Command
{
    protected $signature = 'spreads:calculate-results
        {--debug : Show debug information}
        {--sport= : Filter by sport key (e.g. nba, nfl, mlb)}
        {--casino= : Filter by casino name}';

    protected $description = 'Calculate results for all spreads that have final scores';

    public function handle()
    {
        $processedCount = 0;
        $chunkSize = 100;

        $query = Spread::query()
            ->select('spreads.*')
            ->join('games', 'spreads.game_id', '=', 'games.id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('scores')
                    ->whereColumn('scores.game_id', 'games.id')
                    ->where('scores.period', 'F');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('spread_results')
                    ->whereColumn('spread_results.spread_id', 'spreads.id');
            });

        // Apply sport filter if provided
        if ($sportKey = $this->option('sport')) {
            $query->whereHas('game', function ($q) use ($sportKey) {
                $q->whereHas('sport', function ($sq) use ($sportKey) {
                    $sq->where('key', $sportKey);
                });
            });
        }

        // Apply casino filter if provided
        if ($casinoName = $this->option('casino')) {
            $query->whereHas('casino', function ($q) use ($casinoName) {
                $q->where('name', 'like', "%{$casinoName}%");
            });
        }

        // Get total count for progress bar
        $totalSpreads = $query->count();

        if ($this->option('debug')) {
            $this->info("Found {$totalSpreads} spreads to process");
            if ($sportKey) {
                $this->info("Filtering by sport: {$sportKey}");
            }
            if ($casinoName) {
                $this->info("Filtering by casino: {$casinoName}");
            }
        }

        $bar = $this->output->createProgressBar($totalSpreads);
        $bar->start();

        $query->with(['game.scores' => function($query) {
            $query->where('period', 'F');
        }, 'game.homeTeam', 'game.awayTeam', 'casino'])
            ->chunk($chunkSize, function ($spreads) use (&$processedCount, $bar, $chunkSize) {
                foreach ($spreads as $spread) {
                    try {
                        $score = $spread->game->scores->first();

                        if (!$score) {
                            if ($this->option('debug')) {
                                $this->newLine();
                                $this->warn("No final score found for game {$spread->game_id}");
                            }
                            continue;
                        }

                        // Create the result and store the return value
                        $result = SpreadResult::createFromScore($score, $spread);
                        
                        if (!$result) {
                            if ($this->option('debug')) {
                                $this->newLine();
                                $this->warn("Failed to create result for spread {$spread->id}");
                            }
                            continue;
                        }
                        
                        $processedCount++;
                        $bar->advance();

                        if ($this->option('debug')) {
                            $this->newLine();
                            $this->info("Processed spread {$spread->id}: " .
                                "{$spread->game->homeTeam->name} ({$score->home_score}) vs " .
                                "{$spread->game->awayTeam->name} ({$score->away_score}), " .
                                "Spread: {$spread->spread}, " .
                                "Result: {$result->result}" .
                                " [{$spread->casino->name}]");
                        }

                    } catch (\Exception $e) {
                        $this->newLine();
                        $this->error("Error processing spread {$spread->id}: " . $e->getMessage());
                    }
                }

                if ($this->option('debug')) {
                    $this->newLine();
                    $this->info("Processed chunk of {$chunkSize} spreads");
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->info("Processed {$processedCount} spread results");

        // Show summary if debug is enabled
        if ($this->option('debug')) {
            $results = SpreadResult::select('result', DB::raw('count(*) as count'))
                ->groupBy('result')
                ->get();

            $this->info("\nResults Summary:");
            foreach ($results as $result) {
                $this->line("{$result->result}: {$result->count}");
            }
        }
    }
}
